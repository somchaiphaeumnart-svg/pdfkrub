@extends('layouts.app')

@section('title', 'ตั้งรหัสผ่านใหม่ — PDFkrub')
@section('description', 'ตั้งรหัสผ่านใหม่สำหรับบัญชีผู้ใช้งาน PDFkrub')

@section('content')
<div class="min-h-[calc(100vh-4rem)] flex items-center justify-center py-16 px-4">
    <div class="w-full max-w-md">

        {{-- Logo & Heading --}}
        <div class="text-center mb-8">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-3 mb-6 group">
                <img src="{{ asset('images/logo-mascot.png') }}" alt="PDFkrub" class="w-12 h-12 object-contain rounded-2xl shadow-md border border-gray-100 transition-transform group-hover:scale-105">
                <span class="text-2xl font-bold tracking-tight text-gray-800">PDF<span class="text-gradient">krub</span></span>
            </a>
            <h1 class="text-2xl font-bold text-gray-800">ตั้งรหัสผ่านใหม่</h1>
            <p class="text-gray-500 text-sm mt-2">กรอกรหัสผ่านใหม่ของคุณที่มีความยาวอย่างน้อย 8 ตัวอักษร</p>
        </div>

        {{-- Card --}}
        <div class="bg-white border border-gray-100 shadow-sm rounded-2xl p-8">

            {{-- General Error Alert --}}
            @if($errors->has('email') && !old('email'))
            <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm flex items-start gap-3">
                <svg class="w-5 h-5 flex-shrink-0 text-red-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/>
                </svg>
                <span>{{ $errors->first('email') }}</span>
            </div>
            @endif

            {{-- Form --}}
            <form method="POST" action="{{ route('password.update') }}" class="space-y-4" x-data="{ showPass: false, showConfirm: false, isSubmitting: false }" @submit="isSubmitting = true">
                @csrf

                {{-- Password Reset Token --}}
                <input type="hidden" name="token" value="{{ $token }}">

                {{-- Email --}}
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-600 mb-1.5">อีเมล</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email', $email) }}"
                        required
                        autocomplete="email"
                        placeholder="you@example.com"
                        class="w-full bg-gray-50 border {{ $errors->has('email') ? 'border-red-500 ring-1 ring-red-300' : 'border-gray-200' }} text-gray-800 placeholder-slate-400 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/50 focus:border-brand-500 transition-all">
                    
                    @error('email')
                    <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/></svg>
                        <span>{{ $message }}</span>
                    </p>
                    @enderror
                </div>

                {{-- New Password --}}
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-600 mb-1.5">รหัสผ่านใหม่</label>
                    <div class="relative">
                        <input
                            id="password"
                            :type="showPass ? 'text' : 'password'"
                            name="password"
                            required
                            autocomplete="new-password"
                            placeholder="อย่างน้อย 8 ตัวอักษร"
                            class="w-full bg-gray-50 border {{ $errors->has('password') ? 'border-red-500 ring-1 ring-red-300' : 'border-gray-200' }} text-gray-800 placeholder-slate-400 rounded-xl px-4 py-3 pr-11 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/50 focus:border-brand-500 transition-all">
                        
                        <button type="button" @click="showPass = !showPass"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors cursor-pointer">
                            <svg x-show="!showPass" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                            <svg x-show="showPass" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                        </button>
                    </div>
                    @error('password')
                    <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/></svg>
                        <span>{{ $message }}</span>
                    </p>
                    @enderror
                </div>

                {{-- Confirm Password --}}
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-600 mb-1.5">ยืนยันรหัสผ่านใหม่อีกครั้ง</label>
                    <div class="relative">
                        <input
                            id="password_confirmation"
                            :type="showConfirm ? 'text' : 'password'"
                            name="password_confirmation"
                            required
                            autocomplete="new-password"
                            placeholder="กรอกรหัสผ่านเดิมซ้ำอีกครั้ง"
                            class="w-full bg-gray-50 border border-gray-200 text-gray-800 placeholder-slate-400 rounded-xl px-4 py-3 pr-11 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/50 focus:border-brand-500 transition-all">
                        
                        <button type="button" @click="showConfirm = !showConfirm"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors cursor-pointer">
                            <svg x-show="!showConfirm" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                            <svg x-show="showConfirm" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                        </button>
                    </div>
                </div>

                <button type="submit"
                        :disabled="isSubmitting"
                        class="w-full btn-primary py-3.5 rounded-xl text-sm font-semibold flex items-center justify-center gap-2 cursor-pointer shadow-sm transition-all active:scale-98 disabled:opacity-50 mt-2">
                    <svg x-show="isSubmitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                    <span x-text="isSubmitting ? 'กำลังบันทึกรหัสผ่าน...' : 'บันทึกรหัสผ่านใหม่'"></span>
                </button>
            </form>
        </div>

        {{-- Back to Login Link --}}
        <p class="text-center text-sm text-gray-400 mt-6 flex items-center justify-center gap-1">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
            <a href="{{ route('login') }}" class="text-brand-600 hover:text-brand-700 font-medium transition-colors">กลับไปหน้าเข้าสู่ระบบ</a>
        </p>

    </div>
</div>
@endsection
