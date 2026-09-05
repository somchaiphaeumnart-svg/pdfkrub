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
                    @if(in_array($tool['slug'], ['merge-pdf', 'image-to-pdf']))
                    {{-- Visual Reorder Editor for Merge PDF & Image to PDF --}}
                    <div>
                        <!-- Header Bar with Quick Action Controls -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 pb-3 border-b border-gray-100">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="text-gray-800 font-bold text-base" x-text="tool === 'image-to-pdf' ? 'จัดลำดับรูปภาพเพื่อสร้าง PDF' : 'จัดลำดับไฟล์ PDF'"></span>
                                    <span class="bg-brand-50 text-brand-700 text-xs font-semibold px-2.5 py-0.5 rounded-full" x-text="`${files.length} ${tool === 'image-to-pdf' ? 'รูป' : 'ไฟล์'} (${totalSize})`"></span>
                                </div>
                                <p class="text-xs text-gray-500 mt-0.5" x-text="tool === 'image-to-pdf' ? 'ลากสลับตำแหน่ง หรือใช้ปุ่ม ◀ ▶ เพื่อกำหนดลำดับหน้าที่ต้องการแปลงเป็น PDF' : 'ลากสลับตำแหน่ง หรือใช้ปุ่ม ◀ ▶ เพื่อกำหนดลำดับไฟล์ที่จะรวมจากหน้าแรกไปหน้าสุดท้าย'"></p>
                            </div>

                            <div class="flex items-center flex-wrap gap-1.5">
                                <button type="button" @click="sortFilesByName()" class="inline-flex items-center gap-1 text-xs text-gray-600 hover:text-brand-600 bg-gray-50 hover:bg-brand-50/60 border border-gray-200 px-2.5 py-1.5 rounded-lg transition-colors font-medium" title="เรียงตามชื่อไฟล์ ก-ฮ / A-Z">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4.5h14.25M3 9h9.75M3 13.5h5.25m5.25-.75L17.25 9m0 0L21 12.75M17.25 9v12"/></svg>
                                    <span>เรียงชื่อ ก-ฮ</span>
                                </button>
                                <button type="button" @click="reverseFilesOrder()" class="inline-flex items-center gap-1 text-xs text-gray-600 hover:text-brand-600 bg-gray-50 hover:bg-brand-50/60 border border-gray-200 px-2.5 py-1.5 rounded-lg transition-colors font-medium" title="กลับลำดับไฟล์ทั้งหมด">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/></svg>
                                    <span>กลับลำดับ</span>
                                </button>
                                <button type="button" @click="$refs.fileInput.click()" class="inline-flex items-center gap-1 text-xs text-brand-600 hover:text-brand-700 bg-brand-50 hover:bg-brand-100 border border-brand-200 px-2.5 py-1.5 rounded-lg transition-colors font-semibold">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                    <span x-text="tool === 'image-to-pdf' ? 'เพิ่มรูป' : 'เพิ่มไฟล์'"></span>
                                </button>
                                <button type="button" @click="clearAll()" class="inline-flex items-center gap-1 text-xs text-gray-400 hover:text-error-500 hover:bg-error-50 px-2 py-1.5 rounded-lg transition-colors" title="ล้างรายการทั้งหมด">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                </button>
                            </div>
                        </div>

                        @if($tool['slug'] === 'image-to-pdf')
                        <!-- Page Layout & Formatting Toolbar for Image to PDF -->
                        <div class="bg-slate-50/80 border border-slate-200/90 rounded-2xl p-3.5 sm:p-4 mb-4 shadow-xs">
                            <div class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2.5 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-brand-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75"/></svg>
                                <span>การตั้งค่าหน้ากระดาษ PDF</span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
                                <!-- Orientation -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1.5">การวางแนวหน้ากระดาษ</label>
                                    <div class="grid grid-cols-3 gap-1 bg-white p-1 rounded-xl border border-gray-200 shadow-xs">
                                        <button type="button" @click="imageOrientation = 'auto'"
                                            :class="imageOrientation === 'auto' ? 'bg-brand-600 text-white shadow-xs font-semibold' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'"
                                            class="px-2 py-1.5 rounded-lg text-xs transition-all text-center flex flex-col sm:flex-row items-center justify-center gap-1">
                                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z"/></svg>
                                            <span>อัตโนมัติ</span>
                                        </button>
                                        <button type="button" @click="imageOrientation = 'portrait'"
                                            :class="imageOrientation === 'portrait' ? 'bg-brand-600 text-white shadow-xs font-semibold' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'"
                                            class="px-2 py-1.5 rounded-lg text-xs transition-all text-center flex flex-col sm:flex-row items-center justify-center gap-1">
                                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><rect x="6" y="3" width="12" height="18" rx="2"/></svg>
                                            <span>แนวตั้ง</span>
                                        </button>
                                        <button type="button" @click="imageOrientation = 'landscape'"
                                            :class="imageOrientation === 'landscape' ? 'bg-brand-600 text-white shadow-xs font-semibold' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'"
                                            class="px-2 py-1.5 rounded-lg text-xs transition-all text-center flex flex-col sm:flex-row items-center justify-center gap-1">
                                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><rect x="3" y="6" width="18" height="12" rx="2"/></svg>
                                            <span>แนวนอน</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Page Size -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1.5">ขนาดหน้ากระดาษ</label>
                                    <div class="grid grid-cols-3 gap-1 bg-white p-1 rounded-xl border border-gray-200 shadow-xs">
                                        <button type="button" @click="imagePageSize = 'fit'"
                                            :class="imagePageSize === 'fit' ? 'bg-brand-600 text-white shadow-xs font-semibold' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'"
                                            class="px-2 py-1.5 rounded-lg text-xs transition-all text-center" title="ขนาดกระดาษพอดีกับขนาดรูปภาพ">
                                            พอดีรูป
                                        </button>
                                        <button type="button" @click="imagePageSize = 'a4'"
                                            :class="imagePageSize === 'a4' ? 'bg-brand-600 text-white shadow-xs font-semibold' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'"
                                            class="px-2 py-1.5 rounded-lg text-xs transition-all text-center font-medium" title="A4 มาตรฐาน (210 x 297 mm)">
                                            A4
                                        </button>
                                        <button type="button" @click="imagePageSize = 'letter'"
                                            :class="imagePageSize === 'letter' ? 'bg-brand-600 text-white shadow-xs font-semibold' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'"
                                            class="px-2 py-1.5 rounded-lg text-xs transition-all text-center" title="US Letter (8.5 x 11 นิ้ว)">
                                            Letter
                                        </button>
                                    </div>
                                </div>

                                <!-- Margin -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1.5">ระยะขอบกระดาษ (Margin)</label>
                                    <div class="grid grid-cols-3 gap-1 bg-white p-1 rounded-xl border border-gray-200 shadow-xs">
                                        <button type="button" @click="imageMargin = 'none'"
                                            :class="imageMargin === 'none' ? 'bg-brand-600 text-white shadow-xs font-semibold' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'"
                                            class="px-2 py-1.5 rounded-lg text-xs transition-all text-center">
                                            ไร้ขอบ
                                        </button>
                                        <button type="button" @click="imageMargin = 'small'"
                                            :class="imageMargin === 'small' ? 'bg-brand-600 text-white shadow-xs font-semibold' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'"
                                            class="px-2 py-1.5 rounded-lg text-xs transition-all text-center">
                                            ขอบเล็ก
                                        </button>
                                        <button type="button" @click="imageMargin = 'big'"
                                            :class="imageMargin === 'big' ? 'bg-brand-600 text-white shadow-xs font-semibold' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'"
                                            class="px-2 py-1.5 rounded-lg text-xs transition-all text-center">
                                            ขอบกว้าง
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Minimum files warning banner (only for Merge PDF) -->
                        <div x-show="tool === 'merge-pdf' && files.length < 2" class="bg-amber-50 border border-amber-200/80 rounded-xl p-3 mb-4 flex items-center gap-2.5 text-amber-800 text-xs sm:text-sm shadow-xs">
                            <svg class="w-5 h-5 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
                            <div class="flex-1">
                                <span class="font-bold">ต้องการอีกอย่างน้อย 1 ไฟล์:</span> การรวม PDF ต้องใช้ไฟล์อย่างน้อย 2 ไฟล์ขึ้นไป (กดปุ่ม "เพิ่มไฟล์" เพื่อเลือกไฟล์เพิ่ม)
                            </div>
                        </div>

                        <!-- Visual Grid of PDF / Image Cards -->
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 sm:gap-4 mb-3">
                            <template x-for="(f, index) in files" :key="f.id">
                                <div
                                    draggable="true"
                                    @dragstart.stop="onFileDragStart($event, index)"
                                    @dragover.prevent.stop="onFileDragOver($event, index)"
                                    @drop.prevent.stop="onFileDrop($event, index)"
                                    class="relative group bg-white border-2 rounded-2xl p-2.5 sm:p-3 transition-all duration-200 flex flex-col items-center select-none cursor-grab active:cursor-grabbing hover:shadow-md"
                                    :class="draggedFileIndex === index ? 'border-dashed border-brand-500 bg-brand-50/40 opacity-70 scale-95' : 'border-gray-200 hover:border-brand-300'">

                                    <!-- Top Order Badge & Delete Button -->
                                    <div class="w-full flex items-center justify-between mb-2">
                                        <div class="w-6 h-6 rounded-full bg-brand-600 text-white font-bold text-xs flex items-center justify-center shadow-xs" x-text="index + 1" title="ลำดับที่"></div>
                                        <button type="button" @click.stop="removeFile(f.id)" class="w-6 h-6 rounded-full bg-gray-100 hover:bg-error-50 text-gray-400 hover:text-error-500 flex items-center justify-center transition-colors" title="ลบไฟล์นี้">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>

                                    <!-- Thumbnail Preview Area -->
                                    <div class="w-full aspect-[3/4] bg-slate-50 rounded-xl overflow-hidden mb-2 relative flex items-center justify-center border border-gray-100 shadow-inner">
                                        <template x-if="imageThumbnailsCache[f.id] || mergeThumbnailsCache[f.id]">
                                            <img :src="imageThumbnailsCache[f.id] || mergeThumbnailsCache[f.id]" class="w-full h-full object-contain pointer-events-none rounded-lg" />
                                        </template>
                                        <template x-if="!imageThumbnailsCache[f.id] && !mergeThumbnailsCache[f.id]">
                                            <div class="flex flex-col items-center justify-center text-gray-400 p-2 text-center">
                                                <svg class="w-8 h-8 text-brand-300 animate-pulse mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                <span class="text-[10px] text-gray-400">โหลดตัวอย่าง...</span>
                                            </div>
                                        </template>
                                    </div>

                                    <!-- File Name & Size -->
                                    <div class="w-full text-center px-0.5">
                                        <p class="text-xs font-semibold text-gray-800 truncate" :title="f.name" x-text="f.name"></p>
                                        <p class="text-[11px] text-gray-400 mt-0.5" x-text="f.sizeFormatted"></p>
                                    </div>

                                    <!-- Move Left / Right Controls -->
                                    <div class="flex items-center justify-between w-full mt-2 pt-2 border-t border-gray-100" @click.stop>
                                        <button
                                            type="button"
                                            @click="moveFileUp(index)"
                                            :disabled="index === 0"
                                            :class="index === 0 ? 'opacity-25 cursor-not-allowed text-gray-300' : 'text-gray-500 hover:text-brand-600 hover:bg-brand-50'"
                                            class="p-1 rounded-lg transition-colors"
                                            title="เลื่อนไปข้างหน้า (ก่อนหน้า)">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                                        </button>

                                        <div class="text-[10px] text-gray-400 flex items-center gap-0.5 font-mono cursor-grab" title="ลากเพื่อสลับตำแหน่ง">
                                            <svg class="w-3.5 h-3.5 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path d="M7 2a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 2zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 8zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 14zm6-12a2 2 0 1 0 .001 4.001A2 2 0 0 0 13 2zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 13 8zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 13 14z"/></svg>
                                        </div>

                                        <button
                                            type="button"
                                            @click="moveFileDown(index)"
                                            :disabled="index === files.length - 1"
                                            :class="index === files.length - 1 ? 'opacity-25 cursor-not-allowed text-gray-300' : 'text-gray-500 hover:text-brand-600 hover:bg-brand-50'"
                                            class="p-1 rounded-lg transition-colors"
                                            title="เลื่อนไปข้างหลัง (ถัดไป)">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </template>

                            <!-- Add More Files Card -->
                            <div @click="$refs.fileInput.click()" class="border-2 border-dashed border-gray-200 hover:border-brand-500 hover:bg-brand-50/20 rounded-2xl p-4 flex flex-col items-center justify-center text-center cursor-pointer transition-all duration-200 min-h-[220px] group">
                                <div class="w-10 h-10 rounded-full bg-brand-50 group-hover:bg-brand-100 text-brand-600 flex items-center justify-center mb-2 transition-colors shadow-xs">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                </div>
                                <span class="text-xs font-semibold text-gray-700 group-hover:text-brand-600" x-text="tool === 'image-to-pdf' ? 'เพิ่มรูปภาพ' : 'เพิ่มไฟล์ PDF'"></span>
                                <span class="text-[10px] text-gray-400 mt-1" x-text="tool === 'image-to-pdf' ? 'คลิกเพื่อเลือกรูปเพิ่ม' : 'คลิกเพื่อเลือกไฟล์เพิ่ม'"></span>
                            </div>
                        </div>
                    </div>
                    @else
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
                    @if($tool['slug'] === 'pdf-to-word')
                    {{-- Visual Conversion Settings & Smart Scanner for PDF to Word --}}
                    <div class="mt-5 pt-5 border-t border-gray-100" @click.stop>

                        {{-- Smart Detection Badge & Quick Summary --}}
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 pb-3 border-b border-gray-100">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="text-gray-800 font-bold text-base">ตัวเลือกการแปลงเป็น Word</span>
                                    <template x-if="isAnalyzingWordPdf">
                                        <span class="inline-flex items-center gap-1.5 bg-slate-100 text-slate-600 text-xs px-2.5 py-0.5 rounded-full font-medium animate-pulse">
                                            <svg class="w-3 h-3 animate-spin text-brand-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                            กำลังวิเคราะห์เอกสาร...
                                        </span>
                                    </template>
                                    <template x-if="!isAnalyzingWordPdf && wordDetectedType === 'digital'">
                                        <span class="inline-flex items-center gap-1 bg-emerald-50 text-emerald-700 text-xs px-2.5 py-0.5 rounded-full font-semibold border border-emerald-200">
                                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                            เอกสารข้อความดิจิทัล (Digital Text)
                                        </span>
                                    </template>
                                    <template x-if="!isAnalyzingWordPdf && wordDetectedType === 'scanned'">
                                        <span class="inline-flex items-center gap-1 bg-amber-50 text-amber-800 text-xs px-2.5 py-0.5 rounded-full font-semibold border border-amber-200">
                                            <svg class="w-3.5 h-3.5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/></svg>
                                            เอกสารสแกน/รูปภาพ (Scanned PDF)
                                        </span>
                                    </template>
                                </div>
                                <p class="text-xs text-gray-500 mt-0.5">เลือกโหมดการแปลงที่เหมาะสมกับเอกสารเพื่อผลลัพธ์ที่ดีที่สุด</p>
                            </div>
                            <div class="text-xs text-slate-500 font-mono bg-slate-50 px-2.5 py-1 rounded-lg border border-slate-200 self-start sm:self-center" x-show="wordTotalPages > 0">
                                ทั้งหมด <strong class="text-slate-800" x-text="wordTotalPages"></strong> หน้า
                            </div>
                        </div>

                        {{-- Main Grid: Left Options & Mode Selector, Right Live Document Preview --}}
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">

                            {{-- Left Side: Engine & Layout Mode (7 cols) --}}
                            <div class="lg:col-span-7 space-y-4">
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">โหมดการแปลงเอกสาร (Conversion Engine)</label>

                                {{-- Mode Card 1: Standard High-Fidelity --}}
                                <div @click="wordMode = 'standard'"
                                     class="p-4 rounded-2xl border-2 transition-all cursor-pointer relative"
                                     :class="wordMode === 'standard' ? 'border-brand-600 bg-brand-50/40 shadow-xs ring-2 ring-brand-500/20' : 'border-gray-200 bg-white hover:border-brand-300 hover:bg-slate-50/50'">
                                    <div class="flex items-start gap-3.5">
                                        <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                                             :class="wordMode === 'standard' ? 'bg-brand-600 text-white' : 'bg-brand-50 text-brand-600'">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                            </svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 mb-1">
                                                <h4 class="text-sm font-bold text-gray-900">รักษา Layout & ฟอนต์ไทยดั้งเดิม</h4>
                                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">แนะนำ</span>
                                            </div>
                                            <p class="text-xs text-gray-600 leading-relaxed">
                                                แปลงแบบคงโครงสร้างหน้า ย่อหน้า ตาราง รูปภาพประกอบ สีตัวอักษร และฟอนต์ไทยเดิมให้ตรงกับต้นฉบับมากที่สุด
                                            </p>
                                        </div>
                                        <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center shrink-0 mt-0.5"
                                             :class="wordMode === 'standard' ? 'border-brand-600 bg-brand-600 text-white' : 'border-gray-300'">
                                            <template x-if="wordMode === 'standard'">
                                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                {{-- Mode Card 2: Thai OCR Mode --}}
                                <div @click="wordMode = 'ocr'"
                                     class="p-4 rounded-2xl border-2 transition-all cursor-pointer relative"
                                     :class="wordMode === 'ocr' ? 'border-brand-600 bg-brand-50/40 shadow-xs ring-2 ring-brand-500/20' : 'border-gray-200 bg-white hover:border-brand-300 hover:bg-slate-50/50'">
                                    <div class="flex items-start gap-3.5">
                                        <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                                             :class="wordMode === 'ocr' ? 'bg-amber-600 text-white' : 'bg-amber-50 text-amber-600'">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607zM10.5 7.5v6m3-3h-6" />
                                            </svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 mb-1">
                                                <h4 class="text-sm font-bold text-gray-900">สแกนข้อความ OCR ภาษาไทย</h4>
                                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-800">สำหรับเอกสารสแกน</span>
                                            </div>
                                            <p class="text-xs text-gray-600 leading-relaxed">
                                                อ่านข้อความภาษาไทยและอังกฤษจากภาพถ่ายหรือไฟล์สแกน แล้วแปลงเป็นข้อความ Word ที่แก้ไขได้จริง
                                            </p>
                                        </div>
                                        <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center shrink-0 mt-0.5"
                                             :class="wordMode === 'ocr' ? 'border-brand-600 bg-brand-600 text-white' : 'border-gray-300'">
                                            <template x-if="wordMode === 'ocr'">
                                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                {{-- Advanced Options Panel --}}
                                <div class="bg-slate-50/80 border border-slate-200/90 rounded-2xl p-4 space-y-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">ตัวเลือกเสริม (Additional Settings)</span>
                                    </div>

                                    {{-- Page Range Selection --}}
                                    <div class="bg-white p-3 rounded-xl border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
                                        <div>
                                            <span class="text-xs font-semibold text-gray-800">หน้าที่ต้องการแปลง:</span>
                                            <p class="text-[11px] text-gray-500">แปลงทั้งหมด หรือเลือกเฉพาะบางหน้า</p>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <button type="button" @click="wordPagesMode = 'all'"
                                                :class="wordPagesMode === 'all' ? 'bg-brand-600 text-white font-semibold shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                                class="px-2.5 py-1 rounded-lg text-xs transition-all cursor-pointer">
                                                ทุกหน้า
                                            </button>
                                            <button type="button" @click="wordPagesMode = 'custom'"
                                                :class="wordPagesMode === 'custom' ? 'bg-brand-600 text-white font-semibold shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                                class="px-2.5 py-1 rounded-lg text-xs transition-all cursor-pointer">
                                                กำหนดหน้า
                                            </button>
                                        </div>
                                    </div>
                                    <div x-show="wordPagesMode === 'custom'" class="pt-1">
                                        <input type="text"
                                               x-model="wordCustomPages"
                                               placeholder="ระบุเลขหน้า เช่น 1-3, 5, 8"
                                               class="w-full px-3 py-1.5 text-xs rounded-xl border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-brand-500 text-slate-700 bg-white font-mono">
                                    </div>

                                    {{-- Checkbox Toggles: Table & Images --}}
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1">
                                        <label class="flex items-center gap-2 bg-white px-3 py-2 rounded-xl border border-slate-200 cursor-pointer select-none">
                                            <input type="checkbox" x-model="wordDetectTables" class="rounded text-brand-600 focus:ring-brand-500 w-4 h-4">
                                            <span class="text-xs text-gray-700 font-medium">ตรวจจับและรักษาตาราง</span>
                                        </label>
                                        <label class="flex items-center gap-2 bg-white px-3 py-2 rounded-xl border border-slate-200 cursor-pointer select-none">
                                            <input type="checkbox" x-model="wordKeepImages" class="rounded text-brand-600 focus:ring-brand-500 w-4 h-4">
                                            <span class="text-xs text-gray-700 font-medium">คงรูปภาพประกอบเดิม</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            {{-- Right Side: Live Document First-Page Preview (5 cols) --}}
                            <div class="lg:col-span-5 flex flex-col items-center justify-center">
                                <div class="w-full bg-slate-100/90 rounded-2xl border border-slate-200/90 p-4 sm:p-5 flex flex-col items-center justify-center min-h-[340px]">
                                    <div class="w-full flex items-center justify-between mb-3 px-1">
                                        <span class="text-xs font-bold text-gray-700 flex items-center gap-1.5">
                                            <svg class="w-4 h-4 text-brand-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                            ตัวอย่างหน้าเอกสารจริง
                                        </span>
                                        <span class="text-[11px] text-gray-400 font-mono">หน้า 1</span>
                                    </div>

                                    {{-- Preview Card --}}
                                    <div class="relative max-w-[240px] sm:max-w-[260px] w-full aspect-[1/1.414] bg-white rounded-xl shadow-lg border border-slate-300 overflow-hidden flex items-center justify-center">
                                        <template x-if="wordPreviewPageUrl">
                                            <img :src="wordPreviewPageUrl" class="w-full h-full object-contain pointer-events-none select-none">
                                        </template>
                                        <template x-if="!wordPreviewPageUrl">
                                            <div class="flex flex-col items-center justify-center text-slate-400 p-4 text-center">
                                                <svg class="w-10 h-10 text-brand-300 animate-pulse mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                </svg>
                                                <span class="text-xs text-slate-500 font-medium">กำลังโหลดตัวอย่าง...</span>
                                            </div>
                                        </template>
                                    </div>

                                    <div class="mt-3 text-center">
                                        <span class="text-[11px] text-slate-500">ผลลัพธ์: เอกสาร <strong>Microsoft Word (.docx)</strong></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($tool['slug'] === 'pdf-to-excel')
                    {{-- Visual Table Extraction Settings & Smart Detector for PDF to Excel --}}
                    <div class="mt-5 pt-5 border-t border-gray-100" @click.stop>
                        {{-- Smart Detection Badge & Quick Summary --}}
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 pb-3 border-b border-gray-100">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="text-gray-800 font-bold text-base">ตัวเลือกการแปลงเป็น Excel (.xlsx)</span>
                                    <template x-if="isAnalyzingExcelPdf">
                                        <span class="inline-flex items-center gap-1.5 bg-slate-100 text-slate-600 text-xs px-2.5 py-0.5 rounded-full font-medium animate-pulse">
                                            <svg class="w-3 h-3 animate-spin text-emerald-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                            กำลังวิเคราะห์โครงสร้างตาราง...
                                        </span>
                                    </template>
                                    <template x-if="!isAnalyzingExcelPdf && excelDetectedTableType === 'lattice'">
                                        <span class="inline-flex items-center gap-1 bg-emerald-50 text-emerald-700 text-xs px-2.5 py-0.5 rounded-full font-semibold border border-emerald-200">
                                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                            ตรวจพบตารางมีเส้นขอบ (Grid Table)
                                        </span>
                                    </template>
                                    <template x-if="!isAnalyzingExcelPdf && excelDetectedTableType === 'stream'">
                                        <span class="inline-flex items-center gap-1 bg-amber-50 text-amber-800 text-xs px-2.5 py-0.5 rounded-full font-semibold border border-amber-200">
                                            <svg class="w-3.5 h-3.5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/></svg>
                                            ตรวจพบตารางเว้นวรรค/ไม่มีเส้นขอบ (Whitespace Columns)
                                        </span>
                                    </template>
                                </div>
                                <p class="text-xs text-gray-500 mt-0.5">เลือกรูปแบบการตรวจจับตาราง และการจัดระเบียบแผ่นงาน Excel ตามต้องการ</p>
                            </div>
                            <div class="text-xs text-slate-500 font-mono bg-slate-50 px-2.5 py-1 rounded-lg border border-slate-200 self-start sm:self-center" x-show="excelTotalPages > 0">
                                ทั้งหมด <strong class="text-slate-800" x-text="excelTotalPages"></strong> หน้า
                            </div>
                        </div>

                        {{-- Warning Banner for Corrupted Thai / CID Fonts --}}
                        <div x-show="excelDetectedCorruptedThai" x-transition class="mb-4 p-3.5 bg-amber-50 border border-amber-200 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs">
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0 mt-0.5">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold text-amber-900">ตรวจพบฟอนต์ใน PDF เข้ารหัสแบบพิเศษ (CID Font) หรือสลับลำดับตัวอักษร</h4>
                                    <p class="text-[11px] text-amber-700 mt-0.5">อาจทำให้แปลงเป็น Excel แล้วภาษาไทยเพี้ยนหรือตัวเลขกลับหลัง ระบบได้ปรับไปใช้ <strong>โหมดสแกนข้อความ OCR ภาษาไทย</strong> ให้ท่านโดยอัตโนมัติ</p>
                                </div>
                            </div>
                            <button type="button" @click="excelMode = 'ocr'" class="px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold rounded-xl shadow-xs transition-colors shrink-0 flex items-center gap-1.5 self-start sm:self-center">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                ยืนยันใช้โหมด OCR
                            </button>
                        </div>

                        {{-- Main Grid: Left Options (7 cols), Right Live Document Preview (5 cols) --}}
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">

                            {{-- Left Column: Settings & Modes --}}
                            <div class="lg:col-span-7 space-y-4">
                                {{-- 1. Conversion Engine: Standard vs OCR --}}
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">1. โหมดเครื่องยนต์แปลงข้อมูล (Conversion Engine)</label>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                        {{-- Mode: Standard --}}
                                        <div @click="excelMode = 'standard'"
                                             class="p-3 rounded-2xl border-2 transition-all cursor-pointer flex items-start gap-3"
                                             :class="excelMode === 'standard' ? 'border-emerald-600 bg-emerald-50/50 shadow-2xs ring-2 ring-emerald-500/20' : 'border-gray-200 bg-white hover:border-emerald-300'">
                                            <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 mt-0.5"
                                                 :class="excelMode === 'standard' ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-600'">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z"/></svg>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center justify-between mb-1">
                                                    <span class="text-xs font-bold text-gray-900">แปลงตารางปกติ</span>
                                                    <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-md bg-emerald-100 text-emerald-800">เร็ว & คมชัด</span>
                                                </div>
                                                <p class="text-[11px] text-gray-500 leading-relaxed">ดึงโครงสร้างตารางและตัวเลขโดยตรง เหมาะกับ PDF ดิจิทัลทั่วไป</p>
                                            </div>
                                        </div>

                                        {{-- Mode: Thai OCR --}}
                                        <div @click="excelMode = 'ocr'"
                                             class="p-3 rounded-2xl border-2 transition-all cursor-pointer flex items-start gap-3"
                                             :class="excelMode === 'ocr' ? 'border-amber-600 bg-amber-50/50 shadow-2xs ring-2 ring-amber-500/20' : 'border-gray-200 bg-white hover:border-amber-300'">
                                            <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 mt-0.5"
                                                 :class="excelMode === 'ocr' ? 'bg-amber-600 text-white' : 'bg-amber-50 text-amber-600'">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607zM10.5 7.5v6m3-3h-6" /></svg>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center justify-between mb-1">
                                                    <span class="text-xs font-bold text-gray-900">สแกน OCR ภาษาไทย</span>
                                                    <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-md bg-amber-100 text-amber-800">แก้ฟอนต์เพี้ยน</span>
                                                </div>
                                                <p class="text-[11px] text-gray-500 leading-relaxed">แก้ปัญหาภาษาไทยเพี้ยน/CID/ตัวเลขกลับหลัง โดยสแกนภาพจริงทีละช่อง</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- 2. Table Detection Strategy (Standard Mode) --}}
                                <div x-show="excelMode === 'standard'">
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">2. รูปแบบการตรวจจับตาราง (Table Detection)</label>
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                                        {{-- Mode: Auto --}}
                                        <div @click="excelTableMode = 'auto'"
                                             class="p-3 rounded-2xl border-2 transition-all cursor-pointer flex flex-col justify-between"
                                             :class="excelTableMode === 'auto' ? 'border-emerald-600 bg-emerald-50/50 shadow-2xs ring-2 ring-emerald-500/20' : 'border-gray-200 bg-white hover:border-emerald-300'">
                                            <div>
                                                <div class="flex items-center justify-between mb-1.5">
                                                    <span class="text-xs font-bold text-gray-900">ตรวจจับอัตโนมัติ</span>
                                                    <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-md bg-emerald-100 text-emerald-800">แนะนำ</span>
                                                </div>
                                                <p class="text-[11px] text-gray-500 leading-relaxed">วิเคราะห์เส้นตารางและระยะเว้นวรรคอัตโนมัติ</p>
                                            </div>
                                        </div>

                                        {{-- Mode: Lattice (Grid lines) --}}
                                        <div @click="excelTableMode = 'lattice'"
                                             class="p-3 rounded-2xl border-2 transition-all cursor-pointer flex flex-col justify-between"
                                             :class="excelTableMode === 'lattice' ? 'border-emerald-600 bg-emerald-50/50 shadow-2xs ring-2 ring-emerald-500/20' : 'border-gray-200 bg-white hover:border-emerald-300'">
                                            <div>
                                                <div class="flex items-center justify-between mb-1.5">
                                                    <span class="text-xs font-bold text-gray-900">ตารางมีเส้นขอบ</span>
                                                    <span class="text-[9px] font-medium text-slate-400">Lattice</span>
                                                </div>
                                                <p class="text-[11px] text-gray-500 leading-relaxed">ตรวจจับเส้นตารางจริง เหมาะกับใบเสร็จ งบการเงิน</p>
                                            </div>
                                        </div>

                                        {{-- Mode: Stream (Whitespace) --}}
                                        <div @click="excelTableMode = 'stream'"
                                             class="p-3 rounded-2xl border-2 transition-all cursor-pointer flex flex-col justify-between"
                                             :class="excelTableMode === 'stream' ? 'border-emerald-600 bg-emerald-50/50 shadow-2xs ring-2 ring-emerald-500/20' : 'border-gray-200 bg-white hover:border-emerald-300'">
                                            <div>
                                                <div class="flex items-center justify-between mb-1.5">
                                                    <span class="text-xs font-bold text-gray-900">ตารางไม่มีเส้น</span>
                                                    <span class="text-[9px] font-medium text-slate-400">Stream</span>
                                                </div>
                                                <p class="text-[11px] text-gray-500 leading-relaxed">วิเคราะห์ช่องว่างคอลัมน์ เหมาะกับสลิป รายงานสรุป</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- 3. Worksheet Layout Mode --}}
                                <div class="bg-slate-50/80 border border-slate-200/90 rounded-2xl p-3.5 sm:p-4">
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2.5">3. การจัดแผ่นงาน Excel (Worksheet Layout)</label>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                        <label @click="excelSheetMode = 'single'"
                                               class="flex items-start gap-2.5 p-3 rounded-xl bg-white border cursor-pointer transition-all"
                                               :class="excelSheetMode === 'single' ? 'border-emerald-600 ring-1 ring-emerald-500 bg-emerald-50/20' : 'border-gray-200 hover:border-gray-300'">
                                            <input type="radio" name="excel_sheet_mode" value="single" x-model="excelSheetMode" class="mt-0.5 text-emerald-600 focus:ring-emerald-500">
                                            <div>
                                                <span class="text-xs font-bold text-gray-900 block">รวมทุกหน้าในชีตเดียว (Single Sheet)</span>
                                                <span class="text-[11px] text-gray-500 leading-relaxed block mt-0.5">รวมแถวข้อมูลต่อเนื่อง เหมาะสำหรับทำ Pivot Table, VLOOKUP, หรือกรองข้อมูล</span>
                                            </div>
                                        </label>

                                        <label @click="excelSheetMode = 'multiple'"
                                               class="flex items-start gap-2.5 p-3 rounded-xl bg-white border cursor-pointer transition-all"
                                               :class="excelSheetMode === 'multiple' ? 'border-emerald-600 ring-1 ring-emerald-500 bg-emerald-50/20' : 'border-gray-200 hover:border-gray-300'">
                                            <input type="radio" name="excel_sheet_mode" value="multiple" x-model="excelSheetMode" class="mt-0.5 text-emerald-600 focus:ring-emerald-500">
                                            <div>
                                                <span class="text-xs font-bold text-gray-900 block">แยกหนึ่งหน้าต่อหนึ่งชีต (Multi-Sheet)</span>
                                                <span class="text-[11px] text-gray-500 leading-relaxed block mt-0.5">สร้างแท็บแยกตามหน้า เช่น Page 1, Page 2 รักษาหน้ากระดาษเดิม</span>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                {{-- 4. Page Selection --}}
                                <div class="bg-slate-50/80 border border-slate-200/90 rounded-2xl p-3.5 sm:p-4">
                                    <div class="flex items-center justify-between mb-2">
                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">4. หน้าที่ต้องการแปลง</label>
                                        <div class="flex items-center gap-1.5">
                                            <button type="button" @click="excelPagesMode = 'all'"
                                                    :class="excelPagesMode === 'all' ? 'bg-emerald-600 text-white font-semibold shadow-xs' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'"
                                                    class="px-2.5 py-1 rounded-lg text-xs transition-all cursor-pointer">
                                                ทุกหน้า
                                            </button>
                                            <button type="button" @click="excelPagesMode = 'custom'"
                                                    :class="excelPagesMode === 'custom' ? 'bg-emerald-600 text-white font-semibold shadow-xs' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'"
                                                    class="px-2.5 py-1 rounded-lg text-xs transition-all cursor-pointer">
                                                กำหนดหน้า
                                            </button>
                                        </div>
                                    </div>
                                    <div x-show="excelPagesMode === 'custom'" class="pt-1.5">
                                        <input type="text"
                                               x-model="excelCustomPages"
                                               placeholder="ระบุเลขหน้า เช่น 1-3, 5, 8"
                                               class="w-full px-3 py-1.5 text-xs rounded-xl border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-emerald-500 text-slate-700 bg-white font-mono">
                                        <p class="text-[11px] text-gray-400 mt-1">คั่นด้วยเครื่องหมายจุลภาค เช่น 1, 3-5, 8</p>
                                    </div>
                                </div>
                            </div>

                            {{-- Right Column: Live Document First-Page Preview (5 cols) --}}
                            <div class="lg:col-span-5 flex flex-col items-center justify-center">
                                <div class="w-full bg-slate-100/90 rounded-2xl border border-slate-200/90 p-4 sm:p-5 flex flex-col items-center justify-center min-h-[340px]">
                                    <div class="w-full flex items-center justify-between mb-3 px-1">
                                        <span class="text-xs font-bold text-gray-700 flex items-center gap-1.5">
                                            <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z"/></svg>
                                            ตัวอย่างหน้าเอกสารจริง
                                        </span>
                                        {{-- Pagination --}}
                                        <div class="flex items-center gap-1.5" x-show="excelTotalPages > 1">
                                            <button type="button"
                                                    @click="if (excelCurrentPage > 1) { excelCurrentPage--; loadExcelPdfPreview(); }"
                                                    :disabled="excelCurrentPage <= 1"
                                                    class="p-1 rounded bg-white border border-gray-200 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
                                            </button>
                                            <span class="text-[11px] text-gray-600 font-semibold" x-text="`หน้า ${excelCurrentPage || 1} / ${excelTotalPages || 1}`"></span>
                                            <button type="button"
                                                    @click="if (excelCurrentPage < excelTotalPages) { excelCurrentPage++; loadExcelPdfPreview(); }"
                                                    :disabled="excelCurrentPage >= excelTotalPages"
                                                    class="p-1 rounded bg-white border border-gray-200 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                                            </button>
                                        </div>
                                    </div>

                                    {{-- Preview Card --}}
                                    <div class="relative max-w-[240px] sm:max-w-[260px] w-full aspect-[1/1.414] bg-white rounded-xl shadow-lg border border-slate-300 overflow-hidden flex items-center justify-center">
                                        <template x-if="excelPreviewUrl">
                                            <img :src="excelPreviewUrl" class="w-full h-full object-contain pointer-events-none select-none">
                                        </template>
                                        <template x-if="!excelPreviewUrl">
                                            <div class="flex flex-col items-center justify-center text-slate-400 p-4 text-center">
                                                <svg class="w-10 h-10 text-emerald-300 animate-pulse mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z"/>
                                                </svg>
                                                <span class="text-xs text-slate-500 font-medium">กำลังโหลดตัวอย่าง...</span>
                                            </div>
                                        </template>

                                        {{-- Simulated Spreadsheet Grid Overlay at the bottom --}}
                                        <div class="absolute bottom-0 inset-x-0 bg-white/95 border-t border-emerald-200/80 px-2 py-1 flex items-center justify-between text-[10px] text-emerald-800 font-mono shadow-xs backdrop-blur-xs">
                                            <span class="font-bold flex items-center gap-1">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                <span x-text="excelSheetMode === 'single' ? 'Sheet1 (ตารางรวม)' : `Page ${excelCurrentPage || 1}`"></span>
                                            </span>
                                            <span class="text-slate-400">OpenXML .xlsx</span>
                                        </div>
                                    </div>

                                    <div class="mt-3 text-center">
                                        <span class="text-[11px] text-slate-500">ผลลัพธ์: ตาราง <strong>Microsoft Excel (.xlsx)</strong> พร้อมตัวเลขคำนวณได้</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                    @if($tool['slug'] === 'pdf-to-pptx')
                    {{-- Visual Presentation Studio & Settings for PDF to PowerPoint --}}
                    <div class="mt-5 pt-5 border-t border-gray-100" @click.stop>
                        {{-- Header Bar --}}
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 pb-3 border-b border-gray-100">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="text-gray-800 font-bold text-base">ตัวเลือกการแปลงเป็น PowerPoint (.pptx)</span>
                                    <template x-if="isAnalyzingPptxPdf">
                                        <span class="inline-flex items-center gap-1.5 bg-slate-100 text-slate-600 text-xs px-2.5 py-0.5 rounded-full font-medium animate-pulse">
                                            <svg class="w-3 h-3 animate-spin text-orange-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                            กำลังวิเคราะห์สไลด์...
                                        </span>
                                    </template>
                                    <template x-if="!isAnalyzingPptxPdf && pptxDetectedOrientation === 'landscape'">
                                        <span class="inline-flex items-center gap-1 bg-orange-50 text-orange-700 text-xs px-2.5 py-0.5 rounded-full font-semibold border border-orange-200">
                                            <svg class="w-3.5 h-3.5 text-orange-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                            สัดส่วนแนวนอน (เหมาะกับ 16:9 จอกว้าง)
                                        </span>
                                    </template>
                                    <template x-if="!isAnalyzingPptxPdf && pptxDetectedOrientation === 'portrait'">
                                        <span class="inline-flex items-center gap-1 bg-amber-50 text-amber-700 text-xs px-2.5 py-0.5 rounded-full font-semibold border border-amber-200">
                                            <svg class="w-3.5 h-3.5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/></svg>
                                            สัดส่วนแนวตั้ง (จัดกึ่งกลางสไลด์)
                                        </span>
                                    </template>
                                </div>
                                <p class="text-xs text-gray-500 mt-0.5">เลือกโหมดการสร้างสไลด์ สัดส่วนจอภาพ และระบุหน้าที่ต้องการแปลง</p>
                            </div>
                            <div class="text-xs text-slate-500 font-mono bg-slate-50 px-2.5 py-1 rounded-lg border border-slate-200 self-start sm:self-center" x-show="pptxTotalPages > 0">
                                ทั้งหมด <strong class="text-slate-800" x-text="pptxTotalPages"></strong> หน้า
                            </div>
                        </div>

                        {{-- Main Grid: Left Settings (7 cols), Right Live Slide Preview (5 cols) --}}
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">

                            {{-- Left Column: Settings & Controls --}}
                            <div class="lg:col-span-7 space-y-4">
                                {{-- 1. Conversion Engine Mode --}}
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">1. รูปแบบสไลด์ PowerPoint (Slide Presentation Mode)</label>
                                    <div class="space-y-2">
                                        {{-- Mode: Editable Vector Presentation --}}
                                        <div @click="pptxMode = 'editable'"
                                             class="p-3.5 rounded-2xl border-2 transition-all cursor-pointer flex items-start gap-3.5"
                                             :class="pptxMode === 'editable' ? 'border-orange-600 bg-orange-50/50 shadow-2xs ring-2 ring-orange-500/20' : 'border-gray-200 bg-white hover:border-orange-300'">
                                            <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 mt-0.5"
                                                 :class="pptxMode === 'editable' ? 'bg-orange-600 text-white' : 'bg-orange-50 text-orange-600'">
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center justify-between mb-1">
                                                    <span class="text-xs font-bold text-gray-900">แก้ไขได้เต็มรูปแบบ (Editable Presentation)</span>
                                                    <span class="text-[9px] font-bold px-2 py-0.5 rounded-md bg-orange-100 text-orange-800">แนะนำ</span>
                                                </div>
                                                <p class="text-[11px] text-gray-500 leading-relaxed">แยกกล่องข้อความ ตาราง รูปทรงเวกเตอร์ และรูปภาพ สามารถคลิกแก้ไข เปลี่ยนฟอนต์ และจัดวางใหม่ใน PowerPoint ได้อย่างอิสระ</p>
                                            </div>
                                        </div>

                                        {{-- Mode: High-Res Visual Slides --}}
                                        <div @click="pptxMode = 'image'"
                                             class="p-3.5 rounded-2xl border-2 transition-all cursor-pointer flex items-start gap-3.5"
                                             :class="pptxMode === 'image' ? 'border-orange-600 bg-orange-50/50 shadow-2xs ring-2 ring-orange-500/20' : 'border-gray-200 bg-white hover:border-orange-300'">
                                            <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 mt-0.5"
                                                 :class="pptxMode === 'image' ? 'bg-orange-600 text-white' : 'bg-orange-50 text-orange-600'">
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center justify-between mb-1">
                                                    <span class="text-xs font-bold text-gray-900">สไลด์รูปภาพคมชัดสูง (High-Res Visual Slides)</span>
                                                    <span class="text-[9px] font-medium text-slate-500 bg-slate-100 px-2 py-0.5 rounded-md">ตรงต้นฉบับ 100%</span>
                                                </div>
                                                <p class="text-[11px] text-gray-500 leading-relaxed">วางภาพหน้ากระดาษแบบความละเอียดสูงเต็มสไลด์ รักษาความคมชัดและการจัดหน้าเดิมแบบสมบูรณ์ เหมาะสำหรับฉายพรีเซนต์ทันที</p>
                                            </div>
                                        </div>

                                        {{-- Mode: Thai OCR Mode --}}
                                        <div @click="pptxMode = 'ocr'"
                                             class="p-3.5 rounded-2xl border-2 transition-all cursor-pointer flex items-start gap-3.5"
                                             :class="pptxMode === 'ocr' ? 'border-orange-600 bg-orange-50/50 shadow-2xs ring-2 ring-orange-500/20' : 'border-gray-200 bg-white hover:border-orange-300'">
                                            <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 mt-0.5"
                                                 :class="pptxMode === 'ocr' ? 'bg-orange-600 text-white' : 'bg-orange-50 text-orange-600'">
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607zM10.5 7.5v6m3-3h-6" /></svg>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center justify-between mb-1">
                                                    <span class="text-xs font-bold text-gray-900">สแกน OCR ภาษาไทย (Thai OCR Slides)</span>
                                                    <span class="text-[9px] font-medium text-amber-700 bg-amber-100 px-2 py-0.5 rounded-md">สำหรับไฟล์สแกน</span>
                                                </div>
                                                <p class="text-[11px] text-gray-500 leading-relaxed">สแกนข้อความภาษาไทยและอังกฤษจากภาพถ่ายหรือไฟล์สแกน แล้วแปลงเป็นกล่องข้อความที่แก้ไขและค้นหาได้</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- 2. Slide Aspect Ratio --}}
                                <div class="bg-slate-50/80 border border-slate-200/90 rounded-2xl p-3.5 sm:p-4">
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2.5">2. สัดส่วนหน้าจอพรีเซนต์ (Slide Aspect Ratio)</label>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                        <label @click="pptxRatio = '16:9'"
                                               class="flex items-start gap-2.5 p-3 rounded-xl bg-white border cursor-pointer transition-all"
                                               :class="pptxRatio === '16:9' ? 'border-orange-600 ring-1 ring-orange-500 bg-orange-50/20' : 'border-gray-200 hover:border-gray-300'">
                                            <input type="radio" name="pptx_ratio" value="16:9" x-model="pptxRatio" class="mt-0.5 text-orange-600 focus:ring-orange-500">
                                            <div>
                                                <div class="flex items-center gap-1.5">
                                                    <span class="text-xs font-bold text-gray-900">จอกว้าง 16:9 (Widescreen)</span>
                                                    <span class="text-[9px] font-bold px-1.5 py-0.2 rounded bg-orange-100 text-orange-800">มาตรฐาน</span>
                                                </div>
                                                <span class="text-[11px] text-gray-500 leading-relaxed block mt-0.5">เหมาะสำหรับจอคอมพิวเตอร์ จอทีวี และโปรเจกเตอร์ยุคใหม่</span>
                                            </div>
                                        </label>

                                        <label @click="pptxRatio = '4:3'"
                                               class="flex items-start gap-2.5 p-3 rounded-xl bg-white border cursor-pointer transition-all"
                                               :class="pptxRatio === '4:3' ? 'border-orange-600 ring-1 ring-orange-500 bg-orange-50/20' : 'border-gray-200 hover:border-gray-300'">
                                            <input type="radio" name="pptx_ratio" value="4:3" x-model="pptxRatio" class="mt-0.5 text-orange-600 focus:ring-orange-500">
                                            <div>
                                                <span class="text-xs font-bold text-gray-900 block">จอมาตรฐาน 4:3 (Standard)</span>
                                                <span class="text-[11px] text-gray-500 leading-relaxed block mt-0.5">เหมาะสำหรับเอกสารหน้ากระดาษ A4 หรือจอโปรเจกเตอร์ทั่วไป</span>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                {{-- 3. Page Selection --}}
                                <div class="bg-slate-50/80 border border-slate-200/90 rounded-2xl p-3.5 sm:p-4">
                                    <div class="flex items-center justify-between mb-2">
                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">3. หน้าที่ต้องการแปลงเป็นสไลด์</label>
                                        <div class="flex items-center gap-1.5">
                                            <button type="button" @click="pptxPagesMode = 'all'"
                                                    :class="pptxPagesMode === 'all' ? 'bg-orange-600 text-white font-semibold shadow-xs' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'"
                                                    class="px-2.5 py-1 rounded-lg text-xs transition-all cursor-pointer">
                                                ทุกหน้า
                                            </button>
                                            <button type="button" @click="pptxPagesMode = 'custom'"
                                                    :class="pptxPagesMode === 'custom' ? 'bg-orange-600 text-white font-semibold shadow-xs' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'"
                                                    class="px-2.5 py-1 rounded-lg text-xs transition-all cursor-pointer">
                                                กำหนดหน้า
                                            </button>
                                        </div>
                                    </div>
                                    <div x-show="pptxPagesMode === 'custom'" class="pt-1.5">
                                        <input type="text"
                                               x-model="pptxCustomPages"
                                               placeholder="ระบุเลขหน้า เช่น 1-3, 5, 8"
                                               class="w-full px-3 py-1.5 text-xs rounded-xl border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-orange-500 text-slate-700 bg-white font-mono">
                                        <p class="text-[11px] text-gray-400 mt-1">คั่นด้วยเครื่องหมายจุลภาค เช่น 1, 3-5, 8</p>
                                    </div>
                                </div>
                            </div>

                            {{-- Right Column: Live Slide Presentation Screen Mockup (5 cols) --}}
                            <div class="lg:col-span-5 flex flex-col items-center justify-center">
                                <div class="w-full bg-slate-900 text-white rounded-2xl border border-slate-800 p-4 sm:p-5 flex flex-col items-center justify-center min-h-[360px] shadow-lg">
                                    <div class="w-full flex items-center justify-between mb-3 px-1 text-slate-300">
                                        <span class="text-xs font-bold flex items-center gap-1.5">
                                            <svg class="w-4 h-4 text-orange-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5m.75-9 3-3 2.148 2.148A12.061 12.061 0 0 1 16.5 7.605"/></svg>
                                            ตัวอย่างสไลด์นำเสนอจริง
                                        </span>
                                        {{-- Pagination --}}
                                        <div class="flex items-center gap-1.5" x-show="pptxTotalPages > 1">
                                            <button type="button"
                                                    @click="if (pptxCurrentPage > 1) { pptxCurrentPage--; loadPptxPdfPreview(); }"
                                                    :disabled="pptxCurrentPage <= 1"
                                                    class="p-1 rounded bg-slate-800 text-slate-300 border border-slate-700 hover:bg-slate-700 disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
                                            </button>
                                            <span class="text-[11px] text-slate-300 font-semibold" x-text="`สไลด์ ${pptxCurrentPage || 1} / ${pptxTotalPages || 1}`"></span>
                                            <button type="button"
                                                    @click="if (pptxCurrentPage < pptxTotalPages) { pptxCurrentPage++; loadPptxPdfPreview(); }"
                                                    :disabled="pptxCurrentPage >= pptxTotalPages"
                                                    class="p-1 rounded bg-slate-800 text-slate-300 border border-slate-700 hover:bg-slate-700 disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                                            </button>
                                        </div>
                                    </div>

                                    {{-- Presentation Screen Mockup with Dynamic Aspect Ratio --}}
                                    <div class="relative w-full max-w-[320px] bg-black rounded-xl shadow-2xl border-2 border-slate-700 overflow-hidden flex items-center justify-center transition-all duration-300"
                                         :style="pptxRatio === '16:9' ? 'aspect-ratio: 16/9;' : 'aspect-ratio: 4/3;'">
                                        <template x-if="pptxPreviewUrl">
                                            <img :src="pptxPreviewUrl" class="w-full h-full object-contain pointer-events-none select-none bg-white">
                                        </template>
                                        <template x-if="!pptxPreviewUrl">
                                            <div class="flex flex-col items-center justify-center text-slate-500 p-4 text-center">
                                                <svg class="w-10 h-10 text-orange-400 animate-pulse mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5m.75-9 3-3 2.148 2.148A12.061 12.061 0 0 1 16.5 7.605"/>
                                                </svg>
                                                <span class="text-xs text-slate-400 font-medium">กำลังโหลดตัวอย่างสไลด์...</span>
                                            </div>
                                        </template>

                                        {{-- Simulated Slide Footer Overlay --}}
                                        <div class="absolute bottom-0 inset-x-0 bg-slate-900/90 border-t border-slate-700 px-2 py-0.5 flex items-center justify-between text-[9px] text-slate-400 font-mono backdrop-blur-xs">
                                            <span class="flex items-center gap-1">
                                                <span class="w-1.5 h-1.5 rounded-full bg-orange-500"></span>
                                                <span x-text="pptxRatio === '16:9' ? '16:9 Widescreen' : '4:3 Standard'"></span>
                                            </span>
                                            <span class="text-orange-400 font-semibold" x-text="pptxMode === 'editable' ? 'Editable Text' : (pptxMode === 'image' ? 'HD Picture' : 'OCR Text')"></span>
                                        </div>
                                    </div>

                                    <div class="mt-3 text-center">
                                        <span class="text-[11px] text-slate-400">ผลลัพธ์: สไลด์ <strong>Microsoft PowerPoint (.pptx)</strong></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                    @if(in_array($tool['slug'], ['pdf-to-jpg', 'pdf-to-png']))
                    {{-- Visual Page Selector & Quality Settings for PDF to Images --}}
                    <div class="mt-5 pt-5 border-t border-gray-100" @click.stop>

                        {{-- Quality & Extraction Toolbar --}}
                        <div class="bg-slate-50/90 border border-slate-200/90 rounded-2xl p-4 sm:p-5 shadow-xs mb-4">
                            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-4 pb-3 border-b border-gray-200/70">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-bold text-gray-800 flex items-center gap-2">
                                            <svg class="w-4 h-4 text-brand-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                            </svg>
                                            การตั้งค่าการแปลง PDF เป็นรูปภาพ
                                        </span>
                                        <span class="bg-brand-50 text-brand-700 text-xs font-semibold px-2.5 py-0.5 rounded-full" x-text="`${imgTotalPages} หน้าทั้งหมด`"></span>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-0.5">เลือกความละเอียด และคลิกเลือกหน้าที่ต้องการแปลงเป็นรูปภาพ</p>
                                </div>

                                {{-- DPI Selector --}}
                                <div class="flex items-center gap-2 bg-white p-1 rounded-xl border border-gray-200 shadow-xs self-start sm:self-center">
                                    <span class="text-xs text-gray-500 font-medium pl-2 pr-1">ความละเอียด:</span>
                                    <button type="button" @click="imgDpi = '150'"
                                        :class="imgDpi === '150' ? 'bg-brand-600 text-white font-bold shadow-xs' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'"
                                        class="px-2.5 py-1.5 rounded-lg text-xs transition-all cursor-pointer">
                                        150 DPI (มาตรฐาน)
                                    </button>
                                    <button type="button" @click="imgDpi = '300'"
                                        :class="imgDpi === '300' ? 'bg-brand-600 text-white font-bold shadow-xs' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'"
                                        class="px-2.5 py-1.5 rounded-lg text-xs transition-all cursor-pointer flex items-center gap-1" title="ความละเอียดสูง คมชัด เหมาะกับงานพิมพ์">
                                        <span>300 DPI (HQ)</span>
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                                    </button>
                                </div>
                            </div>

                            {{-- Mode & Selection Controls Bar --}}
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                {{-- Mode Selector: All vs Custom --}}
                                <div class="flex items-center gap-1.5 bg-white p-1 rounded-xl border border-gray-200 shadow-xs self-start">
                                    <button type="button" @click="imgPagesMode = 'all'; selectImgPages('all')"
                                        :class="imgPagesMode === 'all' ? 'bg-brand-600 text-white font-bold shadow-xs' : 'text-gray-600 hover:bg-gray-100'"
                                        class="px-3 py-1.5 rounded-lg text-xs transition-all cursor-pointer">
                                        แปลงทุกหน้า (<span x-text="imgTotalPages"></span> หน้า)
                                    </button>
                                    <button type="button" @click="imgPagesMode = 'custom'"
                                        :class="imgPagesMode === 'custom' ? 'bg-brand-600 text-white font-bold shadow-xs' : 'text-gray-600 hover:bg-gray-100'"
                                        class="px-3 py-1.5 rounded-lg text-xs transition-all cursor-pointer">
                                        เลือกเฉพาะบางหน้า
                                    </button>
                                </div>

                                {{-- Quick Select buttons --}}
                                <div class="flex items-center flex-wrap gap-1.5" x-show="imgPagesMode === 'custom'">
                                    <button type="button" @click="selectImgPages('all')" class="text-xs px-2.5 py-1 rounded-lg bg-white border border-gray-200 hover:bg-brand-50 hover:text-brand-600 text-gray-600 transition-colors font-medium">ทั้งหมด</button>
                                    <button type="button" @click="selectImgPages('none')" class="text-xs px-2.5 py-1 rounded-lg bg-white border border-gray-200 hover:bg-error-50 hover:text-error-500 text-gray-600 transition-colors font-medium">ล้าง</button>
                                    <button type="button" @click="selectImgPages('odd')" class="text-xs px-2.5 py-1 rounded-lg bg-white border border-gray-200 hover:bg-brand-50 hover:text-brand-600 text-gray-600 transition-colors font-medium">หน้าคี่</button>
                                    <button type="button" @click="selectImgPages('even')" class="text-xs px-2.5 py-1 rounded-lg bg-white border border-gray-200 hover:bg-brand-50 hover:text-brand-600 text-gray-600 transition-colors font-medium">หน้าคู่</button>
                                </div>

                                {{-- Range Input & Stats --}}
                                <div class="flex items-center gap-2" x-show="imgPagesMode === 'custom'">
                                    <input type="text"
                                           x-model="imgManualInput"
                                           @input="onImgManualInputChange()"
                                           placeholder="เช่น 1-3, 5, 8"
                                           class="text-xs px-3 py-1.5 rounded-xl border border-slate-300 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 font-mono w-36 bg-white">
                                    <span class="text-xs px-2.5 py-1.5 rounded-xl bg-brand-50 text-brand-700 border border-brand-200 font-bold whitespace-nowrap">
                                        เลือกแล้ว <span x-text="imgSelectedPages.length"></span>/<span x-text="imgTotalPages"></span> หน้า
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- Visual Pages Grid --}}
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 max-h-[480px] overflow-y-auto p-1 rounded-2xl">
                            <template x-for="page in imgPagesList" :key="page.pageNum">
                                <div
                                    @click="toggleImgPage(page.pageNum)"
                                    class="relative group rounded-2xl p-2.5 cursor-pointer border-2 transition-all duration-150 flex flex-col items-center select-none"
                                    :class="isImgPageSelected(page.pageNum)
                                        ? 'border-brand-500 bg-brand-50/60 ring-2 ring-brand-500/20 shadow-xs'
                                        : 'border-slate-200 bg-white opacity-60 hover:opacity-100 hover:border-brand-300 hover:bg-brand-50/10'">

                                    {{-- Top Checkbox Badge --}}
                                    <div class="w-full flex items-center justify-between mb-1.5 px-0.5">
                                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-md"
                                              :class="isImgPageSelected(page.pageNum) ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-600'"
                                              x-text="`หน้า ${page.pageNum}`"></span>
                                        <div class="w-4 h-4 rounded-full flex items-center justify-center transition-colors"
                                             :class="isImgPageSelected(page.pageNum) ? 'bg-brand-600 text-white' : 'border border-slate-300 bg-white'">
                                            <template x-if="isImgPageSelected(page.pageNum)">
                                                <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                            </template>
                                        </div>
                                    </div>

                                    {{-- Thumbnail Box --}}
                                    <div class="w-full aspect-[3/4] bg-slate-50 rounded-xl overflow-hidden relative flex items-center justify-center border border-slate-100 shadow-inner">
                                        <template x-if="page.dataUrl">
                                            <img :src="page.dataUrl" class="w-full h-full object-contain pointer-events-none rounded-lg" />
                                        </template>
                                        <template x-if="!page.dataUrl">
                                            <div class="flex flex-col items-center justify-center p-1 text-slate-300 text-center">
                                                <svg class="w-6 h-6 animate-pulse" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                    @endif

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

                    @if($tool['slug'] === 'protect-pdf')
                    {{-- Protect PDF Password Settings Card --}}
                    <div class="mt-5 pt-5 border-t border-gray-100 text-left" @click.stop>
                        <div class="bg-gradient-to-br from-slate-50 to-indigo-50/40 border border-slate-200/90 rounded-2xl p-5 sm:p-6 shadow-xs">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-10 h-10 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center border border-brand-100 shadow-2xs shrink-0">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-gray-900">กำหนดรหัสผ่านป้องกันเอกสาร PDF</h3>
                                    <p class="text-xs text-gray-500">เข้ารหัสไฟล์ด้วยมาตรฐาน AES-256 ปลอดภัยระดับสูงสุด ป้องกันการเปิดอ่านและแก้ไขเอกสาร</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                {{-- Password Field --}}
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                                        รหัสผ่านที่ต้องการตั้ง <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <input :type="showProtectPassword ? 'text' : 'password'"
                                               x-model="protectPassword"
                                               placeholder="ระบุรหัสผ่านของคุณ"
                                               class="w-full px-3.5 py-2.5 pr-10 text-sm rounded-xl border border-gray-300 focus:outline-hidden focus:ring-2 focus:ring-brand-500 focus:border-brand-500 bg-white text-gray-800 transition-all shadow-2xs">
                                        <button type="button"
                                                @click="showProtectPassword = !showProtectPassword"
                                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 cursor-pointer">
                                            <svg x-show="!showProtectPassword" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                            </svg>
                                            <svg x-show="showProtectPassword" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                {{-- Confirm Password Field --}}
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                                        ยืนยันรหัสผ่านอีกครั้ง <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <input :type="showProtectPasswordConfirm ? 'text' : 'password'"
                                               x-model="protectPasswordConfirm"
                                               placeholder="กรอกรหัสผ่านซ้ำอีกครั้ง"
                                               class="w-full px-3.5 py-2.5 pr-10 text-sm rounded-xl border border-gray-300 focus:outline-hidden focus:ring-2 focus:ring-brand-500 focus:border-brand-500 bg-white text-gray-800 transition-all shadow-2xs">
                                        <button type="button"
                                                @click="showProtectPasswordConfirm = !showProtectPasswordConfirm"
                                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 cursor-pointer">
                                            <svg x-show="!showProtectPasswordConfirm" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                            </svg>
                                            <svg x-show="showProtectPasswordConfirm" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {{-- Validation Notice / Tips --}}
                            <div class="mt-3.5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 text-xs">
                                <div>
                                    <span x-show="!protectPassword && !protectPasswordConfirm" class="text-gray-500">
                                        💡 กรุณากำหนดรหัสผ่านเพื่อใช้เปิดไฟล์เอกสาร PDF
                                    </span>
                                    <span x-show="protectPassword && !protectPasswordConfirm" class="text-amber-600 font-medium">
                                        ⚠️ กรุณากรอกยืนยันรหัสผ่านอีกครั้ง
                                    </span>
                                    <span x-show="protectPassword && protectPasswordConfirm && protectPassword !== protectPasswordConfirm" class="text-red-500 font-medium flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        รหัสผ่านทั้งสองช่องไม่ตรงกัน
                                    </span>
                                    <span x-show="protectPassword && protectPasswordConfirm && protectPassword === protectPasswordConfirm" class="text-green-600 font-semibold flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                        รหัสผ่านถูกต้องตรงกัน พร้อมเข้ารหัสไฟล์
                                    </span>
                                </div>
                                <span class="inline-flex items-center gap-1 text-[11px] text-indigo-700 bg-indigo-50 border border-indigo-200/80 px-2.5 py-1 rounded-md font-medium">
                                    <svg class="w-3 h-3 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                    เข้ารหัส AES-256 บิต
                                </span>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($tool['slug'] === 'unlock-pdf')
                    {{-- Unlock PDF Password Settings Card --}}
                    <div class="mt-5 pt-5 border-t border-gray-100 text-left" @click.stop>
                        <div class="bg-gradient-to-br from-slate-50 to-amber-50/40 border border-slate-200/90 rounded-2xl p-5 sm:p-6 shadow-xs space-y-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center border border-amber-200 shadow-2xs shrink-0">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 1 1 9 0v3.75M3.75 21.75h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H3.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-gray-900">ปลดล็อกรหัสผ่านเอกสาร PDF (Unlock PDF)</h3>
                                    <p class="text-xs text-gray-500">ถอดรหัสผ่านออกจากไฟล์ PDF เพื่อให้สามารถเปิดอ่าน พิมพ์ และแก้ไขได้โดยไม่ต้องใส่รหัสผ่านอีกต่อไป</p>
                                </div>
                            </div>

                            {{-- Notice if file is not encrypted --}}
                            <div x-show="isPdfEncrypted === false" class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-center gap-2">
                                <svg class="w-4 h-4 shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                <span>ไฟล์ PDF นี้ไม่มีรหัสผ่านป้องกันอยู่แล้ว คุณสามารถนำไปใช้งานได้ทันที</span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-12 gap-5 items-start">
                                {{-- Left: Password Input & Verification --}}
                                <div class="md:col-span-7 space-y-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                                            รหัสผ่านของไฟล์ PDF <span class="text-red-500">*</span>
                                        </label>
                                        <div class="relative">
                                            <input :type="showUnlockPassword ? 'text' : 'password'"
                                                   x-model="unlockPassword"
                                                   @input="unlockVerified = false; unlockCheckMessage = ''"
                                                   @keydown.enter="verifyUnlockPassword()"
                                                   placeholder="กรอกรหัสผ่านที่ใช้เปิดไฟล์นี้"
                                                   class="w-full px-3.5 py-2.5 pr-20 text-sm rounded-xl border border-gray-300 focus:outline-hidden focus:ring-2 focus:ring-amber-500 focus:border-amber-500 bg-white text-gray-800 transition-all shadow-2xs">
                                            
                                            <div class="absolute inset-y-0 right-0 flex items-center pr-2 gap-1">
                                                <button type="button"
                                                        @click="showUnlockPassword = !showUnlockPassword"
                                                        class="p-1.5 text-gray-400 hover:text-gray-600 cursor-pointer transition-colors"
                                                        title="แสดง/ซ่อนรหัสผ่าน">
                                                    <svg x-show="!showUnlockPassword" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                                    </svg>
                                                    <svg x-show="showUnlockPassword" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Quick Verify Button --}}
                                    <div class="flex items-center gap-2">
                                        <button type="button"
                                                @click="verifyUnlockPassword()"
                                                :disabled="!unlockPassword || isVerifyingUnlock"
                                                class="px-3.5 py-1.5 rounded-lg text-xs font-semibold bg-amber-100 hover:bg-amber-200 text-amber-900 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer transition-all flex items-center gap-1.5 shadow-2xs">
                                            <svg x-show="isVerifyingUnlock" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                            <span x-text="isVerifyingUnlock ? 'กำลังตรวจสอบ...' : 'ตรวจสอบรหัสผ่าน'"></span>
                                        </button>
                                        <span class="text-[11px] text-gray-400">กด Enter เพื่อตรวจสอบทันที</span>
                                    </div>

                                    {{-- Status / Error Message --}}
                                    <div x-show="unlockCheckMessage" class="text-xs transition-all">
                                        <p :class="unlockVerified ? 'text-emerald-700 bg-emerald-50 border-emerald-200' : 'text-red-700 bg-red-50 border-red-200'"
                                           class="px-3 py-2 rounded-xl border flex items-center gap-1.5 shadow-2xs font-medium"
                                           x-text="unlockCheckMessage">
                                        </p>
                                    </div>
                                </div>

                                {{-- Right: Live Preview Box --}}
                                <div class="md:col-span-5 flex flex-col items-center justify-center">
                                    <div class="w-full h-44 bg-slate-100 border border-slate-200 rounded-xl p-2 flex flex-col items-center justify-center overflow-hidden relative shadow-2xs">
                                        <template x-if="unlockPreviewUrl">
                                            <div class="w-full h-full flex flex-col items-center justify-center">
                                                <img :src="unlockPreviewUrl" class="max-h-full max-w-full object-contain bg-white rounded shadow-sm">
                                            </div>
                                        </template>
                                        <template x-if="!unlockPreviewUrl">
                                            <div class="flex flex-col items-center justify-center text-center p-3 text-slate-400">
                                                <svg class="w-8 h-8 text-slate-300 mb-1" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                                                <span class="text-[11px]">ตัวอย่างเอกสารจะปรากฏเมื่อระบุรหัสผ่านถูกต้อง</span>
                                            </div>
                                        </template>
                                    </div>
                                    <span class="text-[10px] text-slate-400 mt-1">หน้าแรกของเอกสาร</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($tool['slug'] === 'compress-pdf')
                    {{-- Compress PDF Quality Selection & Live Estimation --}}
                    <div class="mt-5 pt-5 border-t border-gray-100" @click.stop>
                        <div class="bg-gradient-to-b from-slate-50 to-gray-50/70 border border-slate-200/80 rounded-3xl p-5 sm:p-6 shadow-xs">
                            {{-- Header --}}
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-5">
                                <div>
                                    <h3 class="text-base font-bold text-gray-800 flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-xl bg-green-100 text-green-700 flex items-center justify-center font-bold">
                                            <svg class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 9V4.5M9 9H4.5M9 9 3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5m0-4.5 5.25 5.25" />
                                            </svg>
                                        </div>
                                        <span>เลือกระดับการบีบอัด PDF</span>
                                    </h3>
                                    <p class="text-xs text-gray-500 mt-1">เลือกระดับความสมดุลระหว่างการลดขนาดไฟล์และความคมชัดของเอกสารตามความต้องการ</p>
                                </div>
                                <span class="self-start sm:self-auto bg-green-50 text-green-700 border border-green-200 text-xs font-semibold px-3 py-1 rounded-full">
                                    Ghostscript Optimization
                                </span>
                            </div>

                            {{-- 3 Compression Level Cards Grid --}}
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5 sm:gap-4 mb-6">
                                {{-- Card 1: Extreme Compression --}}
                                <div
                                    @click="compressQuality = 'screen'"
                                    class="relative rounded-2xl p-4 sm:p-5 cursor-pointer border-2 transition-all duration-200 flex flex-col justify-between"
                                    :class="compressQuality === 'screen'
                                        ? 'bg-amber-50/70 border-amber-500 shadow-sm ring-2 ring-amber-500/20'
                                        : 'bg-white border-slate-200 hover:border-amber-300 hover:bg-amber-50/20'">
                                    
                                    <div>
                                        <div class="flex items-center justify-between mb-3">
                                            <span class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-800 border border-amber-200">
                                                ลดขนาด ~75%
                                            </span>
                                            <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center transition-colors"
                                                 :class="compressQuality === 'screen' ? 'border-amber-600 bg-amber-600' : 'border-slate-300 bg-white'">
                                                <div class="w-2 h-2 rounded-full bg-white" x-show="compressQuality === 'screen'"></div>
                                            </div>
                                        </div>
                                        <h4 class="text-sm font-bold text-gray-900 mb-1">บีบอัดสูงสุด (Extreme)</h4>
                                        <p class="text-xs text-amber-700 font-medium mb-2">72 DPI · ขนาดไฟล์เล็กที่สุด</p>
                                        <p class="text-xs text-gray-500 leading-relaxed">เหมาะสำหรับส่งอีเมล หรือระบบที่จำกัดขนาดไฟล์ไม่เกิน 1-2 MB ภาพและข้อความยังอ่านได้</p>
                                    </div>
                                    <div class="mt-4 pt-3 border-t border-amber-200/50 flex items-center justify-between text-[11px] text-gray-500">
                                        <span>คุณภาพ: <span class="font-medium text-gray-700">พอใช้</span></span>
                                        <span>ประหยัดพื้นที่: <span class="font-bold text-amber-600">สูงสุด</span></span>
                                    </div>
                                </div>

                                {{-- Card 2: Recommended Compression (Default) --}}
                                <div
                                    @click="compressQuality = 'ebook'"
                                    class="relative rounded-2xl p-4 sm:p-5 cursor-pointer border-2 transition-all duration-200 flex flex-col justify-between"
                                    :class="compressQuality === 'ebook'
                                        ? 'bg-brand-50/70 border-brand-600 shadow-sm ring-2 ring-brand-500/20'
                                        : 'bg-white border-slate-200 hover:border-brand-300 hover:bg-brand-50/20'">
                                    
                                    <div>
                                        <div class="flex items-center justify-between mb-3">
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-brand-600 text-white shadow-xs">
                                                    แนะนำ
                                                </span>
                                                <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-brand-100 text-brand-800 border border-brand-200">
                                                    ลดขนาด ~55%
                                                </span>
                                            </div>
                                            <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center transition-colors"
                                                 :class="compressQuality === 'ebook' ? 'border-brand-600 bg-brand-600' : 'border-slate-300 bg-white'">
                                                <div class="w-2 h-2 rounded-full bg-white" x-show="compressQuality === 'ebook'"></div>
                                            </div>
                                        </div>
                                        <h4 class="text-sm font-bold text-gray-900 mb-1">บีบอัดที่แนะนำ (Recommended)</h4>
                                        <p class="text-xs text-brand-700 font-medium mb-2">150 DPI · คุณภาพสมดุลดีเยี่ยม</p>
                                        <p class="text-xs text-gray-500 leading-relaxed">ลดขนาดไฟล์ได้อย่างมีนัยสำคัญ โดยยังคงความคมชัดของข้อความและรูปภาพมาตรฐาน</p>
                                    </div>
                                    <div class="mt-4 pt-3 border-t border-brand-200/50 flex items-center justify-between text-[11px] text-gray-500">
                                        <span>คุณภาพ: <span class="font-medium text-gray-700">ดีมาก</span></span>
                                        <span>ประหยัดพื้นที่: <span class="font-bold text-brand-600">สมดุล</span></span>
                                    </div>
                                </div>

                                {{-- Card 3: Less Compression / High Quality --}}
                                <div
                                    @click="compressQuality = 'printer'"
                                    class="relative rounded-2xl p-4 sm:p-5 cursor-pointer border-2 transition-all duration-200 flex flex-col justify-between"
                                    :class="compressQuality === 'printer'
                                        ? 'bg-emerald-50/70 border-emerald-600 shadow-sm ring-2 ring-emerald-500/20'
                                        : 'bg-white border-slate-200 hover:border-emerald-300 hover:bg-emerald-50/20'">
                                    
                                    <div>
                                        <div class="flex items-center justify-between mb-3">
                                            <span class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                ลดขนาด ~25%
                                            </span>
                                            <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center transition-colors"
                                                 :class="compressQuality === 'printer' ? 'border-emerald-600 bg-emerald-600' : 'border-slate-300 bg-white'">
                                                <div class="w-2 h-2 rounded-full bg-white" x-show="compressQuality === 'printer'"></div>
                                            </div>
                                        </div>
                                        <h4 class="text-sm font-bold text-gray-900 mb-1">บีบอัดน้อย (High Quality)</h4>
                                        <p class="text-xs text-emerald-700 font-medium mb-2">300 DPI · คมชัดสูงสำหรับงานพิมพ์</p>
                                        <p class="text-xs text-gray-500 leading-relaxed">เหมาะสำหรับเอกสารที่ต้องการนำไปพิมพ์สี กราฟิกรายละเอียดสูง หรือต้องการเก็บคุณภาพสูงสุด</p>
                                    </div>
                                    <div class="mt-4 pt-3 border-t border-emerald-200/50 flex items-center justify-between text-[11px] text-gray-500">
                                        <span>คุณภาพ: <span class="font-medium text-gray-700">สูงสุด</span></span>
                                        <span>ประหยัดพื้นที่: <span class="font-bold text-emerald-600">ปานกลาง</span></span>
                                    </div>
                                </div>
                            </div>

                            {{-- Bottom Comparison & Live Preview Bar --}}
                            <div class="bg-white border border-slate-200/80 rounded-2xl p-4 sm:p-5 flex flex-col lg:flex-row items-center justify-between gap-5">
                                {{-- Left: Size Comparison & Visualizer --}}
                                <div class="flex-1 w-full">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-xs font-bold text-gray-700">ประมาณการขนาดไฟล์หลังบีบอัด</span>
                                        <span class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-green-100 text-green-800" x-text="`ประหยัดพื้นที่ ~${compressSavedPercent}%`"></span>
                                    </div>

                                    <div class="flex items-center gap-3 sm:gap-4 my-3 bg-slate-50 p-3 rounded-xl border border-slate-100">
                                        <div>
                                            <p class="text-[11px] text-gray-400">ขนาดเดิม</p>
                                            <p class="text-sm sm:text-base font-bold text-gray-700" x-text="files[0]?.sizeFormatted || '-'"></p>
                                        </div>
                                        <div class="flex items-center text-brand-500 font-bold text-lg">
                                            ➔
                                        </div>
                                        <div>
                                            <p class="text-[11px] text-gray-400">ขนาดใหม่ (โดยประมาณ)</p>
                                            <p class="text-base sm:text-lg font-extrabold text-brand-600" x-text="compressEstimatedSizeFormatted"></p>
                                        </div>
                                    </div>

                                    {{-- Visual Progress Ratio Bar --}}
                                    <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden flex">
                                        <div class="bg-brand-600 h-2 transition-all duration-300 rounded-l-full" :style="`width: ${compressEstimatedRatio * 100}%`"></div>
                                        <div class="bg-emerald-400/70 h-2 transition-all duration-300 rounded-r-full" :style="`width: ${(1 - compressEstimatedRatio) * 100}%`"></div>
                                    </div>
                                    <div class="flex items-center justify-between text-[11px] text-gray-400 mt-1">
                                        <span x-text="`ขนาดไฟล์ใหม่ (${Math.round(compressEstimatedRatio * 100)}%)`"></span>
                                        <span class="text-emerald-600 font-medium" x-text="`พื้นที่ประหยัด (${compressSavedPercent}%)`"></span>
                                    </div>
                                </div>

                                {{-- Right: Document Thumbnail & Meta --}}
                                <div class="flex items-center gap-4 bg-slate-50 border border-slate-200/60 rounded-2xl p-3 sm:px-4 w-full lg:w-auto shrink-0 justify-center">
                                    <div class="w-14 sm:w-16 aspect-[3/4] bg-white rounded-lg border border-slate-200 overflow-hidden shadow-xs relative flex items-center justify-center shrink-0">
                                        <template x-if="compressThumb">
                                            <img :src="compressThumb" class="w-full h-full object-contain pointer-events-none" />
                                        </template>
                                        <template x-if="!compressThumb">
                                            <div class="flex flex-col items-center justify-center p-1 text-slate-300 text-center">
                                                <svg class="w-6 h-6 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            </div>
                                        </template>
                                    </div>
                                    <div class="text-left min-w-0">
                                        <p class="text-xs font-bold text-gray-800 truncate max-w-[180px]" x-text="files[0]?.name"></p>
                                        <p class="text-[11px] text-gray-500 mt-0.5" x-text="compressTotalPages ? `จำนวน ${compressTotalPages} หน้า` : 'กำลังอ่านเอกสาร...'"></p>
                                        <span class="inline-block mt-1 text-[10px] bg-slate-200/70 text-slate-600 px-2 py-0.5 rounded font-mono" x-text="`DPI: ${compressQuality === 'screen' ? '72' : (compressQuality === 'ebook' ? '150' : '300')}`"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($tool['slug'] === 'split-pdf')
                    {{-- Split PDF Visual Range & Page Selector --}}
                    <div class="mt-6 pt-6 border-t border-gray-100" @click.stop>
                        <div class="bg-slate-50/90 rounded-3xl border border-slate-200/90 p-5 sm:p-6 shadow-2xs">
                            {{-- Header & Mode Tabs --}}
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-4 mb-5 border-b border-slate-200">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-xl bg-pink-100 text-pink-600 flex items-center justify-center font-bold">
                                            <svg class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M7.848 8.25a3 3 0 1 0 0-4.5 3 3 0 0 0 0 4.5Zm0 0-3.5 3.5m0 0 3.5 3.5m-3.5-3.5h15.75M7.848 15.75a3 3 0 1 0 0 4.5 3 3 0 0 0 0-4.5Z" />
                                            </svg>
                                        </div>
                                        <h3 class="text-base font-bold text-gray-800">เครื่องมือแยกไฟล์ PDF (Split PDF)</h3>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">เลือกหน้าที่ต้องการดึงออกมา หรือแยกเอกสารทุกหน้าเป็นไฟล์เดี่ยว</p>
                                </div>

                                {{-- Mode Switcher Tabs --}}
                                <div class="flex items-center p-1 bg-slate-200/80 rounded-2xl shadow-inner text-xs font-semibold">
                                    <button type="button"
                                            @click="splitMode = 'range'"
                                            class="px-3.5 py-1.5 rounded-xl transition-all cursor-pointer flex items-center gap-1.5"
                                            :class="splitMode === 'range' ? 'bg-white text-gray-800 shadow-xs font-bold' : 'text-gray-600 hover:text-gray-900'">
                                        <svg class="w-3.5 h-3.5 text-brand-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
                                        <span>แยกตามช่วงหน้า</span>
                                    </button>
                                    <button type="button"
                                            @click="splitMode = 'all'"
                                            class="px-3.5 py-1.5 rounded-xl transition-all cursor-pointer flex items-center gap-1.5"
                                            :class="splitMode === 'all' ? 'bg-white text-gray-800 shadow-xs font-bold' : 'text-gray-600 hover:text-gray-900'">
                                        <svg class="w-3.5 h-3.5 text-pink-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6.878V6a2.25 2.25 0 0 1 2.25-2.25h7.5A2.25 2.25 0 0 1 18 6v.878m-12 0c.235-.083.487-.128.75-.128h10.5c.263 0 .515.045.75.128m-12 0A2.25 2.25 0 0 0 4.5 9v.878m13.5-3A2.25 2.25 0 0 1 19.5 9v.878m-15 0a2.246 2.246 0 0 0-.75.128m15.75 0c-.235-.083-.487-.128-.75-.128m-14.25 0A2.25 2.25 0 0 0 3 12v6a2.25 2.25 0 0 0 2.25 2.25h13.5A2.25 2.25 0 0 0 21 18v-6a2.25 2.25 0 0 0-1.5-2.122" /></svg>
                                        <span>แยกทุกหน้า</span>
                                    </button>
                                </div>
                            </div>

                            {{-- MODE 1: SPLIT BY RANGE --}}
                            <div x-show="splitMode === 'range'" class="space-y-4">
                                {{-- Toolbar: Quick Select, Range Input, & Merge Switch --}}
                                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 bg-white p-3.5 sm:p-4 rounded-2xl border border-slate-200/80 shadow-xs">
                                    {{-- Quick select buttons --}}
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="text-xs text-gray-400 mr-1">เลือกด่วน:</span>
                                        <button type="button" @click="selectAllPagesToSplit()" class="px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-100 hover:bg-slate-200 text-gray-700 transition-colors">
                                            เลือกทั้งหมด
                                        </button>
                                        <button type="button" @click="deselectAllPagesToSplit()" class="px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-100 hover:bg-slate-200 text-gray-700 transition-colors">
                                            ล้างที่เลือก
                                        </button>
                                        <button type="button" @click="selectOddPagesToSplit()" class="px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-100 hover:bg-slate-200 text-gray-700 transition-colors">
                                            หน้าคี่
                                        </button>
                                        <button type="button" @click="selectEvenPagesToSplit()" class="px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-100 hover:bg-slate-200 text-gray-700 transition-colors">
                                            หน้าคู่
                                        </button>
                                    </div>

                                    {{-- Range input & stats --}}
                                    <div class="flex flex-wrap items-center gap-2">
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-xs text-gray-500 font-medium">ช่วงหน้า:</span>
                                            <input type="text"
                                                   x-model="splitManualInput"
                                                   @input="onSplitManualInputChange()"
                                                   placeholder="เช่น 1-3, 5, 8"
                                                   class="text-xs px-3 py-1.5 rounded-xl border border-slate-300 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 font-mono w-40">
                                        </div>
                                        <span class="text-xs px-2.5 py-1 rounded-xl bg-brand-50 text-brand-700 border border-brand-200 font-bold">
                                            เลือกแล้ว <span x-text="selectedPagesToSplit.length"></span>/<span x-text="splitTotalPages"></span> หน้า
                                        </span>
                                    </div>
                                </div>

                                {{-- Output Mode Checkbox --}}
                                <div class="bg-brand-50/50 border border-brand-200/70 rounded-2xl p-3.5 sm:px-4 flex items-center justify-between">
                                    <label class="flex items-center gap-2.5 cursor-pointer select-none">
                                        <input type="checkbox" x-model="splitMergeExtracted" class="rounded text-brand-600 focus:ring-brand-500 w-4.5 h-4.5">
                                        <div>
                                            <span class="text-xs sm:text-sm font-bold text-gray-800">รวมหน้าที่เลือกทั้งหมดเป็น 1 ไฟล์ PDF เดียว</span>
                                            <p class="text-[11px] text-gray-500">หากไม่เลือก ระบบจะแยกแต่ละหน้าออกเป็นไฟล์เดี่ยวใน Zip</p>
                                        </div>
                                    </label>
                                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full"
                                          :class="splitMergeExtracted ? 'bg-brand-600 text-white' : 'bg-slate-200 text-slate-700'"
                                          x-text="splitMergeExtracted ? 'ไฟล์ .PDF เดียว' : 'ไฟล์ .ZIP รวมหน้า'">
                                    </span>
                                </div>

                                {{-- Visual Pages Grid --}}
                                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 max-h-[460px] overflow-y-auto p-1 rounded-2xl">
                                    <template x-for="page in splitPagesList" :key="page.pageNum">
                                        <div
                                            @click="togglePageToSplit(page.pageNum)"
                                            class="relative group rounded-2xl p-2 cursor-pointer border-2 transition-all duration-150 flex flex-col items-center select-none"
                                            :class="isPageSelectedToSplit(page.pageNum)
                                                ? 'border-brand-500 bg-brand-50/60 ring-2 ring-brand-500/20 shadow-xs'
                                                : 'border-slate-200 bg-white hover:border-brand-300 hover:bg-brand-50/10'">

                                            {{-- Top Checkbox Badge --}}
                                            <div class="w-full flex items-center justify-between mb-1.5 px-0.5">
                                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-md"
                                                      :class="isPageSelectedToSplit(page.pageNum) ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-600'"
                                                      x-text="`หน้า ${page.pageNum}`"></span>
                                                <div class="w-4 h-4 rounded-full flex items-center justify-center transition-colors"
                                                     :class="isPageSelectedToSplit(page.pageNum) ? 'bg-brand-600 text-white' : 'border border-slate-300 bg-white'">
                                                    <template x-if="isPageSelectedToSplit(page.pageNum)">
                                                        <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                                    </template>
                                                </div>
                                            </div>

                                            {{-- Thumbnail Box --}}
                                            <div class="w-full aspect-[3/4] bg-slate-50 rounded-xl overflow-hidden relative flex items-center justify-center border border-slate-100 shadow-inner">
                                                <template x-if="page.dataUrl">
                                                    <img :src="page.dataUrl" class="w-full h-full object-contain pointer-events-none rounded-lg" />
                                                </template>
                                                <template x-if="!page.dataUrl">
                                                    <div class="flex flex-col items-center justify-center p-1 text-slate-300 text-center">
                                                        <svg class="w-6 h-6 animate-pulse" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- MODE 2: SPLIT ALL PAGES --}}
                            <div x-show="splitMode === 'all'" class="space-y-4">
                                <div class="bg-white border border-slate-200/80 rounded-2xl p-6 text-center max-w-lg mx-auto shadow-xs">
                                    <div class="w-14 h-14 rounded-2xl bg-pink-100 text-pink-600 flex items-center justify-center mx-auto mb-3">
                                        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6.878V6a2.25 2.25 0 0 1 2.25-2.25h7.5A2.25 2.25 0 0 1 18 6v.878m-12 0c.235-.083.487-.128.75-.128h10.5c.263 0 .515.045.75.128m-12 0A2.25 2.25 0 0 0 4.5 9v.878m13.5-3A2.25 2.25 0 0 1 19.5 9v.878m-15 0a2.246 2.246 0 0 0-.75.128m15.75 0c-.235-.083-.487-.128-.75-.128m-14.25 0A2.25 2.25 0 0 0 3 12v6a2.25 2.25 0 0 0 2.25 2.25h13.5A2.25 2.25 0 0 0 21 18v-6a2.25 2.25 0 0 0-1.5-2.122" />
                                        </svg>
                                    </div>
                                    <h4 class="text-base font-bold text-gray-900 mb-1">แยกเอกสารออกเป็น 1 หน้าต่อ 1 ไฟล์</h4>
                                    <p class="text-xs text-gray-500 leading-relaxed max-w-md mx-auto">
                                        ระบบจะทำการแยกเอกสารทั้งเล่ม (ทั้งหมด <strong class="text-gray-800" x-text="splitTotalPages"></strong> หน้า) ออกเป็นไฟล์ PDF เดี่ยวจำนวน <strong class="text-gray-800" x-text="splitTotalPages"></strong> ไฟล์ และบรรจุลงในไฟล์ <strong>.ZIP</strong> เพื่อให้คุณดาวน์โหลดได้ในคลิกเดียว
                                    </p>
                                    <div class="mt-4 inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold">
                                        <span>ผลลัพธ์:</span>
                                        <span class="text-pink-600 font-mono">pages.zip (<span x-text="splitTotalPages"></span> PDFs)</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($tool['slug'] === 'page-numbers')
                    {{-- Interactive Page Numbers Visual Editor & Live Preview --}}
                    <div class="mt-5 pt-5 border-t border-gray-100" @click.stop>
                        {{-- Header / Quick Info --}}
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5 pb-3 border-b border-gray-100">
                            <div>
                                <h3 class="text-base font-bold text-gray-800 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-brand-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 8.25h15m-16.5 7.5h15m-1.8-13.5l-3.9 19.5m-2.1-19.5l-3.9 19.5" />
                                    </svg>
                                    กำหนดตำแหน่งและรูปแบบเลขหน้า
                                </h3>
                                <p class="text-xs text-gray-500 mt-0.5">เลือกตำแหน่ง รูปแบบตัวเลข และดูตัวอย่างจำลองบนหน้ากระดาษจริงได้ทันที</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-brand-50 text-brand-700 text-xs font-semibold border border-brand-200">
                                    <svg class="w-3.5 h-3.5 text-brand-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9z"/></svg>
                                    <span x-text="`ทั้งหมด ${pnTotalPages || 1} หน้า`"></span>
                                </span>
                            </div>
                        </div>

                        {{-- 2-Column Responsive Layout: Controls on Left, Live Preview on Right --}}
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                            {{-- LEFT COLUMN: Settings & Controls (7 Cols) --}}
                            <div class="lg:col-span-7 space-y-5">
                                {{-- 1. Position Selector (6-Preset Matrix Grid) --}}
                                <div class="bg-slate-50/80 border border-slate-200/90 rounded-2xl p-4">
                                    <div class="flex items-center justify-between mb-3">
                                        <span class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center gap-1.5">
                                            <svg class="w-4 h-4 text-brand-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                                            </svg>
                                            1. ตำแหน่งวางเลขหน้า
                                        </span>
                                        <span class="text-[11px] text-gray-400 font-medium">คลิกเพื่อเลือกตำแหน่งบนกระดาษ</span>
                                    </div>

                                    <div class="grid grid-cols-3 gap-2.5">
                                        {{-- Top Left --}}
                                        <button type="button" @click="pnPosition = 'top-left'"
                                                class="flex flex-col items-center justify-center p-3 rounded-xl border transition-all text-xs font-semibold cursor-pointer"
                                                :class="pnPosition === 'top-left' ? 'bg-brand-50 border-brand-500 text-brand-700 shadow-xs ring-2 ring-brand-500/20' : 'bg-white border-gray-200 text-gray-600 hover:border-brand-300 hover:bg-slate-50/50'">
                                            <div class="w-7 h-5 border border-dashed rounded mb-1.5 flex items-start justify-start p-0.5"
                                                 :class="pnPosition === 'top-left' ? 'border-brand-500 bg-brand-100/50' : 'border-gray-300 bg-gray-50'">
                                                <div class="w-1.5 h-1.5 rounded-full" :class="pnPosition === 'top-left' ? 'bg-brand-600' : 'bg-gray-400'"></div>
                                            </div>
                                            <span>บนซ้าย</span>
                                        </button>

                                        {{-- Top Center --}}
                                        <button type="button" @click="pnPosition = 'top-center'"
                                                class="flex flex-col items-center justify-center p-3 rounded-xl border transition-all text-xs font-semibold cursor-pointer"
                                                :class="pnPosition === 'top-center' ? 'bg-brand-50 border-brand-500 text-brand-700 shadow-xs ring-2 ring-brand-500/20' : 'bg-white border-gray-200 text-gray-600 hover:border-brand-300 hover:bg-slate-50/50'">
                                            <div class="w-7 h-5 border border-dashed rounded mb-1.5 flex items-start justify-center p-0.5"
                                                 :class="pnPosition === 'top-center' ? 'border-brand-500 bg-brand-100/50' : 'border-gray-300 bg-gray-50'">
                                                <div class="w-1.5 h-1.5 rounded-full" :class="pnPosition === 'top-center' ? 'bg-brand-600' : 'bg-gray-400'"></div>
                                            </div>
                                            <span>บนกลาง</span>
                                        </button>

                                        {{-- Top Right --}}
                                        <button type="button" @click="pnPosition = 'top-right'"
                                                class="flex flex-col items-center justify-center p-3 rounded-xl border transition-all text-xs font-semibold cursor-pointer"
                                                :class="pnPosition === 'top-right' ? 'bg-brand-50 border-brand-500 text-brand-700 shadow-xs ring-2 ring-brand-500/20' : 'bg-white border-gray-200 text-gray-600 hover:border-brand-300 hover:bg-slate-50/50'">
                                            <div class="w-7 h-5 border border-dashed rounded mb-1.5 flex items-start justify-end p-0.5"
                                                 :class="pnPosition === 'top-right' ? 'border-brand-500 bg-brand-100/50' : 'border-gray-300 bg-gray-50'">
                                                <div class="w-1.5 h-1.5 rounded-full" :class="pnPosition === 'top-right' ? 'bg-brand-600' : 'bg-gray-400'"></div>
                                            </div>
                                            <span>บนขวา</span>
                                        </button>

                                        {{-- Bottom Left --}}
                                        <button type="button" @click="pnPosition = 'bottom-left'"
                                                class="flex flex-col items-center justify-center p-3 rounded-xl border transition-all text-xs font-semibold cursor-pointer"
                                                :class="pnPosition === 'bottom-left' ? 'bg-brand-50 border-brand-500 text-brand-700 shadow-xs ring-2 ring-brand-500/20' : 'bg-white border-gray-200 text-gray-600 hover:border-brand-300 hover:bg-slate-50/50'">
                                            <div class="w-7 h-5 border border-dashed rounded mb-1.5 flex items-end justify-start p-0.5"
                                                 :class="pnPosition === 'bottom-left' ? 'border-brand-500 bg-brand-100/50' : 'border-gray-300 bg-gray-50'">
                                                <div class="w-1.5 h-1.5 rounded-full" :class="pnPosition === 'bottom-left' ? 'bg-brand-600' : 'bg-gray-400'"></div>
                                            </div>
                                            <span>ล่างซ้าย</span>
                                        </button>

                                        {{-- Bottom Center (Popular) --}}
                                        <button type="button" @click="pnPosition = 'bottom-center'"
                                                class="flex flex-col items-center justify-center p-3 rounded-xl border transition-all text-xs font-semibold cursor-pointer relative"
                                                :class="pnPosition === 'bottom-center' ? 'bg-brand-50 border-brand-500 text-brand-700 shadow-xs ring-2 ring-brand-500/20' : 'bg-white border-gray-200 text-gray-600 hover:border-brand-300 hover:bg-slate-50/50'">
                                            <span class="absolute -top-2 right-2 text-[9px] bg-emerald-500 text-white font-bold px-1.5 py-0.2 rounded-full shadow-2xs">แนะนำ</span>
                                            <div class="w-7 h-5 border border-dashed rounded mb-1.5 flex items-end justify-center p-0.5"
                                                 :class="pnPosition === 'bottom-center' ? 'border-brand-500 bg-brand-100/50' : 'border-gray-300 bg-gray-50'">
                                                <div class="w-1.5 h-1.5 rounded-full" :class="pnPosition === 'bottom-center' ? 'bg-brand-600' : 'bg-gray-400'"></div>
                                            </div>
                                            <span>ล่างกลาง</span>
                                        </button>

                                        {{-- Bottom Right --}}
                                        <button type="button" @click="pnPosition = 'bottom-right'"
                                                class="flex flex-col items-center justify-center p-3 rounded-xl border transition-all text-xs font-semibold cursor-pointer"
                                                :class="pnPosition === 'bottom-right' ? 'bg-brand-50 border-brand-500 text-brand-700 shadow-xs ring-2 ring-brand-500/20' : 'bg-white border-gray-200 text-gray-600 hover:border-brand-300 hover:bg-slate-50/50'">
                                            <div class="w-7 h-5 border border-dashed rounded mb-1.5 flex items-end justify-end p-0.5"
                                                 :class="pnPosition === 'bottom-right' ? 'border-brand-500 bg-brand-100/50' : 'border-gray-300 bg-gray-50'">
                                                <div class="w-1.5 h-1.5 rounded-full" :class="pnPosition === 'bottom-right' ? 'bg-brand-600' : 'bg-gray-400'"></div>
                                            </div>
                                            <span>ล่างขวา</span>
                                        </button>
                                    </div>
                                </div>

                                {{-- 2. Numbering Format Cards --}}
                                <div class="bg-slate-50/80 border border-slate-200/90 rounded-2xl p-4">
                                    <div class="flex items-center justify-between mb-3">
                                        <span class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center gap-1.5">
                                            <svg class="w-4 h-4 text-brand-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 14.25v2.25m3-4.5v4.5m3-6.75v6.75m3-9v9M6 20.25h12A2.25 2.25 0 0 0 20.25 18V6A2.25 2.25 0 0 0 18 3.75H6A2.25 2.25 0 0 0 3.75 6v12A2.25 2.25 0 0 0 6 20.25Z" />
                                            </svg>
                                            2. รูปแบบการแสดงผล
                                        </span>
                                    </div>

                                    <div class="grid grid-cols-2 gap-2.5">
                                        {{-- Format 1: n --}}
                                        <button type="button" @click="pnFormat = 'n'"
                                                class="flex items-center justify-between p-3 rounded-xl border text-left cursor-pointer transition-all"
                                                :class="pnFormat === 'n' ? 'bg-brand-50 border-brand-500 text-brand-800 shadow-xs ring-2 ring-brand-500/20' : 'bg-white border-gray-200 text-gray-700 hover:border-brand-300'">
                                            <div>
                                                <div class="font-bold text-sm font-mono">1, 2, 3</div>
                                                <div class="text-[11px] text-gray-500">ตัวเลขเดี่ยว</div>
                                            </div>
                                            <div class="w-4 h-4 rounded-full border flex items-center justify-center"
                                                 :class="pnFormat === 'n' ? 'border-brand-600 bg-brand-600' : 'border-gray-300'">
                                                <div class="w-1.5 h-1.5 rounded-full bg-white" x-show="pnFormat === 'n'"></div>
                                            </div>
                                        </button>

                                        {{-- Format 2: n-of-total --}}
                                        <button type="button" @click="pnFormat = 'n-of-total'"
                                                class="flex items-center justify-between p-3 rounded-xl border text-left cursor-pointer transition-all"
                                                :class="pnFormat === 'n-of-total' ? 'bg-brand-50 border-brand-500 text-brand-800 shadow-xs ring-2 ring-brand-500/20' : 'bg-white border-gray-200 text-gray-700 hover:border-brand-300'">
                                            <div>
                                                <div class="font-bold text-sm font-mono">1 / <span x-text="pnTotalPages || 10"></span></div>
                                                <div class="text-[11px] text-gray-500">เลขหน้า / ทั้งหมด</div>
                                            </div>
                                            <div class="w-4 h-4 rounded-full border flex items-center justify-center"
                                                 :class="pnFormat === 'n-of-total' ? 'border-brand-600 bg-brand-600' : 'border-gray-300'">
                                                <div class="w-1.5 h-1.5 rounded-full bg-white" x-show="pnFormat === 'n-of-total'"></div>
                                            </div>
                                        </button>

                                        {{-- Format 3: page-n --}}
                                        <button type="button" @click="pnFormat = 'page-n'"
                                                class="flex items-center justify-between p-3 rounded-xl border text-left cursor-pointer transition-all"
                                                :class="pnFormat === 'page-n' ? 'bg-brand-50 border-brand-500 text-brand-800 shadow-xs ring-2 ring-brand-500/20' : 'bg-white border-gray-200 text-gray-700 hover:border-brand-300'">
                                            <div>
                                                <div class="font-bold text-sm">หน้า 1</div>
                                                <div class="text-[11px] text-gray-500">ภาษาไทย</div>
                                            </div>
                                            <div class="w-4 h-4 rounded-full border flex items-center justify-center"
                                                 :class="pnFormat === 'page-n' ? 'border-brand-600 bg-brand-600' : 'border-gray-300'">
                                                <div class="w-1.5 h-1.5 rounded-full bg-white" x-show="pnFormat === 'page-n'"></div>
                                            </div>
                                        </button>

                                        {{-- Format 4: page-n-of-total --}}
                                        <button type="button" @click="pnFormat = 'page-n-of-total'"
                                                class="flex items-center justify-between p-3 rounded-xl border text-left cursor-pointer transition-all"
                                                :class="pnFormat === 'page-n-of-total' ? 'bg-brand-50 border-brand-500 text-brand-800 shadow-xs ring-2 ring-brand-500/20' : 'bg-white border-gray-200 text-gray-700 hover:border-brand-300'">
                                            <div>
                                                <div class="font-bold text-sm">หน้า 1 จาก <span x-text="pnTotalPages || 10"></span></div>
                                                <div class="text-[11px] text-gray-500">แบบทางการ</div>
                                            </div>
                                            <div class="w-4 h-4 rounded-full border flex items-center justify-center"
                                                 :class="pnFormat === 'page-n-of-total' ? 'border-brand-600 bg-brand-600' : 'border-gray-300'">
                                                <div class="w-1.5 h-1.5 rounded-full bg-white" x-show="pnFormat === 'page-n-of-total'"></div>
                                            </div>
                                        </button>
                                    </div>
                                </div>

                                {{-- 3. Options Bar: Skip Cover, Start Number, Font Size, Color --}}
                                <div class="bg-slate-50/80 border border-slate-200/90 rounded-2xl p-4 space-y-4">
                                    <span class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-brand-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />
                                        </svg>
                                        3. ตัวเลือกเพิ่มเติม
                                    </span>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        {{-- Skip First Page (Cover) --}}
                                        <label class="flex items-start gap-2.5 p-3 rounded-xl bg-white border border-gray-200 cursor-pointer hover:border-brand-300 transition-all">
                                            <input type="checkbox" x-model="pnSkipFirst" @change="loadPageNumbersPreview()" class="w-4 h-4 rounded text-brand-600 focus:ring-brand-500 border-gray-300 mt-0.5">
                                            <div>
                                                <div class="text-xs font-bold text-gray-800">เว้นหน้าแรก (หน้าปก)</div>
                                                <div class="text-[11px] text-gray-500 leading-snug">ไม่ใส่เลขหน้าบนปก และเริ่มนับที่หน้า 2</div>
                                            </div>
                                        </label>

                                        {{-- Start Number --}}
                                        <div class="p-3 rounded-xl bg-white border border-gray-200 flex items-center justify-between">
                                            <div>
                                                <div class="text-xs font-bold text-gray-800">เริ่มนับที่หน้า</div>
                                                <div class="text-[11px] text-gray-500">ค่าเริ่มต้นคือ 1</div>
                                            </div>
                                            <input type="number" min="1" max="9999" x-model.number="pnStartNum"
                                                   class="w-16 px-2.5 py-1 text-center font-bold text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
                                        {{-- Font Size --}}
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">ขนาดตัวอักษร:</label>
                                            <div class="grid grid-cols-3 gap-1.5">
                                                <button type="button" @click="pnFontSize = 9"
                                                        class="py-1.5 px-2 rounded-lg text-xs font-medium border transition-all cursor-pointer text-center"
                                                        :class="pnFontSize === 9 ? 'bg-brand-50 border-brand-500 text-brand-700 font-bold' : 'bg-white border-gray-200 text-gray-600 hover:border-gray-300'">
                                                    เล็ก (9pt)
                                                </button>
                                                <button type="button" @click="pnFontSize = 11"
                                                        class="py-1.5 px-2 rounded-lg text-xs font-medium border transition-all cursor-pointer text-center"
                                                        :class="pnFontSize === 11 ? 'bg-brand-50 border-brand-500 text-brand-700 font-bold' : 'bg-white border-gray-200 text-gray-600 hover:border-gray-300'">
                                                    กลาง (11pt)
                                                </button>
                                                <button type="button" @click="pnFontSize = 14"
                                                        class="py-1.5 px-2 rounded-lg text-xs font-medium border transition-all cursor-pointer text-center"
                                                        :class="pnFontSize === 14 ? 'bg-brand-50 border-brand-500 text-brand-700 font-bold' : 'bg-white border-gray-200 text-gray-600 hover:border-gray-300'">
                                                    ใหญ่ (14pt)
                                                </button>
                                            </div>
                                        </div>

                                        {{-- Font Color --}}
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">สีตัวเลข:</label>
                                            <div class="flex items-center gap-2">
                                                {{-- Charcoal/Grey --}}
                                                <button type="button" @click="pnColor = '#333333'" title="เทาเข้ม"
                                                        class="w-8 h-8 rounded-full border-2 transition-all cursor-pointer flex items-center justify-center"
                                                        :class="pnColor === '#333333' ? 'border-brand-600 scale-110 shadow-xs' : 'border-transparent hover:scale-105'"
                                                        style="background-color: #333333;">
                                                    <span x-show="pnColor === '#333333'" class="text-white text-xs">✓</span>
                                                </button>
                                                {{-- Black --}}
                                                <button type="button" @click="pnColor = '#000000'" title="ดำสนิท"
                                                        class="w-8 h-8 rounded-full border-2 transition-all cursor-pointer flex items-center justify-center"
                                                        :class="pnColor === '#000000' ? 'border-brand-600 scale-110 shadow-xs' : 'border-transparent hover:scale-105'"
                                                        style="background-color: #000000;">
                                                    <span x-show="pnColor === '#000000'" class="text-white text-xs">✓</span>
                                                </button>
                                                {{-- Navy --}}
                                                <button type="button" @click="pnColor = '#1e3a8a'" title="น้ำเงินกรมท่า"
                                                        class="w-8 h-8 rounded-full border-2 transition-all cursor-pointer flex items-center justify-center"
                                                        :class="pnColor === '#1e3a8a' ? 'border-brand-600 scale-110 shadow-xs' : 'border-transparent hover:scale-105'"
                                                        style="background-color: #1e3a8a;">
                                                    <span x-show="pnColor === '#1e3a8a'" class="text-white text-xs">✓</span>
                                                </button>
                                                {{-- Dark Red --}}
                                                <button type="button" @click="pnColor = '#991b1b'" title="แดงเข้ม"
                                                        class="w-8 h-8 rounded-full border-2 transition-all cursor-pointer flex items-center justify-center"
                                                        :class="pnColor === '#991b1b' ? 'border-brand-600 scale-110 shadow-xs' : 'border-transparent hover:scale-105'"
                                                        style="background-color: #991b1b;">
                                                    <span x-show="pnColor === '#991b1b'" class="text-white text-xs">✓</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- RIGHT COLUMN: Live Interactive Screen Preview (5 Cols) --}}
                            <div class="lg:col-span-5">
                                <div class="bg-slate-100/80 border border-slate-200/90 rounded-2xl p-4 flex flex-col items-center">
                                    <div class="w-full flex items-center justify-between mb-3 text-xs">
                                        <span class="font-bold text-gray-700 flex items-center gap-1.5">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                            ตัวอย่างหน้าเอกสารจริง
                                        </span>
                                        <span class="text-gray-500 text-[11px]" x-text="pnSkipFirst ? 'หน้า 2 (เว้นหน้าปก)' : 'หน้า 1'"></span>
                                    </div>

                                    {{-- Paper Sheet Container --}}
                                    <div class="w-full max-w-[280px] aspect-[1/1.414] bg-white rounded-lg shadow-md border border-gray-300 relative overflow-hidden select-none flex items-center justify-center">
                                        {{-- Rendered PDF background --}}
                                        <template x-if="pnPreviewPageUrl">
                                            <img :src="pnPreviewPageUrl" class="w-full h-full object-contain pointer-events-none" />
                                        </template>

                                        {{-- Loading skeleton when rendering --}}
                                        <template x-if="!pnPreviewPageUrl">
                                            <div class="flex flex-col items-center justify-center text-slate-300 p-4 text-center">
                                                <svg class="w-8 h-8 animate-spin text-brand-500 mb-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                                <span class="text-xs text-gray-400">กำลังแสดงตัวอย่างหน้า...</span>
                                            </div>
                                        </template>

                                        {{-- Live Interactive Number Badge Overlay --}}
                                        <div class="absolute px-2.5 py-0.5 rounded pointer-events-none transition-all duration-300 font-medium tracking-wide shadow-xs bg-white/80 backdrop-blur-xs border border-brand-300/50 ring-2 ring-brand-500/20"
                                             :class="pnPositionClasses"
                                             :style="{ color: pnColor, fontSize: (pnFontSize * 0.95) + 'px' }">
                                            <span x-text="pnFormattedPreviewText"></span>
                                        </div>
                                    </div>

                                    <p class="text-[11px] text-gray-400 text-center mt-3 leading-relaxed">
                                        ตัวอย่างแสดงตำแหน่งบนกระดาษแบบ Real-time<br>
                                        เลขหน้าจะถูกประทับลงในเอกสารทุกหน้าตามตำแหน่งที่เลือก
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($tool['slug'] === 'crop-pdf')
                    {{-- Interactive Crop PDF Visual Editor & Live Preview --}}
                    <div class="mt-5 pt-5 border-t border-gray-100" @click.stop>
                        {{-- Header / Quick Info --}}
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5 pb-3 border-b border-gray-100">
                            <div>
                                <h3 class="text-base font-bold text-gray-800 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-sky-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.864 4.243A7.5 7.5 0 0 1 19.5 10.5c0 2.92-.556 5.709-1.568 8.268M5.742 6.364A7.465 7.465 0 0 0 4.5 10.5a7.5 7.5 0 1 0 15 0c0-.52-.053-1.028-.154-1.518M6.75 9.75v.008M17.25 9.75v.008M9 13.5h6m-3-3v6" />
                                    </svg>
                                    ครอบตัดและปรับแต่งขอบกระดาษ PDF
                                </h3>
                                <p class="text-xs text-gray-500 mt-0.5">ตัดขอบขาว ลบเงาดำของเครื่องสแกน หรือกำหนดระยะตัดขอบด้วยตนเองพร้อมดูตัวอย่างสด</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-sky-50 text-sky-700 text-xs font-semibold border border-sky-200">
                                    <svg class="w-3.5 h-3.5 text-sky-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9z"/></svg>
                                    <span x-text="`ทั้งหมด ${cropTotalPages || 1} หน้า`"></span>
                                </span>
                            </div>
                        </div>

                        {{-- 2-Column Responsive Layout --}}
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                            {{-- LEFT COLUMN: Controls & Margin Sliders (7 Cols) --}}
                            <div class="lg:col-span-7 space-y-5">
                                {{-- 1. Preset Selection Cards --}}
                                <div class="bg-slate-50/80 border border-slate-200/90 rounded-2xl p-4">
                                    <span class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center gap-1.5 mb-3">
                                        <svg class="w-4 h-4 text-sky-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z" />
                                        </svg>
                                        1. รูปแบบการครอบตัด
                                    </span>

                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                        {{-- Preset 1: Custom --}}
                                        <button type="button" @click="setCropPreset('custom')"
                                                class="flex flex-col items-center p-3.5 rounded-xl border text-center transition-all cursor-pointer"
                                                :class="cropMode === 'custom' ? 'bg-sky-50 border-sky-500 text-sky-900 shadow-xs ring-2 ring-sky-500/20' : 'bg-white border-gray-200 text-gray-700 hover:border-sky-300'">
                                            <div class="w-8 h-8 rounded-lg bg-sky-100 text-sky-700 flex items-center justify-center mb-2">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75"/></svg>
                                            </div>
                                            <span class="font-bold text-xs">กำหนดขอบเอง</span>
                                            <span class="text-[10px] text-gray-500 mt-0.5">ปรับระยะ 4 ด้านอิสระ</span>
                                        </button>

                                        {{-- Preset 2: Trim Scanner Shadows --}}
                                        <button type="button" @click="setCropPreset('trim-scanner')"
                                                class="flex flex-col items-center p-3.5 rounded-xl border text-center transition-all cursor-pointer relative"
                                                :class="cropMode === 'trim-scanner' ? 'bg-sky-50 border-sky-500 text-sky-900 shadow-xs ring-2 ring-sky-500/20' : 'bg-white border-gray-200 text-gray-700 hover:border-sky-300'">
                                            <span class="absolute -top-2 right-2 text-[9px] bg-emerald-500 text-white font-bold px-1.5 py-0.2 rounded-full shadow-2xs">ยอดนิยม</span>
                                            <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center mb-2">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9.75v6.75m0 0-3-3m3 3 3-3m-8.25 6h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Z"/></svg>
                                            </div>
                                            <span class="font-bold text-xs">ลบเงาดำสแกน</span>
                                            <span class="text-[10px] text-gray-500 mt-0.5">ตัดขอบ 4% ลบเงาสแกน</span>
                                        </button>

                                        {{-- Preset 3: Auto-Trim Margins --}}
                                        <button type="button" @click="setCropPreset('auto-margins')"
                                                class="flex flex-col items-center p-3.5 rounded-xl border text-center transition-all cursor-pointer"
                                                :class="cropMode === 'auto-margins' ? 'bg-sky-50 border-sky-500 text-sky-900 shadow-xs ring-2 ring-sky-500/20' : 'bg-white border-gray-200 text-gray-700 hover:border-sky-300'">
                                            <div class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center mb-2">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15"/></svg>
                                            </div>
                                            <span class="font-bold text-xs">ตัดขอบขาวออโต้</span>
                                            <span class="text-[10px] text-gray-500 mt-0.5">ตรวจจับชิดข้อความจริง</span>
                                        </button>
                                    </div>
                                </div>

                                {{-- 2. Four-Way Margin Sliders (Custom & Trim-Scanner) --}}
                                <div x-show="cropMode !== 'auto-margins'" class="bg-slate-50/80 border border-slate-200/90 rounded-2xl p-4 space-y-4">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center gap-1.5">
                                            <svg class="w-4 h-4 text-sky-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12" />
                                            </svg>
                                            2. ปรับระยะขอบ 4 ทิศทาง (%)
                                        </span>
                                        <div class="flex items-center gap-1">
                                            <button type="button" @click="cropTop = 4; cropBottom = 4; cropLeft = 4; cropRight = 4;"
                                                    class="text-[11px] px-2 py-0.5 rounded bg-white border border-gray-200 hover:border-sky-300 text-gray-600 font-medium">4%</button>
                                            <button type="button" @click="cropTop = 8; cropBottom = 8; cropLeft = 8; cropRight = 8;"
                                                    class="text-[11px] px-2 py-0.5 rounded bg-white border border-gray-200 hover:border-sky-300 text-gray-600 font-medium">8%</button>
                                            <button type="button" @click="cropTop = 15; cropBottom = 15; cropLeft = 15; cropRight = 15;"
                                                    class="text-[11px] px-2 py-0.5 rounded bg-white border border-gray-200 hover:border-sky-300 text-gray-600 font-medium">15%</button>
                                            <button type="button" @click="resetCropMargins()"
                                                    class="text-[11px] px-2 py-0.5 rounded bg-white border border-gray-200 hover:text-red-600 text-gray-400">รีเซ็ต</button>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        {{-- Top Margin --}}
                                        <div class="bg-white p-3 rounded-xl border border-gray-200">
                                            <div class="flex items-center justify-between text-xs font-bold text-gray-700 mb-1.5">
                                                <span>ขอบบน (Top)</span>
                                                <span class="text-sky-600 font-mono" x-text="`${cropTop}%`"></span>
                                            </div>
                                            <input type="range" min="0" max="40" step="1" x-model.number="cropTop"
                                                   class="w-full h-1.5 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-sky-600">
                                        </div>

                                        {{-- Bottom Margin --}}
                                        <div class="bg-white p-3 rounded-xl border border-gray-200">
                                            <div class="flex items-center justify-between text-xs font-bold text-gray-700 mb-1.5">
                                                <span>ขอบล่าง (Bottom)</span>
                                                <span class="text-sky-600 font-mono" x-text="`${cropBottom}%`"></span>
                                            </div>
                                            <input type="range" min="0" max="40" step="1" x-model.number="cropBottom"
                                                   class="w-full h-1.5 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-sky-600">
                                        </div>

                                        {{-- Left Margin --}}
                                        <div class="bg-white p-3 rounded-xl border border-gray-200">
                                            <div class="flex items-center justify-between text-xs font-bold text-gray-700 mb-1.5">
                                                <span>ขอบซ้าย (Left)</span>
                                                <span class="text-sky-600 font-mono" x-text="`${cropLeft}%`"></span>
                                            </div>
                                            <input type="range" min="0" max="40" step="1" x-model.number="cropLeft"
                                                   class="w-full h-1.5 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-sky-600">
                                        </div>

                                        {{-- Right Margin --}}
                                        <div class="bg-white p-3 rounded-xl border border-gray-200">
                                            <div class="flex items-center justify-between text-xs font-bold text-gray-700 mb-1.5">
                                                <span>ขอบขวา (Right)</span>
                                                <span class="text-sky-600 font-mono" x-text="`${cropRight}%`"></span>
                                            </div>
                                            <input type="range" min="0" max="40" step="1" x-model.number="cropRight"
                                                   class="w-full h-1.5 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-sky-600">
                                        </div>
                                    </div>
                                </div>

                                {{-- Auto Margin notice --}}
                                <div x-show="cropMode === 'auto-margins'" class="p-4 rounded-2xl bg-indigo-50/70 border border-indigo-200/80 text-xs text-indigo-800 leading-relaxed">
                                    <div class="font-bold mb-1 flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/></svg>
                                        ระบบตรวจจับเนื้อหาอัตโนมัติ (Smart Bounding Box)
                                    </div>
                                    ระบบจะวิเคราะห์ตำแหน่งข้อความ เส้นวาด และรูปภาพจริงบนแต่ละหน้า แล้วครอบตัดขอบขาวส่วนเกินออกโดยคงระยะปลอดภัยไว้ 6 มม. รอบเนื้อหา
                                </div>

                                {{-- 3. Target Pages Options --}}
                                <div class="bg-slate-50/80 border border-slate-200/90 rounded-2xl p-4">
                                    <span class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center gap-1.5 mb-3">
                                        <svg class="w-4 h-4 text-sky-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75" />
                                        </svg>
                                        3. นำไปใช้กับหน้า
                                    </span>

                                    <div class="space-y-2.5">
                                        <label class="flex items-center gap-2.5 p-2.5 rounded-xl bg-white border border-gray-200 cursor-pointer hover:border-sky-300">
                                            <input type="radio" name="crop_pages_mode" value="all" x-model="cropPages" class="w-4 h-4 text-sky-600 focus:ring-sky-500 border-gray-300">
                                            <span class="text-xs font-semibold text-gray-800">ทุกหน้าในเอกสาร (<span x-text="cropTotalPages || 1"></span> หน้า)</span>
                                        </label>

                                        <label class="flex items-center gap-2.5 p-2.5 rounded-xl bg-white border border-gray-200 cursor-pointer hover:border-sky-300">
                                            <input type="radio" name="crop_pages_mode" value="custom" x-model="cropPages" class="w-4 h-4 text-sky-600 focus:ring-sky-500 border-gray-300">
                                            <span class="text-xs font-semibold text-gray-800">เฉพาะหน้าที่ระบุ</span>
                                        </label>

                                        <div x-show="cropPages === 'custom'" class="pt-1">
                                            <input type="text" x-model="cropCustomPages" placeholder="เช่น 1, 3-5, 8"
                                                   class="w-full px-3 py-2 text-xs border border-gray-300 rounded-xl focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                                            <p class="text-[11px] text-gray-400 mt-1">คั่นด้วยเครื่องหมายจุลภาค เช่น 1, 2, 4-6</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- RIGHT COLUMN: Live Screen Preview with Illuminated Crop Window (5 Cols) --}}
                            <div class="lg:col-span-5">
                                <div class="bg-slate-100/80 border border-slate-200/90 rounded-2xl p-4 flex flex-col items-center">
                                    <div class="w-full flex items-center justify-between mb-3 text-xs">
                                        <span class="font-bold text-gray-700 flex items-center gap-1.5">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                            ตัวอย่างหน้ากระดาษจำลอง
                                        </span>
                                        {{-- Pagination Buttons --}}
                                        <div class="flex items-center gap-1.5">
                                            <button type="button" @click="if (cropCurrentPage > 1) { cropCurrentPage--; loadCropPreview(); }"
                                                    :disabled="cropCurrentPage <= 1"
                                                    class="p-1 rounded bg-white border border-gray-200 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
                                            </button>
                                            <span class="text-[11px] text-gray-600 font-semibold" x-text="`หน้า ${cropCurrentPage || 1} / ${cropTotalPages || 1}`"></span>
                                            <button type="button" @click="if (cropCurrentPage < cropTotalPages) { cropCurrentPage++; loadCropPreview(); }"
                                                    :disabled="cropCurrentPage >= cropTotalPages"
                                                    class="p-1 rounded bg-white border border-gray-200 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                                            </button>
                                        </div>
                                    </div>

                                    {{-- Paper Sheet Container with Interactive Crop Overlay --}}
                                    <div class="w-full max-w-[280px] aspect-[1/1.414] bg-white rounded-lg shadow-md border border-gray-300 relative overflow-hidden select-none flex items-center justify-center">
                                        {{-- Rendered PDF background --}}
                                        <template x-if="cropPreviewUrl">
                                            <img :src="cropPreviewUrl" class="w-full h-full object-contain pointer-events-none" />
                                        </template>

                                        {{-- Loading Skeleton --}}
                                        <template x-if="!cropPreviewUrl">
                                            <div class="flex flex-col items-center justify-center text-slate-300 p-4 text-center">
                                                <svg class="w-8 h-8 animate-spin text-sky-500 mb-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                                <span class="text-xs text-gray-400">กำลังโหลดตัวอย่างหน้า...</span>
                                            </div>
                                        </template>

                                        {{-- Crop Overlay Darkening Mask (Visible when not in auto-margins mode) --}}
                                        <div x-show="cropMode !== 'auto-margins'" class="absolute inset-0 pointer-events-none">
                                            {{-- Top Mask --}}
                                            <div class="absolute top-0 left-0 right-0 bg-slate-900/60 backdrop-blur-[1px] transition-all"
                                                 :style="{ height: cropTop + '%' }"></div>
                                            {{-- Bottom Mask --}}
                                            <div class="absolute bottom-0 left-0 right-0 bg-slate-900/60 backdrop-blur-[1px] transition-all"
                                                 :style="{ height: cropBottom + '%' }"></div>
                                            {{-- Left Mask --}}
                                            <div class="absolute left-0 bg-slate-900/60 backdrop-blur-[1px] transition-all"
                                                 :style="{ top: cropTop + '%', bottom: cropBottom + '%', width: cropLeft + '%' }"></div>
                                            {{-- Right Mask --}}
                                            <div class="absolute right-0 bg-slate-900/60 backdrop-blur-[1px] transition-all"
                                                 :style="{ top: cropTop + '%', bottom: cropBottom + '%', width: cropRight + '%' }"></div>

                                            {{-- Illuminated Preserved Crop Box --}}
                                            <div class="absolute border-2 border-dashed border-sky-400 shadow-xs transition-all flex items-center justify-center"
                                                 :style="{ top: cropTop + '%', bottom: cropBottom + '%', left: cropLeft + '%', right: cropRight + '%' }">
                                                {{-- Corner Handles --}}
                                                <div class="absolute -top-1 -left-1 w-2.5 h-2.5 bg-sky-500 rounded-xs border border-white"></div>
                                                <div class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-sky-500 rounded-xs border border-white"></div>
                                                <div class="absolute -bottom-1 -left-1 w-2.5 h-2.5 bg-sky-500 rounded-xs border border-white"></div>
                                                <div class="absolute -bottom-1 -right-1 w-2.5 h-2.5 bg-sky-500 rounded-xs border border-white"></div>

                                                <span class="text-[10px] font-bold bg-sky-600/90 text-white px-2 py-0.5 rounded shadow-xs"
                                                      x-text="`${cropRemainingWidthPercent}% × ${cropRemainingHeightPercent}%`"></span>
                                            </div>
                                        </div>

                                        {{-- Auto Mode Badge Indicator --}}
                                        <div x-show="cropMode === 'auto-margins'" class="absolute inset-0 bg-indigo-950/20 flex items-center justify-center p-4 text-center">
                                            <div class="bg-white/95 backdrop-blur-md rounded-xl p-3 border border-indigo-200 shadow-md">
                                                <span class="text-xs font-bold text-indigo-700 block">⚡ ระบบคำนวณขอบอัจฉริยะ</span>
                                                <span class="text-[10px] text-gray-500 block mt-0.5">ครอบตัดตามกรอบเนื้อหาจริงบนแต่ละหน้า</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-[11px] text-gray-500 text-center mt-3">
                                        พื้นที่สว่างคือส่วนที่จะถูก <strong class="text-gray-700">คงไว้</strong> ในเอกสารใหม่
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($tool['slug'] === 'organize-pdf')
                    {{-- Interactive Organize & Reorder PDF Pages Visual Editor --}}
                    <div class="mt-5 pt-5 border-t border-gray-100" @click.stop>
                        {{-- Header / Quick Info --}}
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5 pb-3 border-b border-gray-100">
                            <div>
                                <h3 class="text-base font-bold text-gray-800 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                                    </svg>
                                    จัดเรียงลำดับหน้าเอกสาร PDF
                                </h3>
                                <p class="text-xs text-gray-500 mt-0.5">ลากและวางการ์ดเพื่อสลับตำแหน่งหน้า หมุน ทำซ้ำ หรือลบหน้าเฉพาะใบได้ตามต้องการ</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-indigo-50 text-indigo-700 text-xs font-semibold border border-indigo-200 shadow-2xs">
                                    <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9z"/></svg>
                                    <span x-text="`ทั้งหมด ${orgPagesList.length || orgTotalPages || 1} หน้า (ลำดับใหม่)`"></span>
                                </span>
                            </div>
                        </div>

                        {{-- Quick Actions Toolbar --}}
                        <div class="bg-slate-50/90 border border-slate-200/90 rounded-2xl p-3.5 sm:p-4 mb-5 shadow-2xs">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    {{-- Reverse Order Button --}}
                                    <button type="button" @click="reverseOrgPages()"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white border border-gray-200 text-gray-700 hover:border-indigo-400 hover:text-indigo-600 hover:shadow-xs text-xs font-semibold transition-all cursor-pointer">
                                        <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/></svg>
                                        เรียงย้อนกลับ (Reverse)
                                    </button>

                                    {{-- Sort Odd / Even Button --}}
                                    <button type="button" @click="sortOrgOddEven()"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white border border-gray-200 text-gray-700 hover:border-indigo-400 hover:text-indigo-600 hover:shadow-xs text-xs font-semibold transition-all cursor-pointer">
                                        <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12"/></svg>
                                        แยกหน้าคี่/หน้าคู่ (Odd/Even)
                                    </button>

                                    {{-- Reset Original Sequence --}}
                                    <button type="button" @click="resetOrgPages()"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white border border-gray-200 text-gray-700 hover:border-red-400 hover:text-red-600 hover:shadow-xs text-xs font-semibold transition-all cursor-pointer">
                                        <svg class="w-3.5 h-3.5 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                                        คืนค่าลำดับเดิม
                                    </button>
                                </div>

                                <div class="text-[11px] text-gray-500 flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    <span>ลากสลับหน้า หรือคลิกปุ่ม ◀ ▶ เพื่อย้ายตำแหน่ง</span>
                                </div>
                            </div>
                        </div>

                        {{-- Thumbnail Cards Grid --}}
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3.5">
                            <template x-for="(page, index) in orgPagesList" :key="page.id">
                                <div class="group relative bg-white border-2 rounded-2xl p-2.5 flex flex-col items-center justify-between transition-all duration-150 select-none shadow-xs hover:shadow-md cursor-grab active:cursor-grabbing"
                                     :class="draggedOrgPageIndex === index ? 'opacity-40 border-dashed border-indigo-400 scale-95' : 'border-slate-200/90 hover:border-indigo-400'"
                                     draggable="true"
                                     @dragstart="onOrgDragStart($event, index)"
                                     @dragover="onOrgDragOver($event, index)"
                                     @drop="onOrgDrop($event, index)">

                                    {{-- Card Header: Current Index & Original Page Badge --}}
                                    <div class="w-full flex items-center justify-between mb-1.5 text-[11px]">
                                        {{-- New sequence number --}}
                                        <span class="font-bold px-2 py-0.5 rounded-full bg-indigo-600 text-white shadow-2xs"
                                              x-text="`#${index + 1}`"></span>
                                        {{-- Original page label --}}
                                        <span class="text-[10px] text-gray-500 bg-gray-100 px-1.5 py-0.2 rounded font-mono"
                                              x-text="`เดิม: p.${page.origPageNum}`"></span>
                                    </div>

                                    {{-- Thumbnail Box with Dynamic Rotation --}}
                                    <div class="w-full aspect-[3/4] bg-slate-50 rounded-xl overflow-hidden relative flex items-center justify-center border border-slate-100 shadow-inner my-1">
                                        <template x-if="page.dataUrl">
                                            <img :src="page.dataUrl"
                                                 class="w-full h-full object-contain pointer-events-none rounded-lg transition-transform duration-200"
                                                 :style="{ transform: `rotate(${page.rotation || 0}deg)` }" />
                                        </template>
                                        <template x-if="!page.dataUrl">
                                            <div class="flex flex-col items-center justify-center p-1 text-slate-300 text-center">
                                                <svg class="w-6 h-6 animate-pulse" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            </div>
                                        </template>

                                        {{-- Active Rotation Badge --}}
                                        <template x-if="page.rotation > 0">
                                            <span class="absolute top-1 right-1 text-[9px] font-bold bg-indigo-600 text-white px-1.5 py-0.5 rounded-full shadow-2xs">
                                                <span x-text="`${page.rotation}°`"></span>
                                            </span>
                                        </template>
                                    </div>

                                    {{-- Card Action Toolbar --}}
                                    <div class="w-full pt-1.5 mt-1 border-t border-gray-100 flex items-center justify-between gap-1">
                                        {{-- Move Left Button --}}
                                        <button type="button" @click.stop="moveOrgPage(index, -1)"
                                                :disabled="index === 0"
                                                title="เลื่อนไปทางซ้าย"
                                                class="p-1 rounded hover:bg-slate-100 text-gray-500 hover:text-indigo-600 disabled:opacity-20 disabled:cursor-not-allowed">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
                                        </button>

                                        {{-- Rotate 90° Button --}}
                                        <button type="button" @click.stop="rotateOrgPage(index)"
                                                title="หมุน 90 องศา"
                                                class="p-1 rounded hover:bg-slate-100 text-gray-500 hover:text-indigo-600">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                                        </button>

                                        {{-- Duplicate Page Button --}}
                                        <button type="button" @click.stop="duplicateOrgPage(index)"
                                                title="ทำซ้ำหน้านี้"
                                                class="p-1 rounded hover:bg-slate-100 text-gray-500 hover:text-emerald-600">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75"/></svg>
                                        </button>

                                        {{-- Delete Page Button --}}
                                        <button type="button" @click.stop="deleteOrgPage(index)"
                                                title="ลบหน้านี้"
                                                class="p-1 rounded hover:bg-red-50 text-gray-400 hover:text-red-600">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                        </button>

                                        {{-- Move Right Button --}}
                                        <button type="button" @click.stop="moveOrgPage(index, 1)"
                                                :disabled="index === orgPagesList.length - 1"
                                                title="เลื่อนไปทางขวา"
                                                class="p-1 rounded hover:bg-slate-100 text-gray-500 hover:text-indigo-600 disabled:opacity-20 disabled:cursor-not-allowed">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </template>
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
                    :class="{ 'opacity-50 cursor-not-allowed': !hasFiles || (tool === 'delete-pages' && hasFiles && !canSubmitDeletePages) || (tool === 'watermark-pdf' && hasFiles && !canSubmitWatermark) || (tool === 'protect-pdf' && hasFiles && !canSubmitProtectPdf) || (tool === 'unlock-pdf' && hasFiles && !canSubmitUnlockPdf) || (tool === 'merge-pdf' && hasFiles && !canSubmitMergePdf) || (tool === 'split-pdf' && hasFiles && !canSubmitSplitPdf) || (['pdf-to-jpg', 'pdf-to-png'].includes(tool) && hasFiles && !canSubmitPdfToImage) }"
                    :disabled="!hasFiles || (tool === 'delete-pages' && hasFiles && !canSubmitDeletePages) || (tool === 'watermark-pdf' && hasFiles && !canSubmitWatermark) || (tool === 'protect-pdf' && hasFiles && !canSubmitProtectPdf) || (tool === 'unlock-pdf' && hasFiles && !canSubmitUnlockPdf) || (tool === 'merge-pdf' && hasFiles && !canSubmitMergePdf) || (tool === 'split-pdf' && hasFiles && !canSubmitSplitPdf) || (['pdf-to-jpg', 'pdf-to-png'].includes(tool) && hasFiles && !canSubmitPdfToImage)"
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

@if(in_array($tool['slug'], ['rotate-pdf', 'delete-pages', 'watermark-pdf', 'unlock-pdf', 'merge-pdf', 'compress-pdf', 'split-pdf', 'pdf-to-word', 'pdf-to-excel', 'pdf-to-pptx', 'pdf-to-jpg', 'pdf-to-png', 'page-numbers', 'crop-pdf', 'organize-pdf']))
@push('scripts')
<script src="{{ asset('vendor/pdfjs/pdf.min.js') }}"></script>
<script>
    if (window.pdfjsLib) {
        window.pdfjsLib.GlobalWorkerOptions.workerSrc = "{{ asset('vendor/pdfjs/pdf.worker.min.js') }}";
    }
</script>
@endpush
@endif
