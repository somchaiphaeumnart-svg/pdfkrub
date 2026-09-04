@extends('layouts.app')

@section('title', 'จัดการโปรไฟล์ — PDFkrub')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
            <nav class="flex items-center gap-2 text-xs text-gray-400 mb-2">
                <a href="{{ route('home') }}" class="hover:text-gray-600 transition-colors">หน้าแรก</a>
                <span>/</span>
                <a href="{{ route('dashboard') }}" class="hover:text-gray-600 transition-colors">แดชบอร์ด</a>
                <span>/</span>
                <span class="text-gray-600">จัดการโปรไฟล์</span>
            </nav>
            <h1 class="text-3xl font-extrabold text-gray-800">จัดการโปรไฟล์และบัญชี</h1>
            <p class="text-gray-500 text-sm mt-1">อัปเดตข้อมูลส่วนตัว ความปลอดภัย และเปลี่ยนรหัสผ่านของคุณ</p>
        </div>
        <div class="flex items-center gap-3">
            @if($user->is_admin)
            <a href="{{ route('admin.index') }}" class="px-3.5 py-2 rounded-xl text-xs font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors flex items-center gap-1.5">
                <span>⚙️</span> แผงควบคุมแอดมิน
            </a>
            @endif
            <a href="{{ route('dashboard') }}" class="btn-ghost px-4 py-2 rounded-xl text-xs flex items-center gap-1.5 border border-gray-200">
                <span>📊</span> ไปยังแดชบอร์ด
            </a>
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if(session('success'))
    <div class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center justify-between shadow-xs">
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
            </svg>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">✕</button>
    </div>
    @endif

    @if($errors->any())
    <div class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-sm shadow-xs">
        <p class="font-bold mb-1">เกิดข้อผิดพลาดในการบันทึกข้อมูล:</p>
        <ul class="list-disc list-inside space-y-1 text-xs">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- User Profile Overview Banner --}}
    <div class="bg-white border border-gray-100 shadow-sm rounded-3xl p-6 mb-8 flex flex-col md:flex-row items-center justify-between gap-6">
        <div class="flex items-center gap-5">
            <div class="relative">
                <img src="{{ $user->avatar_url ?? 'https://ui-avatars.com/api/?name='.urlencode($user->name).'&background=e63946&color=fff&size=80' }}"
                     alt="{{ $user->name }}"
                     class="w-20 h-20 rounded-2xl object-cover border-2 border-brand-100 shadow-md">
                @if($user->provider === 'google')
                <span class="absolute -bottom-1 -right-1 w-6 h-6 rounded-full bg-white shadow-md border border-gray-200 flex items-center justify-center text-xs" title="เชื่อมต่อด้วย Google">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
                </span>
                @endif
            </div>
            <div>
                <div class="flex items-center gap-2 mb-1 flex-wrap">
                    <h2 class="text-xl font-bold text-gray-800">{{ $user->name }}</h2>
                    @if($user->is_admin)
                    <span class="px-2.5 py-0.5 rounded-lg text-xs font-semibold bg-purple-50 text-purple-700 border border-purple-200">
                        🛡️ แอดมิน (Admin)
                    </span>
                    @endif
                    <span class="{{ $plan->isFree() ? 'badge-free' : 'badge-premium' }} text-xs">
                        {{ $plan->display_name_th }}
                    </span>
                </div>
                <p class="text-sm text-gray-500">{{ $user->email }}</p>
                <p class="text-xs text-gray-400 mt-1">เป็นสมาชิกตั้งแต่ {{ $user->created_at->format('d/m/Y') }} ({{ $user->created_at->diffForHumans() }})</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('billing.index') }}" class="btn-primary px-5 py-2.5 rounded-xl text-xs shadow-sm flex items-center gap-1.5">
                <span>⚡</span> จัดการแพ็กเกจสมาชิก
            </a>
        </div>
    </div>

    {{-- Main Content Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        {{-- Left Column: Personal Info & Subscription Details --}}
        <div class="space-y-8">
            {{-- Edit Profile Form --}}
            <div class="bg-white border border-gray-100 shadow-sm rounded-3xl p-6 sm:p-8">
                <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-100">
                    <div class="w-10 h-10 rounded-2xl bg-brand-50 text-brand-600 flex items-center justify-center text-lg">
                        👤
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-800 text-base">ข้อมูลส่วนตัว</h3>
                        <p class="text-xs text-gray-400">แก้ไขชื่อและข้อมูลพื้นฐานของบัญชี</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">ชื่อ-นามสกุล</label>
                        <input type="text"
                               name="name"
                               value="{{ old('name', $user->name) }}"
                               required
                               class="w-full px-4 py-2.5 bg-gray-50 border {{ $errors->has('name') ? 'border-rose-300' : 'border-gray-200' }} rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all">
                        @error('name')
                        <p class="text-xs text-rose-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">อีเมล (Email)</label>
                        <input type="email"
                               name="email"
                               value="{{ old('email', $user->email) }}"
                               required
                               class="w-full px-4 py-2.5 bg-gray-50 border {{ $errors->has('email') ? 'border-rose-300' : 'border-gray-200' }} rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all">
                        @error('email')
                        <p class="text-xs text-rose-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="pt-3 flex justify-end">
                        <button type="submit" class="btn-primary px-6 py-2.5 rounded-xl text-xs font-semibold shadow-sm">
                            บันทึกข้อมูลส่วนตัว
                        </button>
                    </div>
                </form>
            </div>

            {{-- Plan & Quota Card --}}
            <div class="bg-white border border-gray-100 shadow-sm rounded-3xl p-6 sm:p-8">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-gray-800 text-base flex items-center gap-2">
                        <span>📦</span> แผนการใช้งานปัจจุบัน
                    </h3>
                    <span class="{{ $plan->isFree() ? 'badge-free' : 'badge-premium' }} text-xs">
                        {{ $plan->display_name_th }}
                    </span>
                </div>

                <div class="space-y-2.5 text-xs text-gray-600 bg-gray-50/80 p-4 rounded-2xl border border-gray-100 mb-4">
                    <div class="flex justify-between py-1 border-b border-gray-200/50">
                        <span class="text-gray-400">โควต้าการแปลงไฟล์:</span>
                        <span class="font-bold text-gray-800">{{ $plan->hasUnlimitedConversions() ? 'ไม่จำกัด (Unlimited)' : $plan->daily_conversions . ' ครั้ง/วัน' }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-gray-200/50">
                        <span class="text-gray-400">ขนาดไฟล์สูงสุด:</span>
                        <span class="font-bold text-gray-800">{{ $plan->max_file_size_mb }} MB</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-gray-200/50">
                        <span class="text-gray-400">OCR ภาษาไทย:</span>
                        <span class="font-bold {{ $plan->has_ocr ? 'text-emerald-600' : 'text-gray-400' }}">{{ $plan->has_ocr ? 'เปิดใช้งาน' : 'เฉพาะแผน Pro ขึ้นไป' }}</span>
                    </div>
                    <div class="flex justify-between py-1">
                        <span class="text-gray-400">การเก็บไฟล์:</span>
                        <span class="font-bold text-gray-800">{{ $plan->file_retention_hours }} ชั่วโมง</span>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <a href="{{ route('pricing') }}" class="text-xs text-brand-600 hover:underline font-semibold">ดูรายละเอียดแผนทั้งหมด →</a>
                    <a href="{{ route('billing.index') }}" class="btn-ghost px-4 py-2 rounded-xl text-xs font-semibold border border-gray-200">
                        จัดการการชำระเงิน
                    </a>
                </div>
            </div>
        </div>

        {{-- Right Column: Change Password --}}
        <div>
            <div class="bg-white border border-gray-100 shadow-sm rounded-3xl p-6 sm:p-8" x-data="{ showCurrent: false, showNew: false, showConfirm: false }">
                <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-100">
                    <div class="w-10 h-10 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg">
                        🔒
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-800 text-base">เปลี่ยนรหัสผ่าน</h3>
                        <p class="text-xs text-gray-400">ตั้งรหัสผ่านใหม่เพื่อความปลอดภัยของบัญชี</p>
                    </div>
                </div>

                @if($user->provider === 'google')
                <div class="mb-5 p-4 rounded-2xl bg-blue-50 border border-blue-100 text-blue-800 text-xs flex items-start gap-3">
                    <span class="text-base">💡</span>
                    <div>
                        <p class="font-bold mb-0.5">คุณเข้าสู่ระบบด้วยบัญชี Google</p>
                        <p class="text-blue-700 leading-relaxed">คุณสามารถตั้งรหัสผ่านใหม่ที่นี่เพื่อใช้เข้าสู่ระบบด้วยอีเมลและรหัสผ่านได้โดยตรง นอกเหนือจากการกดปุ่ม Google ครับ</p>
                    </div>
                </div>
                @endif

                <form method="POST" action="{{ route('profile.password') }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    {{-- Current Password (only required for regular email users) --}}
                    @if($user->provider === 'email')
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">รหัสผ่านปัจจุบัน</label>
                        <div class="relative">
                            <input :type="showCurrent ? 'text' : 'password'"
                                   name="current_password"
                                   required
                                   autocomplete="current-password"
                                   placeholder="••••••••"
                                   class="w-full px-4 py-2.5 pr-11 bg-gray-50 border {{ $errors->has('current_password') ? 'border-rose-300' : 'border-gray-200' }} rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all">
                            <button type="button" @click="showCurrent = !showCurrent" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <span x-text="showCurrent ? '🙈' : '👁️'"></span>
                            </button>
                        </div>
                        @error('current_password')
                        <p class="text-xs text-rose-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    @endif

                    {{-- New Password --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">รหัสผ่านใหม่</label>
                        <div class="relative">
                            <input :type="showNew ? 'text' : 'password'"
                                   name="password"
                                   required
                                   autocomplete="new-password"
                                   placeholder="รหัสผ่านใหม่ (อย่างน้อย 8 ตัวอักษร)"
                                   class="w-full px-4 py-2.5 pr-11 bg-gray-50 border {{ $errors->has('password') ? 'border-rose-300' : 'border-gray-200' }} rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all">
                            <button type="button" @click="showNew = !showNew" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <span x-text="showNew ? '🙈' : '👁️'"></span>
                            </button>
                        </div>
                        @error('password')
                        <p class="text-xs text-rose-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Confirm Password --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">ยืนยันรหัสผ่านใหม่อีกครั้ง</label>
                        <div class="relative">
                            <input :type="showConfirm ? 'text' : 'password'"
                                   name="password_confirmation"
                                   required
                                   autocomplete="new-password"
                                   placeholder="พิมพ์รหัสผ่านใหม่อีกครั้งให้ตรงกัน"
                                   class="w-full px-4 py-2.5 pr-11 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all">
                            <button type="button" @click="showConfirm = !showConfirm" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <span x-text="showConfirm ? '🙈' : '👁️'"></span>
                            </button>
                        </div>
                    </div>

                    {{-- Tips --}}
                    <div class="p-3 bg-gray-50 rounded-xl text-[11px] text-gray-500 space-y-1 border border-gray-100">
                        <p class="font-bold text-gray-700">ข้อแนะนำความปลอดภัย:</p>
                        <ul class="list-disc list-inside space-y-0.5">
                            <li>รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร</li>
                            <li>ควรผสมผสานตัวอักษรพิมพ์เล็ก พิมพ์ใหญ่ และตัวเลข</li>
                        </ul>
                    </div>

                    <div class="pt-3 flex justify-end">
                        <button type="submit" class="btn-primary px-6 py-2.5 rounded-xl text-xs font-semibold shadow-sm flex items-center gap-1.5">
                            <span>🔒</span> บันทึกรหัสผ่านใหม่
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
