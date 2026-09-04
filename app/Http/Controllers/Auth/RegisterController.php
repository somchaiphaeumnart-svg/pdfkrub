<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function show(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        // Anti-spam bot honeypot
        if ($request->filled('website_url')) {
            return redirect()->route('register');
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'terms' => ['required', 'accepted'],
        ], [
            'name.required' => 'กรุณากรอกชื่อ-นามสกุล',
            'email.required' => 'กรุณากรอกอีเมล',
            'email.unique' => 'อีเมลนี้ถูกใช้งานแล้ว',
            'password.required' => 'กรุณากรอกรหัสผ่าน',
            'password.confirmed' => 'รหัสผ่านไม่ตรงกัน',
            'password.min' => 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร',
            'terms.required' => 'กรุณายอมรับข้อกำหนดการใช้งาน',
        ]);

        $freePlan = Plan::where('name', 'free')->first();

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'locale' => 'th',
            'provider' => 'email',
            'plan_id' => $freePlan?->id,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect()->intended(route('dashboard'))->with('success', 'ยินดีต้อนรับ! บัญชีของคุณถูกสร้างเรียบร้อยแล้ว 🎉');
    }
}
