<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();
        $plan = $user->getActivePlan();
        $subscription = $user->activeSubscription;

        return view('profile.edit', compact('user', 'plan', 'subscription'));
    }

    /**
     * Update the user's profile information.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
        ], [
            'name.required' => 'กรุณากรอกชื่อ-นามสกุล',
            'email.required' => 'กรุณากรอกอีเมล',
            'email.email' => 'รูปแบบอีเมลไม่ถูกต้อง',
            'email.unique' => 'อีเมลนี้ถูกใช้งานแล้วในระบบ',
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return back()->with('success', 'บันทึกข้อมูลโปรไฟล์เรียบร้อยแล้ว');
    }

    /**
     * Update the user's password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();

        // If user logged in via Google OAuth only, they may not know current password
        $rules = [
            'password' => ['required', 'confirmed', Password::min(8)],
        ];

        $messages = [
            'current_password.required' => 'กรุณากรอกรหัสผ่านปัจจุบัน',
            'current_password.current_password' => 'รหัสผ่านปัจจุบันไม่ถูกต้อง',
            'password.required' => 'กรุณากรอกรหัสผ่านใหม่',
            'password.confirmed' => 'การยืนยันรหัสผ่านใหม่ไม่ตรงกัน',
            'password.min' => 'รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 8 ตัวอักษร',
        ];

        if ($user->provider === 'email' || !empty($user->password)) {
            // For OAuth users who want to set their password, require current only if provider is email
            if ($user->provider === 'email') {
                $rules['current_password'] = ['required', 'current_password'];
            }
        }

        $validated = $request->validate($rules, $messages);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'เปลี่ยนรหัสผ่านใหม่สำเร็จเรียบร้อย');
    }
}
