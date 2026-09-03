@extends('layouts.app')
@section('title', 'ลืมรหัสผ่าน')
@section('content')
<div class='min-h-[calc(100vh-4rem)] flex items-center justify-center py-16 px-4'>
    <div class='w-full max-w-md'>
        <div class='text-center mb-8'>
            <h1 class='text-2xl font-bold text-gray-800'>รีเซ็ตรหัสผ่าน</h1>
            <p class='text-gray-500 text-sm mt-2'>กรอกอีเมลเพื่อรับลิงก์รีเซ็ต</p>
        </div>
        <div class='bg-white border border-gray-100 shadow-sm rounded-2xl p-8 border border-gray-100'>
            <form method='POST' action='#' class='space-y-4'>
                @csrf
                <div>
                    <label class='block text-sm font-medium text-gray-600 mb-1.5'>อีเมล</label>
                    <input type='email' name='email' required placeholder='you@example.com'
                           class='w-full bg-gray-50 border border-gray-200 text-gray-800 placeholder-slate-500 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/50'>
                </div>
                <button type='submit' class='w-full btn-primary py-3.5 rounded-xl text-sm'>ส่งลิงก์รีเซ็ต</button>
            </form>
        </div>
        <p class='text-center text-sm text-gray-400 mt-6'>
            <a href='{{ route("login") }}' class='text-brand-600 hover:text-brand-600'>กลับไปเข้าสู่ระบบ</a>
        </p>
    </div>
</div>
@endsection
