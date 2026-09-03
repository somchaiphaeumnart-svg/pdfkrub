@extends('layouts.app')

@section('title', 'PDFkrub')
@section('description', 'PDFkrub — PDF ภาษาไทย ทำง่ายในไม่กี่วินาที สำหรับครูและโรงเรียน รองรับ PDPA ประมวลผลในประเทศไทย')

@section('content')

{{-- ====================================================
     HERO SECTION
     ==================================================== --}}
<section class="relative min-h-screen flex items-center justify-center overflow-hidden py-24">
    {{-- Animated background orbs --}}
    <div class="absolute inset-0 pointer-events-none">
        <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-brand-600/20 rounded-full blur-3xl float-animation"></div>
        <div class="absolute top-1/3 right-1/4 w-80 h-80 bg-accent-500/15 rounded-full blur-3xl float-animation" style="animation-delay: -3s"></div>
        <div class="absolute bottom-1/4 left-1/3 w-72 h-72 bg-brand-800/20 rounded-full blur-3xl float-animation" style="animation-delay: -1.5s"></div>
    </div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        {{-- Badge --}}
        <div class="inline-flex items-center gap-2 glass px-4 py-2 rounded-full text-sm text-brand-300 mb-8 border border-brand-500/20">
            <span class="w-1.5 h-1.5 rounded-full bg-success-500 animate-pulse"></span>
            เครื่องมือ PDF สำหรับครูไทย &middot; รองรับ PDPA &middot; ประมวลผลในประเทศไทย
        </div>

        {{-- Heading --}}
        <h1 class="text-5xl sm:text-6xl lg:text-7xl font-bold text-white leading-tight mb-6">
            PDF ภาษาไทย<br>
            <span class="text-gradient">ทำง่ายในไม่กี่วินาที</span>
        </h1>

        <p class="text-xl text-slate-400 max-w-2xl mx-auto mb-4 leading-relaxed">
            แพลตฟอร์มจัดการเอกสาร PDF สำหรับครูและโรงเรียนโดยเฉพาะ ลดเวลาทำงาน <strong class="text-white">30 นาที</strong> เหลือ <strong class="text-white">30 วินาที</strong>
        </p>
        <p class="text-sm text-brand-300/80 mb-10">
            <span class="inline-flex items-center gap-1">🛡️ รองรับ PDPA</span> &nbsp;&middot;&nbsp;
            <span>🇹🇭 เซิร์ฟเวอร์ในประเทศไทย</span> &nbsp;&middot;&nbsp;
            <span>🔒 ลบไฟล์อัตโนมัติใน 1 ชั่วโมง</span>
        </p>

        {{-- CTA Buttons --}}
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mb-16">
            <a href="{{ route('tools') }}" class="btn-primary text-base px-8 py-4 rounded-2xl flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/>
                </svg>
                เริ่มใช้งานฟรี
            </a>
            <a href="{{ route('tools') }}" class="btn-ghost text-base px-8 py-4 rounded-2xl flex items-center gap-2">
                ดูเครื่องมือทั้งหมด
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
                </svg>
            </a>
        </div>

        {{-- Quick upload widget --}}
        <div class="max-w-2xl mx-auto">
            <div class="upload-zone p-10 text-center cursor-pointer transition-all"
                 x-data="fileUpload({ maxSizeMb: {{ auth()->check() && auth()->user()->getActivePlan()->max_file_size_mb >= 200 ? 200 : 10 }}, accept: '.pdf' })"
                 @dragover.prevent="isDragging = true"
                 @dragleave.prevent="isDragging = false"
                 @drop.prevent="handleDrop($event)"
                 :class="{ 'drag-over': isDragging }"
                 @click="$refs.fileInput.click()">

                <input type="file" x-ref="fileInput" class="hidden" accept=".pdf" multiple @change="handleFileInput($event)">

                <template x-if="!hasFiles">
                    <div>
                        <div class="w-16 h-16 mx-auto mb-4 bg-brand-600/20 rounded-2xl flex items-center justify-center">
                            <svg class="w-8 h-8 text-brand-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/>
                            </svg>
                        </div>
                        <p class="text-white font-semibold text-lg mb-1">ลากและวางไฟล์ PDF ที่นี่</p>
                        <p class="text-slate-400 text-sm">หรือ <span class="text-brand-400 underline underline-offset-2">คลิกเพื่อเลือกไฟล์</span></p>
                        <p class="text-slate-500 text-xs mt-3">รองรับไฟล์สูงสุด {{ auth()->check() && auth()->user()->getActivePlan()->max_file_size_mb >= 200 ? '200' : '10' }} MB</p>
                    </div>
                </template>

                <template x-if="hasFiles">
                    <div class="text-left" @click.stop>
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-white font-semibold" x-text="`${files.length} ไฟล์ (${totalSize})`"></span>
                            <button @click="clearAll()" class="text-xs text-slate-400 hover:text-error-500 transition-colors">ล้างทั้งหมด</button>
                        </div>
                        <div class="space-y-2 max-h-48 overflow-y-auto">
                            <template x-for="f in files" :key="f.id">
                                <div class="flex items-center gap-3 glass-light px-3 py-2 rounded-lg">
                                    <svg class="w-4 h-4 text-brand-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                    </svg>
                                    <span class="text-sm text-slate-200 flex-1 truncate" x-text="f.name"></span>
                                    <span class="text-xs text-slate-500 flex-shrink-0" x-text="f.sizeFormatted"></span>
                                    <button @click="removeFile(f.id)" class="text-slate-500 hover:text-error-500 transition-colors">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                        <div class="mt-4 flex gap-3 justify-end">
                            <a href="{{ route('tools') }}" class="btn-primary text-sm px-5 py-2.5 rounded-xl">เลือกเครื่องมือ →</a>
                        </div>
                    </div>
                </template>

                <div x-show="error" class="mt-3 text-xs text-error-500" x-text="error"></div>
            </div>
        </div>

        {{-- Trust badges --}}
        <div class="flex flex-wrap items-center justify-center gap-6 mt-10 text-xs text-slate-500">
            <span class="flex items-center gap-1.5">
                <svg class="w-4 h-4 text-success-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/>
                </svg>
                รองรับ PDPA
            </span>
            <span class="flex items-center gap-1.5">
                <svg class="w-4 h-4 text-success-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
                ลบไฟล์อัตโนมัติใน 1 ชั่วโมง
            </span>
            <span class="flex items-center gap-1.5">
                <svg class="w-4 h-4 text-success-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/>
                </svg>
                เซิร์ฟเวอร์ในประเทศไทย
            </span>
            <span class="flex items-center gap-1.5">
                <svg class="w-4 h-4 text-success-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 21l5.25-11.25L21 21m-9-3h7.5M3 5.621a48.474 48.474 0 0 1 6-.371m0 0c1.12 0 2.233.038 3.334.114M9 5.25V3m3.334 2.364C11.176 10.658 7.69 15.08 3 17.502m9.334-12.138c.896.061 1.785.147 2.666.257m-4.589 8.495a18.023 18.023 0 0 1-3.827-5.802"/>
                </svg>
                รองรับภาษาไทย 100%
            </span>
            <span class="flex items-center gap-1.5">
                <svg class="w-4 h-4 text-success-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 3.741-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5"/>
                </svg>
                สำหรับครูโดยเฉพาะ
            </span>
        </div>
    </div>
</section>

{{-- ====================================================
     TEACHER TOOLS SECTION
     ==================================================== --}}
<section class="py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14">
            <div class="inline-flex items-center gap-2 glass px-3 py-1.5 rounded-full text-xs text-brand-300 mb-4 border border-brand-500/20">
                <span class="text-base">🏫</span>
                สำหรับครูและโรงเรียน
            </div>
            <h2 class="text-3xl sm:text-4xl font-bold text-white mb-4">เครื่องมือ PDF <span class="text-gradient">เพื่อครู</span></h2>
            <p class="text-slate-400 max-w-xl mx-auto">ด้วยเครื่องมือที่ออกแบบมาเพื่อครูโดยเฉพาะ ลดเวลาทำเอกสารจาก 30 นาที เหลือ 30 วินาที</p>
        </div>

        {{-- 6 Hero Tools Grid --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mb-6">
            @php
            $heroTools = [
                [
                    'emoji' => '📄',
                    'title' => 'PDF เป็น Word',
                    'desc' => 'แปลง PDF เป็นไฟล์ Word แก้ไขได้',
                    'slug' => 'pdf-to-word',
                    'color' => 'from-blue-600/20 to-blue-500/10',
                    'border' => 'border-blue-500/30',
                    'text' => 'text-blue-400',
                    'premium' => false,
                ],
                [
                    'emoji' => '🔍',
                    'title' => 'OCR ภาษาไทย',
                    'desc' => 'อ่านข้อความจากเอกสารสแกน PDF ได้ทันที',
                    'slug' => 'ocr-pdf',
                    'color' => 'from-orange-600/20 to-orange-500/10',
                    'border' => 'border-orange-500/30',
                    'text' => 'text-orange-400',
                    'premium' => true,
                ],
                [
                    'emoji' => '🗜',
                    'title' => 'บีบอัด PDF',
                    'desc' => 'ลดขนาดไฟล์ให้ผ่านระบบการศึกษาได้ง่าย',
                    'slug' => 'compress-pdf',
                    'color' => 'from-green-600/20 to-green-500/10',
                    'border' => 'border-green-500/30',
                    'text' => 'text-green-400',
                    'premium' => false,
                ],
                [
                    'emoji' => '📎',
                    'title' => 'รวมหลักฐาน PA',
                    'desc' => 'รวม PDF หลักฐานหลายไฟล์เป็นเล่มเดียว',
                    'slug' => 'merge-pdf',
                    'color' => 'from-purple-600/20 to-purple-500/10',
                    'border' => 'border-purple-500/30',
                    'text' => 'text-purple-400',
                    'premium' => false,
                ],
                [
                    'emoji' => '✨',
                    'title' => 'AI สรุป PDF',
                    'desc' => 'ให้ AI สรุปสาระสำคัญจากเอกสารของคุณ',
                    'slug' => 'ocr-pdf',
                    'color' => 'from-pink-600/20 to-pink-500/10',
                    'border' => 'border-pink-500/30',
                    'text' => 'text-pink-400',
                    'premium' => true,
                ],
                [
                    'emoji' => '🎫',
                    'title' => 'เครื่องมือทางการ',
                    'desc' => 'ประทับสำเนาถูกต้อง, ลายน้ำ, ลงนาม',
                    'slug' => 'sign-pdf',
                    'color' => 'from-teal-600/20 to-teal-500/10',
                    'border' => 'border-teal-500/30',
                    'text' => 'text-teal-400',
                    'premium' => true,
                ],
            ];
            @endphp

            @foreach($heroTools as $tool)
            <a href="{{ route('tools.'.$tool['slug']) }}"
               class="group relative glass rounded-2xl p-6 border {{ $tool['border'] }} card-hover bg-gradient-to-br {{ $tool['color'] }} transition-all">
                @if($tool['premium'])
                <span class="absolute top-3 right-3 badge-premium text-xs">Pro</span>
                @endif
                <div class="text-3xl mb-3">{{ $tool['emoji'] }}</div>
                <h3 class="font-bold text-white text-sm mb-1 group-hover:{{ $tool['text'] }} transition-colors">{{ $tool['title'] }}</h3>
                <p class="text-xs text-slate-400 leading-relaxed">{{ $tool['desc'] }}</p>
            </a>
            @endforeach
        </div>

        {{-- More tools link --}}
        <div class="text-center mt-6">
            <a href="{{ route('tools') }}" class="btn-ghost px-8 py-3 rounded-xl inline-flex items-center gap-2 text-sm">
                ดูเครื่องมือ PDF ครูทั้งหมด
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
                </svg>
            </a>
        </div>
    </div>
</section>

{{-- ====================================================
     WHY PDFKRUB + PDPA TRUST SECTION
     ==================================================== --}}
<section class="py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl sm:text-4xl font-bold text-white mb-4">ทำไมครูต้องเลือก <span class="text-gradient">PDFkrub</span>?</h2>
            <p class="text-slate-400">ออกแบบมาเพื่อครูโดยเฉพาะ เข้าใจความต้องการของครูไทย</p>
        </div>

        <div class="grid md:grid-cols-3 gap-8 mb-12">
            @php
            $features = [
                [
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/>',
                    'title' => 'ปลอดภัย รองรับ PDPA',
                    'desc' => 'ประมวลผลในประเทศไทย เชุดข้อมูลไม่ถูกส่งออกนอกประเทศ ลบไฟล์อัตโนมัติภายใน 1 ชั่วโมง ทำให้เอกสารสำคัญ เช่น เลขบัตรปชช, ข้อมูลนักเรียน อยู่อย่างปลอดภัย',
                    'color' => 'text-success-500',
                    'bg' => 'bg-success-500/10',
                ],
                [
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M10.5 21l5.25-11.25L21 21m-9-3h7.5M3 5.621a48.474 48.474 0 0 1 6-.371m0 0c1.12 0 2.233.038 3.334.114M9 5.25V3m3.334 2.364C11.176 10.658 7.69 15.08 3 17.502m9.334-12.138c.896.061 1.785.147 2.666.257m-4.589 8.495a18.023 18.023 0 0 1-3.827-5.802"/>',
                    'title' => 'รองรับภาษาไทย 100%',
                    'desc' => 'OCR อ่านข้อความภาษาไทยจากเอกสารสแกนได้อย่างแม่นยำ รองรับฟอนต์ไทยในการแปลงทุกรูปแบบ ไม่มีตัวอักษรเพี้ยนรูป',
                    'color' => 'text-brand-400',
                    'bg' => 'bg-brand-500/10',
                ],
                [
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/>',
                    'title' => 'ช่วยครูประหยัดเวลา',
                    'desc' => 'ลดเวลาทำเอกสาร PA จาก 30 นาที เหลือ 30 วินาที อัปโหลด, เลือกเครื่องมือ, ดาวน์โหลดใน 3 ขั้นตอน ไม่ต้องติดตั้งโปรแกรม',
                    'color' => 'text-accent-400',
                    'bg' => 'bg-accent-500/10',
                ],
            ];
            @endphp

            @foreach($features as $feature)
            <div class="glass rounded-2xl p-8 card-hover border border-white/[0.06]">
                <div class="{{ $feature['bg'] }} {{ $feature['color'] }} w-14 h-14 rounded-2xl flex items-center justify-center mb-6">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        {!! $feature['icon'] !!}
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-white mb-3">{{ $feature['title'] }}</h3>
                <p class="text-slate-400 leading-relaxed">{{ $feature['desc'] }}</p>
            </div>
            @endforeach
        </div>

        {{-- PDPA Trust Banner --}}
        <div class="glass rounded-2xl p-6 border border-success-500/20 bg-gradient-to-r from-success-500/5 to-brand-500/5">
            <div class="flex flex-col md:flex-row items-center gap-6">
                <div class="flex-shrink-0 w-16 h-16 rounded-2xl bg-success-500/10 flex items-center justify-center">
                    <svg class="w-8 h-8 text-success-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/>
                    </svg>
                </div>
                <div class="flex-1 text-center md:text-left">
                    <h3 class="text-white font-bold text-lg mb-1">🇹🇭 ประมวลผลในประเทศไทย · รองรับ PDPA</h3>
                    <p class="text-slate-400 text-sm">เอกสารทั้งหมดประมวลผลบนเซิร์ฟเวอร์ในประเทศไทย ไม่ทำสำเนาข้อมูลเป็น AI ซ้ำ ไม่ขายข้อมูลให้ผู้อื่น เหมาะสำหรับเอกสารสำคัญ เช่น เลขบัตรประชาชน, โปรไฟล์นักเรียน</p>
                </div>
                <div class="flex flex-wrap justify-center gap-3">
                    <span class="inline-flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-full bg-success-500/10 text-success-400 border border-success-500/20">🔒 เข้ารหัส AES-256</span>
                    <span class="inline-flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-full bg-brand-500/10 text-brand-400 border border-brand-500/20">⏱️ ลบใน 1 ชั่วโมง</span>
                    <span class="inline-flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-full bg-accent-500/10 text-accent-400 border border-accent-500/20">🇹🇭 เซิร์ฟเวอร์ไทย</span>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ====================================================
     HOW IT WORKS
     ==================================================== --}}
<section class="py-20">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl sm:text-4xl font-bold text-white mb-4">วิธีใช้งาน</h2>
        <p class="text-slate-400 mb-16">ง่ายแค่ 3 ขั้นตอน ไม่ต้องสมัครสมาชิกก็ใช้ได้</p>

        <div class="grid md:grid-cols-3 gap-8 relative">
            {{-- Connector line --}}
            <div class="hidden md:block absolute top-12 left-1/3 right-1/3 h-px bg-gradient-to-r from-brand-600 to-brand-600 via-brand-400"></div>

            @php
            $steps = [
                ['num' => '1', 'icon' => 'M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5', 'title' => 'อัปโหลดไฟล์', 'desc' => 'ลากและวาง หรือคลิกเลือกไฟล์ PDF ของคุณ รองรับสูงสุด 200 MB (สำหรับ Pro)'],
                ['num' => '2', 'icon' => 'M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z', 'title' => 'เลือกเครื่องมือ', 'desc' => 'เลือกจากเครื่องมือ 50+ รายการ ตั้งค่าตามต้องการ แล้วกดเริ่มประมวลผล'],
                ['num' => '3', 'icon' => 'M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3', 'title' => 'ดาวน์โหลดผลลัพธ์', 'desc' => 'รอไม่นาน ดาวน์โหลดไฟล์สำเร็จรูปได้เลย ไฟล์จะถูกลบอัตโนมัติใน 48 ชั่วโมง'],
            ];
            @endphp

            @foreach($steps as $step)
            <div class="relative flex flex-col items-center">
                <div class="w-24 h-24 glass glow-blue rounded-full flex items-center justify-center mb-6 border border-brand-500/30 relative z-10">
                    <svg class="w-10 h-10 text-brand-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $step['icon'] }}"/>
                    </svg>
                    <div class="absolute -top-2 -right-2 w-7 h-7 bg-gradient-to-br from-brand-500 to-brand-700 rounded-full flex items-center justify-center text-white text-xs font-bold">{{ $step['num'] }}</div>
                </div>
                <h3 class="text-lg font-bold text-white mb-2">{{ $step['title'] }}</h3>
                <p class="text-slate-400 text-sm text-center">{{ $step['desc'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ====================================================
     PRICING SECTION
     ==================================================== --}}
<section class="py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14">
            <h2 class="text-3xl sm:text-4xl font-bold text-white mb-4">แผนราคา</h2>
            <p class="text-slate-400">เริ่มต้นฟรี ไม่ต้องใส่บัตรเครดิต</p>
        </div>

        <div class="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto">
            @foreach($plans as $plan)
            <div class="glass rounded-2xl p-8 border card-hover relative
                        {{ $plan->name === 'pro' ? 'border-brand-500/50 glow-blue' : 'border-white/[0.06]' }}">

                @if($plan->name === 'pro')
                <div class="absolute -top-4 left-1/2 -translate-x-1/2">
                    <span class="bg-gradient-to-r from-brand-600 to-brand-500 text-white text-xs font-bold px-4 py-1.5 rounded-full shadow-lg">แนะนำ</span>
                </div>
                @endif

                <div class="mb-6">
                    <h3 class="text-xl font-bold text-white mb-1">{{ $plan->display_name_th ?? $plan->display_name }}</h3>
                    <div class="flex items-baseline gap-2 mt-4">
                        @if($plan->price_monthly > 0)
                            <span class="text-4xl font-bold text-white">฿{{ number_format($plan->price_monthly) }}</span>
                            <span class="text-slate-400 text-sm">/เดือน</span>
                        @else
                            <span class="text-4xl font-bold text-white">ฟรี</span>
                        @endif
                    </div>
                    @if($plan->price_yearly > 0)
                    <p class="text-xs text-accent-400 mt-1">หรือ ฿{{ number_format($plan->price_yearly) }}/ปี (ประหยัด {{ round((1 - ($plan->price_yearly / ($plan->price_monthly * 12))) * 100) }}%)</p>
                    @endif
                </div>

                <ul class="space-y-3 mb-8">
                    <li class="flex items-center gap-2 text-sm text-slate-300">
                        <svg class="w-4 h-4 text-success-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        ไฟล์สูงสุด {{ $plan->max_file_size_mb }} MB
                    </li>
                    <li class="flex items-center gap-2 text-sm text-slate-300">
                        <svg class="w-4 h-4 text-success-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        {{ $plan->daily_conversions === -1 ? 'แปลงไฟล์ไม่จำกัด' : 'แปลงได้ '.$plan->daily_conversions.' ครั้ง/วัน' }}
                    </li>
                    <li class="flex items-center gap-2 text-sm text-slate-300">
                        <svg class="w-4 h-4 text-success-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        @if($plan->file_retention_hours >= 720)
                            เก็บไฟล์ 30 วัน
                        @elseif($plan->file_retention_hours >= 168)
                            เก็บไฟล์ 7 วัน
                        @elseif($plan->file_retention_hours >= 48)
                            เก็บไฟล์ 48 ชั่วโมง
                        @else
                            เก็บไฟล์ {{ $plan->file_retention_hours }} ชั่วโมง
                        @endif
                    </li>
                    <li class="flex items-center gap-2 text-sm {{ $plan->has_ocr ? 'text-slate-300' : 'text-slate-600' }}">
                        @if($plan->has_ocr)
                        <svg class="w-4 h-4 text-success-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        @else
                        <svg class="w-4 h-4 text-slate-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                        @endif
                        OCR ภาษาไทย (Google Vision)
                    </li>
                    <li class="flex items-center gap-2 text-sm {{ $plan->has_esign ? 'text-slate-300' : 'text-slate-600' }}">
                        @if($plan->has_esign)
                        <svg class="w-4 h-4 text-success-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        @else
                        <svg class="w-4 h-4 text-slate-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                        @endif
                        เซ็นเอกสารดิจิทัล
                    </li>
                    @if($plan->has_api_access)
                    <li class="flex items-center gap-2 text-sm text-slate-300">
                        <svg class="w-4 h-4 text-success-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        REST API Access
                    </li>
                    @endif
                    @if(!$plan->has_watermark)
                    <li class="flex items-center gap-2 text-sm text-slate-300">
                        <svg class="w-4 h-4 text-success-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        ไม่มี Watermark
                    </li>
                    @endif
                </ul>

                @auth
                    @if(auth()->user()->getActivePlan()->name === $plan->name)
                    <button disabled class="w-full btn-ghost rounded-xl py-3 text-sm opacity-50 cursor-not-allowed">แผนปัจจุบัน</button>
                    @else
                    <a href="{{ route('billing.upgrade', $plan) }}" class="w-full block text-center {{ $plan->name === 'pro' ? 'btn-primary' : 'btn-ghost' }} rounded-xl py-3 text-sm">
                        {{ $plan->price_monthly > 0 ? 'อัปเกรด' : 'ดาวน์เกรด' }}
                    </a>
                    @endif
                @else
                    <a href="{{ $plan->price_monthly > 0 ? route('register') : route('register') }}"
                       class="w-full block text-center {{ $plan->name === 'pro' ? 'btn-primary' : 'btn-ghost' }} rounded-xl py-3 text-sm">
                        {{ $plan->price_monthly > 0 ? 'เริ่มต้นใช้งาน' : 'สมัครฟรี' }}
                    </a>
                @endauth
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ====================================================
     CTA SECTION
     ==================================================== --}}
<section class="py-20">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="glass rounded-3xl p-12 border border-brand-500/20 glow-blue relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-brand-900/50 to-transparent pointer-events-none"></div>
            <div class="relative">
                <div class="text-4xl mb-4">🏫</div>
                <h2 class="text-3xl sm:text-4xl font-bold text-white mb-4">
                    เริ่มใช้งานฟรีวันนี้
                </h2>
                <p class="text-slate-400 mb-2 max-w-xl mx-auto">
                    ครูกว่า 500,000 คนทั่วไทยเลือกใช้ PDFkrub สำหรับงานเอกสารทุกวัน
                </p>
                <p class="text-xs text-slate-500 mb-8">ไม่ต้องใส่บัตรเครดิต · ประมวลผลในประเทศไทย · รองรับ PDPA</p>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    <a href="{{ route('register') }}" class="btn-primary text-base px-10 py-4 rounded-2xl inline-flex items-center gap-2">
                        สมัครครูฟรี
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                        </svg>
                    </a>
                    <a href="{{ route('pricing') }}" class="btn-ghost text-base px-8 py-4 rounded-2xl inline-flex items-center gap-2">
                        ดูแผนราคาสำหรับโรงเรียน
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
