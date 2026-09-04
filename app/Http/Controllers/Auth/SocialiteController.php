<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    /**
     * Redirect to Google OAuth.
     */
    public function redirectToGoogle(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle Google callback and log in / register user.
     */
    public function handleGoogleCallback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            // Fallback to stateless in case of session/cookie mismatch (e.g. Cloudflare or cross-site)
            try {
                $googleUser = Socialite::driver('google')->stateless()->user();
            } catch (\Exception $e2) {
                Log::error('Google Login Error: ' . $e2->getMessage());

                return redirect()->route('login')
                    ->withErrors(['email' => 'ไม่สามารถเชื่อมต่อ Google ได้ (' . $e2->getMessage() . ')']);
            }
        }

        $freePlan = Plan::where('name', 'free')->first();

        // Find or create user by provider_id or email
        $user = User::where('provider', 'google')
            ->where('provider_id', $googleUser->getId())
            ->first();

        if (! $user) {
            // Check if email already registered via email/password
            $user = User::where('email', $googleUser->getEmail())->first();

            if ($user) {
                // Link Google to existing account
                $user->update([
                    'provider' => 'google',
                    'provider_id' => $googleUser->getId(),
                    'avatar_url' => $googleUser->getAvatar(),
                ]);
            } else {
                // Create new user
                $user = User::create([
                    'name' => $googleUser->getName() ?? $googleUser->getNickname() ?? 'User',
                    'email' => $googleUser->getEmail(),
                    'password' => Hash::make(Str::random(32)),
                    'avatar_url' => $googleUser->getAvatar(),
                    'provider' => 'google',
                    'provider_id' => $googleUser->getId(),
                    'email_verified_at' => now(), // Google emails are pre-verified
                    'locale' => 'th',
                    'plan_id' => $freePlan?->id,
                ]);
            }
        } else {
            // Update avatar in case it changed
            $user->update(['avatar_url' => $googleUser->getAvatar()]);
        }

        Auth::login($user, remember: true);

        request()->session()->regenerate();

        return redirect()->intended(route('dashboard'))
            ->with('success', 'เข้าสู่ระบบด้วย Google เรียบร้อย!');
    }
}
