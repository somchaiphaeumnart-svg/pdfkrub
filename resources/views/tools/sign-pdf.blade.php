@extends('layouts.app')

@section('title', 'เซ็นเอกสาร PDF')
@section('description', 'เซ็นชื่อดิจิทัลบน PDF ออนไลน์ วาดลายเซ็น อัปโหลดรูป หรือพิมพ์ชื่อ รองรับภาษาไทย')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12"
     x-data="signPdf()"
     x-init="init()">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm text-slate-500 mb-8">
        <a href="{{ route('home') }}" class="hover:text-brand-400 transition-colors">หน้าแรก</a>
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
        <a href="{{ route('tools') }}" class="hover:text-brand-400 transition-colors">เครื่องมือ</a>
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
        <span class="text-slate-300">เซ็นเอกสาร PDF</span>
    </nav>

    {{-- Header --}}
    <div class="flex items-start gap-5 mb-10">
        <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-600 to-indigo-500 flex items-center justify-center text-2xl shadow-lg flex-shrink-0">✍️</div>
        <div>
            <div class="flex items-center gap-3 mb-1">
                <h1 class="text-3xl font-bold text-white">เซ็นเอกสาร PDF</h1>
                @auth
                    @if(auth()->user()->getActivePlan()->has_esign)
                    <span class="badge-free">Pro</span>
                    @else
                    <span class="badge-premium">Pro</span>
                    @endif
                @else
                <span class="badge-premium">Pro</span>
                @endauth
            </div>
            <p class="text-slate-400">เซ็นชื่อดิจิทัลบน PDF ออนไลน์ — วาด, พิมพ์, หรืออัปโหลดลายเซ็น</p>
        </div>
    </div>

    {{-- Premium Gate --}}
    @if(!auth()->check() || !auth()->user()->getActivePlan()->has_esign)
    <div class="glass rounded-2xl p-8 border border-accent-500/30 mb-8">
        <div class="flex flex-col sm:flex-row items-start gap-5">
            <div class="w-12 h-12 bg-accent-500/20 rounded-xl flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-accent-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/>
                </svg>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-bold text-white mb-1">ฟีเจอร์นี้สำหรับสมาชิก Pro</h3>
                <p class="text-slate-400 text-sm mb-4">อัปเกรดเพื่อเซ็นเอกสาร PDF ไม่จำกัด รองรับลายเซ็นดิจิทัลและตราประทับ ปฏิบัติตามกฎหมาย PDPA</p>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('pricing') }}" class="btn-accent text-sm px-6 py-2.5 rounded-xl inline-block">อัปเกรดเป็น Pro — ฿199/เดือน</a>
                    @guest
                    <a href="{{ route('register') }}" class="btn-ghost text-sm px-6 py-2.5 rounded-xl inline-block">สมัครฟรีก่อน</a>
                    @endguest
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="grid lg:grid-cols-2 gap-8">

        {{-- Left: PDF Upload + Preview --}}
        <div class="space-y-5">
            <div class="glass rounded-2xl border border-white/[0.06] overflow-hidden">
                <div class="px-5 py-4 border-b border-white/[0.06] flex items-center justify-between">
                    <h2 class="font-semibold text-white">เอกสาร PDF</h2>
                    <template x-if="pdfLoaded">
                        <div class="flex items-center gap-3 text-xs text-slate-400">
                            <button @click="prevPage()" :disabled="currentPage <= 1" class="px-2 py-1 rounded hover:bg-white/10 disabled:opacity-30 transition-all">← ก่อนหน้า</button>
                            <span x-text="`หน้า ${currentPage} / ${totalPages}`"></span>
                            <button @click="nextPage()" :disabled="currentPage >= totalPages" class="px-2 py-1 rounded hover:bg-white/10 disabled:opacity-30 transition-all">ถัดไป →</button>
                        </div>
                    </template>
                </div>

                {{-- PDF Drop Zone --}}
                <div class="p-5">
                    <template x-if="!pdfLoaded">
                        <div class="upload-zone p-10 text-center cursor-pointer"
                             @dragover.prevent="isDragging = true"
                             @dragleave.prevent="isDragging = false"
                             @drop.prevent="handlePdfDrop($event)"
                             :class="{ 'drag-over': isDragging }"
                             @click="$refs.pdfInput.click()">
                            <input type="file" x-ref="pdfInput" class="hidden" accept=".pdf" @change="loadPdf($event)">
                            <div class="w-16 h-16 bg-indigo-500/20 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/>
                                </svg>
                            </div>
                            <p class="text-white font-semibold mb-1">อัปโหลดไฟล์ PDF</p>
                            <p class="text-slate-400 text-sm">ลากมาวางหรือคลิกเพื่อเลือก</p>
                        </div>
                    </template>

                    {{-- PDF Canvas --}}
                    <template x-if="pdfLoaded">
                        <div class="relative">
                            <canvas x-ref="pdfCanvas"
                                    class="w-full rounded-xl border border-white/10 cursor-crosshair"
                                    @click="placeSignatureOnCanvas($event)"
                                    style="background: white;">
                            </canvas>
                            {{-- Placed signatures overlay --}}
                            <template x-for="(sig, idx) in placedSignatures" :key="idx">
                                <div class="absolute border-2 border-dashed border-indigo-400 rounded cursor-move group"
                                     :style="`left: ${sig.x}px; top: ${sig.y}px; width: ${sig.w}px; height: ${sig.h}px;`">
                                    <img :src="sig.dataUrl" class="w-full h-full object-contain">
                                    <button @click.stop="removeSignature(idx)"
                                            class="absolute -top-3 -right-3 w-6 h-6 bg-error-500 text-white rounded-full text-xs flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">✕</button>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- Right: Signature Tools --}}
        <div class="space-y-5">
            {{-- Signature Tabs --}}
            <div class="glass rounded-2xl border border-white/[0.06] overflow-hidden">
                <div class="px-5 py-4 border-b border-white/[0.06]">
                    <h2 class="font-semibold text-white mb-3">ลายเซ็น</h2>
                    {{-- Tab buttons --}}
                    <div class="flex gap-1 bg-white/5 rounded-xl p-1">
                        <button @click="sigTab = 'draw'"
                                class="flex-1 py-2 text-sm rounded-lg transition-all"
                                :class="sigTab === 'draw' ? 'bg-indigo-600 text-white font-medium' : 'text-slate-400 hover:text-white'">
                            ✍️ วาด
                        </button>
                        <button @click="sigTab = 'type'"
                                class="flex-1 py-2 text-sm rounded-lg transition-all"
                                :class="sigTab === 'type' ? 'bg-indigo-600 text-white font-medium' : 'text-slate-400 hover:text-white'">
                            ⌨️ พิมพ์
                        </button>
                        <button @click="sigTab = 'upload'"
                                class="flex-1 py-2 text-sm rounded-lg transition-all"
                                :class="sigTab === 'upload' ? 'bg-indigo-600 text-white font-medium' : 'text-slate-400 hover:text-white'">
                            📷 อัปโหลด
                        </button>
                    </div>
                </div>

                <div class="p-5">
                    {{-- Draw Tab --}}
                    <div x-show="sigTab === 'draw'" class="space-y-4">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-3">
                                <label class="text-xs text-slate-400">สี:</label>
                                <div class="flex gap-2">
                                    <template x-for="c in ['#1e3a8a','#000000','#dc2626','#16a34a']">
                                        <button @click="drawColor = c"
                                                class="w-6 h-6 rounded-full border-2 transition-all"
                                                :style="`background: ${c}`"
                                                :class="drawColor === c ? 'border-white scale-110' : 'border-transparent'">
                                        </button>
                                    </template>
                                </div>
                            </div>
                            <button @click="clearCanvas()" class="text-xs text-slate-500 hover:text-error-500 transition-colors">ล้าง</button>
                        </div>

                        <canvas x-ref="sigCanvas"
                                width="380" height="160"
                                class="w-full rounded-xl border border-white/10 cursor-crosshair touch-none"
                                style="background: rgba(255,255,255,0.05);"
                                @mousedown="startDraw($event)"
                                @mousemove="draw($event)"
                                @mouseup="stopDraw()"
                                @mouseleave="stopDraw()"
                                @touchstart.prevent="startDraw($event.touches[0])"
                                @touchmove.prevent="draw($event.touches[0])"
                                @touchend="stopDraw()">
                        </canvas>

                        <p class="text-xs text-slate-500 text-center">วาดลายเซ็นของคุณด้านบน</p>
                    </div>

                    {{-- Type Tab --}}
                    <div x-show="sigTab === 'type'" class="space-y-4">
                        <div>
                            <label class="text-xs text-slate-400 block mb-2">พิมพ์ชื่อของคุณ</label>
                            <input type="text" x-model="typedName" placeholder="ชื่อ-นามสกุล"
                                   @input="renderTypedSignature()"
                                   class="w-full bg-white/5 border border-white/10 text-white placeholder-slate-500 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all">
                        </div>
                        <div>
                            <label class="text-xs text-slate-400 block mb-2">สไตล์ลายมือ</label>
                            <div class="grid grid-cols-2 gap-2">
                                <template x-for="(font, idx) in signatureFonts" :key="idx">
                                    <button @click="selectedFont = font; renderTypedSignature()"
                                            class="py-3 px-4 rounded-xl border text-sm transition-all text-center"
                                            :class="selectedFont === font ? 'border-indigo-500 bg-indigo-500/10 text-white' : 'border-white/10 text-slate-400 hover:border-white/20'"
                                            :style="`font-family: ${font.family}`"
                                            x-text="typedName || 'ลายเซ็น'">
                                    </button>
                                </template>
                            </div>
                        </div>
                        <canvas x-ref="typeCanvas" width="380" height="120" class="hidden"></canvas>
                    </div>

                    {{-- Upload Tab --}}
                    <div x-show="sigTab === 'upload'" class="space-y-4">
                        <div class="upload-zone p-8 text-center cursor-pointer"
                             @click="$refs.sigImageInput.click()">
                            <input type="file" x-ref="sigImageInput" class="hidden"
                                   accept=".png,.jpg,.jpeg,.webp"
                                   @change="loadSignatureImage($event)">
                            <template x-if="!uploadedSigUrl">
                                <div>
                                    <p class="text-slate-300 text-sm font-medium mb-1">อัปโหลดรูปลายเซ็น</p>
                                    <p class="text-slate-500 text-xs">PNG พื้นหลังโปร่งใสดีที่สุด</p>
                                </div>
                            </template>
                            <template x-if="uploadedSigUrl">
                                <img :src="uploadedSigUrl" class="max-h-24 mx-auto object-contain">
                            </template>
                        </div>
                    </div>

                    {{-- Preview --}}
                    <div class="mt-4 pt-4 border-t border-white/[0.06]">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-xs text-slate-400 font-medium">ตัวอย่างลายเซ็น</p>
                            <div class="flex items-center gap-2 text-xs text-slate-500">
                                <span>ขนาด:</span>
                                <input type="range" x-model="sigScale" min="0.5" max="2" step="0.1" class="w-20 accent-indigo-500">
                            </div>
                        </div>
                        <div class="bg-white/5 rounded-xl p-4 flex items-center justify-center min-h-16 border border-white/[0.06]">
                            <template x-if="currentSigDataUrl">
                                <img :src="currentSigDataUrl" class="max-h-16 object-contain"
                                     :style="`transform: scale(${sigScale}); transform-origin: center;`">
                            </template>
                            <template x-if="!currentSigDataUrl">
                                <p class="text-slate-600 text-xs">ลายเซ็นจะแสดงที่นี่</p>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Place Signature + Actions --}}
            <div class="glass rounded-2xl border border-white/[0.06] p-5 space-y-4">
                <template x-if="!pdfLoaded">
                    <p class="text-slate-500 text-sm text-center py-2">อัปโหลด PDF ก่อน แล้วคลิกตำแหน่งที่ต้องการวางลายเซ็น</p>
                </template>
                <template x-if="pdfLoaded">
                    <div class="space-y-3">
                        <p class="text-sm text-slate-300">
                            <span class="text-indigo-400">คลิกบน PDF</span> เพื่อวางลายเซ็นในตำแหน่งที่ต้องการ
                            <span x-show="placedSignatures.length > 0" class="text-slate-500" x-text="`(${placedSignatures.length} ตำแหน่ง)`"></span>
                        </p>
                        <button @click="applyAndDownload()"
                                :disabled="placedSignatures.length === 0 || isProcessing"
                                class="w-full btn-primary py-3.5 rounded-xl flex items-center justify-center gap-2 text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                            <template x-if="!isProcessing">
                                <span class="flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                                    </svg>
                                    บันทึกและดาวน์โหลด PDF
                                </span>
                            </template>
                            <template x-if="isProcessing">
                                <span class="flex items-center gap-2">
                                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                    กำลังประมวลผล...
                                </span>
                            </template>
                        </button>
                        <button @click="resetAll()" class="w-full btn-ghost py-2.5 rounded-xl text-sm">เริ่มใหม่</button>
                    </div>
                </template>
            </div>

            {{-- Security badge --}}
            <div class="flex items-center gap-3 text-xs text-slate-500 px-2">
                <svg class="w-4 h-4 text-success-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/>
                </svg>
                การเซ็นชื่อดำเนินการในเบราว์เซอร์ — ไฟล์ไม่ถูกส่งขึ้น Server
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="module">
import { PDFDocument, rgb, degrees } from '/build/pdf-lib.js';

window.signPdf = function() {
    return {
        // PDF state
        pdfLoaded: false,
        pdfFile: null,
        pdfDoc: null,
        pdfBytes: null,
        currentPage: 1,
        totalPages: 1,
        pdfScale: 1,
        isDragging: false,

        // Signature state
        sigTab: 'draw',
        isDrawing: false,
        lastX: 0,
        lastY: 0,
        drawColor: '#1e3a8a',
        typedName: '',
        selectedFont: null,
        signatureFonts: [
            { family: 'Georgia, serif', label: 'Elegant' },
            { family: '"Dancing Script", cursive', label: 'Script' },
            { family: '"Pacifico", cursive', label: 'Bold' },
            { family: 'monospace', label: 'Mono' },
        ],
        uploadedSigUrl: null,
        currentSigDataUrl: null,
        sigScale: 1,

        // Placed signatures
        placedSignatures: [],
        isProcessing: false,

        init() {
            this.selectedFont = this.signatureFonts[0];
            // Load Google Fonts for signature
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = 'https://fonts.googleapis.com/css2?family=Dancing+Script:wght@700&family=Pacifico&display=swap';
            document.head.appendChild(link);
        },

        // ─── PDF Loading ────────────────────────────────────
        handlePdfDrop(event) {
            this.isDragging = false;
            const file = event.dataTransfer.files[0];
            if (file && file.type === 'application/pdf') this.processPdfFile(file);
        },

        loadPdf(event) {
            const file = event.target.files[0];
            if (file) this.processPdfFile(file);
        },

        async processPdfFile(file) {
            this.pdfFile = file;
            const arrayBuffer = await file.arrayBuffer();
            this.pdfBytes = arrayBuffer;

            // Load with PDF.js for rendering (if available), else just set loaded
            this.pdfLoaded = true;
            this.totalPages = 1; // Will be updated when rendering

            this.$nextTick(() => this.renderPage());
        },

        async renderPage() {
            if (!this.pdfBytes) return;
            const canvas = this.$refs.pdfCanvas;
            if (!canvas) return;

            try {
                // Use PDF.js via CDN
                if (!window.pdfjsLib) {
                    const script = document.createElement('script');
                    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.4.168/pdf.min.mjs';
                    script.type = 'module';
                    document.head.appendChild(script);
                    await new Promise(r => script.onload = r);
                    window.pdfjsLib = pdfjsLib;
                    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.4.168/pdf.worker.min.mjs';
                }

                const pdf = await pdfjsLib.getDocument({ data: this.pdfBytes.slice(0) }).promise;
                this.totalPages = pdf.numPages;

                const page = await pdf.getPage(this.currentPage);
                const viewport = page.getViewport({ scale: 1.5 });
                this.pdfScale = 1.5;

                canvas.width = viewport.width;
                canvas.height = viewport.height;
                canvas.style.maxWidth = '100%';

                const ctx = canvas.getContext('2d');
                await page.render({ canvasContext: ctx, viewport }).promise;

            } catch (e) {
                console.error('PDF render error:', e);
            }
        },

        prevPage() {
            if (this.currentPage > 1) { this.currentPage--; this.renderPage(); }
        },
        nextPage() {
            if (this.currentPage < this.totalPages) { this.currentPage++; this.renderPage(); }
        },

        // ─── Drawing ─────────────────────────────────────────
        startDraw(event) {
            this.isDrawing = true;
            const rect = this.$refs.sigCanvas.getBoundingClientRect();
            const scaleX = this.$refs.sigCanvas.width / rect.width;
            const scaleY = this.$refs.sigCanvas.height / rect.height;
            this.lastX = (event.clientX - rect.left) * scaleX;
            this.lastY = (event.clientY - rect.top) * scaleY;
        },

        draw(event) {
            if (!this.isDrawing) return;
            const canvas = this.$refs.sigCanvas;
            const ctx = canvas.getContext('2d');
            const rect = canvas.getBoundingClientRect();
            const scaleX = canvas.width / rect.width;
            const scaleY = canvas.height / rect.height;
            const x = (event.clientX - rect.left) * scaleX;
            const y = (event.clientY - rect.top) * scaleY;

            ctx.beginPath();
            ctx.moveTo(this.lastX, this.lastY);
            ctx.lineTo(x, y);
            ctx.strokeStyle = this.drawColor;
            ctx.lineWidth = 2.5;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            ctx.stroke();

            this.lastX = x;
            this.lastY = y;
            this.currentSigDataUrl = canvas.toDataURL('image/png');
        },

        stopDraw() {
            this.isDrawing = false;
            if (this.$refs.sigCanvas) {
                this.currentSigDataUrl = this.$refs.sigCanvas.toDataURL('image/png');
            }
        },

        clearCanvas() {
            const canvas = this.$refs.sigCanvas;
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            this.currentSigDataUrl = null;
        },

        // ─── Typed Signature ─────────────────────────────────
        renderTypedSignature() {
            if (!this.typedName) { this.currentSigDataUrl = null; return; }
            const canvas = this.$refs.typeCanvas;
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            const font = this.selectedFont?.family || 'Georgia, serif';
            ctx.font = `48px ${font}`;
            ctx.fillStyle = this.drawColor;
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(this.typedName, canvas.width / 2, canvas.height / 2);

            this.currentSigDataUrl = canvas.toDataURL('image/png');
        },

        // ─── Upload Signature ────────────────────────────────
        loadSignatureImage(event) {
            const file = event.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = e => {
                this.uploadedSigUrl = e.target.result;
                this.currentSigDataUrl = e.target.result;
            };
            reader.readAsDataURL(file);
        },

        // ─── Place Signature on PDF ──────────────────────────
        placeSignatureOnCanvas(event) {
            if (!this.currentSigDataUrl) {
                alert('กรุณาสร้างลายเซ็นก่อน');
                return;
            }
            const canvas = this.$refs.pdfCanvas;
            const rect = canvas.getBoundingClientRect();
            const x = event.clientX - rect.left;
            const y = event.clientY - rect.top;
            const w = 180 * this.sigScale;
            const h = 60 * this.sigScale;

            this.placedSignatures.push({
                x: x - w / 2,
                y: y - h / 2,
                w, h,
                dataUrl: this.currentSigDataUrl,
                page: this.currentPage,
                // Store relative to canvas dimensions for pdf-lib
                relX: (x - w / 2) / canvas.width,
                relY: (y - h / 2) / canvas.height,
                relW: w / canvas.width,
                relH: h / canvas.height,
            });
        },

        removeSignature(idx) {
            this.placedSignatures.splice(idx, 1);
        },

        // ─── Apply Signatures & Download ─────────────────────
        async applyAndDownload() {
            if (!this.pdfBytes || this.placedSignatures.length === 0) return;
            this.isProcessing = true;

            try {
                const { PDFDocument, rgb } = await import('https://cdn.jsdelivr.net/npm/pdf-lib@1.17.1/dist/pdf-lib.esm.min.js');

                const pdfDoc = await PDFDocument.load(this.pdfBytes);
                const pages = pdfDoc.getPages();

                for (const sig of this.placedSignatures) {
                    const page = pages[sig.page - 1];
                    const { width: pw, height: ph } = page.getSize();

                    // Convert from canvas coords to PDF coords (Y-axis is flipped in PDF)
                    const pdfX = sig.relX * pw;
                    const pdfY = ph - (sig.relY * ph) - (sig.relH * ph);
                    const pdfW = sig.relW * pw;
                    const pdfH = sig.relH * ph;

                    // Embed the signature image
                    let sigImage;
                    if (sig.dataUrl.startsWith('data:image/png')) {
                        sigImage = await pdfDoc.embedPng(sig.dataUrl);
                    } else {
                        sigImage = await pdfDoc.embedJpg(sig.dataUrl);
                    }

                    page.drawImage(sigImage, {
                        x: pdfX,
                        y: pdfY,
                        width: pdfW,
                        height: pdfH,
                    });
                }

                const pdfBytes = await pdfDoc.save();
                const blob = new Blob([pdfBytes], { type: 'application/pdf' });
                const url = URL.createObjectURL(blob);

                const a = document.createElement('a');
                a.href = url;
                a.download = (this.pdfFile?.name?.replace('.pdf', '') || 'document') + '_signed.pdf';
                a.click();

                URL.revokeObjectURL(url);
            } catch (err) {
                console.error('Sign error:', err);
                alert('เกิดข้อผิดพลาด: ' + err.message);
            } finally {
                this.isProcessing = false;
            }
        },

        resetAll() {
            this.pdfLoaded = false;
            this.pdfFile = null;
            this.pdfBytes = null;
            this.placedSignatures = [];
            this.currentSigDataUrl = null;
        },
    };
};
</script>
@endpush
@endsection
