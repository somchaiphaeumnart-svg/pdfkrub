@extends('layouts.app')

@section('title', 'คลังแบบฟอร์มครูและเอกสารสัญญา — PDFkrub')
@section('description', 'ดาวน์โหลดแบบฟอร์มเอกสารสำหรับครู แบบฟอร์ม PA แผนการสอน บันทึกข้อความราชการ สัญญาจ้าง และเอกสารทางกฎหมายภาษาไทย พร้อมแก้ไขและเซ็นชื่อออนไลน์')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12" x-data="templateLibrary()">
    {{-- Hero Section --}}
    <div class="text-center max-w-3xl mx-auto mb-12">
        <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-brand-50 border border-brand-200 text-brand-600 text-xs font-semibold mb-4">
            <span>🏫</span> คลังแบบฟอร์มครู การศึกษา และนิติกรรมสัญญาไทย
        </div>
        <h1 class="text-4xl sm:text-5xl font-extrabold text-gray-800 tracking-tight mb-4">
            คลังแบบฟอร์มเอกสาร <span class="text-gradient">สำหรับครูและโรงเรียน</span>
        </h1>
        <p class="text-gray-500 text-base sm:text-lg">
            รวบรวมแบบฟอร์มข้อตกลง PA, แผนการสอน, บันทึกข้อความราชการ, เกียรติบัตร และสัญญามาตรฐาน ถูกต้องตามระเบียบราชการ โหลดฟรีและนำไปเซ็นชื่อได้ทันที
        </p>

        {{-- Search & Quick filters --}}
        <div class="mt-8 relative max-w-xl mx-auto">
            <svg class="w-5 h-5 text-gray-500 absolute left-4 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
            </svg>
            <input type="text" x-model="search" placeholder="ค้นหาแบบฟอร์ม เช่น สัญญาจ้าง, มอบอำนาจ, ใบเสนอราคา..."
                   class="w-full bg-gray-100 border border-white/15 text-gray-800 placeholder-slate-400 rounded-2xl pl-12 pr-4 py-3.5 text-base focus:outline-none focus:ring-2 focus:ring-brand-500 shadow-xl">
        </div>
    </div>

    {{-- Category Pills --}}
    <div class="flex items-center justify-center gap-2 overflow-x-auto pb-4 mb-10">
        <template x-for="cat in categories" :key="cat.id">
            <button @click="activeCategory = cat.id"
                    class="px-4 py-2 rounded-xl text-sm font-medium whitespace-nowrap transition-all flex items-center gap-2"
                    :class="activeCategory === cat.id ? 'bg-brand-600 text-white shadow-lg shadow-brand-500/25' : 'bg-white border border-gray-100 shadow-sm text-gray-500 hover:text-gray-800 border border-gray-100'">
                <span x-text="cat.icon"></span>
                <span x-text="cat.name"></span>
                <span class="text-xs px-2 py-0.5 rounded-full"
                      :class="activeCategory === cat.id ? 'bg-white/20 text-gray-800' : 'bg-gray-50 text-gray-400'"
                      x-text="cat.count"></span>
            </button>
        </template>
    </div>

    {{-- Template Grid --}}
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
        <template x-for="item in filteredTemplates" :key="item.id">
            <div class="bg-white border border-gray-100 shadow-sm rounded-2xl p-6 border border-gray-100 card-hover flex flex-col justify-between group">
                <div>
                    {{-- Header with badge --}}
                    <div class="flex items-start justify-between gap-3 mb-4">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center text-2xl flex-shrink-0"
                             :class="item.bgClass">
                            <span x-text="item.icon"></span>
                        </div>
                        <span class="text-xs px-2.5 py-1 rounded-full font-medium"
                              :class="item.badgeClass"
                              x-text="item.badge"></span>
                    </div>

                    {{-- Title & Description --}}
                    <h3 class="text-lg font-bold text-gray-800 mb-2 group-hover:text-brand-600 transition-colors" x-text="item.title"></h3>
                    <p class="text-gray-500 text-xs leading-relaxed mb-4" x-text="item.description"></p>

                    {{-- Tags --}}
                    <div class="flex flex-wrap gap-1.5 mb-6">
                        <template x-for="tag in item.tags" :key="tag">
                            <span class="text-[11px] text-gray-500 bg-gray-50 px-2 py-0.5 rounded-md" x-text="'#' + tag"></span>
                        </template>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="pt-4 border-t border-gray-100 flex items-center gap-2">
                    <button @click="openPreview(item)"
                            class="flex-1 btn-ghost py-2 rounded-xl text-xs flex items-center justify-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                        </svg>
                        ดูตัวอย่าง
                    </button>
                    <a href="{{ route('tools.sign-pdf') }}"
                       class="btn-primary py-2 px-3 rounded-xl text-xs flex items-center gap-1"
                       title="เปิดในโปรแกรมเซ็นลายเซ็น">
                        <span>✍️ เซ็นชื่อ</span>
                    </a>
                </div>
            </div>
        </template>
    </div>

    {{-- Empty State --}}
    <div x-show="filteredTemplates.length === 0" class="text-center py-16" style="display:none">
        <p class="text-4xl mb-3">🔍</p>
        <p class="text-gray-800 font-medium text-lg">ไม่พบแบบฟอร์มที่ค้นหา</p>
        <p class="text-gray-400 text-sm mt-1">ลองใช้คำค้นหาอื่น หรือเปลี่ยนหมวดหมู่</p>
    </div>

    {{-- Preview Modal --}}
    <div x-show="previewModal"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm"
         style="display:none"
         @keydown.escape.window="previewModal = false">
        <div class="bg-white border border-gray-100 shadow-sm max-w-2xl w-full rounded-3xl border border-gray-200 p-6 sm:p-8 space-y-6 shadow-2xl relative"
             @click.away="previewModal = false">
            {{-- Close button --}}
            <button @click="previewModal = false" class="absolute top-6 right-6 text-gray-500 hover:text-gray-800 text-lg">✕</button>

            <div class="flex items-center gap-3">
                <span class="text-3xl" x-text="activeTemplate?.icon"></span>
                <div>
                    <h3 class="text-xl font-bold text-gray-800" x-text="activeTemplate?.title"></h3>
                    <p class="text-xs text-gray-500" x-text="activeTemplate?.categoryName"></p>
                </div>
            </div>

            {{-- Document content snippet --}}
            <div class="bg-gray-50 border border-gray-200 rounded-2xl p-5 text-gray-600 text-xs font-mono leading-relaxed max-h-72 overflow-y-auto space-y-3">
                <p class="text-center font-bold text-sm text-gray-800" x-text="activeTemplate?.title"></p>
                <p class="text-right text-[11px] text-gray-500">ทำขึ้น ณ .............................................................. วันที่ ....... เดือน ................... พ.ศ. ............</p>
                <p>สัญญานี้ทำขึ้นระหว่าง <span class="text-brand-600">[ชื่อผู้ว่าจ้าง/ผู้ให้เช่า/ผู้มอบอำนาจ]</span> ฝ่ายหนึ่ง กับ <span class="text-brand-600">[ชื่อผู้รับจ้าง/ผู้เช่า/ผู้รับมอบอำนาจ]</span> อีกฝ่ายหนึ่ง โดยมีข้อตกลงดังต่อไปนี้:</p>
                <p>ข้อ 1. วัตถุประสงค์และขอบเขตข้อตกลง...</p>
                <p>ข้อ 2. ค่าตอบแทนและการชำระเงินตามที่ตกลงกัน...</p>
                <p>ข้อ 3. การรักษาความลับและข้อมูลส่วนบุคคลตาม พ.ร.บ. คุ้มครองข้อมูลส่วนบุคคล (PDPA)...</p>
                <p>ข้อ 4. การบอกเลิกสัญญาและการชดใช้ค่าเสียหาย...</p>
            </div>

            {{-- Modal Actions --}}
            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="button" @click="previewModal = false" class="btn-ghost px-5 py-2.5 rounded-xl text-sm">
                    ปิด
                </button>
                <a href="{{ route('tools.sign-pdf') }}" class="btn-primary px-6 py-2.5 rounded-xl text-sm flex items-center gap-2">
                    <span>✍️</span> นำไปเซ็นชื่อดิจิทัลทันที
                </a>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function templateLibrary() {
    return {
        search: '',
        activeCategory: 'all',
        previewModal: false,
        activeTemplate: null,

        categories: [
            { id: 'all', name: 'ทั้งหมด', icon: '📁', count: 16 },
            { id: 'teacher', name: 'ครู & การศึกษา', icon: '🏫', count: 4 },
            { id: 'contract', name: 'สัญญาจ้าง & ธุรกิจ', icon: '💼', count: 4 },
            { id: 'realestate', name: 'อสังหาฯ & เช่า', icon: '🏠', count: 3 },
            { id: 'finance', name: 'การเงิน & ภาษี', icon: '💰', count: 3 },
            { id: 'official', name: 'ราชการ & มอบอำนาจ', icon: '🏛️', count: 2 },
        ],

        templates: [
            {
                id: 101,
                title: 'แบบข้อตกลงในการพัฒนางาน (PA 1/ส)',
                description: 'แบบฟอร์มข้อตกลงในการพัฒนางานตามเกณฑ์ ว PA ครบทั้ง 2 ส่วน (ภาระงานตาม ก.ค.ศ. และประเด็นท้าทาย)',
                category: 'teacher',
                categoryName: 'ครู & การศึกษา',
                icon: '📋',
                badge: 'ยอดนิยมสำหรับครู',
                badgeClass: 'bg-success-100 text-success-300 border border-success-200',
                bgClass: 'bg-success-50 text-success-600',
                tags: ['PA', 'ว PA', 'วิทยฐานะ', 'ครู', 'ก.ค.ศ.'],
            },
            {
                id: 102,
                title: 'แผนการจัดการเรียนรู้ Active Learning',
                description: 'เทมเพลตแผนการสอนแบบเชิงรุก 5 ขั้นตอน (5Es / Co-5Step) พร้อมเกณฑ์การวัดและประเมินผลรูบริกส์',
                category: 'teacher',
                categoryName: 'ครู & การศึกษา',
                icon: '📖',
                badge: 'มาตรฐาน สพฐ.',
                badgeClass: 'bg-brand-100 text-brand-600 border border-brand-200',
                bgClass: 'bg-brand-50 text-brand-600',
                tags: ['แผนการสอน', 'Active Learning', 'สพฐ', 'การจัดการเรียนรู้'],
            },
            {
                id: 103,
                title: 'รายงานประเมินตนเองของสถานศึกษา (SAR)',
                description: 'แบบฟอร์มสรุปรายงาน SAR รายบุคคลและกลุ่มสาระฯ สรุปผลสัมฤทธิ์และผลการปฏิบัติหน้าที่ตลอดปีการศึกษา',
                category: 'teacher',
                categoryName: 'ครู & การศึกษา',
                icon: '📊',
                badge: 'งานประกันคุณภาพ',
                badgeClass: 'bg-purple-500/20 text-purple-300 border border-purple-500/30',
                bgClass: 'bg-purple-500/10 text-purple-400',
                tags: ['SAR', 'ประกันคุณภาพ', 'ประเมินตนเอง', 'รายงานผลงาน'],
            },
            {
                id: 104,
                title: 'แม่แบบเกียรติบัตรนักเรียนและครู (A4 แนวนอน)',
                description: 'เทมเพลตเกียรติบัตรงานแข่งขันทักษะวิชาการ, นักเรียนดีเด่น, อบรมสัมมนา พร้อมจุดใส่ตราโรงเรียนและลายเซ็น',
                category: 'teacher',
                categoryName: 'ครู & การศึกษา',
                icon: '🏅',
                badge: 'พร้อมพิมพ์ A4',
                badgeClass: 'bg-amber-500/20 text-amber-300 border border-amber-500/30',
                bgClass: 'bg-amber-500/10 text-amber-400',
                tags: ['เกียรติบัตร', 'ประกาศนียบัตร', 'โรงเรียน', 'แข่งขันวิชาการ'],
            },
            {
                id: 1,
                title: 'สัญญาจ้างงานพนักงานประจำ',
                description: 'สัญญาจ้างแรงงานมาตรฐานตาม พ.ร.บ. คุ้มครองแรงงาน มีระบุช่วงทดลองงานและสวัสดิการครบถ้วน',
                category: 'contract',
                categoryName: 'สัญญาจ้าง & ธุรกิจ',
                icon: '👔',
                badge: 'ยอดนิยม',
                badgeClass: 'bg-brand-100 text-brand-600 border border-brand-200',
                bgClass: 'bg-brand-50 text-brand-600',
                tags: ['สัญญาจ้างงาน', 'HR', 'ทดลองงาน', 'แรงงาน'],
            },
            {
                id: 2,
                title: 'สัญญาไม่เปิดเผยข้อมูล (NDA)',
                description: 'ข้อตกลงรักษาความลับทางการค้าและข้อมูลทางธุรกิจ รองรับภาษาไทยและอังกฤษ ป้องกันข้อมูลรั่วไหล',
                category: 'contract',
                categoryName: 'สัญญาจ้าง & ธุรกิจ',
                icon: '🔒',
                badge: 'มาตรฐาน',
                badgeClass: 'bg-purple-500/20 text-purple-300 border border-purple-500/30',
                bgClass: 'bg-purple-500/10 text-purple-400',
                tags: ['NDA', 'ความลับทางการค้า', 'ธุรกิจ', 'PDPA'],
            },
            {
                id: 3,
                title: 'สัญญาจ้างบริการ / ฟรีแลนซ์',
                description: 'สัญญาจ้างทำของสำหรับงานโปรเจกต์ ฟรีแลนซ์ Outsource ระบุงวดชำระและสิทธิ์ในทรัพย์สินทางปัญญา',
                category: 'contract',
                categoryName: 'สัญญาจ้าง & ธุรกิจ',
                icon: '💻',
                badge: 'ฟรีแลนซ์',
                badgeClass: 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30',
                bgClass: 'bg-emerald-500/10 text-emerald-400',
                tags: ['ฟรีแลนซ์', 'จ้างทำของ', 'ส่งมอบงาน'],
            },
            {
                id: 4,
                title: 'สัญญาเช่าบ้าน / คอนโดมิเนียม',
                description: 'สัญญาเช่าที่พักอาศัย ระบุเงินประกัน มัดจำ การชำระค่าน้ำค่าไฟ และกฎระเบียบการอยู่อาศัย',
                category: 'realestate',
                categoryName: 'อสังหาฯ & เช่า',
                icon: '🏢',
                badge: 'ยอดนิยม',
                badgeClass: 'bg-brand-100 text-brand-600 border border-brand-200',
                bgClass: 'bg-brand-50 text-brand-600',
                tags: ['เช่าคอนโด', 'เช่าบ้าน', 'เงินประกัน', 'อสังหา'],
            },
            {
                id: 5,
                title: 'สัญญาจะซื้อจะขายที่ดินและสิ่งปลูกสร้าง',
                description: 'สัญญาวางมัดจำซื้อขายอสังหาริมทรัพย์ กำหนดวันโอนกรรมสิทธิ์ ณ กรมที่ดิน และค่าธรรมเนียมภาษี',
                category: 'realestate',
                categoryName: 'อสังหาฯ & เช่า',
                icon: '📜',
                badge: 'กฎหมาย',
                badgeClass: 'bg-amber-500/20 text-amber-300 border border-amber-500/30',
                bgClass: 'bg-amber-500/10 text-amber-400',
                tags: ['ซื้อขายที่ดิน', 'โอนกรรมสิทธิ์', 'มัดจำ'],
            },
            {
                id: 6,
                title: 'หนังสือมอบอำนาจทั่วไป',
                description: 'แบบฟอร์มหนังสือมอบอำนาจตามประมวลกฎหมายแพ่งและพาณิชย์ สำหรับติดต่อราชการ ธนาคาร กรมที่ดิน',
                category: 'official',
                categoryName: 'ราชการ & มอบอำนาจ',
                icon: '🏛️',
                badge: 'มาตรฐานราชการ',
                badgeClass: 'bg-blue-500/20 text-blue-300 border border-blue-500/30',
                bgClass: 'bg-blue-500/10 text-blue-400',
                tags: ['มอบอำนาจ', 'ติดต่อราชการ', 'ธนาคาร'],
            },
            {
                id: 7,
                title: 'ใบเสร็จรับเงิน / ใบกำกับภาษี',
                description: 'แบบฟอร์มเอกสารทางบัญชีและภาษี ระบุอัตราภาษีมูลค่าเพิ่ม 7% พร้อมช่องลายเซ็นผู้รับเงิน',
                category: 'finance',
                categoryName: 'การเงิน & ภาษี',
                icon: '🧾',
                badge: 'สรรพากร',
                badgeClass: 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30',
                bgClass: 'bg-emerald-500/10 text-emerald-400',
                tags: ['ใบกำกับภาษี', 'ใบเสร็จ', 'VAT', 'บัญชี'],
            },
            {
                id: 8,
                title: 'สัญญากู้ยืมเงินมีดอกเบี้ย',
                description: 'สัญญากู้ยืมเงินถูกต้องตามกฎหมาย อัตราดอกเบี้ยไม่เกินร้อยละ 15 ต่อปี พร้อมข้อตกลงการผ่อนชำระ',
                category: 'finance',
                categoryName: 'การเงิน & ภาษี',
                icon: '💵',
                badge: 'กฎหมาย',
                badgeClass: 'bg-red-500/20 text-red-300 border border-red-500/30',
                bgClass: 'bg-red-500/10 text-red-400',
                tags: ['กู้ยืมเงิน', 'ดอกเบี้ย', 'ผ่อนชำระ'],
            },
            {
                id: 9,
                title: 'บันทึกข้อตกลงความร่วมมือ (MOU)',
                description: 'กรอบข้อตกลงความร่วมมือระหว่างองค์กร พันธมิตรทางธุรกิจ และสถาบันการศึกษา',
                category: 'official',
                categoryName: 'ราชการ & มอบอำนาจ',
                icon: '🤝',
                badge: 'ธุรกิจ',
                badgeClass: 'bg-indigo-500/20 text-indigo-300 border border-indigo-500/30',
                bgClass: 'bg-indigo-500/10 text-indigo-400',
                tags: ['MOU', 'บันทึกข้อตกลง', 'พันธมิตร'],
            },
            {
                id: 10,
                title: 'ใบเสนอราคา (Quotation)',
                description: 'แบบฟอร์มเสนอราคาสินค้าและบริการ รายการสินค้า ยอดรวม ส่วนลด และเงื่อนไขการชำระเงิน',
                category: 'finance',
                categoryName: 'การเงิน & ภาษี',
                icon: '📊',
                badge: 'ยอดนิยม',
                badgeClass: 'bg-brand-100 text-brand-600 border border-brand-200',
                bgClass: 'bg-brand-50 text-brand-600',
                tags: ['ใบเสนอราคา', 'Quotation', 'การขาย'],
            },
            {
                id: 11,
                title: 'สัญญาเช่าพื้นที่เชิงพาณิชย์',
                description: 'สัญญาเช่าพื้นที่ร้านค้า อาคารสำนักงาน หรือพื้นที่ในห้างสรรพสินค้า พร้อมระบุเงื่อนไขการตกแต่ง',
                category: 'realestate',
                categoryName: 'อสังหาฯ & เช่า',
                icon: '🏬',
                badge: 'พาณิชย์',
                badgeClass: 'bg-teal-500/20 text-teal-300 border border-teal-500/30',
                bgClass: 'bg-teal-500/10 text-teal-400',
                tags: ['เช่าร้านค้า', 'เช่าสำนักงาน', 'คอมเมิร์ซ'],
            },
            {
                id: 12,
                title: 'หนังสือบอกเลิกสัญญา',
                description: 'แบบฟอร์มแจ้งบอกเลิกสัญญาอย่างเป็นทางการ พร้อมระบุเหตุผลและกำหนดวันสิ้นสุดผลผูกพัน',
                category: 'contract',
                categoryName: 'สัญญาจ้าง & ธุรกิจ',
                icon: '✉️',
                badge: 'กฎหมาย',
                badgeClass: 'bg-rose-500/20 text-rose-300 border border-rose-500/30',
                bgClass: 'bg-rose-500/10 text-rose-400',
                tags: ['บอกเลิกสัญญา', 'หนังสือแจ้ง', 'ข้อพิพาท'],
            },
        ],

        get filteredTemplates() {
            return this.templates.filter(item => {
                const matchCategory = this.activeCategory === 'all' || item.category === this.activeCategory;
                const matchSearch = this.search === '' ||
                    item.title.toLowerCase().includes(this.search.toLowerCase()) ||
                    item.description.toLowerCase().includes(this.search.toLowerCase()) ||
                    item.tags.some(tag => tag.toLowerCase().includes(this.search.toLowerCase()));
                return matchCategory && matchSearch;
            });
        },

        openPreview(item) {
            this.activeTemplate = item;
            this.previewModal = true;
        },
    };
}
</script>
@endpush
@endsection
