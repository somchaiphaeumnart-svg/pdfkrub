<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ResetPasswordController extends Controller
{
    /**
     * Display the password reset view for the given token.
     */
    public function show(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    /**
     * Reset the given user's password.
     */
    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ], [
            'email.required' => 'กรุณากรอกอีเมล',
            'email.email' => 'รูปแบบอีเมลไม่ถูกต้อง',
            'password.required' => 'กรุณากรอกรหัสผ่านใหม่',
            'password.confirmed' => 'การยืนยันรหัสผ่านไม่ตรงกัน',
            'password.min' => 'รหัสผ่านต้องมีความยาวอย่างน้อย 8 ตัวอักษร',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', 'รีเซ็ตรหัสผ่านสำเร็จแล้ว กรุณาเข้าสู่ระบบด้วยรหัสผ่านใหม่');
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => 'ลิงก์รีเซ็ตรหัสผ่านนี้หมดอายุหรือไม่ถูกต้อง กรุณาทำรายการใหม่อีกครั้ง']);
    }
}
