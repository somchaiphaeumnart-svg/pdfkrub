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
         x-data="fileUpload({ maxSizeMb: {{ auth()->check() && auth()->user()->getActivePlan()->max_file_size_mb >= 200 ? 200 : 10 }}, accept: '{{ $tool['accepts'] }}', maxFiles: {{ in_array($tool['slug'], ['merge-pdf', 'image-to-pdf']) ? 20 : 1 }} })"
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
                        ขนาดสูงสุด {{ auth()->check() && auth()->user()->getActivePlan()->max_file_size_mb >= 200 ? '200' : '10' }} MB
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
                    <button @click="$refs.fileInput.click()" class="text-xs text-brand-600 hover:text-brand-600 transition-colors mt-2 flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        เพิ่มไฟล์อีก
                    </button>
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
                    {{ $tool['name_th'] ?? $tool['name'] }}
                </button>
            </div>

            {{-- Processing state: progress --}}
            <div x-show="isProcessing" class="space-y-4">
                <div class="flex items-center justify-center gap-3">
                    <svg class="w-5 h-5 text-brand-600 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-gray-600 font-medium" x-text="jobStatus === 'queued' ? 'รอในคิว...' : 'กำลังประมวลผล...'"></span>
                </div>
                <div class="progress-bar h-2 rounded-full overflow-hidden">
                    <div class="progress-fill h-full rounded-full transition-all duration-500"
                         :style="`width: ${jobProgress || 10}%`"></div>
                </div>
                <p class="text-center text-xs text-gray-400">ไฟล์จะถูกลบอัตโนมัติหลังดาวน์โหลด</p>
            </div>

            {{-- Done state: download button --}}
            <div x-show="isDone" class="text-center space-y-4">
                <div class="w-16 h-16 bg-success-100 rounded-full flex items-center justify-center mx-auto">
                    <svg class="w-8 h-8 text-success-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                    </svg>
                </div>
                <p class="text-gray-800 font-semibold">ประมวลผลสำเร็จ! 🎉</p>
                <a href="#" :href="downloadUrl" :download="downloadFileName"
                   class="btn-primary px-8 py-3.5 rounded-xl inline-flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                    </svg>
                    ดาวน์โหลด <span class="text-white" x-text="downloadFileName"></span>
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
