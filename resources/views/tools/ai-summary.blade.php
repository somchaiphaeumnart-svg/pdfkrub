@extends('layouts.app')

@section('title', 'AI สรุป PDF — สรุปสาระสำคัญ จับประเด็น และ Action Items')
@section('description', 'ให้ AI อัจฉริยะช่วยสรุปสาระสำคัญจากเอกสาร PDF, Word, Text ของคุณโดยอัตโนมัติ รวดเร็ว ถูกต้อง พร้อมจับประเด็นสำคัญและข้อเสนอแนะ')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12"
     x-data="aiSummaryTool()"
     x-init="init()">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm text-gray-400 mb-8">
        <a href="{{ route('home') }}" class="hover:text-brand-600 transition-colors">หน้าแรก</a>
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
        <span class="text-gray-600">AI สรุป PDF</span>
    </nav>

    {{-- Header --}}
    <div class="flex items-start gap-5 mb-10">
        <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-purple-600 via-pink-600 to-rose-500 flex items-center justify-center text-3xl shadow-lg flex-shrink-0 text-white shadow-pink-500/20">
            ✨
        </div>
        <div>
            <div class="flex items-center gap-3 mb-1">
                <h1 class="text-3xl font-bold text-gray-800">AI สรุป PDF</h1>
                <span class="badge-premium">Pro</span>
                <span class="text-xs bg-purple-500/10 text-purple-600 px-3 py-1 rounded-full font-semibold border border-purple-200">
                    Smart AI Engine
                </span>
            </div>
            <p class="text-gray-500 text-base">ให้ AI สรุปสาระสำคัญจากเอกสารของคุณโดยอัตโนมัติ จับประเด็นสำคัญ สถิติตัวเลข และสรุปสิ่งที่ต้องดำเนินการต่อ</p>
        </div>
    </div>

    <div class="grid lg:grid-cols-12 gap-8">

        {{-- Left: Upload & Settings (5 Cols) --}}
        <div class="lg:col-span-5 space-y-6">

            {{-- Upload Card --}}
            <div class="bg-white border border-gray-100 shadow-sm rounded-3xl p-6 border border-gray-100">
                <h2 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <span>📁</span> เลือกเอกสารที่ต้องการสรุป
                </h2>

                <div class="upload-zone p-8 text-center cursor-pointer transition-all"
                     @dragover.prevent="isDragging = true"
                     @dragleave.prevent="isDragging = false"
                     @drop.prevent="handleDrop($event)"
                     :class="{ 'drag-over': isDragging }"
                     @click="$refs.fileInput.click()">
                    
                    <input type="file" x-ref="fileInput" class="hidden"
                           accept=".pdf,.doc,.docx,.txt"
                           @change="handleFiles($event)">

                    <div class="w-14 h-14 bg-purple-50 text-purple-600 rounded-2xl flex items-center justify-center mx-auto mb-3">
                        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                        </svg>
                    </div>
                    <p class="text-gray-800 font-semibold mb-1 text-sm">ลากเอกสารมาวางที่นี่ หรือคลิกเพื่อเลือกไฟล์</p>
                    <p class="text-gray-400 text-xs">รองรับ PDF, Word (.docx), ข้อความ (.txt)</p>
                </div>

                {{-- Selected File Display --}}
                <template x-if="file">
                    <div class="mt-4 p-3 bg-purple-50/60 rounded-2xl border border-purple-100 flex items-center justify-between">
                        <div class="flex items-center gap-3 overflow-hidden">
                            <span class="text-2xl">📑</span>
                            <div class="truncate">
                                <p class="text-xs font-semibold text-gray-800 truncate" x-text="file.name"></p>
                                <p class="text-[10px] text-gray-500" x-text="formatSize(file.size)"></p>
                            </div>
                        </div>
                        <button type="button" @click="file = null; summaryText = ''" class="text-gray-400 hover:text-red-500 p-1 transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </template>
            </div>

            {{-- AI Settings Card --}}
            <div class="bg-white border border-gray-100 shadow-sm rounded-3xl p-6 border border-gray-100 space-y-5">
                <h2 class="font-bold text-gray-800 flex items-center gap-2">
                    <span>⚙️</span> การตั้งค่าการสรุป
                </h2>

                {{-- Summary Length --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-2">ระดับความยาวของบทสรุป</label>
                    <div class="grid grid-cols-3 gap-2">
                        <button type="button" 
                                @click="summaryLength = 'short'"
                                :class="summaryLength === 'short' ? 'border-purple-600 bg-purple-50/80 text-purple-700 font-semibold' : 'border-gray-200 text-gray-600 hover:bg-gray-50'"
                                class="p-2.5 rounded-xl border text-xs text-center transition-all">
                            ⚡ กระชับ
                        </button>
                        <button type="button" 
                                @click="summaryLength = 'standard'"
                                :class="summaryLength === 'standard' ? 'border-purple-600 bg-purple-50/80 text-purple-700 font-semibold' : 'border-gray-200 text-gray-600 hover:bg-gray-50'"
                                class="p-2.5 rounded-xl border text-xs text-center transition-all">
                            📑 มาตรฐาน
                        </button>
                        <button type="button" 
                                @click="summaryLength = 'detailed'"
                                :class="summaryLength === 'detailed' ? 'border-purple-600 bg-purple-50/80 text-purple-700 font-semibold' : 'border-gray-200 text-gray-600 hover:bg-gray-50'"
                                class="p-2.5 rounded-xl border text-xs text-center transition-all">
                            📖 ละเอียด
                        </button>
                    </div>
                </div>

                {{-- Summary Focus --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-2">จุดเน้นพิเศษ</label>
                    <div class="space-y-2">
                        <label class="flex items-center gap-3 p-2.5 rounded-xl border cursor-pointer transition-all"
                               :class="summaryFocus === 'general' ? 'border-purple-600 bg-purple-50/50' : 'border-gray-200 hover:bg-gray-50'">
                            <input type="radio" name="focus" value="general" x-model="summaryFocus" class="text-purple-600 focus:ring-purple-500">
                            <div>
                                <p class="text-xs font-semibold text-gray-800">🌟 สาระสำคัญทั่วไป</p>
                                <p class="text-[10px] text-gray-500">ภาพรวมครบทุกหมวดหมู่ เหมาะกับรายงานและเอกสารทั่วไป</p>
                            </div>
                        </label>

                        <label class="flex items-center gap-3 p-2.5 rounded-xl border cursor-pointer transition-all"
                               :class="summaryFocus === 'action_items' ? 'border-purple-600 bg-purple-50/50' : 'border-gray-200 hover:bg-gray-50'">
                            <input type="radio" name="focus" value="action_items" x-model="summaryFocus" class="text-purple-600 focus:ring-purple-500">
                            <div>
                                <p class="text-xs font-semibold text-gray-800">✅ เน้น Action Items & งานที่ต้องทำ</p>
                                <p class="text-[10px] text-gray-500">ดึงภารกิจ ผู้รับผิดชอบ และกำหนดการที่ต้องปฏิบัติตาม</p>
                            </div>
                        </label>

                        <label class="flex items-center gap-3 p-2.5 rounded-xl border cursor-pointer transition-all"
                               :class="summaryFocus === 'numbers' ? 'border-purple-600 bg-purple-50/50' : 'border-gray-200 hover:bg-gray-50'">
                            <input type="radio" name="focus" value="numbers" x-model="summaryFocus" class="text-purple-600 focus:ring-purple-500">
                            <div>
                                <p class="text-xs font-semibold text-gray-800">📊 เน้นสถิติ ตัวเลข & เงื่อนไข</p>
                                <p class="text-[10px] text-gray-500">เน้นงบประมาณ ตัวชี้วัด เกณฑ์คะแนน และเงื่อนไขสำคัญ</p>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Action Button --}}
                <button type="button"
                        @click="startSummarize()"
                        :disabled="!file || isProcessing"
                        class="w-full py-3.5 px-6 rounded-2xl font-bold text-sm text-white shadow-lg transition-all flex items-center justify-center gap-2"
                        :class="!file || isProcessing ? 'bg-gray-300 cursor-not-allowed shadow-none' : 'bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 shadow-purple-500/25 active:scale-[0.98]'">
                    <template x-if="!isProcessing">
                        <span class="flex items-center gap-2">
                            <span>✨</span> ให้ AI สรุปเอกสารทันที
                        </span>
                    </template>
                    <template x-if="isProcessing">
                        <span class="flex items-center gap-2">
                            <svg class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span x-text="`กำลังประมวลผล ( ${progress}% )`"></span>
                        </span>
                    </template>
                </button>
            </div>

        </div>

        {{-- Right: Result Display (7 Cols) --}}
        <div class="lg:col-span-7">
            <div class="bg-white border border-gray-100 shadow-sm rounded-3xl p-6 border border-gray-100 min-h-[500px] flex flex-col">

                {{-- Card Header --}}
                <div class="flex items-center justify-between pb-4 mb-4 border-b border-gray-100">
                    <div>
                        <h2 class="font-bold text-gray-800 text-lg flex items-center gap-2">
                            <span>📑</span> ผลการสรุปสาระสำคัญ
                        </h2>
                        <p class="text-xs text-gray-400">เนื้อหาที่ผ่านการประมวลผลด้วย AI</p>
                    </div>

                    <div class="flex items-center gap-2" x-show="summaryText">
                        <button type="button" 
                                @click="copySummary()" 
                                class="px-3 py-1.5 rounded-xl border border-gray-200 text-xs font-semibold text-gray-700 hover:bg-gray-50 flex items-center gap-1.5 transition-all">
                            <span x-text="copied ? '✓ คัดลอกแล้ว' : '📋 คัดลอก'"></span>
                        </button>

                        <button type="button" 
                                @click="downloadSummary()" 
                                class="px-3 py-1.5 rounded-xl bg-purple-50 text-purple-700 border border-purple-200 text-xs font-semibold hover:bg-purple-100 flex items-center gap-1.5 transition-all">
                            <span>⬇️ บันทึกไฟล์ TXT</span>
                        </button>
                    </div>
                </div>

                {{-- Processing Progress View --}}
                <template x-if="isProcessing">
                    <div class="my-auto py-16 text-center space-y-5">
                        <div class="relative w-20 h-20 mx-auto">
                            <div class="absolute inset-0 rounded-full bg-gradient-to-tr from-purple-500 to-pink-500 opacity-20 animate-ping"></div>
                            <div class="relative w-20 h-20 rounded-2xl bg-gradient-to-tr from-purple-600 to-pink-600 flex items-center justify-center text-3xl shadow-xl shadow-pink-500/20 text-white animate-pulse">
                                ✨
                            </div>
                        </div>
                        <div>
                            <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-purple-50 border border-purple-200 text-purple-700 text-sm font-bold mb-2 shadow-xs">
                                <span class="w-2 h-2 rounded-full bg-purple-600 animate-ping"></span>
                                <span x-text="`${progress}%`"></span>
                            </div>
                            <h3 class="text-gray-800 font-bold text-lg mb-1">
                                <span x-text="processingStatus"></span>
                                <span class="text-purple-600 font-extrabold ml-1" x-text="`(${progress}%)`"></span>
                            </h3>
                            <p class="text-gray-400 text-xs">กำลังสกัดใจความสำคัญและเรียบเรียงเป็นข้อความที่เข้าใจง่าย...</p>
                        </div>
                        <div class="w-72 mx-auto space-y-2">
                            <div class="w-full bg-gray-100 rounded-full h-3 overflow-hidden p-0.5 border border-gray-200/60 shadow-inner">
                                <div class="bg-gradient-to-r from-purple-500 via-pink-500 to-rose-500 h-full transition-all duration-300 rounded-full"
                                     :style="`width: ${progress}%`"></div>
                            </div>
                            <div class="flex justify-between items-center text-xs text-gray-500 px-1 font-medium">
                                <span>ความคืบหน้า</span>
                                <span class="font-bold text-purple-600 bg-purple-50 px-2 py-0.5 rounded border border-purple-100" x-text="`${progress}%`"></span>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- Empty State --}}
                <template x-if="!isProcessing && !summaryText">
                    <div class="my-auto py-16 text-center space-y-3">
                        <div class="w-16 h-16 rounded-2xl bg-gray-50 flex items-center justify-center text-3xl mx-auto text-gray-300">
                            🤖
                        </div>
                        <p class="text-gray-600 font-medium text-sm">ยังไม่มีข้อมูลบทสรุป</p>
                        <p class="text-gray-400 text-xs max-w-xs mx-auto">เลือกไฟล์เอกสาร PDF หรือ Word จากฝั่งซ้าย แล้วกดปุ่ม "ให้ AI สรุปเอกสารทันที"</p>
                    </div>
                </template>

                {{-- Result Text Box --}}
                <template x-if="!isProcessing && summaryText">
                    <div class="flex-1 flex flex-col">
                        <div class="flex-1 p-5 rounded-2xl bg-slate-50/70 border border-slate-100 text-gray-700 text-sm leading-relaxed overflow-y-auto max-h-[550px] whitespace-pre-wrap font-sans selection:bg-purple-100"
                             x-text="summaryText">
                        </div>

                        {{-- Metadata footer --}}
                        <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between text-xs text-gray-400">
                            <span x-text="`ความยาว: ${summaryText.length.toLocaleString()} ตัวอักษร`"></span>
                            <span>ประมวลผลด้วย AI สรุปสาระสำคัญ</span>
                        </div>
                    </div>
                </template>

            </div>
        </div>

    </div>

</div>

@push('scripts')
<script>
function aiSummaryTool() {
    return {
        file: null,
        isDragging: false,
        isProcessing: false,
        progress: 0,
        processingStatus: 'กำลังส่งเอกสาร...',
        summaryText: '',
        copied: false,
        pollTimer: null,

        // Config options
        summaryLength: 'standard',
        summaryFocus: 'general',

        async init() {
            if (window.getStagedFiles) {
                try {
                    const staged = await window.getStagedFiles();
                    if (staged && staged.length > 0) {
                        const first = staged[0]?.file;
                        if (first) {
                            this.file = first;
                            if (window.clearStagedFiles) await window.clearStagedFiles();
                        }
                    }
                } catch (e) {
                    console.warn('AI Summary load staged file error:', e);
                }
            }
        },

        handleDrop(event) {
            this.isDragging = false;
            const dropped = event.dataTransfer.files;
            if (dropped.length > 0) {
                this.file = dropped[0];
            }
        },

        handleFiles(event) {
            const picked = event.target.files;
            if (picked.length > 0) {
                this.file = picked[0];
            }
        },

        async startSummarize() {
            if (!this.file) return;

            this.isProcessing = true;
            this.progress = 15;
            this.summaryText = '';
            this.processingStatus = 'กำลังอัปโหลดเอกสาร...';

            const formData = new FormData();
            formData.append('files[]', this.file);
            formData.append('tool', 'ai-summary');
            formData.append('config[length]', this.summaryLength);
            formData.append('config[focus]', this.summaryFocus);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

            try {
                const res = await fetch('/files/upload', {
                    method: 'POST',
                    body: formData,
                });
                const data = await res.json();

                if (!res.ok) {
                    throw new Error(data.error || 'อัปโหลดเอกสารไม่สำเร็จ');
                }

                this.progress = 30;
                this.processingStatus = 'AI กำลังวิเคราะห์และสรุปสาระสำคัญ...';
                this.startPolling(data.status_url);
            } catch (err) {
                this.isProcessing = false;
                alert('เกิดข้อผิดพลาด: ' + err.message);
            }
        },

        startPolling(statusUrl) {
            let tick = 30;
            this.pollTimer = setInterval(async () => {
                try {
                    const res = await fetch(statusUrl);
                    const data = await res.json();

                    if (data.status === 'processing') {
                        tick = Math.min(95, tick + 5);
                        this.progress = (data.progress && data.progress > 0) ? data.progress : tick;
                        if (this.progress < 40) {
                            this.processingStatus = 'กำลังสกัดข้อความจากเอกสาร...';
                        } else if (this.progress < 75) {
                            this.processingStatus = 'AI กำลังวิเคราะห์และสรุปสาระสำคัญ...';
                        } else {
                            this.processingStatus = 'กำลังเรียบเรียงและจัดรูปแบบบทสรุป...';
                        }
                    } else if (data.status === 'done') {
                        clearInterval(this.pollTimer);
                        this.progress = 100;
                        this.processingStatus = 'สรุปเอกสารเรียบร้อยแล้ว!';
                        this.isProcessing = false;
                        if (data.extracted_text) {
                            this.summaryText = data.extracted_text;
                        } else if (data.download_url) {
                            const textRes = await fetch(data.download_url);
                            this.summaryText = await textRes.text();
                        }
                    } else if (data.status === 'failed') {
                        clearInterval(this.pollTimer);
                        this.isProcessing = false;
                        alert('การสรุปเอกสารล้มเหลว: ' + (data.error_message || 'เกิดข้อผิดพลาดไม่ทราบสาเหตุ'));
                    }
                } catch (e) {
                    console.error('Polling error:', e);
                }
            }, 1500);
        },

        copySummary() {
            if (!this.summaryText) return;
            navigator.clipboard.writeText(this.summaryText);
            this.copied = true;
            setTimeout(() => { this.copied = false; }, 2500);
        },

        downloadSummary() {
            if (!this.summaryText) return;
            const blob = new Blob([this.summaryText], { type: 'text/plain;charset=utf-8' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            const base = this.file ? this.file.name.replace(/\.[^/.]+$/, '') : 'summary';
            a.download = `${base}_summary.txt`;
            a.click();
            URL.revokeObjectURL(url);
        },

        formatSize(bytes) {
            if (!bytes) return '0 B';
            const units = ['B', 'KB', 'MB', 'GB'];
            let s = bytes, u = 0;
            while (s >= 1024 && u < units.length - 1) { s /= 1024; u++; }
            return `${s.toFixed(1)} ${units[u]}`;
        }
    };
}
</script>
@endpush
@endsection
