@extends('layouts.app')

@section('title', 'เครื่องมือ PDF ทั้งหมด')
@section('description', 'เครื่องมือ PDF ครบครัน 50+ รายการ แปลง, แก้ไข, บีบอัด, รวม, แยก, OCR ไฟล์ PDF ออนไลน์ฟรี')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    {{-- Page header --}}
    <div class="mb-10 text-center">
        <h1 class="text-4xl font-bold text-gray-800 mb-3">เครื่องมือ PDF ทั้งหมด</h1>
        <p class="text-gray-500">ครบครัน 50+ รายการ รองรับภาษาไทย 100%</p>
    </div>

    {{-- Guest Call-to-action banner --}}
    @guest
    <div class="mb-10 p-5 rounded-2xl bg-gradient-to-r from-brand-50 to-red-50 border border-brand-100 flex flex-col sm:flex-row items-center justify-between gap-4 shadow-sm">
        <div class="flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-2xl bg-brand-600 text-white flex items-center justify-center flex-shrink-0 shadow-md">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/>
                </svg>
            </div>
            <div>
                <h3 class="font-bold text-gray-800 text-sm">เข้าสู่ระบบก่อนเริ่มใช้งานเครื่องมือ</h3>
                <p class="text-xs text-gray-500">กรุณาเข้าสู่ระบบหรือสมัครสมาชิกฟรีก่อนคลิกเลือกใช้งานเครื่องมือ</p>
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ route('login') }}" class="px-4 py-2 text-xs font-semibold text-gray-700 bg-white hover:bg-gray-50 border border-gray-200 rounded-xl transition-all shadow-xs">เข้าสู่ระบบ</a>
            <a href="{{ route('register') }}" class="btn-primary px-4 py-2 text-xs font-semibold rounded-xl shadow-sm">สมัครสมาชิกฟรี</a>
        </div>
    </div>
    @endguest

    {{-- Tool categories --}}
    @foreach($grouped as $categoryKey => $tools)
    <div class="mb-14">
        <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-3">
            <span class="w-1 h-6 bg-gradient-to-b from-brand-500 to-brand-700 rounded-full"></span>
            {{ $categories[$categoryKey] ?? $categoryKey }}
        </h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
            @foreach($tools as $tool)
            <a href="{{ route('tools.'.$tool['slug']) }}" class="tool-card group relative">
                @if($tool['premium'])
                <span class="absolute top-3 right-3 badge-premium">Pro</span>
                @endif
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br {{ $tool['color'] }} flex items-center justify-center text-xl mb-4 transition-transform group-hover:scale-110 shadow-lg">
                    {{ $tool['icon'] }}
                </div>
                <h3 class="text-sm font-semibold text-gray-800 group-hover:text-brand-600 transition-colors leading-tight">
                    {{ $tool['name_th'] }}
                </h3>
                <p class="text-xs text-gray-400 mt-1 line-clamp-2 leading-relaxed">{{ $tool['description_th'] }}</p>
            </a>
            @endforeach
        </div>
    </div>
    @endforeach
</div>
@endsection
