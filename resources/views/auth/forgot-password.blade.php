@extends('layouts.app')

@section('title', 'ลืมรหัสผ่าน — PDFkrub')
@section('description', 'รีเซ็ตรหัสผ่านบัญชีผู้ใช้งาน PDFkrub กรอกอีเมลเพื่อรับลิงก์สำหรับตั้งรหัสผ่านใหม่')

@section('content')
<div class="min-h-[calc(100vh-4rem)] flex items-center justify-center py-16 px-4">
    <div class="w-full max-w-md">

        {{-- Logo & Heading --}}
        <div class="text-center mb-8">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-3 mb-6 group">
                <img src="{{ asset('images/logo-mascot.png') }}" alt="PDFkrub" class="w-12 h-12 object-contain rounded-2xl shadow-md border border-gray-100 transition-transform group-hover:scale-105">
                <span class="text-2xl font-bold tracking-tight text-gray-800">PDF<span class="text-gradient">krub</span></span>
            </a>
            <h1 class="text-2xl font-bold text-gray-800">ลืมรหัสผ่าน?</h1>
            <p class="text-gray-500 text-sm mt-2">กรอกอีเมลของคุณเพื่อรับลิงก์สำหรับตั้งรหัสผ่านใหม่</p>
        </div>

        {{-- Card --}}
        <div class="bg-white border border-gray-100 shadow-sm rounded-2xl p-8">

            {{-- Status Alert (Success Message) --}}
            @if(session('status'))
            <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-start gap-3">
                <svg class="w-5 h-5 flex-shrink-0 text-emerald-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
                <div class="leading-relaxed">
                    <span class="font-bold block mb-0.5">ส่งข้อมูลสำเร็จ</span>
                    <span>{{ session('status') }}</span>
                </div>
            </div>
            @endif

            {{-- Form --}}
            <form method="POST" action="{{ route('password.email') }}" class="space-y-4" x-data="{ isSubmitting: false }" @submit="isSubmitting = true">
                @csrf

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-600 mb-1.5">อีเมลที่ใช้สมัคร</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
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

                <button type="submit"
                        :disabled="isSubmitting"
                        class="w-full btn-primary py-3.5 rounded-xl text-sm font-semibold flex items-center justify-center gap-2 cursor-pointer shadow-sm transition-all active:scale-98 disabled:opacity-50">
                    <svg x-show="isSubmitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                    <span x-text="isSubmitting ? 'กำลังส่งข้อมูล...' : 'ส่งลิงก์รีเซ็ตรหัสผ่าน'"></span>
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
