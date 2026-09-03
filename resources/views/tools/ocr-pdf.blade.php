@extends('layouts.app')

@section('title', 'OCR ภาษาไทย — แปลงรูปภาพเป็นข้อความ')
@section('description', 'OCR ภาษาไทยแม่นยำสูง ด้วย Google Cloud Vision API รองรับ PDF สแกน รูปภาพ ทั้งภาษาไทยและอังกฤษ')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12"
     x-data="ocrTool()"
     x-init="init()">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm text-gray-400 mb-8">
        <a href="{{ route('home') }}" class="hover:text-brand-600 transition-colors">หน้าแรก</a>
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
        <span class="text-gray-600">OCR ภาษาไทย</span>
    </nav>

    {{-- Header --}}
    <div class="flex items-start gap-5 mb-10">
        <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-emerald-600 to-emerald-500 flex items-center justify-center text-2xl shadow-lg flex-shrink-0">🔍</div>
        <div>
            <div class="flex items-center gap-3 mb-1">
                <h1 class="text-3xl font-bold text-gray-800">OCR ภาษาไทย</h1>
                <span class="badge-premium">Pro</span>
                <span class="text-xs bg-emerald-500/20 text-emerald-400 px-2.5 py-1 rounded-full font-medium">Google Vision AI</span>
            </div>
            <p class="text-gray-500">แปลงรูปภาพและ PDF สแกนเป็นข้อความ รองรับภาษาไทย-อังกฤษ แม่นยำระดับ 99%</p>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-8">

        {{-- Left: Upload --}}
        <div class="space-y-5">
            {{-- Upload zone --}}
            <div class="bg-white border border-gray-100 shadow-sm rounded-2xl border border-gray-100 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="font-semibold text-gray-800">อัปโหลดไฟล์</h2>
                </div>
                <div class="p-5">
                    <div class="upload-zone p-8 text-center cursor-pointer"
                         @dragover.prevent="isDragging = true"
                         @dragleave.prevent="isDragging = false"
                         @drop.prevent="handleDrop($event)"
                         :class="{ 'drag-over': isDragging }"
                         @click="$refs.fileInput.click()">
                        <input type="file" x-ref="fileInput" class="hidden"
                               accept=".pdf,.jpg,.jpeg,.png,.webp,.tiff,.bmp"
                               multiple
                               @change="handleFiles($event)">
                        <div class="w-14 h-14 bg-emerald-500/20 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <svg class="w-7 h-7 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/>
                            </svg>
                        </div>
                        <p class="text-gray-800 font-semibold mb-1">ลากไฟล์มาวางหรือคลิกเพื่อเลือก</p>
                        <p class="text-gray-500 text-sm">PDF, JPG, PNG, TIFF, BMP — สูงสุด 200 MB</p>
                    </div>

                    {{-- File list --}}
                    <template x-if="files.length > 0">
                        <div class="mt-4 space-y-2">
                            <template x-for="(f, i) in files" :key="i">
                                <div class="flex items-center gap-3 bg-white border border-gray-100 shadow-sm-light rounded-xl px-4 py-3 border border-white/[0.04]">
                                    <div class="w-8 h-8 bg-emerald-500/20 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <span class="text-xs text-emerald-400 font-bold" x-text="f.name.split('.').pop().toUpperCase().slice(0,3)"></span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm text-gray-800 truncate" x-text="f.name"></p>
                                        <p class="text-xs text-gray-400" x-text="formatSize(f.size)"></p>
                                    </div>
                                    <button @click="removeFile(i)" class="text-gray-300 hover:text-error-500 transition-colors">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Settings --}}
            <div class="bg-white border border-gray-100 shadow-sm rounded-2xl border border-gray-100 p-5 space-y-4">
                <h3 class="font-semibold text-gray-800 text-sm">ตั้งค่า OCR</h3>

                <div>
                    <label class="text-xs text-gray-500 block mb-2">ภาษาหลัก</label>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="lang in languages" :key="lang.code">
                            <button @click="toggleLanguage(lang.code)"
                                    class="px-3 py-1.5 rounded-xl text-xs border transition-all"
                                    :class="selectedLangs.includes(lang.code)
                                        ? 'border-emerald-500 bg-emerald-500/20 text-emerald-300 font-medium'
                                        : 'border-gray-200 text-gray-500 hover:border-gray-200'">
                                <span x-text="lang.flag + ' ' + lang.name"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <div>
                    <label class="text-xs text-gray-500 block mb-2">รูปแบบผลลัพธ์</label>
                    <div class="grid grid-cols-3 gap-2">
                        <template x-for="fmt in formats" :key="fmt.value">
                            <button @click="outputFormat = fmt.value"
                                    class="py-2 px-3 rounded-xl text-xs border transition-all text-center"
                                    :class="outputFormat === fmt.value
                                        ? 'border-emerald-500 bg-emerald-500/10 text-gray-800 font-medium'
                                        : 'border-gray-200 text-gray-500'">
                                <span x-text="fmt.icon + ' ' + fmt.label"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <div class="relative">
                            <input type="checkbox" x-model="detectTables" class="sr-only">
                            <div class="w-10 h-5 rounded-full transition-all" :class="detectTables ? 'bg-emerald-500' : 'bg-gray-100'"></div>
                            <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform" :class="detectTables ? 'translate-x-5' : ''"></div>
                        </div>
                        <span class="text-xs text-gray-600">ตรวจจับตาราง (Table Detection)</span>
                    </label>
                </div>

                {{-- Process button --}}
                <button @click="startOcr()"
                        :disabled="files.length === 0 || isProcessing"
                        class="w-full btn-primary py-3.5 rounded-xl text-sm flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                    <template x-if="!isProcessing">
                        <span class="flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/>
                            </svg>
                            เริ่ม OCR
                        </span>
                    </template>
                    <template x-if="isProcessing">
                        <span class="flex items-center gap-2">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            กำลังประมวลผล (<span x-text="progress + '%'"></span>)
                        </span>
                    </template>
                </button>

                @guest
                <div class="bg-white border border-gray-100 shadow-sm-light rounded-xl p-3 border border-accent-500/20 text-center">
                    <p class="text-xs text-gray-500">
                        <a href="{{ route('register') }}" class="text-brand-600 hover:text-brand-600">สมัครสมาชิก</a>
                        หรือ
                        <a href="{{ route('login') }}" class="text-brand-600 hover:text-brand-600">เข้าสู่ระบบ</a>
                        เพื่อใช้ OCR แบบเต็ม (ต้องการแผน Pro)
                    </p>
                </div>
                @endguest
            </div>
        </div>

        {{-- Right: Result --}}
        <div class="space-y-5">
            <div class="bg-white border border-gray-100 shadow-sm rounded-2xl border border-gray-100 overflow-hidden h-full flex flex-col">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="font-semibold text-gray-800">ข้อความที่ได้</h2>
                    <div class="flex items-center gap-2" x-show="extractedText">
                        <button @click="copyText()" class="text-xs text-gray-500 hover:text-gray-800 transition-colors flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 0 1-.75.75H9a.75.75 0 0 1-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 0 1 1.927-.184"/></svg>
                            คัดลอก
                        </button>
                        <button @click="downloadText()" class="text-xs text-gray-500 hover:text-gray-800 transition-colors flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                            ดาวน์โหลด
                        </button>
                    </div>
                </div>

                <div class="flex-1 p-5">
                    {{-- Empty state --}}
                    <div x-show="!extractedText && !isProcessing" class="h-64 flex flex-col items-center justify-center text-center">
                        <div class="w-16 h-16 bg-emerald-500/10 rounded-2xl flex items-center justify-center mb-4">
                            <svg class="w-8 h-8 text-emerald-500/50" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                            </svg>
                        </div>
                        <p class="text-gray-400 text-sm">อัปโหลดไฟล์และกด "เริ่ม OCR"<br>ข้อความจะแสดงที่นี่</p>
                    </div>

                    {{-- Processing animation --}}
                    <div x-show="isProcessing" class="h-64 flex flex-col items-center justify-center gap-4">
                        <div class="relative w-20 h-20">
                            <div class="absolute inset-0 rounded-full border-4 border-emerald-500/20"></div>
                            <div class="absolute inset-0 rounded-full border-4 border-emerald-500 border-t-transparent animate-spin"></div>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="text-emerald-400 text-sm font-bold" x-text="progress + '%'"></span>
                            </div>
                        </div>
                        <div class="text-center">
                            <p class="text-gray-600 font-medium text-sm" x-text="processingStatus"></p>
                            <p class="text-gray-400 text-xs mt-1">Google Cloud Vision กำลังวิเคราะห์...</p>
                        </div>
                    </div>

                    {{-- Result text --}}
                    <div x-show="extractedText" class="h-full">
                        <textarea x-model="extractedText"
                                  class="w-full h-96 bg-transparent text-gray-700 text-sm leading-relaxed resize-none focus:outline-none font-mono"
                                  placeholder="ข้อความที่แยกได้จะแสดงที่นี่..."
                                  readonly></textarea>

                        {{-- Stats bar --}}
                        <div class="flex items-center gap-4 mt-3 pt-3 border-t border-gray-100 text-xs text-gray-400">
                            <span x-text="`${wordCount} คำ`"></span>
                            <span>·</span>
                            <span x-text="`${charCount} ตัวอักษร`"></span>
                            <span>·</span>
                            <span x-text="`${lineCount} บรรทัด`"></span>
                            <span x-show="confidence" class="ml-auto text-emerald-400 font-medium" x-text="`ความแม่นยำ: ${confidence}%`"></span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Pro features showcase --}}
            <div class="bg-white border border-gray-100 shadow-sm rounded-2xl border border-gray-100 p-5">
                <h3 class="text-sm font-semibold text-gray-800 mb-4">ฟีเจอร์ OCR Pro</h3>
                <div class="grid grid-cols-2 gap-3">
                    @foreach([
                        ['🇹🇭', 'ภาษาไทย 100%', 'ตัวพิมพ์ใหญ่-เล็ก, ตัวเลข'],
                        ['📊', 'ตรวจจับตาราง', 'Export เป็น CSV/Excel'],
                        ['📝', 'รักษา Layout', 'ย่อหน้า, หัวข้อ'],
                        ['⚡', 'ประมวลผลเร็ว', '< 5 วินาที/หน้า'],
                        ['🔄', 'Batch OCR', 'หลายไฟล์พร้อมกัน'],
                        ['🌐', '50+ ภาษา', 'ทั่วโลกรองรับ'],
                    ] as $feat)
                    <div class="flex items-start gap-2">
                        <span class="text-lg flex-shrink-0 mt-0.5">{{ $feat[0] }}</span>
                        <div>
                            <p class="text-xs font-medium text-gray-600">{{ $feat[1] }}</p>
                            <p class="text-xs text-gray-300">{{ $feat[2] }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function ocrTool() {
    return {
        files: [],
        isDragging: false,
        isProcessing: false,
        progress: 0,
        processingStatus: 'กำลังส่งไฟล์...',
        extractedText: '',
        confidence: null,
        jobId: null,
        pollTimer: null,

        // Settings
        selectedLangs: ['th', 'en'],
        outputFormat: 'txt',
        detectTables: false,

        languages: [
            { code: 'th', name: 'ไทย', flag: '🇹🇭' },
            { code: 'en', name: 'English', flag: '🇬🇧' },
            { code: 'zh', name: '中文', flag: '🇨🇳' },
            { code: 'ja', name: '日本語', flag: '🇯🇵' },
            { code: 'ko', name: '한국어', flag: '🇰🇷' },
            { code: 'ar', name: 'العربية', flag: '🇸🇦' },
        ],

        formats: [
            { value: 'txt', label: 'ข้อความ', icon: '📄' },
            { value: 'docx', label: 'Word', icon: '📝' },
            { value: 'pdf', label: 'PDF', icon: '🔴' },
        ],

        init() {},

        handleDrop(event) {
            this.isDragging = false;
            this.addFiles(Array.from(event.dataTransfer.files));
        },

        handleFiles(event) {
            this.addFiles(Array.from(event.target.files));
        },

        addFiles(newFiles) {
            const allowed = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp', 'image/tiff', 'image/bmp'];
            for (const f of newFiles) {
                if (allowed.includes(f.type) || f.name.endsWith('.tiff') || f.name.endsWith('.bmp')) {
                    this.files.push(f);
                }
            }
        },

        removeFile(i) { this.files.splice(i, 1); },

        toggleLanguage(code) {
            const idx = this.selectedLangs.indexOf(code);
            if (idx >= 0) {
                if (this.selectedLangs.length > 1) this.selectedLangs.splice(idx, 1);
            } else {
                this.selectedLangs.push(code);
            }
        },

        async startOcr() {
            if (this.files.length === 0) return;
            this.isProcessing = true;
            this.progress = 0;
            this.extractedText = '';
            this.confidence = null;
            this.processingStatus = 'กำลังส่งไฟล์...';

            const formData = new FormData();
            this.files.forEach(f => formData.append('files[]', f));
            formData.append('tool', 'ocr-pdf');
            formData.append('config[languages]', JSON.stringify(this.selectedLangs));
            formData.append('config[format]', this.outputFormat);
            formData.append('config[detect_tables]', this.detectTables ? '1' : '0');
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

            try {
                const res = await fetch('/files/upload', { method: 'POST', body: formData });
                const data = await res.json();

                if (!res.ok) throw new Error(data.error || 'Upload failed');

                this.jobId = data.job_id;
                this.progress = 20;
                this.processingStatus = 'รอในคิว...';
                this.startPolling(data.status_url);
            } catch (err) {
                this.isProcessing = false;
                alert('เกิดข้อผิดพลาด: ' + err.message);
            }
        },

        startPolling(statusUrl) {
            let tick = 20;
            this.pollTimer = setInterval(async () => {
                try {
                    const res = await fetch(statusUrl);
                    const data = await res.json();

                    if (data.status === 'processing') {
                        tick = Math.min(90, tick + 5);
                        this.progress = tick;
                        this.processingStatus = 'กำลังวิเคราะห์ข้อความ...';
                    } else if (data.status === 'done') {
                        clearInterval(this.pollTimer);
                        this.progress = 100;
                        this.isProcessing = false;
                        if (data.download_url) {
                            // For txt format, fetch the text content
                            const textRes = await fetch(data.download_url);
                            this.extractedText = await textRes.text();
                            this.confidence = 95;
                        }
                    } else if (data.status === 'failed') {
                        clearInterval(this.pollTimer);
                        this.isProcessing = false;
                        alert('OCR ล้มเหลว: ' + (data.error_message || 'Unknown error'));
                    }
                } catch (e) { console.error('Poll error:', e); }
            }, 1500);
        },

        copyText() {
            navigator.clipboard.writeText(this.extractedText);
        },

        downloadText() {
            const blob = new Blob([this.extractedText], { type: 'text/plain;charset=utf-8' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'ocr_result.txt';
            a.click();
            URL.revokeObjectURL(url);
        },

        formatSize(bytes) {
            const units = ['B', 'KB', 'MB', 'GB'];
            let s = bytes, u = 0;
            while (s >= 1024 && u < units.length - 1) { s /= 1024; u++; }
            return `${s.toFixed(1)} ${units[u]}`;
        },

        get wordCount() { return this.extractedText ? this.extractedText.trim().split(/\s+/).filter(Boolean).length : 0; },
        get charCount() { return this.extractedText.length; },
        get lineCount() { return this.extractedText ? this.extractedText.split('\n').length : 0; },
    };
}
</script>
@endpush
@endsection
