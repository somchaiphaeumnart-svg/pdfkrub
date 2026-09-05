@extends('layouts.app')

@section('title', 'เซ็นเอกสาร PDF ออนไลน์ (e-Sign) — PDFkrub')
@section('description', 'เซ็นชื่อดิจิทัลบน PDF ออนไลน์ฟรี วาดลายเซ็น พิมพ์ชื่อ หรืออัปโหลดรูปลายเซ็น รองรับภาษาไทย ใช้งานง่าย ปลอดภัย 100% บนเบราว์เซอร์')

@push('head')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alex+Brush&family=Dancing+Script:wght@600;700&family=Great+Vibes&family=Pacifico&family=Sarabun:ital,wght@0,400;0,600;0,700;1,400;1,600;1,700&display=swap" rel="stylesheet">
    <script src="/vendor/pdfjs/pdf.min.js"></script>
    <script src="/vendor/pdf-lib.min.js"></script>
@endpush

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10"
     x-data="signPdf()"
     x-init="init()">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm text-gray-400 mb-6">
        <a href="{{ route('home') }}" class="hover:text-brand-600 transition-colors">หน้าแรก</a>
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
        <a href="{{ route('tools') }}" class="hover:text-brand-600 transition-colors">เครื่องมือ</a>
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
        <span class="text-gray-700 font-medium">เซ็นเอกสาร PDF</span>
    </nav>

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div class="flex items-start gap-4">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-brand-600 to-indigo-600 text-white flex items-center justify-center text-2xl shadow-lg shadow-brand-500/20 flex-shrink-0">
                ✍️
            </div>
            <div>
                <div class="flex items-center gap-2.5 mb-1">
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight">เซ็นเอกสาร PDF ออนไลน์</h1>
                    @auth
                        @if(auth()->user()->getActivePlan()->has_esign)
                        <span class="px-2.5 py-0.5 text-xs font-bold rounded-full bg-green-100 text-green-700 border border-green-200">Pro Active</span>
                        @else
                        <span class="badge-premium">Pro</span>
                        @endif
                    @else
                    <span class="badge-premium">Pro</span>
                    @endauth
                </div>
                <p class="text-sm text-gray-500">เซ็นชื่อดิจิทัล วาดลายเซ็น พิมพ์ชื่อ หรืออัปโหลดตราประทับ — ปรับขนาดและลากวางได้อิสระ</p>
            </div>
        </div>

        <div class="flex items-center gap-2 text-xs text-slate-500 bg-white border border-slate-200 px-3.5 py-2 rounded-xl shadow-2xs">
            <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/>
            </svg>
            <span>ดำเนินการบนเบราว์เซอร์ 100% · ไฟล์ปลอดภัยไม่ถูกส่งขึ้นเซิร์ฟเวอร์</span>
        </div>
    </div>

    {{-- Premium Gate Notice if not subscribed --}}
    @if(!auth()->check() || !auth()->user()->getActivePlan()->has_esign)
    <div class="bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200 rounded-2xl p-6 mb-8 shadow-xs">
        <div class="flex flex-col sm:flex-row items-start gap-4">
            <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center flex-shrink-0 text-amber-600">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/>
                </svg>
            </div>
            <div class="flex-1">
                <h3 class="text-base font-bold text-gray-900 mb-1">ฟีเจอร์นี้สำหรับสมาชิก Pro</h3>
                <p class="text-sm text-gray-600 mb-3.5">อัปเกรดเป็นสมาชิก Pro เพื่อเซ็นเอกสาร PDF ไม่จำกัด รองรับลายเซ็นดิจิทัล ตราประทับ และสอดคล้องตามกฎหมาย PDPA</p>
                <div class="flex flex-wrap gap-2.5">
                    <a href="{{ route('pricing') }}" class="btn-primary text-xs px-5 py-2.5 rounded-xl inline-flex items-center gap-1.5 shadow-sm">
                        อัปเกรดเป็น Pro — ฿199/เดือน
                    </a>
                    @guest
                    <a href="{{ route('login') }}" class="btn-ghost text-xs px-4 py-2 rounded-xl inline-block bg-white border border-gray-200">
                        เข้าสู่ระบบ
                    </a>
                    @endguest
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Main 2-Column Grid Layout --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

        {{-- LEFT COLUMN: PDF Document Viewer (7 Cols) --}}
        <div class="lg:col-span-7 space-y-4">
            <div class="bg-white rounded-2xl border border-gray-200/90 shadow-sm overflow-hidden">
                
                {{-- Card Header & Controls Toolbar --}}
                <div class="px-5 py-3.5 bg-slate-50/80 border-b border-gray-200 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-bold text-gray-800 flex items-center gap-2">
                            <svg class="w-4 h-4 text-brand-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                            </svg>
                            เอกสาร PDF
                        </span>
                        <template x-if="pdfLoaded">
                            <span class="text-xs text-gray-500 font-medium truncate max-w-[180px] sm:max-w-[220px]" x-text="pdfFileName"></span>
                        </template>
                    </div>

                    {{-- Navigation & Zoom Toolbar (when PDF is loaded) --}}
                    <template x-if="pdfLoaded">
                        <div class="flex items-center gap-2">
                            {{-- Page Pager --}}
                            <div class="flex items-center gap-1 bg-white border border-slate-200 px-2 py-1 rounded-lg text-xs shadow-2xs">
                                <button type="button" @click="prevPage()" :disabled="currentPage <= 1 || isRenderingPage" class="p-1 text-slate-600 hover:text-brand-600 disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer" title="หน้าก่อน">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                                </button>
                                <span class="px-1 text-slate-700 font-medium">
                                    หน้า <span class="font-bold text-brand-600" x-text="currentPage"></span> / <span x-text="totalPages"></span>
                                </span>
                                <button type="button" @click="nextPage()" :disabled="currentPage >= totalPages || isRenderingPage" class="p-1 text-slate-600 hover:text-brand-600 disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer" title="หน้าถัดไป">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            </div>

                            {{-- Zoom Controls --}}
                            <div class="hidden sm:flex items-center gap-0.5 bg-white border border-slate-200 px-1.5 py-1 rounded-lg text-xs shadow-2xs">
                                <button type="button" @click="zoomOut()" :disabled="zoomLevel <= 0.6 || isRenderingPage" class="p-1 text-slate-600 hover:text-brand-600 disabled:opacity-30 cursor-pointer" title="ย่อ">-</button>
                                <button type="button" @click="resetZoom()" class="px-1.5 text-[11px] font-semibold text-slate-600 hover:text-brand-600 cursor-pointer" title="รีเซ็ตขนาด" x-text="`${Math.round(zoomLevel * 100)}%`"></button>
                                <button type="button" @click="zoomIn()" :disabled="zoomLevel >= 2.0 || isRenderingPage" class="p-1 text-slate-600 hover:text-brand-600 disabled:opacity-30 cursor-pointer" title="ขยาย">+</button>
                            </div>

                            {{-- Change File Button --}}
                            <button type="button" @click="$refs.pdfInput.click()" class="text-xs text-brand-600 hover:text-brand-700 font-medium px-2 py-1 rounded-lg hover:bg-brand-50 transition-colors cursor-pointer">
                                เปลี่ยนไฟล์
                            </button>
                        </div>
                    </template>
                </div>

                {{-- Document Body Area --}}
                <div class="p-4 sm:p-6">
                    {{-- Hidden file input --}}
                    <input type="file" x-ref="pdfInput" accept=".pdf" @change="handlePdfInput($event)" class="hidden">

                    {{-- 1. EMPTY STATE: Big Drag & Drop Zone --}}
                    <template x-if="!pdfLoaded">
                        <div class="border-2 border-dashed border-slate-300 rounded-2xl p-10 sm:p-14 text-center cursor-pointer transition-all hover:border-brand-500 hover:bg-brand-50/20 group"
                             :class="{ 'border-brand-600 bg-brand-50/40 ring-4 ring-brand-100': isDraggingFile }"
                             @dragover.prevent="isDraggingFile = true"
                             @dragleave.prevent="isDraggingFile = false"
                             @drop.prevent="handlePdfDrop($event)"
                             @click="$refs.pdfInput.click()">
                            
                            <div class="w-16 h-16 rounded-2xl bg-brand-50 border border-brand-100 text-brand-600 flex items-center justify-center mx-auto mb-4 shadow-sm group-hover:scale-110 transition-transform">
                                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m6.75 12-3-3m0 0-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                                </svg>
                            </div>
                            <h3 class="text-base font-bold text-gray-800 mb-1">เลือกไฟล์ PDF ที่ต้องการลงลายเซ็น</h3>
                            <p class="text-xs text-gray-500 mb-4">ลากไฟล์ PDF มาวางที่นี่ หรือคลิกเพื่อเปิดหาไฟล์ในเครื่องของคุณ</p>
                            <span class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-brand-600 text-white text-xs font-semibold shadow-sm group-hover:bg-brand-700 transition-colors">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                เลือกเอกสาร PDF
                            </span>
                        </div>
                    </template>

                    {{-- 2. PDF LOADED STATE: Canvas Screen with Placed Signatures Overlay --}}
                    <template x-if="pdfLoaded">
                        <div class="space-y-4">
                            {{-- Canvas Scroll Area --}}
                            <div class="relative bg-slate-200/70 border border-slate-300/80 rounded-2xl p-4 sm:p-6 flex flex-col items-center justify-center min-h-[480px] overflow-auto select-none"
                                 x-ref="pdfContainer">

                                {{-- Loading Spinner Overlay --}}
                                <div x-show="isRenderingPage" class="absolute inset-0 bg-white/70 backdrop-blur-xs flex flex-col items-center justify-center z-40">
                                    <svg class="w-8 h-8 animate-spin text-brand-600 mb-2" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    <span class="text-xs font-semibold text-slate-700">กำลังแสดงผลเอกสาร PDF...</span>
                                </div>

                                {{-- Document Wrapper matching exact rendered page width & height --}}
                                <div class="relative shadow-2xl rounded-lg bg-white overflow-hidden cursor-crosshair"
                                     :style="`width: ${canvasDisplayWidth}px; height: ${canvasDisplayHeight}px;`"
                                     @click="handleCanvasClick($event)">

                                    {{-- PDF.js Canvas --}}
                                    <canvas x-ref="pdfCanvas" class="block w-full h-full pointer-events-none"></canvas>

                                    {{-- Signatures Overlay on Current Page --}}
                                    <div class="absolute inset-0 pointer-events-none">
                                        <template x-for="sig in currentPageSignatures" :key="sig.id">
                                            <div class="absolute pointer-events-auto cursor-move select-none group"
                                                 :class="selectedSigId === sig.id ? 'ring-2 ring-brand-500 ring-offset-1 shadow-lg bg-brand-50/15' : 'hover:ring-1 hover:ring-brand-400/80'"
                                                 :style="`left: ${sig.x}px; top: ${sig.y}px; width: ${sig.w}px; height: ${sig.h}px;`"
                                                 @mousedown.stop="startDragSig($event, sig)"
                                                 @touchstart.stop="startDragSig($event, sig)">

                                                {{-- Signature Image Display --}}
                                                <img :src="sig.dataUrl" class="w-full h-full object-contain pointer-events-none select-none">

                                                {{-- Controls shown when signature is selected --}}
                                                <template x-if="selectedSigId === sig.id">
                                                    <div>
                                                        {{-- Delete Button (Top-Right) --}}
                                                        <button type="button"
                                                                @click.stop="removePlacedSignature(sig.id)"
                                                                title="ลบลายเซ็นนี้"
                                                                class="absolute -top-3 -right-3 w-6 h-6 rounded-full bg-red-600 text-white flex items-center justify-center shadow-md hover:bg-red-700 active:scale-90 transition-all cursor-pointer z-30">
                                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                                        </button>

                                                        {{-- Resize Handle (Bottom-Right) --}}
                                                        <div @mousedown.stop="startResizeSig($event, sig)"
                                                             @touchstart.stop="startResizeSig($event, sig)"
                                                             title="คลิกลากเพื่อย่อ-ขยาย"
                                                             class="absolute -bottom-2 -right-2 w-5 h-5 rounded-full bg-brand-600 text-white flex items-center justify-center shadow-md hover:scale-125 cursor-nwse-resize active:scale-95 transition-transform z-30">
                                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m9 15 6-6m0 6V9m-6 0h6"/></svg>
                                                        </div>

                                                        {{-- Move Indicator Badge (Top-Left) --}}
                                                        <div class="absolute -top-2.5 -left-2.5 px-1.5 py-0.5 rounded bg-brand-600 text-[9px] text-white font-medium shadow-xs pointer-events-none">
                                                            ลากย้าย
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            {{-- Bottom Status & Export Bar --}}
                            <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-2">
                                <div class="flex items-center gap-2 text-xs text-slate-600">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    <span>วางลายเซ็นแล้ว: <strong class="text-slate-900" x-text="`${totalPlacedSignaturesCount} จุด`"></strong> (หน้านี้มี <span x-text="currentPageSignatures.length"></span> จุด)</span>
                                </div>

                                <div class="flex items-center gap-2 w-full sm:w-auto">
                                    <template x-if="totalPlacedSignaturesCount > 0">
                                        <button type="button"
                                                @click="clearAllPlacedSignatures()"
                                                class="text-xs text-red-500 hover:text-red-700 px-3 py-2 rounded-xl hover:bg-red-50 transition-colors cursor-pointer">
                                            ล้างลายเซ็นทั้งหมด
                                        </button>
                                    </template>

                                    <button type="button"
                                            @click="applyAndDownload()"
                                            :disabled="totalPlacedSignaturesCount === 0 || isExporting"
                                            class="btn-primary px-6 py-3 rounded-xl text-sm font-semibold flex items-center justify-center gap-2 flex-1 sm:flex-initial shadow-md disabled:opacity-50 disabled:cursor-not-allowed">
                                        <template x-if="!isExporting">
                                            <span class="flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                                                </svg>
                                                บันทึกและดาวน์โหลด PDF
                                            </span>
                                        </template>
                                        <template x-if="isExporting">
                                            <span class="flex items-center gap-2">
                                                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                                กำลังประมวลผล PDF...
                                            </span>
                                        </template>
                                    </button>
                                </div>
                            </div>

                            {{-- Success message notice --}}
                            <div x-show="exportSuccess" class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs rounded-xl flex items-center gap-2">
                                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                <span>ลงลายเซ็นในไฟล์ PDF สำเร็จเรียบร้อย! กำลังเริ่มดาวน์โหลดไฟล์...</span>
                            </div>

                            {{-- Error message notice --}}
                            <div x-show="exportError" class="p-3 bg-red-50 border border-red-200 text-red-600 text-xs rounded-xl flex items-center gap-2" x-text="exportError"></div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- RIGHT COLUMN: Signature Creation Studio (5 Cols) --}}
        <div class="lg:col-span-5 space-y-4">
            <div class="bg-white rounded-2xl border border-gray-200/90 shadow-sm overflow-hidden">
                
                {{-- Card Header & Tabs --}}
                <div class="p-4 sm:p-5 border-b border-gray-200 bg-slate-50/50">
                    <h2 class="font-bold text-gray-900 text-sm mb-3 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-lg bg-brand-100 text-brand-600 flex items-center justify-center text-xs">✍️</span>
                        เครื่องมือสร้างลายเซ็น
                    </h2>

                    {{-- 4-Tab Bar --}}
                    <div class="grid grid-cols-4 gap-1 bg-slate-200/60 p-1 rounded-xl">
                        <button type="button"
                                @click="switchTab('draw')"
                                :class="sigTab === 'draw' ? 'bg-white text-brand-600 font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                                class="py-2 text-xs rounded-lg transition-all text-center cursor-pointer">
                            ✍️ วาด
                        </button>
                        <button type="button"
                                @click="switchTab('type')"
                                :class="sigTab === 'type' ? 'bg-white text-brand-600 font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                                class="py-2 text-xs rounded-lg transition-all text-center cursor-pointer">
                            ⌨️ พิมพ์
                        </button>
                        <button type="button"
                                @click="switchTab('upload')"
                                :class="sigTab === 'upload' ? 'bg-white text-brand-600 font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                                class="py-2 text-xs rounded-lg transition-all text-center cursor-pointer">
                            📷 รูปภาพ
                        </button>
                        <button type="button"
                                @click="switchTab('date')"
                                :class="sigTab === 'date' ? 'bg-white text-brand-600 font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                                class="py-2 text-xs rounded-lg transition-all text-center cursor-pointer">
                            📅 วันที่
                        </button>
                    </div>
                </div>

                {{-- Tab Contents Area --}}
                <div class="p-4 sm:p-5 space-y-4">
                    
                    {{-- 1. DRAW TAB --}}
                    <div x-show="sigTab === 'draw'" class="space-y-3">
                        <div class="flex items-center justify-between">
                            {{-- Color Pills --}}
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-slate-500 font-medium">สีหมึก:</span>
                                <div class="flex items-center gap-1.5">
                                    <button type="button" @click="drawColor = '#003399'" class="w-6 h-6 rounded-full bg-[#003399] border-2 transition-transform" :class="drawColor === '#003399' ? 'border-slate-800 scale-110 shadow-xs' : 'border-white'"></button>
                                    <button type="button" @click="drawColor = '#111827'" class="w-6 h-6 rounded-full bg-[#111827] border-2 transition-transform" :class="drawColor === '#111827' ? 'border-slate-800 scale-110 shadow-xs' : 'border-white'"></button>
                                    <button type="button" @click="drawColor = '#dc2626'" class="w-6 h-6 rounded-full bg-[#dc2626] border-2 transition-transform" :class="drawColor === '#dc2626' ? 'border-slate-800 scale-110 shadow-xs' : 'border-white'"></button>
                                </div>
                            </div>

                            {{-- Pen Width Pills --}}
                            <div class="flex items-center gap-1">
                                <button type="button" @click="penWidth = 2" :class="penWidth === 2 ? 'bg-brand-50 text-brand-700 font-bold border-brand-300' : 'text-slate-500 border-transparent'" class="px-2 py-1 rounded-md text-[11px] border">บาง</button>
                                <button type="button" @click="penWidth = 3" :class="penWidth === 3 ? 'bg-brand-50 text-brand-700 font-bold border-brand-300' : 'text-slate-500 border-transparent'" class="px-2 py-1 rounded-md text-[11px] border">กลาง</button>
                                <button type="button" @click="penWidth = 5" :class="penWidth === 5 ? 'bg-brand-50 text-brand-700 font-bold border-brand-300' : 'text-slate-500 border-transparent'" class="px-2 py-1 rounded-md text-[11px] border">หนา</button>
                            </div>
                        </div>

                        {{-- Drawing Canvas Box --}}
                        <div class="relative bg-white border border-slate-300 rounded-xl overflow-hidden shadow-2xs">
                            <canvas x-ref="sigDrawCanvas"
                                    width="460" height="180"
                                    class="w-full h-[160px] cursor-crosshair touch-none bg-white block"
                                    @mousedown="startDraw($event)"
                                    @mousemove="draw($event)"
                                    @mouseup="stopDraw()"
                                    @mouseleave="stopDraw()"
                                    @touchstart.passive="false"
                                    @touchstart="startDraw($event)"
                                    @touchmove.passive="false"
                                    @touchmove="draw($event)"
                                    @touchend="stopDraw()">
                            </canvas>

                            {{-- Canvas Helper text when empty --}}
                            <div x-show="!hasDrawn" class="absolute inset-0 flex items-center justify-center pointer-events-none text-slate-400 text-xs">
                                ✍️ ใช้เมาส์ ปากกา หรือนิ้วลากเซ็นชื่อตรงนี้
                            </div>

                            {{-- Action buttons on canvas footer --}}
                            <div class="absolute bottom-2 right-2 flex items-center gap-1.5 z-10">
                                <button type="button"
                                        @click="undoDraw()"
                                        :disabled="drawStrokes.length === 0"
                                        class="px-2 py-1 bg-white/90 border border-slate-200 text-slate-600 rounded-md text-[11px] hover:bg-white disabled:opacity-40 shadow-2xs cursor-pointer">
                                    ย้อนกลับ
                                </button>
                                <button type="button"
                                        @click="clearDraw()"
                                        class="px-2 py-1 bg-white/90 border border-slate-200 text-red-600 rounded-md text-[11px] hover:bg-white shadow-2xs cursor-pointer">
                                    ล้าง
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- 2. TYPE TAB --}}
                    <div x-show="sigTab === 'type'" class="space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">พิมพ์ชื่อ-นามสกุล หรือข้อความ</label>
                            <input type="text"
                                   x-model="typedName"
                                   @input="renderTypedSignature()"
                                   placeholder="เช่น สมชาย แผ่อำนาจ หรือ Somchai P."
                                   class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-slate-800">
                        </div>

                        {{-- Color selector for font --}}
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-slate-500 font-medium">สีตัวอักษร:</span>
                            <div class="flex items-center gap-1.5">
                                <button type="button" @click="typeColor = '#003399'; renderTypedSignature()" class="w-6 h-6 rounded-full bg-[#003399] border-2" :class="typeColor === '#003399' ? 'border-slate-800 ring-1 ring-blue-300 scale-110' : 'border-white'"></button>
                                <button type="button" @click="typeColor = '#111827'; renderTypedSignature()" class="w-6 h-6 rounded-full bg-[#111827] border-2" :class="typeColor === '#111827' ? 'border-slate-800 ring-1 ring-gray-300 scale-110' : 'border-white'"></button>
                                <button type="button" @click="typeColor = '#dc2626'; renderTypedSignature()" class="w-6 h-6 rounded-full bg-[#dc2626] border-2" :class="typeColor === '#dc2626' ? 'border-slate-800 ring-1 ring-red-300 scale-110' : 'border-white'"></button>
                            </div>
                        </div>

                        {{-- Font Family Cards --}}
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">เลือกรูปแบบฟอนต์ลายมือ</label>
                            <div class="space-y-1.5 max-h-[190px] overflow-y-auto pr-1">
                                <template x-for="font in typeFonts" :key="font.id">
                                    <button type="button"
                                            @click="typeFont = font.id; renderTypedSignature()"
                                            :class="typeFont === font.id ? 'border-brand-600 bg-brand-50/50 shadow-xs' : 'border-slate-200 hover:border-slate-300 bg-white'"
                                            class="w-full text-left p-2.5 rounded-xl border transition-all flex items-center justify-between cursor-pointer">
                                        <div class="truncate">
                                            <div class="text-xs text-slate-400" x-text="font.name"></div>
                                            <div class="text-base truncate pt-0.5" :style="`font-family: ${font.family}; color: ${typeColor};`" x-text="typedName || 'สมชาย แผ่อำนาจ'"></div>
                                        </div>
                                        <span x-show="typeFont === font.id" class="text-brand-600 text-xs font-bold shrink-0">✓</span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- 3. UPLOAD TAB --}}
                    <div x-show="sigTab === 'upload'" class="space-y-3">
                        <input type="file" x-ref="sigImageInput" accept="image/png,image/jpeg,image/webp" @change="handleSignatureImageUpload($event)" class="hidden">

                        <div class="border-2 border-dashed border-slate-300 rounded-xl p-6 text-center cursor-pointer hover:border-brand-500 hover:bg-brand-50/30 transition-all group"
                             @click="$refs.sigImageInput.click()">
                            <template x-if="!uploadedImageDataUrl">
                                <div>
                                    <div class="w-10 h-10 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center mx-auto mb-2 group-hover:scale-110 transition-transform">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                                    </div>
                                    <p class="text-xs font-semibold text-slate-700">อัปโหลดรูปภาพลายเซ็น / ตราประทับ</p>
                                    <p class="text-[11px] text-slate-400 mt-0.5">รองรับไฟล์ PNG, JPG, WEBP</p>
                                </div>
                            </template>
                            <template x-if="uploadedImageDataUrl">
                                <div>
                                    <img :src="activeSigDataUrl || uploadedImageDataUrl" class="max-h-24 mx-auto object-contain mb-2">
                                    <button type="button" @click.stop="$refs.sigImageInput.click()" class="text-xs text-brand-600 font-semibold hover:underline">คลิกเพื่อเปลี่ยนรูป</button>
                                </div>
                            </template>
                        </div>

                        {{-- Auto Transparent Filter Toggle --}}
                        <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 flex items-center justify-between">
                            <div>
                                <span class="text-xs font-bold text-slate-800 block">✨ ลบพื้นหลังสีขาวอัตโนมัติ</span>
                                <span class="text-[11px] text-slate-500">เหมาะกับภาพถ่ายลายเซ็นบนกระดาษขาว</span>
                            </div>
                            <input type="checkbox"
                                   x-model="autoRemoveWhite"
                                   @change="processUploadedSignature()"
                                   class="w-4 h-4 accent-brand-600 rounded cursor-pointer">
                        </div>
                    </div>

                    {{-- 4. DATE STAMP TAB --}}
                    <div x-show="sigTab === 'date'" class="space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">รูปแบบวันที่</label>
                            <div class="space-y-1.5">
                                <button type="button" @click="dateType = 'th_buddhist'; generateDateStamp()" :class="dateType === 'th_buddhist' ? 'border-brand-600 bg-brand-50/50 font-bold' : 'border-slate-200 bg-white'" class="w-full text-left px-3 py-2 rounded-xl border text-xs text-slate-700 flex items-center justify-between cursor-pointer">
                                    <span>วันที่ พ.ศ. (๕ ก.ย. ๒๕๖๙)</span>
                                    <span x-show="dateType === 'th_buddhist'" class="text-brand-600">✓</span>
                                </button>
                                <button type="button" @click="dateType = 'th_full'; generateDateStamp()" :class="dateType === 'th_full' ? 'border-brand-600 bg-brand-50/50 font-bold' : 'border-slate-200 bg-white'" class="w-full text-left px-3 py-2 rounded-xl border text-xs text-slate-700 flex items-center justify-between cursor-pointer">
                                    <span>วันที่ พ.ศ. แบบเต็ม (วันที่ 5 กันยายน พ.ศ. 2569)</span>
                                    <span x-show="dateType === 'th_full'" class="text-brand-600">✓</span>
                                </button>
                                <button type="button" @click="dateType = 'th_short'; generateDateStamp()" :class="dateType === 'th_short' ? 'border-brand-600 bg-brand-50/50 font-bold' : 'border-slate-200 bg-white'" class="w-full text-left px-3 py-2 rounded-xl border text-xs text-slate-700 flex items-center justify-between cursor-pointer">
                                    <span>ตัวเลข พ.ศ. (05/09/2569)</span>
                                    <span x-show="dateType === 'th_short'" class="text-brand-600">✓</span>
                                </button>
                                <button type="button" @click="dateType = 'en_short'; generateDateStamp()" :class="dateType === 'en_short' ? 'border-brand-600 bg-brand-50/50 font-bold' : 'border-slate-200 bg-white'" class="w-full text-left px-3 py-2 rounded-xl border text-xs text-slate-700 flex items-center justify-between cursor-pointer">
                                    <span>ตัวเลข ค.ศ. (05/09/2026)</span>
                                    <span x-show="dateType === 'en_short'" class="text-brand-600">✓</span>
                                </button>
                            </div>
                        </div>

                        {{-- Color selector for Date --}}
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-slate-500 font-medium">สีหมึกตราประทับ:</span>
                            <div class="flex items-center gap-1.5">
                                <button type="button" @click="dateColor = '#003399'; generateDateStamp()" class="w-6 h-6 rounded-full bg-[#003399] border-2" :class="dateColor === '#003399' ? 'border-slate-800 ring-1 ring-blue-300 scale-110' : 'border-white'"></button>
                                <button type="button" @click="dateColor = '#111827'; generateDateStamp()" class="w-6 h-6 rounded-full bg-[#111827] border-2" :class="dateColor === '#111827' ? 'border-slate-800 ring-1 ring-gray-300 scale-110' : 'border-white'"></button>
                                <button type="button" @click="dateColor = '#dc2626'; generateDateStamp()" class="w-6 h-6 rounded-full bg-[#dc2626] border-2" :class="dateColor === '#dc2626' ? 'border-slate-800 ring-1 ring-red-300 scale-110' : 'border-white'"></button>
                            </div>
                        </div>
                    </div>

                    {{-- ─── LIVE PREVIEW & PLACEMENT CARD ─── --}}
                    <div class="pt-3 border-t border-slate-200 space-y-3">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold text-slate-700">ตัวอย่างลายเซ็นพร้อมวาง:</span>
                            <div class="flex items-center gap-1.5 text-slate-500">
                                <span>ขนาด:</span>
                                <span class="font-bold text-brand-600" x-text="`${Math.round(sigScale * 100)}%`"></span>
                            </div>
                        </div>

                        {{-- Signature Preview Box --}}
                        <div class="h-24 bg-slate-50 rounded-xl border border-slate-200/80 flex items-center justify-center p-3 overflow-hidden">
                            <template x-if="activeSigDataUrl">
                                <img :src="activeSigDataUrl"
                                     class="max-h-full max-w-full object-contain transition-transform select-none"
                                     :style="`transform: scale(${sigScale});`">
                            </template>
                            <template x-if="!activeSigDataUrl">
                                <span class="text-xs text-slate-400">ยังไม่มีลายเซ็น — กรุณาวาด พิมพ์ หรืออัปโหลดรูปลายเซ็น</span>
                            </template>
                        </div>

                        {{-- Scale Slider --}}
                        <div class="flex items-center gap-2">
                            <span class="text-[11px] text-slate-400">เล็ก</span>
                            <input type="range" min="0.6" max="1.8" step="0.1" x-model.number="sigScale" class="w-full accent-brand-600 cursor-pointer">
                            <span class="text-[11px] text-slate-400">ใหญ่</span>
                        </div>

                        {{-- PLACE ON PDF CTA BUTTON --}}
                        <button type="button"
                                @click="placeSignature()"
                                :disabled="!pdfLoaded || !activeSigDataUrl"
                                class="w-full btn-primary py-3 rounded-xl text-sm font-semibold flex items-center justify-center gap-2 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer transition-all active:scale-98">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                            </svg>
                            <span>นำลายเซ็นไปวางบนหน้า <span x-text="currentPage"></span></span>
                        </button>

                        <p class="text-[11px] text-slate-400 text-center leading-relaxed">
                            💡 คุณยังสามารถ <strong>คลิกตรงจุดที่ต้องการบนเอกสาร PDF</strong> เพื่อวางลายเซ็นได้ทันที จากนั้นสามารถลากย้ายตำแหน่งและดึงมุมเพื่อย่อ-ขยายได้อย่างอิสระ
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
