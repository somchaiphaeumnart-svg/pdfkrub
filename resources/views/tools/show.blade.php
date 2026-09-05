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

                            {{-- Error notice if any --}}
                            <p x-show="pdfRenderError" class="text-xs text-amber-600 mt-2 text-center" x-text="pdfRenderError"></p>

                            {{-- Pagination if multi-page PDF --}}
                            <div x-show="pdfTotalPages > 1" class="flex items-center justify-center gap-3 mt-4 pt-3 border-t border-slate-200/70 w-full text-xs text-slate-600">
                                <button type="button"
                                        @click="prevPage()"
                                        :disabled="pdfCurrentPage <= 1 || isRenderingPdf"
                                        class="px-3 py-1 rounded-lg bg-white border border-slate-200 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed shadow-2xs font-medium transition-all cursor-pointer">
                                    ‹ หน้าก่อน
                                </button>
                                <span>
                                    หน้า <strong class="text-slate-800" x-text="pdfCurrentPage"></strong> จาก <span x-text="pdfTotalPages"></span>
                                </span>
                                <button type="button"
                                        @click="nextPage()"
                                        :disabled="pdfCurrentPage >= pdfTotalPages || isRenderingPdf"
                                        class="px-3 py-1 rounded-lg bg-white border border-slate-200 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed shadow-2xs font-medium transition-all cursor-pointer">
                                    หน้าถัดไป ›
                                </button>
                            </div>
                        </div>

                        <p class="text-center text-xs text-gray-400 mt-2.5">
                            * ทุกหน้าในไฟล์ PDF จะถูกหมุนตามมุมที่ท่านเลือก
                        </p>
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
                    :class="{ 'opacity-50 cursor-not-allowed': !hasFiles }"
                    @if($tool['premium'] && !(auth()->check() && auth()->user()->getActivePlan()->has_ocr)) disabled @endif>
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/>
                    </svg>
                    <span x-text="tool === 'rotate-pdf' && hasFiles ? `หมุน PDF (${rotationAngle}°) และบันทึก` : '{{ $tool['name_th'] ?? $tool['name'] }}'"></span>
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
