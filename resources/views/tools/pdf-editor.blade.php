@extends('layouts.app')

@section('title', 'PDF Editor (Pro) — แก้ไขและจัดการเอกสาร PDF ครบวงจร | PDFkrub')
@section('description', 'โปรแกรมแก้ไข PDF ออนไลน์ระดับมืออาชีพ วาดเขียน ไฮไลท์ พิมพ์ข้อความ แปะโน้ต ประทับตรา จัดการหน้าเอกสาร และค้นหาข้อความ ทำงานบนเบราว์เซอร์ 100%')

@push('head')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alex+Brush&family=Dancing+Script:wght@600;700&family=Great+Vibes&family=Pacifico&family=Kanit:wght@300;400;500;600;700&family=Prompt:wght@300;400;500;600;700&family=Sarabun:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600;1,700&family=Niramit:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400;1,600&family=Chakra+Petch:wght@400;600&family=Mitr:wght@400;500&display=swap" rel="stylesheet">
    <script src="/vendor/pdfjs/pdf.min.js"></script>
    <script src="/vendor/pdf-lib.min.js"></script>
    <style>
        @font-face {
            font-family: 'TH Niramit AS';
            src: local('TH Niramit AS'), local('Niramit');
        }
        @font-face {
            font-family: 'TH Sarabun PSK';
            src: local('TH Sarabun PSK'), local('TH Sarabun New'), local('Sarabun');
        }
        .editor-checkerboard {
            background-color: #f1f5f9;
            background-image: radial-gradient(#cbd5e1 1px, transparent 1px);
            background-size: 16px 16px;
        }
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: rgba(241, 245, 249, 0.5);
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
@endpush

@section('content')
<div class="max-w-[1600px] mx-auto px-2 sm:px-4 py-4"
     x-data="pdfEditor()"
     x-init="init()">

    {{-- Header / Breadcrumb Bar --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-3 px-2">
        <div class="flex items-center gap-3">
            <a href="{{ route('tools') }}" class="inline-flex items-center gap-1.5 text-xs text-slate-500 hover:text-brand-600 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
                <span>กลับหน้าเครื่องมือ</span>
            </a>
            <span class="text-slate-300">|</span>
            <div class="flex items-center gap-2">
                <span class="text-base font-bold text-slate-800">📝 PDF Editor</span>
                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-gradient-to-r from-amber-500 to-amber-600 text-white shadow-xs">PRO</span>
            </div>
            <span class="text-xs text-slate-400 font-mono hidden sm:inline" x-text="pdfFileName"></span>
        </div>

        {{-- Browser-only Privacy Notice --}}
        <div class="flex items-center gap-2 text-xs text-slate-500 bg-white border border-slate-200 px-3 py-1.5 rounded-lg shadow-2xs">
            <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/>
            </svg>
            <span class="hidden md:inline">แก้ไขบนเบราว์เซอร์ 100% · ปลอดภัย ไม่ส่งไฟล์ขึ้นเซิร์ฟเวอร์</span>
            <span class="md:hidden">ประมวลผลบนเครื่อง 100%</span>
        </div>
    </div>

    {{-- Premium Gate Notice if not subscribed --}}
    @if(!auth()->check() || !auth()->user()->getActivePlan()->has_esign)
    <div class="mb-4 p-3 bg-gradient-to-r from-amber-50 via-amber-100/70 to-amber-50 border border-amber-300/80 rounded-xl flex items-center justify-between gap-4 text-xs">
        <div class="flex items-center gap-2.5">
            <span class="text-lg">⭐</span>
            <span class="text-amber-900 font-medium">คุณกำลังทดลองใช้งาน <strong>PDF Editor (Pro)</strong> — เพื่อปลดล็อกการใช้งานและดาวน์โหลดไฟล์ไม่จำกัด กรุณาสมัครสมาชิก Pro</span>
        </div>
        <a href="{{ route('pricing') }}" class="shrink-0 px-3.5 py-1.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-semibold rounded-lg shadow-sm transition-all text-xs">
            อัปเกรดเป็น Pro
        </a>
    </div>
    @endif

    {{-- Hidden File Input for PDF --}}
    <input type="file" id="pdfEditorFileInput" accept=".pdf,application/pdf" class="hidden" @change="handleFileInput($event)">
    {{-- Hidden File Input for Picture / Image insertion --}}
    <input type="file" id="pdfEditorImageInput" accept="image/png,image/jpeg,image/webp,image/gif" class="hidden" @change="handleImageInput($event)">

    {{-- ══════════════════════════════════════════════════════════════════ --}}
    {{-- MAIN EDITOR CONTAINER --}}
    {{-- ══════════════════════════════════════════════════════════════════ --}}
    <div class="bg-white rounded-2xl shadow-xl border border-slate-200/90 overflow-hidden flex flex-col h-[84vh] min-h-[700px]">

        {{-- ─── 1. TOP RIBBON TABS (Document | Organize Pages | Edit Content | Security | Form Editor) ─── --}}
        <div class="bg-slate-100/95 border-b border-slate-200 px-3 pt-1.5 flex items-center justify-between gap-2 shrink-0 select-none">
            <div class="flex items-center gap-1 overflow-x-auto text-xs font-medium">
                {{-- Quick Save Icon --}}
                <button type="button" @click="saveAndDownloadPdf()" class="p-1.5 mr-1 text-slate-600 hover:text-brand-600 hover:bg-white rounded-lg cursor-pointer transition-colors" title="บันทึกเอกสาร (Save PDF)">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16v4m0 0H7m10 0v-4m-10 4v-4m0 4h10m1-16H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.414a2 2 0 0 0-.586-1.414l-4.414-4.414A2 2 0 0 0 13.586 1H6Z"/></svg>
                </button>

                {{-- Tab 1: Document --}}
                <button type="button"
                        @click="setRibbonTab('document')"
                        :class="activeRibbonTab === 'document' ? 'bg-white text-brand-700 font-bold border-b-2 border-brand-600 shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/60'"
                        class="px-3 py-1.5 rounded-t-lg flex items-center gap-1.5 transition-all cursor-pointer">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    <span>Document</span>
                </button>

                {{-- Tab 2: Organize Pages --}}
                <button type="button"
                        @click="setRibbonTab('organize')"
                        :class="activeRibbonTab === 'organize' ? 'bg-white text-brand-700 font-bold border-b-2 border-brand-600 shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/60'"
                        class="px-3 py-1.5 rounded-t-lg flex items-center gap-1.5 transition-all cursor-pointer">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                    <span>Organize Pages</span>
                </button>

                {{-- Tab 3: Edit Content (🔴 Highlighted & Circled matching user screenshot!) --}}
                <button type="button"
                        @click="setRibbonTab('edit-content')"
                        :class="activeRibbonTab === 'edit-content' ? 'bg-white text-brand-600 font-extrabold border-b-2 border-brand-600 shadow-2xs ring-1 ring-brand-500/20' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-200/60'"
                        class="px-3.5 py-1.5 rounded-t-lg flex items-center gap-1.5 transition-all cursor-pointer relative group">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="text-xs font-bold text-brand-700">Edit Content</span>
                    <span class="px-1.5 py-0.2 bg-red-100 text-red-700 font-extrabold text-[9px] rounded-full">แก้ไขเนื้อหา</span>
                </button>

                {{-- Tab 4: Security --}}
                <button type="button"
                        @click="setRibbonTab('security')"
                        :class="activeRibbonTab === 'security' ? 'bg-white text-brand-700 font-bold border-b-2 border-brand-600 shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/60'"
                        class="px-3 py-1.5 rounded-t-lg flex items-center gap-1.5 transition-all cursor-pointer">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                    <span>Security</span>
                </button>

                {{-- Tab 5: Form Editor --}}
                <button type="button"
                        @click="setRibbonTab('form')"
                        :class="activeRibbonTab === 'form' ? 'bg-white text-brand-700 font-bold border-b-2 border-brand-600 shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/60'"
                        class="px-3 py-1.5 rounded-t-lg flex items-center gap-1.5 transition-all cursor-pointer">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                    <span>Form Editor</span>
                </button>
            </div>

            {{-- Right: Quick File Actions --}}
            <div class="flex items-center gap-1.5 pb-1">
                <button type="button" @click="createNewTask()" class="px-2 py-1 rounded text-xs text-slate-700 hover:bg-slate-200 flex items-center gap-1 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    <span class="hidden sm:inline">งานใหม่</span>
                </button>
                <button type="button" @click="triggerOpenDialog()" class="px-2 py-1 rounded text-xs text-slate-700 hover:bg-slate-200 flex items-center gap-1 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 0 0-1.883 2.542l.857 6a2.25 2.25 0 0 0 2.227 1.932H19.05a2.25 2.25 0 0 0 2.227-1.932l.857-6a2.25 2.25 0 0 0-1.883-2.542m-16.5 0V6A2.25 2.25 0 0 1 6 3.75h3.879a1.5 1.5 0 0 1 1.06.44l2.122 2.12a1.5 1.5 0 0 0 1.06.44H18A2.25 2.25 0 0 1 20.25 9v.776"/></svg>
                    <span class="hidden sm:inline">เปิดไฟล์</span>
                </button>
                <button type="button" @click="printDocument()" class="p-1 rounded text-slate-700 hover:bg-slate-200 cursor-pointer" title="พิมพ์ (Print)">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z"/></svg>
                </button>
                <button type="button" @click="saveAndDownloadPdf()" :disabled="isExporting" class="px-3 py-1 rounded-lg text-xs font-bold bg-brand-600 hover:bg-brand-700 text-white shadow-xs flex items-center gap-1.5 cursor-pointer transition-all disabled:opacity-50">
                    <svg x-show="!isExporting" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                    <span x-text="isExporting ? 'กำลังบันทึก...' : 'ดาวน์โหลด PDF'"></span>
                </button>
            </div>
        </div>

        {{-- ─── SECONDARY CONTEXTUAL TOOLBAR ─── --}}
        <div class="bg-white border-b border-slate-200/80 px-3 py-1.5 flex flex-wrap items-center justify-between text-xs gap-2 shrink-0 select-none">
            
            {{-- ═══ RIBBON 1: EDIT CONTENT (ตรงตามภาพหน้าจอของผู้ใช้!) ═══ --}}
            <div x-show="activeRibbonTab === 'edit-content'" class="flex items-center gap-1.5 sm:gap-2 flex-wrap" x-cloak>
                {{-- Header and Footer dropdown --}}
                <div class="relative" x-data="{ hfOpen: false }">
                    <button type="button"
                            @click="hfOpen = !hfOpen"
                            class="px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100 border border-slate-200 flex items-center gap-1 cursor-pointer">
                        <span>Header and Footer</span>
                        <svg class="w-3 h-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    </button>
                    <div x-show="hfOpen" @click.away="hfOpen = false" x-cloak class="absolute left-0 mt-1 w-48 bg-white border border-slate-200 rounded-lg shadow-xl z-50 py-1 text-xs">
                        <button type="button" @click="addHeaderFooterText('header'); hfOpen = false;" class="w-full text-left px-3 py-1.5 hover:bg-slate-100 flex items-center gap-2 text-slate-700">
                            <span>🔝 เพิ่มหัวกระดาษ (Header)</span>
                        </button>
                        <button type="button" @click="addHeaderFooterText('footer'); hfOpen = false;" class="w-full text-left px-3 py-1.5 hover:bg-slate-100 flex items-center gap-2 text-slate-700">
                            <span>🔚 เพิ่มท้ายกระดาษ (Footer)</span>
                        </button>
                    </div>
                </div>

                <div class="h-4 w-px bg-slate-200"></div>

                {{-- Picture (Insert Image) --}}
                <button type="button"
                        @click="triggerImageDialog()"
                        class="px-2.5 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100 border border-slate-200 flex items-center gap-1.5 cursor-pointer"
                        title="แทรกรูปภาพลงในเอกสาร (Picture)">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                    <span>Picture</span>
                </button>

                {{-- Add Text --}}
                <button type="button"
                        @click="setTool('text')"
                        :class="activeTool === 'text' ? 'bg-brand-50 border-brand-500 text-brand-700 font-bold' : 'text-slate-700 hover:bg-slate-100 border-slate-200'"
                        class="px-2.5 py-1 rounded text-xs font-medium border flex items-center gap-1.5 cursor-pointer"
                        title="เพิ่มกล่องข้อความใหม่ (Add Text)">
                    <svg class="w-4 h-4 text-brand-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15M4.5 7.5V6a2.25 2.25 0 0 1 2.25-2.25h10.5A2.25 2.25 0 0 1 19.5 6v1.5"/></svg>
                    <span>Add Text</span>
                </button>

                <div class="h-4 w-px bg-slate-200"></div>

                {{-- Font Family Dropdown with TH Niramit AS selected as default --}}
                <div class="flex items-center" title="แบบอักษรภาษาไทย">
                    <select x-model="textFontFamily"
                            @change="updateSelectedTextProp('fontFamily', textFontFamily)"
                            class="border border-slate-300 rounded px-2 py-1 text-xs bg-white text-slate-800 font-medium focus:ring-1 focus:ring-brand-500">
                        <option value="TH Niramit AS">TH Niramit AS</option>
                        <option value="TH Sarabun PSK">TH Sarabun PSK</option>
                        <option value="Sarabun">สารบัญ (Sarabun)</option>
                        <option value="Kanit">คณิต (Kanit)</option>
                        <option value="Prompt">พร้อมพ์ (Prompt)</option>
                        <option value="Chakra Petch">Chakra Petch</option>
                        <option value="Mitr">Mitr</option>
                        <option value="sans-serif">Sans-serif มาตรฐาน</option>
                    </select>
                </div>

                {{-- Font Size Dropdown (e.g. 12pt default) --}}
                <div class="flex items-center" title="ขนาดตัวอักษร">
                    <select x-model.number="textSize"
                            @change="updateSelectedTextProp('fontSize', textSize)"
                            class="border border-slate-300 rounded px-1.5 py-1 text-xs bg-white text-slate-800 font-medium focus:ring-1 focus:ring-brand-500 w-16">
                        <option value="8">8</option>
                        <option value="9">9</option>
                        <option value="10">10</option>
                        <option value="11">11</option>
                        <option value="12">12</option>
                        <option value="14">14</option>
                        <option value="16">16</option>
                        <option value="18">18</option>
                        <option value="20">20</option>
                        <option value="22">22</option>
                        <option value="24">24</option>
                        <option value="28">28</option>
                        <option value="32">32</option>
                        <option value="36">36</option>
                        <option value="48">48</option>
                    </select>
                </div>

                {{-- A^ / A_ Font Size Step Buttons --}}
                <div class="flex items-center gap-0.5">
                    <button type="button" @click="increaseFontSize()" class="px-1.5 py-1 rounded hover:bg-slate-100 text-slate-700 font-bold text-xs cursor-pointer border border-slate-200" title="เพิ่มขนาดตัวอักษร">A<span class="text-[9px] align-super">▲</span></button>
                    <button type="button" @click="decreaseFontSize()" class="px-1.5 py-1 rounded hover:bg-slate-100 text-slate-700 font-bold text-xs cursor-pointer border border-slate-200" title="ลดขนาดตัวอักษร">A<span class="text-[9px] align-sub">▼</span></button>
                </div>

                <div class="h-4 w-px bg-slate-200"></div>

                {{-- Style: B, I, U --}}
                <div class="flex items-center gap-0.5 bg-slate-100 p-0.5 rounded border border-slate-200">
                    <button type="button"
                            @click="toggleTextProp('bold')"
                            :class="textBold ? 'bg-white shadow-2xs text-brand-600 font-extrabold' : 'text-slate-600 hover:text-slate-900'"
                            class="w-6 h-6 rounded flex items-center justify-center font-bold text-xs cursor-pointer"
                            title="ตัวหนา (Bold)">B</button>
                    <button type="button"
                            @click="toggleTextProp('italic')"
                            :class="textItalic ? 'bg-white shadow-2xs text-brand-600 font-extrabold' : 'text-slate-600 hover:text-slate-900'"
                            class="w-6 h-6 rounded flex items-center justify-center italic font-serif text-xs cursor-pointer"
                            title="ตัวเอียง (Italic)">I</button>
                    <button type="button"
                            @click="toggleTextProp('underline')"
                            :class="textUnderline ? 'bg-white shadow-2xs text-brand-600 font-extrabold' : 'text-slate-600 hover:text-slate-900'"
                            class="w-6 h-6 rounded flex items-center justify-center underline text-xs cursor-pointer"
                            title="ขีดเส้นใต้ (Underline)">U</button>
                </div>

                <div class="h-4 w-px bg-slate-200"></div>

                {{-- Alignment: Left, Center, Right, Justify --}}
                <div class="flex items-center gap-0.5 bg-slate-100 p-0.5 rounded border border-slate-200">
                    <button type="button" @click="setTextAlign('left')" :class="textAlign === 'left' ? 'bg-white shadow-2xs text-brand-600' : 'text-slate-600 hover:text-slate-900'" class="w-6 h-6 rounded flex items-center justify-center cursor-pointer" title="จัดชิดซ้าย">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h10.5m-10.5 5.25h16.5"/></svg>
                    </button>
                    <button type="button" @click="setTextAlign('center')" :class="textAlign === 'center' ? 'bg-white shadow-2xs text-brand-600' : 'text-slate-600 hover:text-slate-900'" class="w-6 h-6 rounded flex items-center justify-center cursor-pointer" title="จัดกึ่งกลาง">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M6.75 12h10.5m-13.5 5.25h16.5"/></svg>
                    </button>
                    <button type="button" @click="setTextAlign('right')" :class="textAlign === 'right' ? 'bg-white shadow-2xs text-brand-600' : 'text-slate-600 hover:text-slate-900'" class="w-6 h-6 rounded flex items-center justify-center cursor-pointer" title="จัดชิดขวา">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M9.75 12h10.5m-16.5 5.25h16.5"/></svg>
                    </button>
                </div>

                {{-- Line Spacing --}}
                <div class="flex items-center" title="ระยะห่างบรรทัด (Line Spacing)">
                    <select x-model="textLineHeight" @change="updateSelectedTextProp('lineHeight', textLineHeight)" class="border border-slate-300 rounded px-1.5 py-1 text-xs bg-white text-slate-800 font-medium focus:ring-1 focus:ring-brand-500">
                        <option value="1.15">บรรทัด 1.15</option>
                        <option value="1.3">บรรทัด 1.30</option>
                        <option value="1.45">บรรทัด 1.45</option>
                        <option value="1.6">บรรทัด 1.60</option>
                        <option value="1.8">บรรทัด 1.80</option>
                        <option value="2.0">บรรทัด 2.00</option>
                    </select>
                </div>

                <div class="h-4 w-px bg-slate-200"></div>

                {{-- Text Color A --}}
                <div class="flex items-center" title="สีตัวอักษร">
                    <label class="flex items-center gap-1 cursor-pointer px-1.5 py-0.5 rounded border border-slate-200 hover:bg-slate-50">
                        <span class="font-extrabold text-xs" :style="`color: ${textColor}`">A</span>
                        <input type="color" x-model="textColor" @change="updateSelectedTextProp('color', textColor)" class="w-3.5 h-3.5 rounded border-0 p-0 cursor-pointer">
                    </label>
                </div>

                {{-- Cover Original Text (Whiteout) Toggle --}}
                <div class="flex items-center">
                    <button type="button"
                            @click="toggleTextCover()"
                            :class="textBgColor === '#ffffff' ? 'bg-emerald-50 border-emerald-500 text-emerald-700 font-semibold' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50'"
                            class="px-2 py-1 rounded border text-[11px] flex items-center gap-1 cursor-pointer"
                            title="ปิดทับข้อความเดิมด้านหลังอัตโนมัติด้วยสีขาว">
                        <span class="w-2 h-2 rounded-xs" :class="textBgColor === '#ffffff' ? 'bg-emerald-500' : 'bg-slate-300'"></span>
                        <span>ปิดทับข้อความเดิม</span>
                    </button>
                </div>

                {{-- Text Group Mode Toggle --}}
                <div class="flex items-center gap-0.5 bg-slate-100 p-0.5 rounded border border-slate-200" title="โหมดตรวจจับข้อความเดิม">
                    <button type="button" @click="setTextGroupMode('line')" :class="textGroupMode === 'line' ? 'bg-white shadow-2xs text-brand-600 font-bold' : 'text-slate-600 hover:text-slate-900'" class="px-2 py-0.5 rounded text-[11px] cursor-pointer">
                        ทีละบรรทัด (คงต้นฉบับ 100%)
                    </button>
                    <button type="button" @click="setTextGroupMode('block')" :class="textGroupMode === 'block' ? 'bg-white shadow-2xs text-brand-600 font-bold' : 'text-slate-600 hover:text-slate-900'" class="px-1.5 py-0.5 rounded text-[11px] cursor-pointer">
                        กลุ่มย่อหน้า
                    </button>
                </div>

                                {{-- Auto-Fix Thai Text Button --}}
                <div class="flex items-center">
                    <button type="button"
                            @click="autoFixThaiText()"
                            class="px-2.5 py-1 rounded border border-amber-300 bg-amber-50 hover:bg-amber-100 text-amber-900 font-semibold text-xs flex items-center gap-1.5 cursor-pointer shadow-2xs transition-all"
                            title="แก้สระลอย วรรณยุกต์ซ้อนตัวอักษร ตัดช่องว่างในคำ และแปลงรหัสภาษาไทยเพี้ยน">
                        <span class="text-xs">✨</span>
                        <span>แก้ภาษาไทยเพี้ยน</span>
                    </button>
                </div>

                <div class="h-4 w-px bg-slate-200"></div>
                {{-- Undo / Redo in Ribbon --}}
                <div class="flex items-center gap-0.5">
                    <button type="button" @click="undo()" :disabled="!canUndo" class="p-1 rounded text-slate-700 hover:bg-slate-100 disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer" title="Undo (Ctrl+Z)">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3"/></svg>
                    </button>
                    <button type="button" @click="redo()" :disabled="!canRedo" class="p-1 rounded text-slate-700 hover:bg-slate-100 disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer" title="Redo (Ctrl+Y)">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m15 15 6-6m0 0-6-6m6 6H9a6 6 0 0 0 0 12h3"/></svg>
                    </button>
                </div>
            </div>

            {{-- ═══ RIBBON 2: DOCUMENT (เครื่องมือวาดเขียน ไฮไลท์ โน้ต ตราประทับ ไวท์เอาท์) ═══ --}}
            <div x-show="activeRibbonTab === 'document'" class="flex items-center gap-1 sm:gap-2 flex-wrap" x-cloak>
                {{-- 1. Pointer --}}
                <button type="button" @click="setTool('pointer')" :class="activeTool === 'pointer' ? 'bg-brand-50 border-brand-500 text-brand-700 font-bold' : 'text-slate-700 hover:bg-slate-100 border-slate-200'" class="px-2 py-1 rounded text-xs border flex items-center gap-1 cursor-pointer" title="เลือกวัตถุและแก้ไข (Pointer)">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.042 21.672 13.684 16.6m0 0-2.51 2.225.569-9.47 5.227 7.917-3.286-.672ZM12 2.25V4.5m5.834.166-1.591 1.591M20.25 10.5H18M7.757 14.743l-1.59 1.59M6 10.5H3.75m4.007-4.243-1.59-1.59"/></svg>
                    <span>เลือก</span>
                </button>

                {{-- 2. Hand --}}
                <button type="button" @click="setTool('hand')" :class="activeTool === 'hand' ? 'bg-brand-50 border-brand-500 text-brand-700 font-bold' : 'text-slate-700 hover:bg-slate-100 border-slate-200'" class="px-2 py-1 rounded text-xs border flex items-center gap-1 cursor-pointer" title="เลื่อนหน้าเอกสาร (Hand)">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.05 4.575a1.575 1.575 0 1 0-3.15 0v3m3.15-3v-1.5a1.575 1.575 0 0 1 3.15 0v1.5m-3.15 0 .075 5.925m3.075.075V4.575m0 0a1.575 1.575 0 0 1 3.15 0V15M6.9 7.575a1.575 1.575 0 1 0-3.15 0v8.175a6.75 6.75 0 0 0 6.75 6.75h2.018a5.25 5.25 0 0 0 3.712-1.538l1.732-1.732a5.25 5.25 0 0 0 1.538-3.712l.003-2.024a.66.66 0 0 1 .66-.66h.024a.66.66 0 0 0 .66-.66V9.75a1.575 1.575 0 0 0-3.15 0v2.25"/></svg>
                    <span>เลื่อน</span>
                </button>

                {{-- 3. Whiteout --}}
                <button type="button" @click="setTool('whiteout')" :class="activeTool === 'whiteout' ? 'bg-brand-50 border-brand-500 text-brand-700 font-bold' : 'text-slate-700 hover:bg-slate-100 border-slate-200'" class="px-2 py-1 rounded text-xs border flex items-center gap-1 cursor-pointer" title="ลบข้อความ / ไวท์เอาท์">
                    <span class="w-3 h-3 rounded-xs bg-white border border-slate-400"></span>
                    <span>ลบข้อความ</span>
                </button>

                {{-- 4. Draw --}}
                <button type="button" @click="setTool('draw')" :class="activeTool === 'draw' ? 'bg-brand-50 border-brand-500 text-brand-700 font-bold' : 'text-slate-700 hover:bg-slate-100 border-slate-200'" class="px-2 py-1 rounded text-xs border flex items-center gap-1 cursor-pointer" title="วาดเขียนอิสระ">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125"/></svg>
                    <span>วาดเขียน</span>
                </button>

                {{-- 5. Highlight --}}
                <button type="button" @click="setTool('highlight')" :class="activeTool === 'highlight' ? 'bg-brand-50 border-brand-500 text-brand-700 font-bold' : 'text-slate-700 hover:bg-slate-100 border-slate-200'" class="px-2 py-1 rounded text-xs border flex items-center gap-1 cursor-pointer" title="ไฮไลท์เน้นข้อความ">
                    <svg class="w-3.5 h-3.5 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/></svg>
                    <span>ไฮไลท์</span>
                </button>

                {{-- 6. Sticky Note --}}
                <button type="button" @click="setTool('note')" :class="activeTool === 'note' ? 'bg-brand-50 border-brand-500 text-brand-700 font-bold' : 'text-slate-700 hover:bg-slate-100 border-slate-200'" class="px-2 py-1 rounded text-xs border flex items-center gap-1 cursor-pointer" title="แปะโน้ต / ความคิดเห็น">
                    <span>💬</span>
                    <span>แปะโน้ต</span>
                </button>

                {{-- 7. Stamp --}}
                <button type="button" @click="setTool('stamp')" :class="activeTool === 'stamp' ? 'bg-brand-50 border-brand-500 text-brand-700 font-bold' : 'text-slate-700 hover:bg-slate-100 border-slate-200'" class="px-2 py-1 rounded text-xs border flex items-center gap-1 cursor-pointer" title="ประทับตรา">
                    <span class="text-emerald-600 font-bold">✓</span>
                    <span>ตราประทับ</span>
                </button>

                {{-- Draw Properties --}}
                <template x-if="activeTool === 'draw'">
                    <div class="flex items-center gap-2 pl-2 border-l border-slate-200">
                        <span class="text-slate-500 text-[11px]">สี:</span>
                        <template x-for="c in ['#dc2626', '#2563eb', '#16a34a', '#111827', '#eab308']" :key="c">
                            <button type="button" @click="drawColor = c" class="w-4 h-4 rounded-full border" :style="`background-color: ${c}`" :class="drawColor === c ? 'ring-2 ring-brand-500' : ''"></button>
                        </template>
                        <span class="text-slate-500 text-[11px] ml-1">ขนาด:</span>
                        <template x-for="sz in [2, 3, 5]" :key="sz">
                            <button type="button" @click="drawWidth = sz" :class="drawWidth === sz ? 'bg-slate-200 font-bold text-brand-600' : 'text-slate-600 hover:bg-slate-100'" class="px-1.5 py-0.5 rounded text-[11px]" x-text="`${sz}px`"></button>
                        </template>
                    </div>
                </template>

                {{-- Highlight Properties --}}
                <template x-if="activeTool === 'highlight'">
                    <div class="flex items-center gap-2 pl-2 border-l border-slate-200">
                        <span class="text-slate-500 text-[11px]">สีไฮไลท์:</span>
                        <template x-for="c in ['#fde047', '#86efac', '#f9a8d4', '#93c5fd']" :key="c">
                            <button type="button" @click="highlightColor = c" class="w-4 h-4 rounded-full border" :style="`background-color: ${c}`" :class="highlightColor === c ? 'ring-2 ring-brand-500' : ''"></button>
                        </template>
                    </div>
                </template>

                {{-- Stamp Properties --}}
                <template x-if="activeTool === 'stamp'">
                    <div class="flex items-center gap-2 pl-2 border-l border-slate-200">
                        <select x-model="activeStampPreset" class="border border-slate-200 rounded px-1.5 py-0.5 text-xs bg-white text-slate-700">
                            <option value="APPROVED">APPROVED (อนุมัติ)</option>
                            <option value="DRAFT">DRAFT (ร่าง)</option>
                            <option value="CONFIDENTIAL">CONFIDENTIAL (ลับเฉพาะ)</option>
                            <option value="VERIFIED">สำเนาถูกต้อง</option>
                            <option value="CUSTOM">กำหนดข้อความเอง...</option>
                        </select>
                        <template x-if="activeStampPreset === 'CUSTOM'">
                            <input type="text" x-model="customStampText" placeholder="ข้อความตราประทับ" class="border border-slate-200 rounded px-1.5 py-0.5 text-xs w-28">
                        </template>
                    </div>
                </template>
            </div>

            {{-- ═══ RIBBON 3: ORGANIZE PAGES ═══ --}}
            <div x-show="activeRibbonTab === 'organize'" class="flex items-center gap-2 flex-wrap" x-cloak>
                <button type="button" @click="addBlankPage('before')" class="px-2.5 py-1 rounded text-xs border border-slate-200 hover:bg-slate-100 text-slate-700 flex items-center gap-1 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    <span>แทรกหน้าว่างก่อนหน้านี้</span>
                </button>
                <button type="button" @click="addBlankPage('after')" class="px-2.5 py-1 rounded text-xs border border-slate-200 hover:bg-slate-100 text-slate-700 flex items-center gap-1 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    <span>แทรกหน้าว่างต่อท้าย</span>
                </button>
                <div class="h-4 w-px bg-slate-200"></div>
                <button type="button" @click="rotateCurrentPage(90)" class="px-2.5 py-1 rounded text-xs border border-slate-200 hover:bg-slate-100 text-slate-700 flex items-center gap-1 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                    <span>หมุน 90° ตามเข็ม</span>
                </button>
                <button type="button" @click="deleteCurrentPage()" class="px-2.5 py-1 rounded text-xs border border-red-200 text-red-600 hover:bg-red-50 flex items-center gap-1 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                    <span>ลบหน้านี้</span>
                </button>
            </div>

            {{-- ═══ RIBBON 4: SECURITY ═══ --}}
            <div x-show="activeRibbonTab === 'security'" class="flex items-center gap-3 text-xs text-slate-600" x-cloak>
                <div class="flex items-center gap-1.5 text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded border border-emerald-200">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/></svg>
                    <span>ความปลอดภัยระดับเบราว์เซอร์: ไฟล์ของคุณไม่ถูกอัปโหลดขึ้นเซิร์ฟเวอร์</span>
                </div>
                <a href="{{ route('tools.protect-pdf') }}" class="text-brand-600 hover:underline flex items-center gap-1">
                    <span>ต้องการตั้งรหัสผ่านล็อกไฟล์ PDF? คลิกที่นี่</span>
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </a>
            </div>

            {{-- ═══ RIBBON 5: FORM EDITOR ═══ --}}
            <div x-show="activeRibbonTab === 'form'" class="flex items-center gap-2 flex-wrap" x-cloak>
                <button type="button" @click="addFormField('text')" class="px-2.5 py-1 rounded text-xs border border-slate-200 hover:bg-slate-100 text-slate-700 flex items-center gap-1 cursor-pointer">
                    <span>🔲 เพิ่มช่องกรอกข้อความ</span>
                </button>
                <button type="button" @click="addFormField('checkbox')" class="px-2.5 py-1 rounded text-xs border border-slate-200 hover:bg-slate-100 text-slate-700 flex items-center gap-1 cursor-pointer">
                    <span>☑️ เพิ่มช่องเช็คถูก</span>
                </button>
                <button type="button" @click="addFormField('signature')" class="px-2.5 py-1 rounded text-xs border border-slate-200 hover:bg-slate-100 text-slate-700 flex items-center gap-1 cursor-pointer">
                    <span>✍️ เพิ่มช่องลายเซ็น</span>
                </button>
            </div>

            {{-- Right: Delete selected item button --}}
            <div class="flex items-center gap-2 ml-auto">
                <template x-if="selectedAnnotationId">
                    <button type="button"
                            @click="deleteSelectedAnnotation()"
                            class="px-2.5 py-1 rounded bg-red-50 text-red-600 hover:bg-red-100 border border-red-200 flex items-center gap-1 cursor-pointer transition-colors text-xs">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                        <span>ลบรายการที่เลือก</span>
                    </button>
                </template>
            </div>
        </div>

        {{-- ─── WORKSPACE BODY (LEFT SIDEBAR + CENTER PDF VIEW) ─── --}}
        <div class="flex flex-1 overflow-hidden relative">

            {{-- 1. VERTICAL ICON STRIP --}}
            <div class="w-12 bg-slate-100/90 border-r border-slate-200 flex flex-col items-center py-2.5 gap-2 shrink-0 select-none z-10">
                {{-- Tab 1: Pages / Thumbnails --}}
                <button type="button"
                        @click="if (activeSidebarTab === 'pages' && !sidebarCollapsed) { sidebarCollapsed = true; } else { activeSidebarTab = 'pages'; sidebarCollapsed = false; }"
                        :class="!sidebarCollapsed && activeSidebarTab === 'pages' ? 'bg-white text-brand-600 shadow-xs border border-slate-200' : 'text-slate-500 hover:text-slate-800 hover:bg-slate-200/60'"
                        class="w-9 h-9 rounded-xl flex items-center justify-center cursor-pointer transition-colors"
                        title="หน้าเอกสารทั้งหมด (Pages)">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                </button>

                {{-- Tab 2: Bookmarks / Outline --}}
                <button type="button"
                        @click="if (activeSidebarTab === 'bookmarks' && !sidebarCollapsed) { sidebarCollapsed = true; } else { activeSidebarTab = 'bookmarks'; sidebarCollapsed = false; }"
                        :class="!sidebarCollapsed && activeSidebarTab === 'bookmarks' ? 'bg-white text-brand-600 shadow-xs border border-slate-200' : 'text-slate-500 hover:text-slate-800 hover:bg-slate-200/60'"
                        class="w-9 h-9 rounded-xl flex items-center justify-center cursor-pointer transition-colors"
                        title="สารบัญ (Bookmarks)">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z"/></svg>
                </button>

                {{-- Tab 3: Search --}}
                <button type="button"
                        @click="if (activeSidebarTab === 'search' && !sidebarCollapsed) { sidebarCollapsed = true; } else { activeSidebarTab = 'search'; sidebarCollapsed = false; }"
                        :class="!sidebarCollapsed && activeSidebarTab === 'search' ? 'bg-white text-brand-600 shadow-xs border border-slate-200' : 'text-slate-500 hover:text-slate-800 hover:bg-slate-200/60'"
                        class="w-9 h-9 rounded-xl flex items-center justify-center cursor-pointer transition-colors"
                        title="ค้นหาข้อความในเอกสาร (Search)">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                </button>

                {{-- Tab 4: Attachments / Info --}}
                <button type="button"
                        @click="if (activeSidebarTab === 'attachments' && !sidebarCollapsed) { sidebarCollapsed = true; } else { activeSidebarTab = 'attachments'; sidebarCollapsed = false; }"
                        :class="!sidebarCollapsed && activeSidebarTab === 'attachments' ? 'bg-white text-brand-600 shadow-xs border border-slate-200' : 'text-slate-500 hover:text-slate-800 hover:bg-slate-200/60'"
                        class="w-9 h-9 rounded-xl flex items-center justify-center cursor-pointer transition-colors"
                        title="ไฟล์แนบและข้อมูลเอกสาร (Attachments)">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13"/></svg>
                </button>

                {{-- Tab 5: Comments --}}
                <button type="button"
                        @click="if (activeSidebarTab === 'comments' && !sidebarCollapsed) { sidebarCollapsed = true; } else { activeSidebarTab = 'comments'; sidebarCollapsed = false; }"
                        :class="!sidebarCollapsed && activeSidebarTab === 'comments' ? 'bg-white text-brand-600 shadow-xs border border-slate-200' : 'text-slate-500 hover:text-slate-800 hover:bg-slate-200/60'"
                        class="w-9 h-9 rounded-xl flex items-center justify-center cursor-pointer transition-colors relative"
                        title="ความคิดเห็นและโน้ตทั้งหมด (Comments)">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.502 49.177 49.177 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z"/></svg>
                    <template x-if="annotations.filter(a => a.type === 'note').length > 0">
                        <span class="absolute top-1 right-1 w-2 h-2 rounded-full bg-brand-600"></span>
                    </template>
                </button>

                <div class="mt-auto">
                    <button type="button"
                            @click="sidebarCollapsed = !sidebarCollapsed"
                            class="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-700 flex items-center justify-center cursor-pointer transition-colors"
                            :title="sidebarCollapsed ? 'ขยายแถบด้านข้าง' : 'ย่อแถบด้านข้าง'">
                        <svg class="w-4 h-4 transition-transform duration-200" :class="sidebarCollapsed ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
                    </button>
                </div>
            </div>

            {{-- 2. COLLAPSIBLE SUB-PANEL --}}
            <div x-show="!sidebarCollapsed"
                 x-cloak
                 class="w-64 bg-slate-50 border-r border-slate-200 flex flex-col shrink-0 select-none overflow-hidden transition-all duration-200">
                
                {{-- ── TAB 1: PAGES / THUMBNAILS ── --}}
                <div x-show="activeSidebarTab === 'pages'" class="flex flex-col h-full">
                    <div class="p-3 border-b border-slate-200 flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-800">หน้าเอกสาร (<span x-text="totalPages"></span>)</span>
                        <div class="flex items-center gap-1">
                            <button type="button" @click="addBlankPage('after')" class="p-1 rounded hover:bg-slate-200 text-slate-600 cursor-pointer" title="แทรกหน้าว่างต่อจากหน้านี้">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                            </button>
                            <button type="button" @click="deleteCurrentPage()" class="p-1 rounded hover:bg-red-100 text-red-500 cursor-pointer" title="ลบหน้านี้">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                            </button>
                        </div>
                    </div>

                    {{-- Thumbnails Scroll Container --}}
                    <div class="flex-1 overflow-y-auto p-3 space-y-3 custom-scrollbar">
                        <template x-for="thumb in thumbnails" :key="thumb.page">
                            <div @click="goToPage(thumb.page)"
                                 :class="currentPage === thumb.page ? 'ring-2 ring-brand-600 bg-brand-50/50' : 'hover:bg-slate-100 border-slate-200'"
                                 class="border rounded-xl p-2 cursor-pointer transition-all flex flex-col items-center group">
                                <div class="w-36 h-48 bg-white border border-slate-200 rounded shadow-xs flex items-center justify-center overflow-hidden relative">
                                    <template x-if="thumb.dataUrl">
                                        <img :src="thumb.dataUrl" class="w-full h-full object-contain">
                                    </template>
                                    <template x-if="!thumb.dataUrl">
                                        <div class="text-[10px] text-slate-400 animate-pulse">กำลังโหลด...</div>
                                    </template>
                                </div>
                                <span class="text-xs mt-1.5 font-medium" :class="currentPage === thumb.page ? 'text-brand-600 font-bold' : 'text-slate-600'" x-text="`หน้า ${thumb.page}`"></span>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- ── TAB 2: BOOKMARKS ── --}}
                <div x-show="activeSidebarTab === 'bookmarks'" class="flex flex-col h-full p-3">
                    <span class="text-xs font-bold text-slate-800 mb-3">สารบัญ (Bookmarks)</span>
                    <template x-if="bookmarks.length > 0">
                        <div class="flex-1 overflow-y-auto space-y-1 custom-scrollbar">
                            <template x-for="(bm, i) in bookmarks" :key="i">
                                <button type="button" @click="goToPage(bm.pageNumber || 1)" class="w-full text-left px-2.5 py-1.5 rounded-lg text-xs hover:bg-slate-200 text-slate-700 truncate" x-text="bm.title"></button>
                            </template>
                        </div>
                    </template>
                    <template x-if="bookmarks.length === 0">
                        <div class="flex-1 flex flex-col items-center justify-center text-center p-4 text-slate-400">
                            <span class="text-2xl mb-1">🔖</span>
                            <span class="text-xs">เอกสารนี้ไม่มีสารบัญที่ฝังมาในไฟล์</span>
                        </div>
                    </template>
                </div>

                {{-- ── TAB 3: SEARCH ── --}}
                <div x-show="activeSidebarTab === 'search'" class="flex flex-col h-full p-3">
                    <span class="text-xs font-bold text-slate-800 mb-2">ค้นหาคำในเอกสาร</span>
                    <div class="flex items-center gap-1.5 mb-2">
                        <input type="text"
                               x-model="searchQuery"
                               @keydown.enter="performSearch()"
                               placeholder="พิมพ์คำที่ต้องการค้นหา..."
                               class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs bg-white text-slate-800">
                        <button type="button" @click="performSearch()" class="p-1.5 bg-brand-600 text-white rounded-lg hover:bg-brand-700 cursor-pointer">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                        </button>
                    </div>

                    {{-- Results Nav --}}
                    <div x-show="searchResults.length > 0" class="flex items-center justify-between text-[11px] text-slate-500 mb-2 pb-2 border-b border-slate-200">
                        <span x-text="`พบ ${searchResults.length} จุด (${searchResultIndex + 1}/${searchResults.length})`"></span>
                        <div class="flex items-center gap-1">
                            <button type="button" @click="prevSearchResult()" class="p-1 hover:bg-slate-200 rounded text-slate-600">▲</button>
                            <button type="button" @click="nextSearchResult()" class="p-1 hover:bg-slate-200 rounded text-slate-600">▼</button>
                        </div>
                    </div>

                    {{-- Results List --}}
                    <div class="flex-1 overflow-y-auto space-y-1.5 custom-scrollbar text-xs">
                        <template x-for="(res, idx) in searchResults" :key="idx">
                            <div @click="searchResultIndex = idx; goToPage(res.page)"
                                 :class="searchResultIndex === idx ? 'bg-brand-50 border-brand-300 text-brand-900' : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-100'"
                                 class="p-2 rounded-lg border cursor-pointer transition-colors">
                                <span class="font-bold text-[10px] text-brand-600 uppercase" x-text="`หน้า ${res.page}`"></span>
                                <p class="text-[11px] leading-relaxed mt-0.5 line-clamp-2" x-text="res.snippet"></p>
                            </div>
                        </template>
                        <template x-if="isSearching">
                            <div class="text-center py-4 text-xs text-slate-400">กำลังค้นหา...</div>
                        </template>
                    </div>
                </div>

                {{-- ── TAB 4: ATTACHMENTS & INFO ── --}}
                <div x-show="activeSidebarTab === 'attachments'" class="flex flex-col h-full p-3 text-xs text-slate-600 space-y-3">
                    <span class="font-bold text-slate-800">ข้อมูลเอกสาร</span>
                    <div class="bg-white p-3 rounded-xl border border-slate-200 space-y-2">
                        <div>
                            <span class="text-slate-400 text-[10px] block">ชื่อไฟล์</span>
                            <span class="font-medium text-slate-800 break-all" x-text="pdfFileName"></span>
                        </div>
                        <div>
                            <span class="text-slate-400 text-[10px] block">จำนวนหน้า</span>
                            <span class="font-medium text-slate-800" x-text="`${totalPages} หน้า`"></span>
                        </div>
                        <div>
                            <span class="text-slate-400 text-[10px] block">จำนวนวัตถุที่แก้ไข</span>
                            <span class="font-medium text-slate-800" x-text="`${annotations.length} รายการ`"></span>
                        </div>
                    </div>
                </div>

                {{-- ── TAB 5: COMMENTS LIST ── --}}
                <div x-show="activeSidebarTab === 'comments'" class="flex flex-col h-full p-3">
                    <span class="text-xs font-bold text-slate-800 mb-2">ความคิดเห็นทั้งหมด</span>
                    <div class="flex-1 overflow-y-auto space-y-2 custom-scrollbar">
                        <template x-for="note in annotations.filter(a => a.type === 'note')" :key="note.id">
                            <div @click="goToPage(note.page); openNoteEditor(note.id)"
                                 class="p-2.5 rounded-xl border border-amber-200 bg-amber-50/70 hover:bg-amber-100/70 cursor-pointer transition-colors">
                                <div class="flex items-center justify-between text-[10px] text-amber-800 mb-1">
                                    <span class="font-bold" x-text="`หน้า ${note.page} · ${note.author}`"></span>
                                    <span x-text="note.createdAt"></span>
                                </div>
                                <p class="text-xs text-slate-800 line-clamp-2" x-text="note.text"></p>
                            </div>
                        </template>
                        <template x-if="annotations.filter(a => a.type === 'note').length === 0">
                            <div class="text-center py-8 text-xs text-slate-400">
                                <span>ยังไม่มีความคิดเห็นในเอกสาร</span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- 3. CENTER VIEWPORT / CANVAS WORKSPACE --}}
            <div id="pdfEditorWorkspace"
                 class="flex-1 editor-checkerboard overflow-auto relative custom-scrollbar select-none"
                 :class="activeTool === 'hand' ? (isPanning ? 'cursor-grabbing' : 'cursor-grab') : (activeTool === 'pointer' ? 'cursor-default' : 'cursor-crosshair')">

                {{-- Loading Overlay --}}
                <div x-show="isLoading" x-cloak class="absolute inset-0 bg-white/80 backdrop-blur-xs flex flex-col items-center justify-center z-40">
                    <div class="w-12 h-12 border-4 border-brand-200 border-t-brand-600 rounded-full animate-spin mb-3"></div>
                    <span class="text-sm font-semibold text-slate-700" x-text="loadingMessage"></span>
                </div>

                {{-- Scrollable Page Wrapper: Starts at top (justify-start py-8) so the header is always 100% visible and never cut off --}}
                <div class="w-fit min-w-full min-h-full flex flex-col items-center justify-start py-8 px-4 sm:px-8">
                    {{-- The Centered Page Container --}}
                    <div id="pdfEditorPageContainer"
                         class="relative shrink-0 bg-white shadow-2xl ring-1 ring-slate-300 rounded-sm transition-all duration-75"
                         :style="`width: ${displayWidth}px; height: ${displayHeight}px;`">

                    {{-- Base PDF.js Canvas --}}
                    <canvas id="pdfEditorCanvas" class="block w-full h-full"></canvas>

                    {{-- SVG Layer for Drawings & Paths --}}
                    <svg id="drawingSvg"
                         class="absolute inset-0 w-full h-full pointer-events-none"
                         viewBox="0 0 100 100"
                         preserveAspectRatio="none">
                        {{-- Saved paths for current page --}}
                        <template x-for="draw in annotations.filter(a => a.page === currentPage && a.type === 'draw')" :key="draw.id">
                            <path :d="draw.path"
                                  :stroke="draw.color || '#dc2626'"
                                  :stroke-width="draw.strokeWidth || 3"
                                  stroke-linecap="round"
                                  stroke-linejoin="round"
                                  fill="none"
                                  vector-effect="non-scaling-stroke"/>
                        </template>
                        {{-- Live drawing in progress --}}
                        <template x-if="isDrawing && currentDrawSvgPath">
                            <path :d="currentDrawSvgPath"
                                  :stroke="drawColor"
                                  :stroke-width="drawWidth"
                                  stroke-linecap="round"
                                  stroke-linejoin="round"
                                  fill="none"
                                  vector-effect="non-scaling-stroke"/>
                        </template>
                    </svg>

                    {{-- Interactive Overlay for Annotations (Text, Note, Stamp, Markup, Whiteout) --}}
                    <div id="pdfEditorOverlay"
                         class="absolute inset-0 w-full h-full"
                         @mousedown="handleOverlayMouseDown($event)"
                         @mousemove="handleOverlayMouseMove($event)"
                         @mouseup="handleOverlayMouseUp($event)">

                        {{-- Layer 0: Detected Original Text Block (Single unified container preserving 100% typography) --}}
                        <div x-show="['pointer', 'edit-text', 'text'].includes(activeTool) && currentOriginalTextBlocks.length > 0" class="absolute inset-0 pointer-events-none z-10" x-cloak>
                            <template x-for="block in currentOriginalTextBlocks" :key="block.id">
                                <div class="absolute pointer-events-auto cursor-pointer border-2 border-dashed border-sky-400 bg-sky-50/10 hover:bg-sky-50/25 hover:border-brand-500 rounded-sm transition-all group"
                                     :style="`left: ${block.pctX}%; top: ${block.pctY}%; width: ${block.pctW}%; height: ${block.pctH}%;`"
                                     @click.stop="startEditingOriginalText(block, $event)"
                                     title="คลิกเพื่อแก้ไขข้อความทั้งหมดในเอกสารนี้ (คงรูปแบบ ฟอนต์ ขนาด ตัวหนา และการจัดวางเดิม 100%)">
                                    <span class="absolute -top-6 right-1 text-[11px] font-bold text-sky-700 bg-white/95 px-2 py-0.5 rounded shadow-xs border border-sky-200 flex items-center gap-1.5 pointer-events-none">
                                        <svg class="w-3.5 h-3.5 text-brand-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
                                        <span>พื้นที่ข้อความต้นฉบับ (สแกนรูปแบบ 100% • คลิกเพื่อแก้ไข)</span>
                                    </span>
                                </div>
                            </template>
                        </div>

                        {{-- Live Whiteout Preview while dragging --}}
                        <div x-show="isDrawingWhiteout && currentWhiteoutRect"
                             class="absolute bg-white/95 border-2 border-dashed border-slate-400 shadow-sm pointer-events-none z-20"
                             :style="currentWhiteoutRectStyle" x-cloak></div>

                        {{-- Loop over current page annotations --}}
                        <template x-for="ann in pageAnnotations" :key="ann.id">
                            <div class="absolute group"
                                 :style="`left: ${ann.pctX}%; top: ${ann.pctY}%; width: ${ann.pctW || 'auto'}%; height: ${ann.pctH || 'auto'}%;`"
                                 @mousedown.stop="startDragAnnotation($event, ann.id)"
                                 @click.stop="selectAnnotation(ann.id, $event)">

                                {{-- Selection Box Border & 8 Directional Square Handles (matching Foxit / Acrobat style in user screenshot) --}}
                                <div x-show="selectedAnnotationId === ann.id"
                                     class="absolute -inset-1 border-2 border-[#0078d4] pointer-events-none z-30">
                                    {{-- nw --}}
                                    <div class="absolute -top-1.5 -left-1.5 w-2.5 h-2.5 bg-white border-2 border-[#0078d4] cursor-nwse-resize pointer-events-auto shadow-2xs"
                                         @mousedown.stop="startResizeAnnotation($event, ann.id, 'nw')"></div>
                                    {{-- n --}}
                                    <div class="absolute -top-1.5 left-1/2 -translate-x-1/2 w-2.5 h-2.5 bg-white border-2 border-[#0078d4] cursor-ns-resize pointer-events-auto shadow-2xs"
                                         @mousedown.stop="startResizeAnnotation($event, ann.id, 'n')"></div>
                                    {{-- ne --}}
                                    <div class="absolute -top-1.5 -right-1.5 w-2.5 h-2.5 bg-white border-2 border-[#0078d4] cursor-nesw-resize pointer-events-auto shadow-2xs"
                                         @mousedown.stop="startResizeAnnotation($event, ann.id, 'ne')"></div>
                                    {{-- e --}}
                                    <div class="absolute top-1/2 -translate-y-1/2 -right-1.5 w-2.5 h-2.5 bg-white border-2 border-[#0078d4] cursor-ew-resize pointer-events-auto shadow-2xs"
                                         @mousedown.stop="startResizeAnnotation($event, ann.id, 'e')"></div>
                                    {{-- se --}}
                                    <div class="absolute -bottom-1.5 -right-1.5 w-2.5 h-2.5 bg-white border-2 border-[#0078d4] cursor-nwse-resize pointer-events-auto shadow-2xs"
                                         @mousedown.stop="startResizeAnnotation($event, ann.id, 'se')"></div>
                                    {{-- s --}}
                                    <div class="absolute -bottom-1.5 left-1/2 -translate-x-1/2 w-2.5 h-2.5 bg-white border-2 border-[#0078d4] cursor-ns-resize pointer-events-auto shadow-2xs"
                                         @mousedown.stop="startResizeAnnotation($event, ann.id, 's')"></div>
                                    {{-- sw --}}
                                    <div class="absolute -bottom-1.5 -left-1.5 w-2.5 h-2.5 bg-white border-2 border-[#0078d4] cursor-nesw-resize pointer-events-auto shadow-2xs"
                                         @mousedown.stop="startResizeAnnotation($event, ann.id, 'sw')"></div>
                                    {{-- w --}}
                                    <div class="absolute top-1/2 -translate-y-1/2 -left-1.5 w-2.5 h-2.5 bg-white border-2 border-[#0078d4] cursor-ew-resize pointer-events-auto shadow-2xs"
                                         @mousedown.stop="startResizeAnnotation($event, ann.id, 'w')"></div>
                                </div>

                                {{-- ── TYPE 0: IMAGE / PICTURE ── --}}
                                <template x-if="ann.type === 'image'">
                                    <div class="w-full h-full cursor-move rounded-xs overflow-hidden flex items-center justify-center select-none bg-transparent">
                                        <img :src="ann.dataUrl" class="w-full h-full object-contain pointer-events-none select-none">
                                    </div>
                                </template>

                                {{-- ── TYPE 1: TEXT BOX (Supports background fill for covering original text) ── --}}
                                <template x-if="ann.type === 'text'">
                                    <div class="w-full h-full p-0.5 cursor-move rounded-xs transition-colors overflow-hidden flex flex-col"
                                         :style="`background-color: ${ann.bgColor || 'transparent'}; color: ${ann.color || '#111827'}; font-size: ${(ann.fontSize || 14) * (zoom / 100)}px; font-family: '${ann.fontFamily || 'TH Niramit AS'}', 'Niramit', 'TH Sarabun PSK', 'Sarabun', sans-serif; font-weight: ${ann.bold ? 'bold' : 'normal'}; font-style: ${ann.italic ? 'italic' : 'normal'}; text-decoration: ${ann.underline ? 'underline' : 'none'}; line-height: ${ann.lineHeight || 1.45};`">
                                        <textarea x-model="ann.text"
                                                  :data-ann-id="ann.id"
                                                  @mousedown.stop
                                                  @click.stop
                                                  class="w-full h-full flex-1 bg-transparent resize-none border-none outline-none font-inherit text-inherit p-0 m-0 block custom-scrollbar"
                                                  :style="`text-align: ${ann.align || 'left'}; line-height: ${ann.lineHeight || 1.45}; font-size: inherit; font-family: inherit; font-weight: inherit;`"
                                                  placeholder="พิมพ์หรือแก้ไขข้อความ..."></textarea>
                                    </div>
                                </template>

                                {{-- ── TYPE 1.5: UNIFIED DOCUMENT TEXT CONTAINER (Single block preserving per-line format 100%) ── --}}
                                <template x-if="ann.type === 'text_document'">
                                    <div class="w-full h-full p-2 rounded-xs overflow-y-auto overflow-x-hidden flex flex-col custom-scrollbar select-text cursor-default"
                                         :style="`background-color: ${ann.bgColor || '#ffffff'};`;">
                                        <template x-for="(para, pIdx) in (ann.paragraphs || ann.lines)" :key="pIdx">
                                            <div class="w-full relative rounded-md transition-all group/para"
                                                 :class="activeDocLineIdx === pIdx ? 'ring-2 ring-brand-500 bg-brand-50/15' : 'hover:ring-1 hover:ring-sky-300'"
                                                 :style="`margin-bottom: ${(para.gapAfter !== undefined ? para.gapAfter : 4) * (zoom / 100)}px;`">
                                                <textarea x-model="para.text"
                                                          :data-doc-ann="ann.id"
                                                          :data-para-idx="pIdx"
                                                          x-init="$nextTick(() => autoResizeTextarea($el))"
                                                          @focus="activeDocLineIdx = pIdx; syncToolbarToLine(para)"
                                                          @input="autoResizeTextarea($el)"
                                                          @click.stop
                                                          @mousedown.stop
                                                          class="w-full bg-transparent border-none outline-none resize-none overflow-hidden font-inherit block px-1 py-0.5"
                                                          :style="`text-align: ${para.align || 'left'}; font-size: ${(para.fontSize || 14) * (zoom / 100)}px; font-weight: ${para.bold ? 'bold' : 'normal'}; font-style: ${para.italic ? 'italic' : 'normal'}; font-family: '${para.fontFamily || 'TH Niramit AS'}', 'TH Sarabun PSK', 'Sarabun', sans-serif; color: ${para.color || '#111827'}; line-height: ${para.lineHeightRatio || para.lineHeight || 1.35}; text-indent: ${para.textIndent ? (para.textIndent * (zoom / 100)) + 'px' : '0px'}; letter-spacing: ${para.letterSpacing || 'normal'};`;"
                                                          rows="1"></textarea>
                                                {{-- Paragraph badge indicating whole paragraph is selected --}}
                                                <div x-show="activeDocLineIdx === pIdx"
                                                     class="absolute -top-3 right-2 text-[10px] font-bold text-white bg-brand-600 px-1.5 py-0.5 rounded shadow-xs pointer-events-none z-20 flex items-center gap-1">
                                                    <span>ย่อหน้า (ทั้งข้อความชุดเดียวกัน)</span>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </template>

                                {{-- ── TYPE 2: STICKY NOTE BADGE ── --}}
                                <template x-if="ann.type === 'note'">
                                    <div @click.stop="openNoteEditor(ann.id)"
                                         class="w-8 h-8 rounded-lg shadow-md flex items-center justify-center cursor-pointer border border-amber-400 hover:scale-110 transition-transform"
                                         :style="`background-color: ${ann.color || '#fef08a'};`">
                                        <span class="text-base select-none">💬</span>
                                    </div>
                                </template>

                                {{-- ── TYPE 3: STAMP ── --}}
                                <template x-if="ann.type === 'stamp'">
                                    <div class="w-full h-full border-2 rounded-lg flex flex-col items-center justify-center p-1 cursor-move select-none -rotate-3"
                                         :style="`color: ${ann.color || '#16a34a'}; border-color: ${ann.color || '#16a34a'};`">
                                        <div class="border border-current rounded px-2 py-0.5 w-full h-full flex flex-col items-center justify-center">
                                            <span class="font-extrabold uppercase tracking-wider text-center text-xs sm:text-sm leading-none"
                                                  x-text="ann.preset === 'CUSTOM' ? (ann.customText || 'สำเนาถูกต้อง') :
                                                         (ann.preset === 'APPROVED' ? 'APPROVED' :
                                                         (ann.preset === 'DRAFT' ? 'DRAFT' :
                                                         (ann.preset === 'CONFIDENTIAL' ? 'CONFIDENTIAL' :
                                                         (ann.preset === 'VERIFIED' ? 'สำเนาถูกต้อง' : ann.preset))))"></span>
                                            <template x-if="ann.date">
                                                <span class="text-[9px] mt-0.5 opacity-90" x-text="ann.date"></span>
                                            </template>
                                        </div>
                                    </div>
                                </template>

                                {{-- ── TYPE 4: HIGHLIGHT ── --}}
                                <template x-if="ann.type === 'highlight'">
                                    <div class="w-full h-full cursor-move rounded-xs"
                                         :style="`background-color: ${ann.color || '#fde047'}; opacity: 0.45;`"></div>
                                </template>

                                {{-- ── TYPE 5: UNDERLINE ── --}}
                                <template x-if="ann.type === 'underline'">
                                    <div class="w-full h-full cursor-move border-b-2"
                                         :style="`border-color: ${ann.color || '#dc2626'};`"></div>
                                </template>

                                {{-- ── TYPE 6: STRIKETHROUGH ── --}}
                                <template x-if="ann.type === 'strikethrough'">
                                    <div class="w-full h-full cursor-move flex items-center">
                                        <div class="w-full border-b-2" :style="`border-color: ${ann.color || '#dc2626'};`"></div>
                                    </div>
                                </template>

                                {{-- ── TYPE 7: WHITEOUT (ลบข้อความเดิม / ปิดทับ) ── --}}
                                <template x-if="ann.type === 'whiteout'">
                                    <div class="w-full h-full cursor-move rounded-xs border border-dashed border-slate-300 hover:border-brand-500 group/wo relative"
                                         :style="`background-color: ${ann.color || '#ffffff'};`">
                                        <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover/wo:opacity-100 transition-opacity bg-slate-100/70 text-slate-500 text-[10px] font-medium pointer-events-none select-none">
                                            <span>ลบข้อความ (ไวท์เอาท์)</span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
                </div>
            </div>
        </div>

        {{-- ─── BOTTOM VIEWPORT & STATUS CONTROLS BAR ─── --}}
        <div class="bg-slate-50 border-t border-slate-200 px-4 py-2 flex flex-wrap items-center justify-between gap-3 shrink-0 text-xs text-slate-600 select-none">
            {{-- Left: Pagination --}}
            <div class="flex items-center gap-1.5">
                <button type="button"
                        @click="prevPage()"
                        :disabled="currentPage <= 1"
                        class="p-1 rounded-lg border border-slate-200 hover:bg-slate-200 disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer transition-colors"
                        title="หน้าก่อนหน้า">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
                </button>
                <div class="flex items-center gap-1">
                    <span>หน้า</span>
                    <input type="number"
                           :value="currentPage"
                           @change="goToPage($event.target.value)"
                           min="1"
                           :max="totalPages"
                           class="w-12 border border-slate-200 rounded px-1.5 py-0.5 text-center bg-white text-slate-800 font-bold text-xs">
                    <span>/ <span x-text="totalPages"></span></span>
                </div>
                <button type="button"
                        @click="nextPage()"
                        :disabled="currentPage >= totalPages"
                        class="p-1 rounded-lg border border-slate-200 hover:bg-slate-200 disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer transition-colors"
                        title="หน้าถัดไป">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                </button>
            </div>

            {{-- Center: Tool Tip / Status Message --}}
            <div class="hidden lg:block text-slate-500 text-xs text-center">
                <template x-if="activeTool === 'pointer'"><span class="text-brand-600 font-medium">💡 คลิกที่ข้อความบนหน้าเพื่อแก้ไขข้อความเดิมได้ทันที หรือคลิกเลือกวัตถุเพื่อปรับย้าย</span></template>
                <template x-if="activeTool === 'hand'"><span>โหมดเลื่อน: กดค้างแล้วลากเพื่อเลื่อนหน้าเอกสาร</span></template>
                <template x-if="activeTool === 'edit-text'"><span class="text-brand-600 font-medium">โหมดแก้ไขข้อความ: เลื่อนเมาส์ชี้ข้อความเดิมเพื่อคลิกแก้ไข หรือคลิกที่ว่างเพื่อพิมพ์ข้อความใหม่</span></template>
                <template x-if="activeTool === 'text'"><span>โหมดพิมพ์เพิ่ม: คลิกตรงตำแหน่งที่ต้องการวางกล่องข้อความใหม่</span></template>
                <template x-if="activeTool === 'whiteout'"><span class="text-brand-600 font-medium">โหมดลบข้อความ: คลิกแล้วลากคลุมบริเวณที่ต้องการลบหรือปิดทับด้วยสีขาว</span></template>
                <template x-if="activeTool === 'draw'"><span>โหมดวาดเขียน: กดเมาส์ค้างแล้ววาดเส้นอิสระ</span></template>
                <template x-if="activeTool === 'highlight'"><span>โหมดไฮไลท์: ลากคลุมข้อความที่ต้องการเน้นสี</span></template>
                <template x-if="activeTool === 'stamp'"><span>โหมดตราประทับ: คลิกเพื่อประทับตราบนเอกสาร</span></template>
                <template x-if="activeTool === 'note'"><span>โหมดหมายเหตุ: คลิกบนตำแหน่งที่ต้องการวางบันทึก</span></template>
            </div>

            {{-- Right: Zoom Controls --}}
            <div class="flex items-center gap-1.5 ml-auto">
                <button type="button" @click="zoomOut()" class="p-1 rounded-lg border border-slate-200 hover:bg-slate-200 text-slate-700 cursor-pointer" title="ย่อขนาด (-)">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14"/></svg>
                </button>
                <span class="w-14 text-center font-bold text-slate-700" x-text="`${zoom}%`"></span>
                <button type="button" @click="zoomIn()" class="p-1 rounded-lg border border-slate-200 hover:bg-slate-200 text-slate-700 cursor-pointer" title="ขยายขนาด (+)">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                </button>
                <div class="h-4 w-px bg-slate-200 mx-1"></div>
                <button type="button" @click="fitWidth()" class="px-2 py-1 rounded-lg border border-slate-200 hover:bg-slate-200 text-[11px] text-slate-700 cursor-pointer">
                    พอดีความกว้าง
                </button>
                <button type="button" @click="fitPage()" class="px-2 py-1 rounded-lg border border-slate-200 hover:bg-slate-200 text-[11px] text-slate-700 cursor-pointer">
                    พอดีหน้า
                </button>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════ --}}
    {{-- MODALS --}}
    {{-- ══════════════════════════════════════════════════════════════════ --}}

    {{-- 1. Sticky Note Editor Modal --}}
    <div x-show="noteModalOpen" x-cloak class="fixed inset-0 bg-black/40 backdrop-blur-xs flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-md p-5 space-y-4" @click.away="saveActiveNote()">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-xl">💬</span>
                    <h3 class="font-bold text-slate-800 text-sm">หมายเหตุ / ความคิดเห็น</h3>
                </div>
                <button type="button" @click="saveActiveNote()" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            <div>
                <textarea x-model="activeNoteText"
                          rows="4"
                          class="w-full border border-slate-200 rounded-xl p-3 text-xs leading-relaxed focus:ring-2 focus:ring-brand-500 focus:border-brand-500"
                          placeholder="พิมพ์ข้อความความคิดเห็นที่นี่..."></textarea>
            </div>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-1.5">
                    <span class="text-[11px] text-slate-500">สีโน้ต:</span>
                    <template x-for="c in ['#fef08a', '#bbf7d0', '#fed7aa', '#bae6fd', '#fbcfe8']" :key="c">
                        <button type="button" @click="noteColor = c" class="w-5 h-5 rounded-full border cursor-pointer" :style="`background-color: ${c}`" :class="noteColor === c ? 'ring-2 ring-slate-800' : ''"></button>
                    </template>
                </div>
                <button type="button" @click="saveActiveNote()" class="btn-primary px-4 py-1.5 rounded-lg text-xs font-semibold cursor-pointer">
                    บันทึกโน้ต
                </button>
            </div>
        </div>
    </div>

    {{-- 2. Share Modal --}}
    <div x-show="shareModalOpen" x-cloak class="fixed inset-0 bg-black/40 backdrop-blur-xs flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-md p-6 space-y-4" @click.away="shareModalOpen = false">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-xl">🔗</span>
                    <h3 class="font-bold text-slate-800 text-base">แชร์โปรแกรมแก้ไข PDF</h3>
                </div>
                <button type="button" @click="shareModalOpen = false" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            <p class="text-xs text-slate-500 leading-relaxed">
                คัดลอกลิงก์เพื่อส่งต่อให้เพื่อนร่วมงาน หรือเปิดเครื่องมือบนอุปกรณ์อื่น
            </p>
            <div class="flex items-center gap-2">
                <input type="text" readonly value="{{ url('/pdf-editor') }}" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-xs bg-slate-50 text-slate-700">
                <button type="button" @click="copyShareLink()" class="btn-primary px-3.5 py-2 rounded-xl text-xs font-semibold shrink-0 cursor-pointer">
                    <span x-text="shareCopied ? 'คัดลอกแล้ว!' : 'คัดลอกลิงก์'"></span>
                </button>
            </div>
            <div class="pt-2 border-t border-slate-100 flex items-center justify-end">
                <button type="button" @click="shareModalOpen = false" class="px-4 py-1.5 rounded-lg border border-slate-200 text-xs text-slate-600 hover:bg-slate-50 cursor-pointer">
                    ปิด
                </button>
            </div>
        </div>
    </div>

</div>
@endsection
