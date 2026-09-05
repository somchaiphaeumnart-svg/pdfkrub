@extends('layouts.app')

@section('title', $tool['name_th'] ?? $tool['name'])
@section('description', $tool['description_th'] ?? '')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm text-gray-400 mb-8">
        <a href="{{ route('home') }}" class="hover:text-brand-600 transition-colors">หน้าแรก</a>
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
        <a href="{{ route('tools') }}" class="hover:text-brand-600 transition-colors">เครื่องมือ</a>
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
        <span class="text-gray-600">{{ $tool['name_th'] ?? $tool['name'] }}</span>
    </nav>

    {{-- Tool Header --}}
    <div class="flex items-start gap-5 mb-10">
        <div class="w-16 h-16 rounded-2xl bg-gradient-to-br {{ $tool['color'] }} flex items-center justify-center text-2xl shadow-lg flex-shrink-0">
            {{ $tool['icon'] }}
        </div>
        <div>
            <div class="flex items-center gap-3 mb-1">
                <h1 class="text-3xl font-bold text-gray-800">{{ $tool['name_th'] ?? $tool['name'] }}</h1>
                @if($tool['premium'])
                <span class="badge-premium">Pro</span>
                @else
                <span class="badge-free">ฟรี</span>
                @endif
            </div>
            <p class="text-gray-500">{{ $tool['description_th'] ?? '' }}</p>
        </div>
    </div>

    {{-- Premium gate --}}
    @if($tool['premium'] && !(auth()->check() && auth()->user()->getActivePlan()->has_ocr))
    <div class="bg-white border border-gray-100 shadow-sm rounded-2xl p-8 border border-accent-200 mb-8">
        <div class="flex items-start gap-4">
            <div class="w-10 h-10 bg-accent-100 rounded-xl flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-accent-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/>
                </svg>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-bold text-gray-800 mb-1">ฟีเจอร์นี้สำหรับสมาชิก Pro</h3>
                <p class="text-gray-500 text-sm mb-4">อัปเกรดเพื่อใช้งาน {{ $tool['name_th'] }} ได้ไม่จำกัด พร้อม OCR ภาษาไทยความแม่นยำสูง</p>
                <a href="{{ route('pricing') }}" class="btn-accent text-sm px-6 py-2.5 rounded-xl inline-block">อัปเกรดเป็น Pro — ฿199/เดือน</a>
            </div>
        </div>
    </div>
    @endif

    {{-- Upload Zone --}}
    <div class="bg-white border border-gray-100 shadow-sm rounded-3xl p-8 border border-gray-100"
         x-data="fileUpload({ maxSizeMb: {{ auth()->check() ? auth()->user()->getActivePlan()->max_file_size_mb : 10 }}, accept: '{{ $tool['accepts'] }}', maxFiles: {{ in_array($tool['slug'], ['merge-pdf', 'image-to-pdf']) ? 20 : 1 }}, tool: '{{ $tool['slug'] }}' })"
         x-init="$watch('files', val => {})">

        {{-- Drop zone --}}
        <div class="upload-zone p-12 text-center cursor-pointer mb-6"
             @dragover.prevent="isDragging = true"
             @dragleave.prevent="isDragging = false"
             @drop.prevent="handleDrop($event)"
             :class="{ 'drag-over': isDragging }"
             @click="$refs.fileInput.click()">

            <input type="file"
                   x-ref="fileInput"
                   class="hidden"
                   accept="{{ $tool['accepts'] }}"
                   @if(in_array($tool['slug'], ['merge-pdf', 'image-to-pdf'])) multiple @endif
                   @change="handleFileInput($event)">

            <template x-if="!hasFiles">
                <div>
                    <div class="w-20 h-20 mx-auto mb-5 bg-brand-50 rounded-2xl flex items-center justify-center">
                        <svg class="w-10 h-10 text-brand-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/>
                        </svg>
                    </div>
                    <p class="text-gray-800 font-semibold text-lg mb-2">ลากและวางไฟล์ที่นี่</p>
                    <p class="text-gray-500 text-sm">รองรับ <span class="text-brand-600">{{ str_replace(',', ', ', $tool['accepts']) }}</span></p>
                    <p class="text-gray-400 text-xs mt-2">
                        ขนาดสูงสุด {{ auth()->check() ? auth()->user()->getActivePlan()->max_file_size_mb : '10' }} MB
                        @if(!auth()->check()) · <a href="{{ route('register') }}" class="text-brand-600 hover:underline">สมัครสมาชิก</a>เพื่อเพิ่มขีดจำกัด @endif
                    </p>
                </div>
            </template>

            <template x-if="hasFiles">
                <div class="text-left w-full" @click.stop>
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-gray-800 font-semibold" x-text="`${files.length} ไฟล์ (${totalSize})`"></span>
                        <button @click="clearAll()" class="text-xs text-gray-500 hover:text-error-500 transition-colors px-3 py-1 rounded-lg hover:bg-error-500/10">ล้างทั้งหมด</button>
                    </div>
                    <div class="space-y-2 max-h-60 overflow-y-auto mb-2">
                        <template x-for="f in files" :key="f.id">
                            <div class="flex items-center gap-3 bg-white border border-gray-100 shadow-sm-light px-4 py-3 rounded-xl">
                                <svg class="w-5 h-5 text-brand-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-gray-700 truncate" x-text="f.name"></p>
                                    <p class="text-xs text-gray-400" x-text="f.sizeFormatted"></p>
                                </div>
                                <button @click="removeFile(f.id)" class="text-gray-400 hover:text-error-500 transition-colors p-1 rounded">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </template>
                    </div>
                    <div class="flex items-center justify-between mt-2">
                        @if($tool['slug'] === 'rotate-pdf')
                        <button type="button" @click="$refs.fileInput.click()" class="text-xs text-brand-600 hover:text-brand-700 transition-colors flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                            เลือกไฟล์ PDF อื่น
                        </button>
                        @else
                        <button @click="$refs.fileInput.click()" class="text-xs text-brand-600 hover:text-brand-600 transition-colors flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                            เพิ่มไฟล์อีก
                        </button>
                        @endif
                    </div>

                    @if($tool['slug'] === 'rotate-pdf')
                    {{-- Rotate Controls & Live Preview Editor --}}
                    <div class="mt-5 pt-5 border-t border-gray-100" @click.stop>
                        {{-- Editor Action Bar --}}
                        <div class="bg-slate-50/90 border border-slate-200/90 rounded-2xl p-4 sm:p-5 shadow-xs mb-4">
                            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-3.5">
                                <span class="text-sm font-bold text-gray-800 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-brand-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
                                    </svg>
                                    ตัวเลือกการหมุนหน้า PDF
                                </span>

                                {{-- Angle Badge --}}
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-gray-500">มุมหมุนปัจจุบัน:</span>
                                    <span class="inline-flex items-center gap-1.5 text-xs font-bold px-3 py-1 rounded-full bg-brand-50 text-brand-700 border border-brand-200 shadow-2xs">
                                        <span class="w-2 h-2 rounded-full bg-brand-500 animate-pulse"></span>
                                        <span x-text="rotationText"></span>
                                    </span>
                                </div>
                            </div>

                            {{-- Action Buttons: Rotate Left, Rotate Right, Rotate 180, Reset --}}
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                                {{-- Rotate Left (-90°) --}}
                                <button type="button"
                                        @click="rotateLeft()"
                                        class="flex items-center justify-center gap-2 px-3 py-2.5 rounded-xl bg-white border border-gray-200 text-gray-700 hover:border-brand-500 hover:text-brand-600 hover:shadow-xs active:scale-95 transition-all text-sm font-semibold cursor-pointer">
                                    <svg class="w-4 h-4 text-brand-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
                                    </svg>
                                    หมุนซ้าย 90°
                                </button>

                                {{-- Rotate Right (+90°) --}}
                                <button type="button"
                                        @click="rotateRight()"
                                        class="flex items-center justify-center gap-2 px-3 py-2.5 rounded-xl bg-white border border-gray-200 text-gray-700 hover:border-brand-500 hover:text-brand-600 hover:shadow-xs active:scale-95 transition-all text-sm font-semibold cursor-pointer">
                                    <svg class="w-4 h-4 text-brand-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m15 15 6-6m0 0-6-6m6 6H9a6 6 0 0 0 0 12h3" />
                                    </svg>
                                    หมุนขวา 90°
                                </button>

                                {{-- Rotate 180° --}}
                                <button type="button"
                                        @click="rotate180()"
                                        class="flex items-center justify-center gap-2 px-3 py-2.5 rounded-xl bg-white border border-gray-200 text-gray-700 hover:border-brand-500 hover:text-brand-600 hover:shadow-xs active:scale-95 transition-all text-sm font-medium cursor-pointer">
                                    <svg class="w-4 h-4 text-brand-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12c0-1.232-.046-2.453-.138-3.662a4.006 4.006 0 0 0-3.7-3.7 48.678 48.678 0 0 0-7.324 0 4.006 4.006 0 0 0-3.7 3.7c-.017.22-.032.441-.046.662M19.5 12l3-3m-3 3-3-3m-9 3c0 1.232.046 2.453.138 3.662a4.006 4.006 0 0 0 3.7 3.7 48.656 48.656 0 0 0 7.324 0 4.006 4.006 0 0 0 3.7-3.7c.017-.22.032-.441.046-.662M4.5 12l3 3m-3-3-3 3" />
                                    </svg>
                                    หมุน 180°
                                </button>

                                {{-- Reset / Original (0°) --}}
                                <button type="button"
                                        @click="resetRotation()"
                                        :class="rotationAngle === 0 ? 'bg-gray-100 text-gray-400 border-gray-200' : 'bg-white text-gray-600 hover:border-gray-300 hover:text-gray-800 hover:shadow-xs'"
                                        class="flex items-center justify-center gap-1.5 px-3 py-2.5 rounded-xl border text-xs font-medium transition-all cursor-pointer">
                                    คืนค่าเดิม (0°)
                                </button>
                            </div>
                        </div>

                        {{-- Live Preview Canvas Screen --}}
                        <div class="relative bg-slate-100/90 rounded-2xl border border-slate-200/90 p-4 sm:p-6 overflow-hidden flex flex-col items-center justify-center min-h-[350px]">
                            {{-- Preview Header Badge --}}
                            <div class="absolute top-3 left-3 z-10">
                                <span class="text-[11px] font-medium text-slate-500 bg-white/95 backdrop-blur-xs px-2.5 py-1 rounded-md border border-slate-200 shadow-2xs flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                    ตัวอย่างแบบเรียลไทม์
                                </span>
                            </div>

                            {{-- Page Counter Badge --}}
                            <div class="absolute top-3 right-3 z-10" x-show="pdfTotalPages > 0">
                                <span class="text-[11px] font-medium text-slate-600 bg-white/95 backdrop-blur-xs px-2.5 py-1 rounded-md border border-slate-200 shadow-2xs">
                                    หน้า <span class="font-bold text-slate-900" x-text="pdfCurrentPage"></span> จาก <span x-text="pdfTotalPages"></span>
                                </span>
                            </div>

                            {{-- Canvas Preview Screen with Loading Overlay --}}
                            <div class="relative w-full flex items-center justify-center py-4 min-h-[310px]">
                                {{-- Loading Spinner Overlay --}}
                                <div x-show="isRenderingPdf"
                                     class="absolute inset-0 z-20 flex flex-col items-center justify-center bg-slate-100/80 backdrop-blur-xs rounded-xl transition-opacity">
                                    <svg class="w-8 h-8 animate-spin text-brand-600 mb-2" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    <span class="text-xs font-semibold text-slate-600">กำลังโหลดตัวอย่างเอกสาร PDF...</span>
                                </div>

                                {{-- Image Preview with CSS Rotate Animation on Wrapper --}}
                                <div class="relative max-w-[280px] max-h-[300px] flex items-center justify-center transition-transform duration-300 ease-out origin-center"
                                     :style="`transform: rotate(${rotationAngle}deg);`">
                                    <template x-if="previewImageUrl">
                                        <img :src="previewImageUrl"
                                             alt="PDF Preview"
                                             class="max-w-[260px] max-h-[290px] w-auto h-auto rounded-lg shadow-xl border border-slate-300 bg-white object-contain select-none">
                                    </template>
                                </div>
                            </div>

                            {{-- Blank page explanation alert --}}
                            <div x-show="isCurrentPageBlank"
                                 class="mt-2 flex items-center justify-center gap-1.5 text-xs text-amber-700 bg-amber-50/95 border border-amber-200/80 px-3 py-1.5 rounded-xl shadow-2xs">
                                <svg class="w-4 h-4 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>หน้านี้เป็นหน้าว่างในไฟล์ PDF ต้นฉบับ (ไม่มีข้อความหรือเนื้อหา)</span>
                            </div>

                            {{-- Error notice if any --}}
                            <p x-show="pdfRenderError" class="text-xs text-amber-600 mt-2 text-center" x-text="pdfRenderError"></p>

                            {{-- Pagination if multi-page PDF --}}
                            <div x-show="pdfTotalPages > 1" class="flex flex-wrap items-center justify-center gap-2 mt-4 pt-3 border-t border-slate-200/70 w-full text-xs text-slate-600">
                                <button type="button"
                                        @click="prevPage()"
                                        :disabled="pdfCurrentPage <= 1 || isRenderingPdf"
                                        class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed shadow-2xs font-medium transition-all cursor-pointer flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                    หน้าก่อน
                                </button>
                                <div class="flex items-center gap-1">
                                    <template x-for="p in Math.min(pdfTotalPages, 10)" :key="p">
                                        <button type="button"
                                                @click="goToPage(p)"
                                                :class="pdfCurrentPage === p ? 'bg-brand-600 text-white font-bold border-brand-600 shadow-2xs' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'"
                                                class="w-7 h-7 rounded-lg border text-xs flex items-center justify-center font-medium transition-all cursor-pointer"
                                                x-text="p">
                                        </button>
                                    </template>
                                    <span x-show="pdfTotalPages > 10" class="text-slate-400 text-xs px-1">...</span>
                                </div>
                                <button type="button"
                                        @click="nextPage()"
                                        :disabled="pdfCurrentPage >= pdfTotalPages || isRenderingPdf"
                                        class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed shadow-2xs font-medium transition-all cursor-pointer flex items-center gap-1">
                                    หน้าถัดไป
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            </div>
                        </div>

                        <p class="text-center text-xs text-gray-400 mt-2.5">
                            * ทุกหน้าในไฟล์ PDF จะถูกหมุนตามมุมที่ท่านเลือก
                        </p>
                    </div>
                    @endif

                    {{-- Delete Pages Visual Editor --}}
                    @if($tool['slug'] === 'delete-pages')
                    <div class="mt-6 pt-6 border-t border-gray-100" @click.stop>
                        {{-- Editor Card Container --}}
                        <div class="bg-slate-50/90 rounded-2xl border border-slate-200/90 p-4 sm:p-6 shadow-2xs">
                            {{-- Header & Stats --}}
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-4 mb-4 border-b border-slate-200">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full bg-red-500 animate-pulse"></span>
                                        <h3 class="text-base font-bold text-gray-800">เลือกหน้าที่ต้องการลบออกจาก PDF</h3>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        คลิกที่หน้าเพื่อเลือกลบ (หรือคลิกซ้ำเพื่อยกเลิก)
                                    </p>
                                </div>

                                {{-- Stats badges --}}
                                <div class="flex flex-wrap items-center gap-2 text-xs">
                                    <span class="px-3 py-1.5 rounded-xl bg-white border border-slate-200 font-medium text-slate-700 shadow-2xs">
                                        ทั้งหมด: <strong class="text-slate-900" x-text="deleteTotalPages"></strong> หน้า
                                    </span>
                                    <span class="px-3 py-1.5 rounded-xl bg-red-50 border border-red-200 font-medium text-red-700 shadow-2xs">
                                        ลบออก: <strong class="text-red-600 font-bold" x-text="selectedPagesToDelete.length"></strong> หน้า
                                    </span>
                                    <span class="px-3 py-1.5 rounded-xl bg-emerald-50 border border-emerald-200 font-medium text-emerald-700 shadow-2xs">
                                        คงเหลือ: <strong class="text-emerald-700 font-bold" x-text="remainingPagesCount"></strong> หน้า
                                    </span>
                                </div>
                            </div>

                            {{-- Action Toolbar & Manual input --}}
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                                {{-- Quick Selection Buttons --}}
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="text-xs text-gray-400 mr-1">เลือกด่วน:</span>
                                    <button type="button"
                                            @click="selectEvenPages()"
                                            class="px-2.5 py-1 rounded-lg text-xs font-medium bg-white border border-gray-200 text-gray-700 hover:border-brand-500 hover:text-brand-600 shadow-2xs transition-all cursor-pointer">
                                        หน้าคู่
                                    </button>
                                    <button type="button"
                                            @click="selectOddPages()"
                                            class="px-2.5 py-1 rounded-lg text-xs font-medium bg-white border border-gray-200 text-gray-700 hover:border-brand-500 hover:text-brand-600 shadow-2xs transition-all cursor-pointer">
                                        หน้าคี่
                                    </button>
                                    <button type="button"
                                            x-show="deletePagesList.some(x => x.isBlank)"
                                            @click="selectBlankPages()"
                                            class="px-2.5 py-1 rounded-lg text-xs font-medium bg-amber-50 border border-amber-300 text-amber-800 hover:bg-amber-100 shadow-2xs transition-all cursor-pointer flex items-center gap-1">
                                        📄 เลือกลบหน้าว่าง
                                    </button>
                                    <button type="button"
                                            x-show="selectedPagesToDelete.length > 0"
                                            @click="clearPageSelection()"
                                            class="px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-100 border border-slate-200 text-slate-600 hover:bg-slate-200 shadow-2xs transition-all cursor-pointer">
                                        ล้างการเลือก
                                    </button>
                                </div>

                                {{-- Manual Input --}}
                                <div class="flex items-center gap-2">
                                    <label class="text-xs text-gray-500 whitespace-nowrap">ระบุเลขหน้า:</label>
                                    <input type="text"
                                           :value="deleteManualInput"
                                           @input="handleManualPageInput($event.target.value)"
                                           placeholder="เช่น 1, 3-5"
                                           class="w-32 sm:w-36 px-2.5 py-1 text-xs rounded-lg border border-gray-300 bg-white focus:outline-hidden focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-gray-700 shadow-2xs">
                                </div>
                            </div>

                            {{-- Error Banner if any --}}
                            <div x-show="deletePagesError"
                                 class="mb-4 flex items-center gap-2 px-3.5 py-2 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs shadow-2xs"
                                 x-text="deletePagesError">
                            </div>

                            {{-- Grid of Page Cards --}}
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3.5 max-h-[540px] overflow-y-auto p-1 rounded-xl">
                                <template x-for="item in deletePagesList" :key="item.pageNum">
                                    <div @click="togglePageDeletion(item.pageNum)"
                                         :class="isPageSelectedForDeletion(item.pageNum)
                                             ? 'border-2 border-red-500 bg-red-50/80 shadow-md ring-2 ring-red-400/30'
                                             : 'border border-slate-200 bg-white hover:border-brand-500 hover:shadow-md'"
                                         class="group relative rounded-xl p-2.5 flex flex-col items-center justify-between cursor-pointer transition-all duration-150 select-none overflow-hidden">

                                        {{-- Delete / Keep Action Button at top-right --}}
                                        <button type="button"
                                                @click.stop="togglePageDeletion(item.pageNum)"
                                                :title="isPageSelectedForDeletion(item.pageNum) ? 'คลิกเพื่อเก็บหน้านี้ไว้' : 'คลิกเพื่อลบหน้านี้'"
                                                :class="isPageSelectedForDeletion(item.pageNum)
                                                    ? 'bg-red-500 text-white shadow-xs'
                                                    : 'bg-white/95 text-slate-400 hover:text-red-500 hover:bg-red-50 border border-slate-200 shadow-2xs'"
                                                class="absolute top-2 right-2 z-20 w-6 h-6 rounded-lg flex items-center justify-center transition-all cursor-pointer">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                            </svg>
                                        </button>

                                        {{-- Thumbnail Canvas/Image Area --}}
                                        <div class="relative w-full aspect-[3/4] flex items-center justify-center bg-slate-100 rounded-lg overflow-hidden my-1">
                                            {{-- Loading Skeleton --}}
                                            <template x-if="!item.dataUrl">
                                                <div class="w-full h-full flex flex-col items-center justify-center bg-slate-100 animate-pulse text-slate-400">
                                                    <svg class="w-6 h-6 animate-spin text-slate-400 mb-1" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                                    </svg>
                                                    <span class="text-[10px]" x-text="`หน้า ${item.pageNum}`"></span>
                                                </div>
                                            </template>

                                            {{-- Loaded Image --}}
                                            <template x-if="item.dataUrl">
                                                <img :src="item.dataUrl"
                                                     alt="Page thumbnail"
                                                     class="w-full h-full object-contain bg-white rounded">
                                            </template>

                                            {{-- Deletion Overlay Tint & Badge --}}
                                            <div x-show="isPageSelectedForDeletion(item.pageNum)"
                                                 class="absolute inset-0 bg-red-600/25 backdrop-blur-[1px] flex flex-col items-center justify-center z-10 transition-all">
                                                <div class="bg-red-600 text-white rounded-full p-2 shadow-md mb-1 animate-bounce">
                                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                                    </svg>
                                                </div>
                                                <span class="text-[11px] font-bold text-white bg-red-600 px-2 py-0.5 rounded-md shadow-xs">
                                                    จะถูกลบ
                                                </span>
                                            </div>
                                        </div>

                                        {{-- Footer Label --}}
                                        <div class="w-full flex items-center justify-between px-1 pt-1">
                                            <span class="text-xs font-semibold text-slate-700" x-text="`หน้า ${item.pageNum}`"></span>
                                            <template x-if="item.isBlank">
                                                <span class="text-[10px] text-amber-700 bg-amber-100 px-1.5 py-0.5 rounded font-medium">หน้าว่าง</span>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            {{-- Bottom Hint --}}
                            <div class="mt-4 pt-3 border-t border-slate-200/80 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-2">
                                <span>* เอกสารใหม่หลังการลบจะมีเฉพาะหน้าที่ไม่ได้ถูกเลือก</span>
                                <span x-show="selectedPagesToDelete.length === 0" class="text-brand-600 font-medium">
                                    👈 กรุณาคลิกเลือกหน้าที่ต้องการลบอย่างน้อย 1 หน้า
                                </span>
                                <span x-show="selectedPagesToDelete.length > 0 && canSubmitDeletePages" class="text-emerald-600 font-semibold">
                                    ✓ พร้อมลบ (จะเหลือเอกสาร <span x-text="remainingPagesCount"></span> หน้า)
                                </span>
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- Watermark PDF Visual Editor --}}
                    @if($tool['slug'] === 'watermark-pdf')
                    <div class="mt-6 pt-6 border-t border-gray-100" @click.stop>
                        <div class="bg-slate-50/90 rounded-2xl border border-slate-200/90 p-4 sm:p-6 shadow-2xs">
                            {{-- Editor Title Header --}}
                            <div class="flex items-center justify-between pb-4 mb-5 border-b border-slate-200">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-brand-500 animate-pulse"></span>
                                    <h3 class="text-base font-bold text-gray-800">จัดการลายน้ำเอกสาร PDF (Watermark Editor)</h3>
                                </div>
                                <span class="text-xs text-slate-500 bg-white px-2.5 py-1 rounded-lg border border-slate-200 shadow-2xs">
                                    ตัวอย่างแบบเรียลไทม์
                                </span>
                            </div>

                            {{-- Two-Column Layout: Controls (Left) & Live Preview (Right) --}}
                            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                                {{-- Left Controls Column (7 cols) --}}
                                <div class="lg:col-span-7 space-y-4">
                                    {{-- Tab Selector: Image vs Text --}}
                                    <div class="flex rounded-xl bg-slate-200/70 p-1">
                                        <button type="button"
                                                @click="watermarkType = 'image'"
                                                :class="watermarkType === 'image' ? 'bg-white text-brand-600 font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900 font-medium'"
                                                class="flex-1 py-2 text-xs rounded-lg transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            รูปลายน้ำ (Logo / Image)
                                        </button>
                                        <button type="button"
                                                @click="watermarkType = 'text'"
                                                :class="watermarkType === 'text' ? 'bg-white text-brand-600 font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900 font-medium'"
                                                class="flex-1 py-2 text-xs rounded-lg transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            ข้อความลายน้ำ (Text)
                                        </button>
                                    </div>

                                    {{-- If Image Watermark --}}
                                    <div x-show="watermarkType === 'image'" class="space-y-3 bg-white p-4 rounded-xl border border-slate-200">
                                        <label class="block text-xs font-semibold text-slate-700">อัปโหลดไฟล์รูปลายน้ำ (PNG, JPG, SVG, WEBP)</label>
                                        <template x-if="!watermarkImageDataUrl">
                                            <div @click="$refs.wmFileInput.click()"
                                                 class="border-2 border-dashed border-slate-300 hover:border-brand-500 rounded-xl p-4 text-center cursor-pointer transition-colors bg-slate-50/60 hover:bg-brand-50/30">
                                                <input type="file"
                                                       x-ref="wmFileInput"
                                                       accept="image/png,image/jpeg,image/svg+xml,image/webp"
                                                       @change="handleWatermarkImageSelect($event)"
                                                       class="hidden">
                                                <svg class="w-8 h-8 text-slate-400 mx-auto mb-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                <p class="text-xs font-medium text-slate-700">คลิกเพื่อเลือกไฟล์รูปภาพลายน้ำ</p>
                                                <p class="text-[11px] text-slate-400 mt-0.5">แนะนำไฟล์ PNG พื้นหลังโปร่งใสเพื่อผลลัพธ์ที่ดีที่สุด</p>
                                            </div>
                                        </template>
                                        <template x-if="watermarkImageDataUrl">
                                            <div class="flex items-center justify-between p-2.5 bg-slate-50 rounded-xl border border-slate-200">
                                                <div class="flex items-center gap-3">
                                                    <img :src="watermarkImageDataUrl" class="w-10 h-10 object-contain bg-white rounded border border-slate-200 p-0.5 shadow-2xs">
                                                    <div class="min-w-0">
                                                        <p class="text-xs font-medium text-slate-800 truncate" x-text="watermarkImageName || 'รูปลายน้ำ'"></p>
                                                        <span class="text-[10px] text-emerald-600 font-medium">✓ โหลดรูปภาพสำเร็จ</span>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-1.5">
                                                    <button type="button"
                                                            @click="$refs.wmFileInput.click()"
                                                            class="text-xs px-2.5 py-1 text-slate-600 bg-white hover:bg-slate-100 border border-slate-200 rounded-lg cursor-pointer">
                                                        เปลี่ยนรูป
                                                    </button>
                                                    <button type="button"
                                                            @click="removeWatermarkImage()"
                                                            class="p-1 text-slate-400 hover:text-red-600 rounded-lg cursor-pointer">
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    </button>
                                                </div>
                                                <input type="file"
                                                       x-ref="wmFileInput"
                                                       accept="image/png,image/jpeg,image/svg+xml,image/webp"
                                                       @change="handleWatermarkImageSelect($event)"
                                                       class="hidden">
                                            </div>
                                        </template>
                                    </div>

                                    {{-- If Text Watermark --}}
                                    <div x-show="watermarkType === 'text'" class="space-y-3 bg-white p-4 rounded-xl border border-slate-200">
                                        <div>
                                            <label class="block text-xs font-semibold text-slate-700 mb-1">ข้อความลายน้ำ</label>
                                            <input type="text"
                                                   x-model="watermarkText"
                                                   placeholder="เช่น สำเนาถูกต้อง, DRAFT, CONFIDENTIAL"
                                                   class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-slate-800">
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs text-slate-600 font-medium">สีข้อความ:</span>
                                            <div class="flex items-center gap-1.5">
                                                <button type="button" @click="watermarkTextColor = '#dc2626'" class="w-5 h-5 rounded-full bg-red-600 border-2" :class="watermarkTextColor === '#dc2626' ? 'border-slate-800 ring-2 ring-red-400' : 'border-white'"></button>
                                                <button type="button" @click="watermarkTextColor = '#2563eb'" class="w-5 h-5 rounded-full bg-blue-600 border-2" :class="watermarkTextColor === '#2563eb' ? 'border-slate-800 ring-2 ring-blue-400' : 'border-white'"></button>
                                                <button type="button" @click="watermarkTextColor = '#4b5563'" class="w-5 h-5 rounded-full bg-gray-600 border-2" :class="watermarkTextColor === '#4b5563' ? 'border-slate-800 ring-2 ring-gray-400' : 'border-white'"></button>
                                                <button type="button" @click="watermarkTextColor = '#000000'" class="w-5 h-5 rounded-full bg-black border-2" :class="watermarkTextColor === '#000000' ? 'border-slate-800 ring-2 ring-black' : 'border-white'"></button>
                                                <button type="button" @click="watermarkTextColor = '#d97706'" class="w-5 h-5 rounded-full bg-amber-600 border-2" :class="watermarkTextColor === '#d97706' ? 'border-slate-800 ring-2 ring-amber-400' : 'border-white'"></button>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Slider 1: Opacity / Transparency --}}
                                    <div class="bg-white p-3.5 rounded-xl border border-slate-200">
                                        <div class="flex items-center justify-between text-xs font-semibold text-slate-700 mb-1.5">
                                            <span>ระดับความโปร่งใส (ความเข้ม)</span>
                                            <span class="px-2 py-0.5 rounded-md bg-brand-50 text-brand-700 text-[11px] font-bold" x-text="`${watermarkOpacity}%`"></span>
                                        </div>
                                        <input type="range"
                                               min="10"
                                               max="100"
                                               step="5"
                                               x-model.number="watermarkOpacity"
                                               class="w-full accent-brand-600 cursor-pointer">
                                        <div class="flex justify-between text-[10px] text-slate-400 mt-0.5">
                                            <span>10% (จางมาก)</span>
                                            <span>50% (ปานกลาง)</span>
                                            <span>100% (ทึบแสง)</span>
                                        </div>
                                    </div>

                                    {{-- Slider 2: Scale / Size --}}
                                    <div class="bg-white p-3.5 rounded-xl border border-slate-200">
                                        <div class="flex items-center justify-between text-xs font-semibold text-slate-700 mb-1.5">
                                            <span>ขนาดลายน้ำ (Size)</span>
                                            <span class="px-2 py-0.5 rounded-md bg-brand-50 text-brand-700 text-[11px] font-bold" x-text="`${watermarkScale}%`"></span>
                                        </div>
                                        <input type="range"
                                               min="10"
                                               max="90"
                                               step="5"
                                               x-model.number="watermarkScale"
                                               class="w-full accent-brand-600 cursor-pointer">
                                        <div class="flex justify-between text-[10px] text-slate-400 mt-0.5">
                                            <span>เล็ก (10%)</span>
                                            <span>ปานกลาง (40%)</span>
                                            <span>ใหญ่ (90%)</span>
                                        </div>
                                    </div>

                                    {{-- Rotation & Positioning Controls in Grid --}}
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        {{-- Rotation --}}
                                        <div class="bg-white p-3.5 rounded-xl border border-slate-200">
                                            <label class="block text-xs font-semibold text-slate-700 mb-2">มุมเอียง (Rotation)</label>
                                            <div class="grid grid-cols-4 gap-1">
                                                <button type="button" @click="setWatermarkRotation(0)" :class="watermarkRotation === 0 ? 'bg-brand-600 text-white font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="py-1.5 rounded-lg text-xs transition-all cursor-pointer">0°</button>
                                                <button type="button" @click="setWatermarkRotation(45)" :class="watermarkRotation === 45 ? 'bg-brand-600 text-white font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="py-1.5 rounded-lg text-xs transition-all cursor-pointer">45°</button>
                                                <button type="button" @click="setWatermarkRotation(-45)" :class="watermarkRotation === -45 ? 'bg-brand-600 text-white font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="py-1.5 rounded-lg text-xs transition-all cursor-pointer">-45°</button>
                                                <button type="button" @click="setWatermarkRotation(90)" :class="watermarkRotation === 90 ? 'bg-brand-600 text-white font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="py-1.5 rounded-lg text-xs transition-all cursor-pointer">90°</button>
                                            </div>
                                        </div>

                                        {{-- Position Matrix --}}
                                        <div class="bg-white p-3.5 rounded-xl border border-slate-200">
                                            <div class="flex items-center justify-between mb-1.5">
                                                <label class="text-xs font-semibold text-slate-700">ตำแหน่ง (Position)</label>
                                                <button type="button"
                                                        @click="setWatermarkPosition('tile')"
                                                        :class="watermarkPosition === 'tile' ? 'bg-brand-600 text-white font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                                        class="px-2 py-0.5 rounded text-[10px] transition-all cursor-pointer">
                                                    ▦ ปูเต็มหน้า
                                                </button>
                                            </div>
                                            <div class="grid grid-cols-3 gap-1 max-w-[130px] mx-auto">
                                                <button type="button" @click="setWatermarkPosition('top-left')" :class="watermarkPosition === 'top-left' ? 'bg-brand-600 text-white' : 'bg-slate-100 hover:bg-slate-200 text-slate-600'" class="w-8 h-7 rounded text-[11px] font-bold cursor-pointer">↖</button>
                                                <button type="button" @click="setWatermarkPosition('top-center')" :class="watermarkPosition === 'top-center' ? 'bg-brand-600 text-white' : 'bg-slate-100 hover:bg-slate-200 text-slate-600'" class="w-8 h-7 rounded text-[11px] font-bold cursor-pointer">↑</button>
                                                <button type="button" @click="setWatermarkPosition('top-right')" :class="watermarkPosition === 'top-right' ? 'bg-brand-600 text-white' : 'bg-slate-100 hover:bg-slate-200 text-slate-600'" class="w-8 h-7 rounded text-[11px] font-bold cursor-pointer">↗</button>
                                                <button type="button" @click="setWatermarkPosition('center-left')" :class="watermarkPosition === 'center-left' ? 'bg-brand-600 text-white' : 'bg-slate-100 hover:bg-slate-200 text-slate-600'" class="w-8 h-7 rounded text-[11px] font-bold cursor-pointer">←</button>
                                                <button type="button" @click="setWatermarkPosition('center')" :class="watermarkPosition === 'center' ? 'bg-brand-600 text-white' : 'bg-slate-100 hover:bg-slate-200 text-slate-600'" class="w-8 h-7 rounded text-[11px] font-bold cursor-pointer">🎯</button>
                                                <button type="button" @click="setWatermarkPosition('center-right')" :class="watermarkPosition === 'center-right' ? 'bg-brand-600 text-white' : 'bg-slate-100 hover:bg-slate-200 text-slate-600'" class="w-8 h-7 rounded text-[11px] font-bold cursor-pointer">→</button>
                                                <button type="button" @click="setWatermarkPosition('bottom-left')" :class="watermarkPosition === 'bottom-left' ? 'bg-brand-600 text-white' : 'bg-slate-100 hover:bg-slate-200 text-slate-600'" class="w-8 h-7 rounded text-[11px] font-bold cursor-pointer">↙</button>
                                                <button type="button" @click="setWatermarkPosition('bottom-center')" :class="watermarkPosition === 'bottom-center' ? 'bg-brand-600 text-white' : 'bg-slate-100 hover:bg-slate-200 text-slate-600'" class="w-8 h-7 rounded text-[11px] font-bold cursor-pointer">↓</button>
                                                <button type="button" @click="setWatermarkPosition('bottom-right')" :class="watermarkPosition === 'bottom-right' ? 'bg-brand-600 text-white' : 'bg-slate-100 hover:bg-slate-200 text-slate-600'" class="w-8 h-7 rounded text-[11px] font-bold cursor-pointer">↘</button>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Target Pages Selection --}}
                                    <div class="bg-white p-3.5 rounded-xl border border-slate-200 flex flex-wrap items-center justify-between gap-2">
                                        <span class="text-xs font-semibold text-slate-700">หน้าที่ประทับลายน้ำ:</span>
                                        <div class="flex items-center gap-1.5 text-xs">
                                            <button type="button"
                                                    @click="watermarkPages = 'all'"
                                                    :class="watermarkPages === 'all' ? 'bg-brand-600 text-white font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                                    class="px-2.5 py-1 rounded-lg transition-all cursor-pointer">
                                                ทุกหน้า
                                            </button>
                                            <button type="button"
                                                    @click="watermarkPages = 'first'"
                                                    :class="watermarkPages === 'first' ? 'bg-brand-600 text-white font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                                    class="px-2.5 py-1 rounded-lg transition-all cursor-pointer">
                                                เฉพาะหน้าแรก
                                            </button>
                                            <button type="button"
                                                    @click="watermarkPages = 'custom'"
                                                    :class="watermarkPages === 'custom' ? 'bg-brand-600 text-white font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                                    class="px-2.5 py-1 rounded-lg transition-all cursor-pointer">
                                                กำหนดเอง
                                            </button>
                                        </div>
                                    </div>
                                    <div x-show="watermarkPages === 'custom'" class="pt-1">
                                        <input type="text"
                                               x-model="watermarkCustomPages"
                                               placeholder="ระบุเลขหน้า เช่น 1, 3-5"
                                               class="w-full px-3 py-1.5 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-brand-500 text-slate-700 bg-white">
                                    </div>
                                </div>

                                {{-- Right Preview Column (5 cols) --}}
                                <div class="lg:col-span-5 flex flex-col items-center justify-center">
                                    <div class="w-full bg-slate-200/60 rounded-2xl p-4 sm:p-5 border border-slate-200 flex flex-col items-center justify-center min-h-[380px]">
                                        {{-- Live Preview Canvas with Watermark Overlay --}}
                                        <div class="relative max-w-[280px] sm:max-w-[300px] w-full aspect-[1/1.414] bg-white rounded-xl shadow-xl border border-slate-300 overflow-hidden flex items-center justify-center">
                                            {{-- PDF Page Image --}}
                                            <template x-if="watermarkPdfPageUrl">
                                                <img :src="watermarkPdfPageUrl" class="w-full h-full object-contain pointer-events-none select-none">
                                            </template>
                                            <div x-show="isRenderingWatermarkPdf" class="absolute inset-0 bg-white/80 backdrop-blur-xs flex flex-col items-center justify-center z-30">
                                                <svg class="w-7 h-7 animate-spin text-brand-600 mb-1.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                                <span class="text-xs text-slate-600 font-medium">กำลังโหลดตัวอย่าง...</span>
                                            </div>

                                            {{-- Tile Overlay Mode (3x3 grid pattern) --}}
                                            <template x-if="watermarkPosition === 'tile'">
                                                <div class="absolute inset-0 pointer-events-none grid grid-cols-3 grid-rows-3 p-2 z-10">
                                                    <template x-for="i in 9" :key="i">
                                                        <div class="flex items-center justify-center overflow-hidden">
                                                            <template x-if="watermarkType === 'image' && watermarkImageDataUrl">
                                                                <img :src="watermarkImageDataUrl"
                                                                     :style="`opacity: ${watermarkOpacity / 100}; transform: rotate(${watermarkRotation}deg); width: ${watermarkScale * 0.75}%;`"
                                                                     class="max-w-full max-h-full object-contain transition-all select-none">
                                                            </template>
                                                            <template x-if="watermarkType === 'text' && watermarkText">
                                                                <span :style="`opacity: ${watermarkOpacity / 100}; transform: rotate(${watermarkRotation}deg); color: ${watermarkTextColor}; font-size: ${Math.max(9, watermarkScale * 0.28)}px;`"
                                                                      class="font-bold whitespace-nowrap select-none transition-all"
                                                                      x-text="watermarkText"></span>
                                                            </template>
                                                        </div>
                                                    </template>
                                                </div>
                                            </template>

                                            {{-- 9-Grid Position Overlay Mode --}}
                                            <template x-if="watermarkPosition !== 'tile'">
                                                <div class="absolute inset-0 pointer-events-none flex z-10" :class="watermarkFlexClasses">
                                                    <template x-if="watermarkType === 'image' && watermarkImageDataUrl">
                                                        <img :src="watermarkImageDataUrl"
                                                             :style="`opacity: ${watermarkOpacity / 100}; transform: rotate(${watermarkRotation}deg); width: ${watermarkScale}%;`"
                                                             class="max-w-[90%] max-h-[90%] object-contain transition-all select-none">
                                                    </template>
                                                    <template x-if="watermarkType === 'text' && watermarkText">
                                                        <div :style="`opacity: ${watermarkOpacity / 100}; transform: rotate(${watermarkRotation}deg); color: ${watermarkTextColor}; font-size: ${Math.max(11, watermarkScale * 0.4)}px; border-color: ${watermarkTextColor};`"
                                                             class="font-bold border-2 border-dashed px-2.5 py-1 rounded-lg whitespace-nowrap select-none transition-all shadow-2xs"
                                                             x-text="watermarkText">
                                                        </div>
                                                    </template>
                                                    <template x-if="watermarkType === 'image' && !watermarkImageDataUrl">
                                                        <div class="text-[11px] text-slate-400 bg-white/90 border border-dashed border-slate-300 px-3 py-2 rounded-lg text-center">
                                                            ยังไม่ได้เลือกรูปภาพ
                                                        </div>
                                                    </template>
                                                </div>
                                            </template>
                                        </div>

                                        {{-- Pagination Controls --}}
                                        <div x-show="watermarkTotalPages > 1" class="flex items-center justify-center gap-3 mt-4 text-xs text-slate-600 w-full">
                                            <button type="button"
                                                    @click="prevWatermarkPage()"
                                                    :disabled="watermarkCurrentPage <= 1 || isRenderingWatermarkPdf"
                                                    class="px-2.5 py-1 rounded-lg bg-white border border-slate-200 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed shadow-2xs font-medium transition-all cursor-pointer">
                                                ‹ หน้าก่อน
                                            </button>
                                            <span>
                                                หน้า <strong class="text-slate-800" x-text="watermarkCurrentPage"></strong> จาก <span x-text="watermarkTotalPages"></span>
                                            </span>
                                            <button type="button"
                                                    @click="nextWatermarkPage()"
                                                    :disabled="watermarkCurrentPage >= watermarkTotalPages || isRenderingWatermarkPdf"
                                                    class="px-2.5 py-1 rounded-lg bg-white border border-slate-200 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed shadow-2xs font-medium transition-all cursor-pointer">
                                                หน้าถัดไป ›
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </template>

            <div x-show="error" class="mt-3 text-sm text-error-500" x-text="error"></div>
        </div>

        {{-- Convert Button + Result --}}
        <div class="mt-6">
            {{-- Idle state: show convert button --}}
            <div x-show="!isProcessing && !isDone && !isFailed" class="flex justify-center">
                <button
                    @click="hasFiles ? startConversion('{{ $tool['slug'] }}') : $refs.fileInput.click()"
                    class="btn-primary px-10 py-4 rounded-2xl text-base flex items-center gap-2"
                    :class="{ 'opacity-50 cursor-not-allowed': !hasFiles || (tool === 'delete-pages' && hasFiles && !canSubmitDeletePages) || (tool === 'watermark-pdf' && hasFiles && !canSubmitWatermark) }"
                    :disabled="(tool === 'delete-pages' && hasFiles && !canSubmitDeletePages) || (tool === 'watermark-pdf' && hasFiles && !canSubmitWatermark)"
                    @if($tool['premium'] && !(auth()->check() && auth()->user()->getActivePlan()->has_ocr)) disabled @endif>
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/>
                    </svg>
                    <span x-text="toolButtonText || (tool === 'rotate-pdf' && hasFiles ? `หมุน PDF (${rotationAngle}°) และบันทึก` : '{{ $tool['name_th'] ?? $tool['name'] }}')"></span>
                </button>
            </div>

            {{-- Processing state: progress --}}
            <div x-show="isProcessing && !isDone && !isFailed" class="space-y-4 max-w-lg mx-auto bg-gray-50/80 border border-gray-200/80 rounded-2xl p-5 shadow-xs">
                {{-- 2-Step Indicator --}}
                <div class="flex items-center justify-between text-xs font-medium pb-2.5 border-b border-gray-200/60">
                    <div class="flex items-center gap-1.5" :class="isUploading ? 'text-brand-600 font-bold' : (isProcessingServer || isDone ? 'text-green-600' : 'text-gray-400')">
                        <template x-if="isUploading">
                            <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        </template>
                        <template x-if="!isUploading && (isProcessingServer || isDone)">
                            <svg class="w-3.5 h-3.5 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        </template>
                        <span>1. อัปโหลดไฟล์</span>
                    </div>
                    <div class="h-0.5 flex-1 mx-3 bg-gray-200 rounded-full" :class="(isProcessingServer || isDone) ? 'bg-green-500' : ''"></div>
                    <div class="flex items-center gap-1.5" :class="isProcessingServer ? 'text-brand-600 font-bold' : (isDone ? 'text-green-600' : 'text-gray-400')">
                        <template x-if="isProcessingServer">
                            <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        </template>
                        <template x-if="isDone">
                            <svg class="w-3.5 h-3.5 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        </template>
                        <span>2. ประมวลผลแปลงไฟล์</span>
                    </div>
                </div>

                {{-- Status title & percentage --}}
                <div class="flex items-center justify-between pt-0.5">
                    <div class="flex items-center gap-2.5">
                        <svg class="w-5 h-5 text-brand-600 animate-spin flex-shrink-0" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-gray-800 font-semibold text-sm" x-text="progressTitle"></span>
                    </div>
                    <span class="text-brand-600 font-bold text-sm bg-white border border-brand-200 px-2.5 py-0.5 rounded-full shadow-xs" x-text="`${currentProgress}%`"></span>
                </div>

                {{-- Progress bar --}}
                <div class="h-3 bg-gray-200/80 rounded-full overflow-hidden p-0.5 border border-gray-300/60 shadow-inner">
                    <div class="h-full rounded-full transition-all duration-300 shadow-sm"
                         :class="isUploading ? 'bg-gradient-to-r from-sky-500 to-brand-600' : 'bg-gradient-to-r from-brand-600 to-indigo-600'"
                         :style="`width: ${currentProgress}%`"></div>
                </div>

                {{-- Subtitle info & cancel button --}}
                <div class="flex items-center justify-between text-xs text-gray-500 pt-0.5">
                    <span class="truncate pr-2" x-text="progressSubtitle"></span>
                    <button type="button" @click="clearAll()" class="text-gray-400 hover:text-red-500 transition-colors font-medium underline flex-shrink-0">
                        ยกเลิก / เริ่มใหม่
                    </button>
                </div>
            </div>

            {{-- Done state: download button --}}
            <div x-show="isDone" class="text-center space-y-4">
                <div class="w-16 h-16 bg-success-100 rounded-full flex items-center justify-center mx-auto">
                    <svg class="w-8 h-8 text-success-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                    </svg>
                </div>
                <p class="text-gray-800 font-semibold">ประมวลผลสำเร็จ! 🎉</p>
                <a :href="downloadUrl" :download="downloadFileName" target="_blank"
                   class="btn-primary px-8 py-3.5 rounded-xl inline-flex items-center gap-2 shadow-lg hover:shadow-xl transition-all cursor-pointer">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                    </svg>
                    ดาวน์โหลด <span class="text-white font-semibold" x-text="downloadFileName"></span>
                </a>
                <p class="text-xs text-gray-400">
                    ขนาดไฟล์: <span x-text="downloadFileSize"></span>
                    &nbsp;·&nbsp;ลิงก์หมดอายุใน 60 นาที
                </p>
                <button @click="clearAll()" class="text-sm text-gray-500 hover:text-gray-800 transition-colors block mx-auto">
                    แปลงไฟล์อีกครั้ง →
                </button>
            </div>

            {{-- Failed state --}}
            <div x-show="isFailed" class="text-center space-y-4">
                <div class="w-16 h-16 bg-error-500/20 rounded-full flex items-center justify-center mx-auto">
                    <svg class="w-8 h-8 text-error-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/>
                    </svg>
                </div>
                <p class="text-gray-800 font-semibold">เกิดข้อผิดพลาด</p>
                <p class="text-sm text-error-500 max-w-sm mx-auto" x-text="errorMessage"></p>
                <button @click="clearAll()" class="btn-ghost px-6 py-2.5 rounded-xl text-sm">ลองใหม่อีกครั้ง</button>
            </div>
        </div>

    </div>

    {{-- Info cards --}}
    <div class="grid sm:grid-cols-3 gap-4 mt-8">
        <div class="bg-white border border-gray-100 shadow-sm rounded-xl p-4 border border-gray-100 text-center">
            <svg class="w-6 h-6 text-success-500 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/>
            </svg>
            <p class="text-xs text-gray-600 font-medium">ไฟล์ปลอดภัย</p>
            <p class="text-xs text-gray-400">เข้ารหัส AES-256</p>
        </div>
        <div class="bg-white border border-gray-100 shadow-sm rounded-xl p-4 border border-gray-100 text-center">
            <svg class="w-6 h-6 text-brand-600 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
            </svg>
            <p class="text-xs text-gray-600 font-medium">ลบอัตโนมัติ</p>
            <p class="text-xs text-gray-400">ภายใน 48 ชั่วโมง</p>
        </div>
        <div class="bg-white border border-gray-100 shadow-sm rounded-xl p-4 border border-gray-100 text-center">
            <svg class="w-6 h-6 text-accent-600 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/>
            </svg>
            <p class="text-xs text-gray-600 font-medium">Cloud Processing</p>
            <p class="text-xs text-gray-400">รวดเร็ว ไม่ต้องติดตั้ง</p>
        </div>
    </div>
</div>
@endsection

@if($tool['slug'] === 'rotate-pdf')
@push('scripts')
<script src="{{ asset('vendor/pdfjs/pdf.min.js') }}"></script>
<script>
    if (window.pdfjsLib) {
        window.pdfjsLib.GlobalWorkerOptions.workerSrc = "{{ asset('vendor/pdfjs/pdf.worker.min.js') }}";
    }
</script>
@endpush
@endif
