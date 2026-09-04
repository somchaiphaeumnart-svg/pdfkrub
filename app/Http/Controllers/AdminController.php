<?php

namespace App\Http\Controllers;

use App\Models\PdfJob;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\UploadedFile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function index(): View
    {
        // Metric cards
        $totalUsers = User::count();
        $newUsersToday = User::whereDate('created_at', today())->count();

        $activeSubscriptions = Subscription::where('status', 'active')->count();
        $totalJobs = PdfJob::count();
        $completedJobs = PdfJob::where('status', PdfJob::STATUS_DONE)->count();
        $failedJobs = PdfJob::where('status', PdfJob::STATUS_FAILED)->count();
        $successRate = $totalJobs > 0 ? round(($completedJobs / $totalJobs) * 100, 1) : 100;

        $totalStorageBytes = UploadedFile::sum('file_size');
        $totalFiles = UploadedFile::count();

        // Subscriptions breakdown by plan
        $plansBreakdown = Plan::withCount(['subscriptions' => function ($q) {
            $q->where('status', 'active');
        }])->get();

        // Top Tools
        $topTools = PdfJob::select('tool_name', DB::raw('count(*) as count'))
            ->groupBy('tool_name')
            ->orderByDesc('count')
            ->limit(6)
            ->get();

        // Recent Jobs Stream
        $recentJobs = PdfJob::with(['user', 'outputFile'])
            ->latest()
            ->limit(15)
            ->get();

        // Recent Users
        $recentUsers = User::with('plan')
            ->latest()
            ->limit(8)
            ->get();

        return view('admin.index', compact(
            'totalUsers',
            'newUsersToday',
            'activeSubscriptions',
            'totalJobs',
            'completedJobs',
            'failedJobs',
            'successRate',
            'totalStorageBytes',
            'totalFiles',
            'plansBreakdown',
            'topTools',
            'recentJobs',
            'recentUsers'
        ));
    }

    /**
     * User management list with search, filter, and plan assignment.
     */
    public function users(Request $request): View
    {
        $query = User::with(['plan', 'subscriptions' => fn ($q) => $q->where('status', 'active')])
            ->withCount(['pdfJobs', 'uploadedFiles']);

        // Search by name or email
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by Plan
        if ($planFilter = $request->input('plan')) {
            $query->whereHas('plan', function ($q) use ($planFilter) {
                $q->where('name', $planFilter);
            });
        }

        // Filter by Role
        if ($request->input('role') === 'admin') {
            $query->where('is_admin', true);
        } elseif ($request->input('role') === 'member') {
            $query->where('is_admin', false);
        }

        $users = $query->latest()->paginate(15)->withQueryString();
        $plans = Plan::active()->get();

        // Counts for tab metrics
        $totalCount = User::count();
        $premiumCount = User::whereHas('plan', fn ($q) => $q->where('name', '!=', 'free'))->count();
        $freeCount = User::whereHas('plan', fn ($q) => $q->where('name', 'free'))->orWhereNull('plan_id')->count();
        $adminCount = User::where('is_admin', true)->count();

        return view('admin.users.index', compact(
            'users',
            'plans',
            'totalCount',
            'premiumCount',
            'freeCount',
            'adminCount'
        ));
    }

    /**
     * Quick assign plan to user (Free, Pro, Business, Teacher, etc.)
     */
    public function assignPlan(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
        ]);

        $plan = Plan::findOrFail($request->plan_id);
        $user->update(['plan_id' => $plan->id]);

        if ($plan->isFree()) {
            // Cancel active subscriptions
            Subscription::where('user_id', $user->id)
                ->where('status', 'active')
                ->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                ]);
        } else {
            // Activate subscription
            Subscription::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'plan_id' => $plan->id,
                    'status' => 'active',
                    'billing_interval' => 'yearly',
                    'current_period_start' => now(),
                    'current_period_end' => now()->addYear(),
                ]
            );
        }

        return back()->with('success', "กำหนดแผน {$plan->display_name_th} ให้แก่ \"{$user->name}\" เรียบร้อยแล้ว");
    }

    /**
     * Update user details (name, email, password, admin role, plan).
     */
    public function updateUser(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'plan_id' => ['required', 'exists:plans,id'],
            'is_admin' => ['nullable', 'boolean'],
            'password' => ['nullable', 'string', Password::min(6)],
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'is_admin' => $request->boolean('is_admin'),
            'plan_id' => $validated['plan_id'],
        ];

        if (! empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        // Sync subscription with chosen plan
        $plan = Plan::find($validated['plan_id']);
        if ($plan) {
            if ($plan->isFree()) {
                Subscription::where('user_id', $user->id)
                    ->where('status', 'active')
                    ->update([
                        'status' => 'cancelled',
                        'cancelled_at' => now(),
                    ]);
            } else {
                Subscription::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'plan_id' => $plan->id,
                        'status' => 'active',
                        'billing_interval' => 'yearly',
                        'current_period_start' => now(),
                        'current_period_end' => now()->addYear(),
                    ]
                );
            }
        }

        return back()->with('success', "อัปเดตข้อมูลผู้ใช้ \"{$user->name}\" สำเร็จเรียบร้อย");
    }

    /**
     * Delete user account.
     */
    public function deleteUser(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'ไม่สามารถลบบัญชีของตนเองที่กำลังเข้าสู่ระบบอยู่ได้');
        }

        $userName = $user->name;
        $user->delete();

        return back()->with('success', "ลบบัญชีผู้ใช้ \"{$userName}\" เรียบร้อยแล้ว");
    }
}
