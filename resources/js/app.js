import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';

Alpine.plugin(focus);

// =====================================================
// Staged Files Store (IndexedDB)
// Carries files seamlessly across pages (e.g. Home -> Tool)
// =====================================================
const STAGE_DB_NAME = 'pdfkrub_staged_db';
const STAGE_STORE_NAME = 'staged_files';

function getStageDB() {
    return new Promise((resolve) => {
        if (typeof window === 'undefined' || !window.indexedDB) {
            return resolve(null);
        }
        try {
            const req = window.indexedDB.open(STAGE_DB_NAME, 1);
            req.onupgradeneeded = (e) => {
                const db = e.target.result;
                if (!db.objectStoreNames.contains(STAGE_STORE_NAME)) {
                    db.createObjectStore(STAGE_STORE_NAME, { keyPath: 'id' });
                }
            };
            req.onsuccess = () => resolve(req.result);
            req.onerror = () => resolve(null);
        } catch (err) {
            resolve(null);
        }
    });
}

async function saveStagedFiles(fileList) {
    try {
        const db = await getStageDB();
        if (!db) return false;
        const tx = db.transaction(STAGE_STORE_NAME, 'readwrite');
        const store = tx.objectStore(STAGE_STORE_NAME);
        store.clear();
        const timestamp = Date.now();
        for (let i = 0; i < fileList.length; i++) {
            store.put({
                id: i,
                file: fileList[i],
                name: fileList[i].name,
                size: fileList[i].size,
                type: fileList[i].type,
                timestamp: timestamp,
            });
        }
        return new Promise((resolve) => {
            tx.oncomplete = () => resolve(true);
            tx.onerror = () => resolve(false);
        });
    } catch (e) {
        console.warn('saveStagedFiles error:', e);
        return false;
    }
}

async function getStagedFiles() {
    try {
        const db = await getStageDB();
        if (!db) return [];
        const tx = db.transaction(STAGE_STORE_NAME, 'readonly');
        const store = tx.objectStore(STAGE_STORE_NAME);
        return new Promise((resolve) => {
            const req = store.getAll();
            req.onsuccess = () => resolve(req.result || []);
            req.onerror = () => resolve([]);
        });
    } catch (e) {
        return [];
    }
}

async function clearStagedFiles() {
    try {
        const db = await getStageDB();
        if (!db) return;
        const tx = db.transaction(STAGE_STORE_NAME, 'readwrite');
        tx.objectStore(STAGE_STORE_NAME).clear();
    } catch (e) {}
}

// Non-reactive PDF.js document and render task instances
// (Must stay outside Alpine's Proxy wrapper to avoid "Cannot read private member #d" errors)
let rotatePdfDoc = null;
let rotateRenderTask = null;
let pageImageCache = {};
let blankPageCache = {};

// =====================================================
// Alpine Component: fileUpload
// Handles drag-drop, file validation, and submission
// =====================================================
Alpine.data('fileUpload', (config = {}) => ({
    isDragging: false,
    files: [],
    maxSizeMb: config.maxSizeMb ?? 10,
    accept: config.accept ?? '.pdf',
    maxFiles: config.maxFiles ?? 10,
    tool: config.tool ?? null,
    isHome: config.isHome ?? false,
    error: null,

    // Rotate PDF state
    rotationAngle: 90,
    isRenderingPdf: false,
    isCurrentPageBlank: false,
    pdfTotalPages: 0,
    pdfCurrentPage: 1,
    pdfRenderError: null,
    previewImageUrl: null,

    // Processing state
    isUploading: false,
    uploadProgress: 0,
    uploadBytesLoaded: 0,
    uploadBytesTotal: 0,

    isProcessingServer: false,
    serverProgress: 0,
    processingStep: '',
    processingDetail: '',

    jobId: null,
    jobStatus: null,      // queued|processing|done|failed
    downloadUrl: null,
    downloadFileName: null,
    downloadFileSize: null,
    errorMessage: null,

    activeXhr: null,
    pollTimer: null,
    serverTickTimer: null,

    async init() {
        // If on a tool page, auto-restore staged files from the homepage
        if (!this.isHome) {
            await this.loadStagedFiles();
        }
        if (this.tool === 'rotate-pdf' && this.files.length > 0) {
            this.loadPdfPreview();
        }
    },

    async loadStagedFiles() {
        try {
            const staged = await getStagedFiles();
            if (staged && staged.length > 0) {
                const now = Date.now();
                // Valid for 1 hour
                const validItems = staged.filter(item => !item.timestamp || (now - item.timestamp < 3600000));
                const compatibleFiles = [];
                for (const item of validItems) {
                    if (item.file && this.isAccepted(item.file)) {
                        compatibleFiles.push(item.file);
                    }
                }
                if (compatibleFiles.length > 0) {
                    this.addFiles(compatibleFiles);
                    // Clear after loading
                    await clearStagedFiles();
                }
            }
        } catch (e) {
            console.warn('Failed to restore staged files:', e);
        }
    },

    isAccepted(file) {
        if (!this.accept || this.accept === '*' || this.accept === '*/*') return true;
        const patterns = this.accept.split(',').map(p => p.trim().toLowerCase());
        const fileName = (file.name || '').toLowerCase();
        const fileType = (file.type || '').toLowerCase();

        return patterns.some(pattern => {
            if (pattern.startsWith('.')) {
                return fileName.endsWith(pattern);
            }
            if (pattern.endsWith('/*')) {
                return fileType.startsWith(pattern.slice(0, -2));
            }
            return fileType === pattern;
        });
    },

    async stageAndGo(targetUrl) {
        if (this.files.length > 0) {
            await saveStagedFiles(this.files.map(f => f.file));
        }
        window.location.href = targetUrl;
    },

    handleDrop(event) {
        this.isDragging = false;
        this.addFiles(Array.from(event.dataTransfer.files));
    },

    handleFileInput(event) {
        this.addFiles(Array.from(event.target.files));
    },

    addFiles(newFiles) {
        this.error = null;
        const maxBytes = this.maxSizeMb * 1024 * 1024;

        for (const file of newFiles) {
            if (file.size > maxBytes) {
                this.error = `ไฟล์ "${file.name}" มีขนาดใหญ่เกิน ${this.maxSizeMb} MB`;
                continue;
            }
            if (this.files.length >= this.maxFiles) {
                this.error = `สามารถเพิ่มได้สูงสุด ${this.maxFiles} ไฟล์`;
                break;
            }
            this.files.push({
                id: Math.random().toString(36).slice(2),
                file,
                name: file.name,
                size: file.size,
                sizeFormatted: this.formatSize(file.size),
            });
        }

        // Auto-save to IndexedDB if on homepage
        if (this.isHome && this.files.length > 0) {
            saveStagedFiles(this.files.map(f => f.file));
        }

        // If rotate-pdf, load PDF preview
        if (this.tool === 'rotate-pdf' && this.files.length > 0) {
            this.rotationAngle = 90;
            setTimeout(() => this.loadPdfPreview(), 60);
        }
    },

    removeFile(id) {
        this.files = this.files.filter(f => f.id !== id);
        this.error = null;
        if (this.isHome) {
            if (this.files.length > 0) {
                saveStagedFiles(this.files.map(f => f.file));
            } else {
                clearStagedFiles();
            }
        }
        if (this.tool === 'rotate-pdf') {
            pageImageCache = {};
            blankPageCache = {};
            this.isCurrentPageBlank = false;
            this.previewImageUrl = null;
            if (this.files.length > 0) {
                setTimeout(() => this.loadPdfPreview(), 60);
            } else {
                rotatePdfDoc = null;
                if (rotateRenderTask) {
                    try { rotateRenderTask.cancel(); } catch (e) {}
                    rotateRenderTask = null;
                }
                this.pdfTotalPages = 0;
                this.pdfCurrentPage = 1;
            }
        }
    },

    clearAll() {
        this.cancelConversion();
        this.files = [];
        this.error = null;
        rotatePdfDoc = null;
        if (rotateRenderTask) {
            try { rotateRenderTask.cancel(); } catch (e) {}
            rotateRenderTask = null;
        }
        pageImageCache = {};
        blankPageCache = {};
        this.isCurrentPageBlank = false;
        this.previewImageUrl = null;
        this.pdfTotalPages = 0;
        this.pdfCurrentPage = 1;
        this.rotationAngle = 90;
        this.pdfRenderError = null;
        clearStagedFiles();
    },

    cancelConversion() {
        if (this.activeXhr) {
            try { this.activeXhr.abort(); } catch (e) {}
            this.activeXhr = null;
        }
        this.stopServerProgressTicker();
        if (this.pollTimer) {
            clearInterval(this.pollTimer);
            this.pollTimer = null;
        }
        this.isUploading = false;
        this.isProcessingServer = false;
        this.uploadProgress = 0;
        this.uploadBytesLoaded = 0;
        this.uploadBytesTotal = 0;
        this.serverProgress = 0;
        this.processingStep = '';
        this.processingDetail = '';
        this.jobStatus = null;
        this.jobId = null;
        this.errorMessage = null;
    },

    reset() {
        this.cancelConversion();
        this.downloadUrl = null;
        this.downloadFileName = null;
        this.downloadFileSize = null;
    },

    // Upload files with real progress and start server processing
    async startConversion(toolName) {
        if (!this.hasFiles || this.isUploading || this.isProcessingServer) return;

        this.cancelConversion();
        this.error = null;
        this.isUploading = true;
        this.uploadProgress = 0;

        const totalBytes = this.files.reduce((sum, f) => sum + (f.file ? f.file.size : (f.size || 0)), 0);
        this.uploadBytesTotal = totalBytes;
        this.uploadBytesLoaded = 0;

        const formData = new FormData();
        this.files.forEach(f => formData.append('files[]', f.file));
        const activeTool = toolName || this.tool || 'unknown';
        formData.append('tool', activeTool);
        if (activeTool === 'rotate-pdf') {
            const deg = this.rotationAngle !== undefined ? this.rotationAngle : 90;
            formData.append('degrees', deg);
            formData.append('config[degrees]', deg);
        }
        const tokenMeta = document.querySelector('meta[name="csrf-token"]');
        if (tokenMeta) {
            formData.append('_token', tokenMeta.content);
        }

        const xhr = new XMLHttpRequest();
        this.activeXhr = xhr;

        // 1. Real Upload Progress via XMLHttpRequest
        xhr.upload.onprogress = (e) => {
            if (e.lengthComputable && e.total > 0) {
                const pct = Math.round((e.loaded / e.total) * 100);
                this.uploadProgress = Math.min(99, Math.max(1, pct));
                this.uploadBytesLoaded = e.loaded;
            }
        };

        // When bytes finish transmitting to server
        xhr.upload.onload = () => {
            this.uploadProgress = 100;
            this.uploadBytesLoaded = this.uploadBytesTotal;
            this.isUploading = false;
            this.isProcessingServer = true;
            this.startServerProgressTicker(activeTool);
        };

        xhr.onload = () => {
            this.activeXhr = null;
            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    const data = JSON.parse(xhr.responseText);
                    this.jobId = data.job_id;
                    this.jobStatus = data.status;

                    if (data.status === 'done' && data.download_url) {
                        this.finishSuccess(data);
                    } else if (data.status_url) {
                        this.startPolling(data.status_url);
                    } else if (data.status === 'failed') {
                        this.handleFailure(data.error || 'เกิดข้อผิดพลาดในการประมวลผลไฟล์');
                    }
                } catch (err) {
                    this.handleFailure('ไม่สามารถอ่านข้อมูลผลลัพธ์จากเซิร์ฟเวอร์');
                }
            } else {
                let errorMsg = `เกิดข้อผิดพลาด (${xhr.status})`;
                try {
                    const errData = JSON.parse(xhr.responseText);
                    errorMsg = errData.error || errData.message || (errData.errors ? Object.values(errData.errors).flat().join(' | ') : errorMsg);
                } catch {}
                if (xhr.status === 413 || (errorMsg && errorMsg.toLowerCase().includes('post data is too large'))) {
                    errorMsg = 'ไฟล์มีขนาดใหญ่เกินกว่าที่เซิร์ฟเวอร์รองรับ (กรุณาลดขนาดไฟล์)';
                }
                this.handleFailure(errorMsg);
            }
        };

        xhr.onerror = () => {
            this.activeXhr = null;
            this.handleFailure('การเชื่อมต่อไปยังเซิร์ฟเวอร์ขัดข้อง กรุณาตรวจสอบอินเทอร์เน็ตแล้วลองใหม่');
        };

        xhr.onabort = () => {
            this.activeXhr = null;
        };

        xhr.open('POST', '/files/upload');
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.send(formData);
    },

    startServerProgressTicker(toolName) {
        this.stopServerProgressTicker();
        this.serverProgress = 15;

        const rotateDeg = this.rotationAngle !== undefined ? this.rotationAngle : 90;
        const toolLabels = {
            'pdf-to-pptx': 'กำลังแปลงหน้า PDF เป็นสไลด์ PowerPoint (.pptx)...',
            'pdf-to-word': 'กำลังแปลงหน้าเอกสารเป็น Word (.docx)...',
            'pdf-to-excel': 'กำลังวิเคราะห์ตารางและแปลงเป็น Excel (.xlsx)...',
            'ocr-pdf': 'กำลังตรวจจับข้อความภาษาไทย (OCR)...',
            'ai-summary': 'กำลังส่งให้ AI วิเคราะห์และสรุปสาระสำคัญ...',
            'merge-pdf': 'กำลังรวมไฟล์เอกสาร PDF ทั้งหมด...',
            'split-pdf': 'กำลังแยกหน้าเอกสาร PDF...',
            'compress-pdf': 'กำลังบีบอัดลดขนาดเอกสาร PDF...',
            'pdf-to-jpg': 'กำลังแปลงหน้าเอกสารเป็นภาพ JPG...',
            'pdf-to-png': 'กำลังแปลงหน้าเอกสารเป็นภาพ PNG...',
            'rotate-pdf': `กำลังหมุนหน้าเอกสาร PDF (${rotateDeg}°)...`,
            'watermark-pdf': 'กำลังใส่ลายน้ำเอกสาร PDF...',
            'protect-pdf': 'กำลังตั้งรหัสผ่านป้องกัน PDF...',
            'unlock-pdf': 'กำลังปลดล็อครหัสผ่าน PDF...',
            'pdf-to-txt': 'กำลังดึงข้อความภาษาไทยออกจาก PDF (.txt)...',
            'pptx-to-pdf': 'กำลังแปลงสไลด์ PowerPoint เป็นเอกสาร PDF...',
            'word-to-pdf': 'กำลังแปลงเอกสาร Word เป็นเอกสาร PDF...',
            'excel-to-pdf': 'กำลังแปลงเอกสาร Excel เป็นเอกสาร PDF...',
            'image-to-pdf': 'กำลังแปลงรูปภาพเป็นเอกสาร PDF...',
        };
        const detail = toolLabels[toolName] || 'เซิร์ฟเวอร์กำลังประมวลผลไฟล์...';
        this.processingDetail = detail;
        this.processingStep = 'กำลังเริ่มประมวลผลหน้าเอกสาร...';

        this.serverTickTimer = setInterval(() => {
            if (this.serverProgress < 35) {
                this.serverProgress += 5;
                this.processingStep = 'กำลังอ่านและตรวจสอบโครงสร้างเอกสาร...';
            } else if (this.serverProgress < 65) {
                this.serverProgress += 3;
                this.processingStep = detail;
            } else if (this.serverProgress < 85) {
                this.serverProgress += 2;
                this.processingStep = 'กำลังจัดฟอนต์ รูปภาพ และเนื้อหา...';
            } else if (this.serverProgress < 94) {
                this.serverProgress += 1;
                this.processingStep = 'กำลังจัดเตรียมไฟล์สำหรับดาวน์โหลด...';
            }
        }, 700);
    },

    stopServerProgressTicker() {
        if (this.serverTickTimer) {
            clearInterval(this.serverTickTimer);
            this.serverTickTimer = null;
        }
    },

    finishSuccess(data) {
        this.stopServerProgressTicker();
        if (this.pollTimer) {
            clearInterval(this.pollTimer);
            this.pollTimer = null;
        }

        this.serverProgress = 100;
        this.uploadProgress = 100;
        this.processingStep = 'ประมวลผลเสร็จสิ้น! 🎉';
        this.processingDetail = 'พร้อมดาวน์โหลดไฟล์แล้ว';

        setTimeout(() => {
            this.isUploading = false;
            this.isProcessingServer = false;
            this.jobStatus = 'done';
            this.downloadUrl = data.download_url;
            this.downloadFileName = data.file_name || 'converted_document';
            this.downloadFileSize = data.file_size || '';
        }, 350);
    },

    handleFailure(message) {
        this.stopServerProgressTicker();
        if (this.pollTimer) {
            clearInterval(this.pollTimer);
            this.pollTimer = null;
        }
        this.isUploading = false;
        this.isProcessingServer = false;
        this.jobStatus = 'failed';
        this.errorMessage = message || 'เกิดข้อผิดพลาดในการประมวลผลไฟล์';
    },

    startPolling(statusUrl) {
        let pollCount = 0;
        if (this.pollTimer) clearInterval(this.pollTimer);

        this.pollTimer = setInterval(async () => {
            try {
                pollCount++;
                if (pollCount > 180) { // 180 * 1.5s = 270 seconds (4.5 minutes)
                    clearInterval(this.pollTimer);
                    this.handleFailure('การประมวลผลใช้เวลานานเกินไป กรุณากดปุ่มลองใหม่อีกครั้ง');
                    return;
                }

                const res = await fetch(statusUrl, {
                    headers: { 'Accept': 'application/json' }
                });

                if (!res.ok) {
                    if (res.status === 403) {
                        clearInterval(this.pollTimer);
                        this.handleFailure('เซสชันหมดอายุ กรุณารีเฟรชหน้าเว็บแล้วลองใหม่อีกครั้ง');
                    }
                    return;
                }

                const data = await res.json();
                this.jobStatus = data.status;

                if (data.progress && data.progress > this.serverProgress) {
                    this.serverProgress = data.progress;
                }

                if (data.status === 'done') {
                    this.finishSuccess(data);
                } else if (data.status === 'failed') {
                    this.handleFailure(data.error_message || 'เกิดข้อผิดพลาดในการประมวลผล');
                }
            } catch (e) {
                console.warn('Poll error:', e);
            }
        }, 1500);
    },

    formatSize(bytes) {
        const units = ['B', 'KB', 'MB', 'GB'];
        let size = bytes, unit = 0;
        while (size >= 1024 && unit < units.length - 1) { size /= 1024; unit++; }
        return `${size.toFixed(1)} ${units[unit]}`;
    },

    get currentProgress() {
        if (this.isUploading) {
            return Math.max(1, Math.min(100, this.uploadProgress));
        }
        if (this.isProcessingServer || ['queued', 'processing'].includes(this.jobStatus)) {
            return Math.max(10, Math.min(98, this.serverProgress));
        }
        if (this.jobStatus === 'done') return 100;
        return 0;
    },

    get jobProgress() {
        return this.currentProgress;
    },

    get progressTitle() {
        if (this.isUploading) {
            return `กำลังอัปโหลดไฟล์... (${this.uploadProgress}%)`;
        }
        if (this.processingStep) {
            return this.processingStep;
        }
        if (this.jobStatus === 'queued') {
            return 'กำลังจัดคิวและเตรียมไฟล์...';
        }
        return 'กำลังประมวลผลไฟล์...';
    },

    get progressSubtitle() {
        if (this.isUploading) {
            if (this.uploadBytesTotal > 0) {
                return `ส่งแล้ว ${this.formatSize(this.uploadBytesLoaded)} จาก ${this.formatSize(this.uploadBytesTotal)}`;
            }
            return 'กำลังส่งข้อมูลไฟล์ไปยังเซิร์ฟเวอร์...';
        }
        if (this.processingDetail) {
            return `${this.processingDetail} (ขนาด ${this.totalSize})`;
        }
        return 'ระบบกำลังประมวลผล กรุณารอสักครู่';
    },

    // Rotate PDF Methods & Preview Helpers
    rotateRight() {
        this.rotationAngle = (this.rotationAngle + 90) % 360;
    },

    rotateLeft() {
        this.rotationAngle = (this.rotationAngle - 90 + 360) % 360;
    },

    rotate180() {
        this.rotationAngle = (this.rotationAngle + 180) % 360;
    },

    resetRotation() {
        this.rotationAngle = 0;
    },

    get rotationText() {
        switch (this.rotationAngle) {
            case 90: return '90° (ตามเข็มนาฬิกา)';
            case 180: return '180° (กลับหัว)';
            case 270: return '270° (ทวนเข็มนาฬิกา)';
            case 0:
            default: return '0° (ทิศทางเดิม)';
        }
    },

    async prevPage() {
        if (this.pdfCurrentPage > 1 && !this.isRenderingPdf) {
            this.pdfCurrentPage--;
            await this.renderCurrentPage();
        }
    },

    async nextPage() {
        if (this.pdfCurrentPage < this.pdfTotalPages && !this.isRenderingPdf) {
            this.pdfCurrentPage++;
            await this.renderCurrentPage();
        }
    },

    async goToPage(pageNum) {
        if (pageNum >= 1 && pageNum <= this.pdfTotalPages && pageNum !== this.pdfCurrentPage && !this.isRenderingPdf) {
            this.pdfCurrentPage = pageNum;
            await this.renderCurrentPage();
        }
    },

    async ensurePdfJs() {
        if (window.pdfjsLib) {
            if (window.pdfjsLib.GlobalWorkerOptions && !window.pdfjsLib.GlobalWorkerOptions.workerSrc) {
                window.pdfjsLib.GlobalWorkerOptions.workerSrc = '/vendor/pdfjs/pdf.worker.min.js';
            }
            return;
        }
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = '/vendor/pdfjs/pdf.min.js';
            script.onload = () => {
                if (window.pdfjsLib && window.pdfjsLib.GlobalWorkerOptions) {
                    window.pdfjsLib.GlobalWorkerOptions.workerSrc = '/vendor/pdfjs/pdf.worker.min.js';
                }
                resolve();
            };
            script.onerror = () => reject(new Error('ไม่สามารถโหลดระบบแสดงตัวอย่าง PDF ได้'));
            document.head.appendChild(script);
        });
    },

    async loadPdfPreview() {
        if (!this.files.length || !this.files[0].file) {
            rotatePdfDoc = null;
            pageImageCache = {};
            blankPageCache = {};
            this.isCurrentPageBlank = false;
            this.previewImageUrl = null;
            this.pdfTotalPages = 0;
            this.pdfCurrentPage = 1;
            return;
        }
        const file = this.files[0].file;
        if (file.type !== 'application/pdf' && !file.name.toLowerCase().endsWith('.pdf')) {
            return;
        }

        this.isRenderingPdf = true;
        this.pdfRenderError = null;
        pageImageCache = {};
        blankPageCache = {};
        this.isCurrentPageBlank = false;
        this.previewImageUrl = null;

        try {
            await this.ensurePdfJs();
            const arrayBuffer = await file.arrayBuffer();
            const loadingTask = window.pdfjsLib.getDocument({
                data: arrayBuffer,
                cMapUrl: 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/cmaps/',
                cMapPacked: true,
                standardFontDataUrl: 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/standard_fonts/',
            });
            rotatePdfDoc = await loadingTask.promise;
            this.pdfTotalPages = rotatePdfDoc.numPages || 1;
            this.pdfCurrentPage = 1;
            await this.renderCurrentPage();
        } catch (e) {
            console.warn('PDF preview error:', e);
            this.pdfRenderError = 'ไม่สามารถอ่านไฟล์ PDF ได้: ' + (e.message || '');
        } finally {
            this.isRenderingPdf = false;
        }
    },

    async renderCurrentPage() {
        if (!rotatePdfDoc) return;
        const pageNum = this.pdfCurrentPage;

        // Check cache first - instantaneous page switching!
        if (pageImageCache[pageNum]) {
            this.previewImageUrl = pageImageCache[pageNum];
            this.isCurrentPageBlank = !!blankPageCache[pageNum];
            this.isRenderingPdf = false;
            this.pdfRenderError = null;
            return;
        }

        this.isRenderingPdf = true;
        this.pdfRenderError = null;

        try {
            // Cancel and await any ongoing render task to completely settle
            if (rotateRenderTask) {
                try {
                    rotateRenderTask.cancel();
                    await rotateRenderTask.promise.catch(() => {});
                } catch (err) {}
                rotateRenderTask = null;
            }

            // Ensure page number hasn't changed during cancellation wait
            if (this.pdfCurrentPage !== pageNum) return;

            const page = await rotatePdfDoc.getPage(pageNum);

            // Determine render resolution
            const baseViewport = page.getViewport({ scale: 1.0 });
            const maxDim = Math.max(baseViewport.width, baseViewport.height);
            // Render at crisp resolution (up to 2x for retina displays)
            const scale = Math.min(2.0, Math.max(0.6, 500 / maxDim));
            const viewport = page.getViewport({ scale: scale });

            // Create a 100% brand new, isolated offscreen canvas for each page
            const offCanvas = document.createElement('canvas');
            offCanvas.width = Math.floor(viewport.width);
            offCanvas.height = Math.floor(viewport.height);

            const ctx = offCanvas.getContext('2d');
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, offCanvas.width, offCanvas.height);

            const renderContext = {
                canvasContext: ctx,
                viewport: viewport
            };

            const task = page.render(renderContext);
            rotateRenderTask = task;
            await task.promise;
            if (rotateRenderTask === task) {
                rotateRenderTask = null;
            }

            // Check whether the page has actual text or drawn content
            let hasText = false;
            try {
                const textContent = await page.getTextContent();
                hasText = (textContent.items || []).some(item => item.str && item.str.trim().length > 0);
            } catch (te) {}

            const imgData = ctx.getImageData(0, 0, offCanvas.width, offCanvas.height).data;
            let hasNonWhitePixels = false;
            for (let i = 0; i < imgData.length; i += 16) {
                const r = imgData[i];
                const g = imgData[i + 1];
                const b = imgData[i + 2];
                if (r < 245 || g < 245 || b < 245) {
                    hasNonWhitePixels = true;
                    break;
                }
            }

            const isBlank = !hasText && !hasNonWhitePixels;
            if (isBlank) {
                ctx.save();
                // Subtle dashed border inside the page
                ctx.strokeStyle = '#cbd5e1';
                ctx.lineWidth = 2;
                ctx.setLineDash([6, 6]);
                ctx.strokeRect(20, 20, offCanvas.width - 40, offCanvas.height - 40);

                const cx = offCanvas.width / 2;
                const cy = offCanvas.height / 2;

                ctx.fillStyle = '#64748b';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.font = 'bold 16px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Noto Sans Thai", sans-serif';
                ctx.fillText('📄 หน้านี้เป็นหน้าว่าง', cx, cy - 12);

                ctx.fillStyle = '#94a3b8';
                ctx.font = '12px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Noto Sans Thai", sans-serif';
                ctx.fillText('(ไม่มีข้อความในไฟล์ PDF ต้นฉบับ)', cx, cy + 14);
                ctx.restore();
            }

            // Convert rendered canvas to PNG data URL and store in cache
            const dataUrl = offCanvas.toDataURL('image/png');
            pageImageCache[pageNum] = dataUrl;
            blankPageCache[pageNum] = isBlank;

            // Only update active display if user is still on this page
            if (this.pdfCurrentPage === pageNum) {
                this.previewImageUrl = dataUrl;
                this.isCurrentPageBlank = isBlank;
            }
            this.pdfRenderError = null;
        } catch (e) {
            if (e && (e.name === 'RenderingCancelledException' || e.message?.includes('cancelled'))) {
                return;
            }
            console.error('renderCurrentPage error on page ' + pageNum, e);
            this.pdfRenderError = 'ไม่สามารถวาดหน้า ' + pageNum + ' ได้: ' + (e.message || '');
        } finally {
            if (this.pdfCurrentPage === pageNum) {
                this.isRenderingPdf = false;
            }
        }
    },

    get hasFiles() { return this.files.length > 0; },
    get totalSize() { return this.formatSize(this.files.reduce((s, f) => s + (f.file ? f.file.size : (f.size || 0)), 0)); },
    get isProcessing() { return this.isUploading || this.isProcessingServer || ['queued', 'processing'].includes(this.jobStatus); },
    get isDone() { return this.jobStatus === 'done'; },
    get isFailed() { return this.jobStatus === 'failed'; },
}));

// =====================================================
// Alpine Component: jobPoller (standalone for result page)
// =====================================================
Alpine.data('jobPoller', (jobId) => ({
    status: 'queued',
    progress: 0,
    downloadUrl: null,
    downloadFileName: null,
    errorMessage: null,
    pollInterval: null,

    init() {
        if (jobId) this.startPolling();
    },

    startPolling() {
        this.pollInterval = setInterval(() => this.poll(), 1500);
    },

    async poll() {
        try {
            const res = await fetch(`/api/jobs/${jobId}`);
            const data = await res.json();
            this.status = data.status;
            this.progress = data.progress ?? 0;

            if (data.status === 'done') {
                clearInterval(this.pollInterval);
                this.downloadUrl = data.download_url;
                this.downloadFileName = data.file_name;
            } else if (data.status === 'failed') {
                clearInterval(this.pollInterval);
                this.errorMessage = data.error_message;
            }
        } catch (e) {
            console.error('Poll error:', e);
        }
    },

    destroy() {
        if (this.pollInterval) clearInterval(this.pollInterval);
    },

    get isProcessing() { return ['queued', 'processing'].includes(this.status); },
    get isDone() { return this.status === 'done'; },
    get isFailed() { return this.status === 'failed'; },
}));

// =====================================================
// Alpine Component: mobileNav
// =====================================================
Alpine.data('mobileNav', () => ({
    isOpen: false,
    toggle() { this.isOpen = !this.isOpen; },
    close() { this.isOpen = false; },
}));

// =====================================================
// Alpine Component: toast notifications
// =====================================================
Alpine.data('toast', () => ({
    show: false,
    message: '',
    type: 'success',
    timeout: null,

    notify(message, type = 'success', duration = 4000) {
        this.message = message;
        this.type = type;
        this.show = true;
        if (this.timeout) clearTimeout(this.timeout);
        this.timeout = setTimeout(() => { this.show = false; }, duration);
    },

    dismiss() {
        this.show = false;
        if (this.timeout) clearTimeout(this.timeout);
    },
}));

// =====================================================
// Start Alpine
// =====================================================
window.Alpine = Alpine;
window.getStagedFiles = getStagedFiles;
window.clearStagedFiles = clearStagedFiles;
Alpine.start();
