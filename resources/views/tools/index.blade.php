@extends('layouts.app')

@section('title', 'เครื่องมือ PDF ทั้งหมด')
@section('description', 'เครื่องมือ PDF ครบครัน 50+ รายการ แปลง, แก้ไข, บีบอัด, รวม, แยก, OCR ไฟล์ PDF ออนไลน์ฟรี')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    {{-- Page header --}}
    <div class="mb-12 text-center">
        <h1 class="text-4xl font-bold text-gray-800 mb-3">เครื่องมือ PDF ทั้งหมด</h1>
        <p class="text-gray-500">ครบครัน 50+ รายการ รองรับภาษาไทย 100%</p>
    </div>

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
