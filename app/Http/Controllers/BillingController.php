<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\OmiseService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function __construct(
        protected OmiseService $omiseService
    ) {}

    /**
     * Display billing overview & current subscription.
     */
    public function index(): View
    {
        $user = auth()->user();
        $plan = $user->getActivePlan();
        $subscription = $user->activeSubscription;

        return view('billing.index', compact('user', 'plan', 'subscription'));
    }

    /**
     * Show upgrade page for a specific plan.
     */
    public function upgrade(Plan $plan): View
    {
        if ($plan->isFree()) {
            return view('billing.upgrade', compact('plan'));
        }

        $omisePublicKey = config('services.omise.public_key', env('OMISE_PUBLIC_KEY', ''));

        return view('billing.upgrade', compact('plan', 'omisePublicKey'));
    }

    /**
     * Process payment charge (Credit Card or PromptPay).
     */
    public function charge(Request $request): JsonResponse
    {
        $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
            'billing_interval' => ['required', 'in:monthly,yearly'],
            'payment_method' => ['required', 'in:card,promptpay'],
            'card_token' => ['required_if:payment_method,card', 'nullable', 'string'],
        ]);

        $user = $request->user();
        $plan = Plan::findOrFail($request->plan_id);

        $price = $request->billing_interval === 'yearly'
            ? $plan->price_yearly
            : $plan->price_monthly;

        $amountInSatang = (int) round($price * 100);

        $metadata = [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'billing_interval' => $request->billing_interval,
        ];

        try {
            if ($request->payment_method === 'promptpay') {
                $charge = $this->omiseService->createPromptPayCharge($amountInSatang, $metadata);

                $qrUrl = $charge['source']['scannable_code']['image']['download_uri']
                    ?? 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=PROMPTPAY_'.$charge['id'];

                return response()->json([
                    'status' => 'pending',
                    'charge_id' => $charge['id'],
                    'payment_method' => 'promptpay',
                    'qr_url' => $qrUrl,
                    'amount' => $price,
                    'expires_in_seconds' => 900,
                ]);
            }

            // Credit Card Charge
            $charge = $this->omiseService->createCardCharge(
                $amountInSatang,
                (string) $request->card_token,
                $metadata
            );

            if (($charge['status'] ?? '') === 'successful' || ($charge['paid'] ?? false)) {
                $this->activateSubscription($user, $plan, $request->billing_interval, $charge['id']);

                return response()->json([
                    'status' => 'successful',
                    'message' => 'ชำระเงินและอัปเกรดแผนเรียบร้อยแล้ว!',
                    'redirect_url' => route('dashboard'),
                ]);
            }

            return response()->json([
                'status' => 'failed',
                'message' => $charge['failure_message'] ?? 'การชำระเงินไม่สำเร็จ กรุณาลองใหม่อีกครั้ง',
            ], 422);
        } catch (Exception $e) {
            Log::error('Billing Charge Error: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'เกิดข้อผิดพลาดในการประมวลผลการชำระเงิน: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check status of a PromptPay charge (polled by front-end).
     */
    public function checkPromptPayStatus(string $chargeId): JsonResponse
    {
        $user = auth()->user();

        try {
            $charge = $this->omiseService->getCharge($chargeId);

            if (($charge['status'] ?? '') === 'successful' || ($charge['paid'] ?? false)) {
                $metadata = $charge['metadata'] ?? [];
                $planId = $metadata['plan_id'] ?? null;
                $billingInterval = $metadata['billing_interval'] ?? 'monthly';

                if ($planId) {
                    $plan = Plan::find($planId);
                    if ($plan) {
                        $this->activateSubscription($user, $plan, $billingInterval, $chargeId);
                    }
                }

                return response()->json([
                    'status' => 'successful',
                    'redirect_url' => route('dashboard'),
                ]);
            }

            return response()->json([
                'status' => $charge['status'] ?? 'pending',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel an active subscription at period end.
     */
    public function cancelSubscription(Request $request): RedirectResponse
    {
        $user = $request->user();
        $subscription = $user->activeSubscription;

        if ($subscription) {
            $subscription->update([
                'cancel_at_period_end' => true,
                'cancelled_at' => now(),
            ]);

            return back()->with('success', 'ยกเลิกการต่ออายุสมาชิกเรียบร้อยแล้ว คุณสามารถใช้งานได้จนสิ้นสุดรอบบิลปัจจุบัน');
        }

        return back()->with('error', 'ไม่พบการสมัครสมาชิกที่สามารถยกเลิกได้');
    }

    /**
     * Handle Omise Webhooks.
     */
    public function webhook(Request $request): JsonResponse
    {
        $event = $request->input('key');
        $data = $request->input('data');

        Log::info('Omise Webhook received', ['event' => $event]);

        if ($event === 'charge.complete' && ($data['status'] ?? '') === 'successful') {
            $metadata = $data['metadata'] ?? [];
            $userId = $metadata['user_id'] ?? null;
            $planId = $metadata['plan_id'] ?? null;
            $interval = $metadata['billing_interval'] ?? 'monthly';

            if ($userId && $planId) {
                $user = User::find($userId);
                $plan = Plan::find($planId);

                if ($user && $plan) {
                    $this->activateSubscription($user, $plan, $interval, $data['id'] ?? '');
                }
            }
        }

        return response()->json(['received' => true]);
    }

    /**
     * Helper to activate user subscription and update user's plan.
     */
    protected function activateSubscription(User $user, Plan $plan, string $billingInterval, string $chargeId): Subscription
    {
        return DB::transaction(function () use ($user, $plan, $billingInterval, $chargeId) {
            $periodEnd = $billingInterval === 'yearly'
                ? now()->addYear()
                : now()->addMonth();

            // Cancel any existing active subscriptions
            $user->subscriptions()->where('status', 'active')->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            $subscription = Subscription::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'status' => 'active',
                'billing_interval' => $billingInterval,
                'current_period_start' => now(),
                'current_period_end' => $periodEnd,
                'cancel_at_period_end' => false,
                'omise_subscription_id' => $chargeId,
            ]);

            $user->update([
                'plan_id' => $plan->id,
            ]);

            return $subscription;
        });
    }
}
