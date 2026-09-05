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
let deletePdfDoc = null;
let deleteThumbnailsCache = {};
let watermarkPdfDoc = null;
let watermarkRenderTask = null;
let watermarkPageCache = {};
let signPdfDoc = null;
let signRenderTask = null;
let editorPdfDoc = null;
let editorRenderTask = null;
let editorThumbnailsCache = {};
let splitPdfDoc = null;
let splitThumbnailsCache = {};

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

    // Delete Pages state
    isRenderingDeletePages: false,
    deleteTotalPages: 0,
    selectedPagesToDelete: [],
    deletePagesList: [],
    deleteManualInput: '',
    deletePagesError: null,

    // Watermark Editor State
    isRenderingWatermarkPdf: false,
    watermarkTotalPages: 0,
    watermarkCurrentPage: 1,
    watermarkPdfPageUrl: null,
    watermarkError: null,

    watermarkType: 'image', // 'image' or 'text'
    watermarkImageFile: null,
    watermarkImageDataUrl: null,
    watermarkImageName: null,
    watermarkText: 'สำเนาถูกต้อง',
    watermarkTextColor: '#dc2626',
    watermarkOpacity: 40, // 10 to 100
    watermarkScale: 40,   // 10 to 100
    watermarkPosition: 'center', // 'center', 'top-left', 'top-center', 'top-right', 'center-left', 'center-right', 'bottom-left', 'bottom-center', 'bottom-right', 'tile'
    watermarkRotation: 0, // -180 to 180
    watermarkPages: 'all', // 'all', 'first', 'custom'
    watermarkCustomPages: '',

    // Protect & Unlock PDF Password State
    protectPassword: '',
    protectPasswordConfirm: '',
    showProtectPassword: false,
    showProtectPasswordConfirm: false,
    unlockPassword: '',
    showUnlockPassword: false,
    isVerifyingUnlock: false,
    unlockVerified: false,
    unlockCheckMessage: '',
    unlockPreviewUrl: null,
    isPdfEncrypted: null,

    // Merge PDF Reorder State
    mergeThumbnailsCache: {},
    draggedFileIndex: null,

    // Compress PDF State
    compressQuality: 'ebook', // 'screen', 'ebook', 'printer'
    compressThumb: null,
    compressTotalPages: null,
    compressIsLoadingPreview: false,

    // Split PDF State
    splitMode: 'range', // 'range' or 'all'
    selectedPagesToSplit: [],
    splitManualInput: '',
    splitMergeExtracted: true,
    splitTotalPages: 0,
    splitPagesList: [],
    splitPagesError: null,
    isRenderingSplitPages: false,

    // Image to PDF Layout State
    imageOrientation: 'auto', // 'auto', 'portrait', 'landscape'
    imagePageSize: 'fit', // 'fit', 'a4', 'letter'
    imageMargin: 'none', // 'none', 'small', 'big'
    imageThumbnailsCache: {},

    // PDF to Word State
    wordMode: 'standard', // 'standard' (High-Fidelity Layout) or 'ocr' (Thai OCR)
    wordPagesMode: 'all', // 'all' or 'custom'
    wordCustomPages: '',
    wordDetectTables: true,
    wordKeepImages: true,
    wordDetectedType: 'checking', // 'checking', 'digital', 'scanned'
    wordPreviewPageUrl: null,
    wordTotalPages: 0,
    isAnalyzingWordPdf: false,

    // PDF to Images State (pdf-to-jpg & pdf-to-png)
    imgDpi: '150', // '150' or '300'
    imgPagesMode: 'all', // 'all' or 'custom'
    imgSelectedPages: [],
    imgManualInput: '',
    imgPagesList: [],
    imgTotalPages: 0,
    isRenderingImgPages: false,

    // Page Numbers State
    pnPosition: 'bottom-center', // 'bottom-center', 'bottom-left', 'bottom-right', 'top-center', 'top-left', 'top-right'
    pnFormat: 'n', // 'n', 'n-of-total', 'page-n', 'page-n-of-total'
    pnStartNum: 1,
    pnSkipFirst: false,
    pnFontSize: 11, // 9, 11, 14
    pnColor: '#333333',
    pnPreviewPageUrl: null,
    pnTotalPages: 0,
    isRenderingPnPages: false,

    // Crop PDF State
    cropMode: 'custom', // 'custom', 'auto-margins', 'trim-scanner'
    cropTop: 8,
    cropBottom: 8,
    cropLeft: 8,
    cropRight: 8,
    cropPages: 'all', // 'all', 'custom'
    cropCustomPages: '',
    cropPreviewUrl: null,
    cropTotalPages: 0,
    cropCurrentPage: 1,
    isRenderingCropPreview: false,

    // Organize PDF State
    orgPagesList: [],
    orgTotalPages: 0,
    isRenderingOrgPages: false,
    draggedOrgPageIndex: null,

    // PDF to Excel State
    excelMode: 'standard', // 'standard', 'ocr'
    excelTableMode: 'auto', // 'auto', 'lattice', 'stream'
    excelSheetMode: 'single', // 'single', 'multiple'
    excelPagesMode: 'all', // 'all', 'custom'
    excelCustomPages: '',
    excelPreviewUrl: null,
    excelTotalPages: 0,
    excelCurrentPage: 1,
    isAnalyzingExcelPdf: false,
    excelDetectedTableType: 'checking', // 'checking', 'lattice', 'stream'
    excelDetectedCorruptedThai: false,

    // PDF to PowerPoint State
    pptxMode: 'editable', // 'editable', 'image', 'ocr'
    pptxRatio: '16:9', // '16:9', '4:3'
    pptxPagesMode: 'all', // 'all', 'custom'
    pptxCustomPages: '',
    pptxPreviewUrl: null,
    pptxTotalPages: 0,
    pptxCurrentPage: 1,
    isAnalyzingPptxPdf: false,
    pptxDetectedOrientation: 'landscape', // 'landscape', 'portrait'

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
        if (this.tool === 'delete-pages' && this.files.length > 0) {
            this.loadDeletePagesPreview();
        }
        if (this.tool === 'watermark-pdf' && this.files.length > 0) {
            this.loadWatermarkPdfPreview();
        }
        if (this.tool === 'unlock-pdf' && this.files.length > 0) {
            this.detectPdfEncryption();
        }
        if (this.tool === 'merge-pdf' && this.files.length > 0) {
            this.loadMergeThumbnails();
        }
        if (this.tool === 'compress-pdf' && this.files.length > 0) {
            this.loadCompressPdfPreview();
        }
        if (this.tool === 'split-pdf' && this.files.length > 0) {
            this.loadSplitPagesPreview();
        }
        if (this.tool === 'image-to-pdf' && this.files.length > 0) {
            this.loadImageThumbnails();
        }
        if (this.tool === 'pdf-to-word' && this.files.length > 0) {
            this.analyzePdfForWord();
        }
        if (['pdf-to-jpg', 'pdf-to-png'].includes(this.tool) && this.files.length > 0) {
            this.loadPdfToImagesPreview();
        }
        if (this.tool === 'page-numbers' && this.files.length > 0) {
            this.loadPageNumbersPreview();
        }
        if (this.tool === 'crop-pdf' && this.files.length > 0) {
            this.loadCropPreview();
        }
        if (this.tool === 'organize-pdf' && this.files.length > 0) {
            this.loadOrganizePreview();
        }
        if (this.tool === 'pdf-to-excel' && this.files.length > 0) {
            this.loadExcelPdfPreview();
        }
        if (this.tool === 'pdf-to-pptx' && this.files.length > 0) {
            this.loadPptxPdfPreview();
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
        // If delete-pages, load thumbnails editor
        if (this.tool === 'delete-pages' && this.files.length > 0) {
            setTimeout(() => this.loadDeletePagesPreview(), 60);
        }
        // If watermark-pdf, load preview editor
        if (this.tool === 'watermark-pdf' && this.files.length > 0) {
            setTimeout(() => this.loadWatermarkPdfPreview(), 60);
        }
        // If unlock-pdf, check if encrypted
        if (this.tool === 'unlock-pdf' && this.files.length > 0) {
            setTimeout(() => this.detectPdfEncryption(), 60);
        }
        // If merge-pdf, load thumbnails
        if (this.tool === 'merge-pdf' && this.files.length > 0) {
            setTimeout(() => this.loadMergeThumbnails(), 60);
        }
        // If compress-pdf, load preview
        if (this.tool === 'compress-pdf' && this.files.length > 0) {
            setTimeout(() => this.loadCompressPdfPreview(), 60);
        }
        // If split-pdf, load preview
        if (this.tool === 'split-pdf' && this.files.length > 0) {
            setTimeout(() => this.loadSplitPagesPreview(), 60);
        }
        // If image-to-pdf, load thumbnails
        if (this.tool === 'image-to-pdf' && this.files.length > 0) {
            this.loadImageThumbnails();
        }
        // If pdf-to-word, analyze document
        if (this.tool === 'pdf-to-word' && this.files.length > 0) {
            setTimeout(() => this.analyzePdfForWord(), 60);
        }
        // If pdf-to-jpg / pdf-to-png, load preview
        if (['pdf-to-jpg', 'pdf-to-png'].includes(this.tool) && this.files.length > 0) {
            setTimeout(() => this.loadPdfToImagesPreview(), 60);
        }
        // If page-numbers, load preview
        if (this.tool === 'page-numbers' && this.files.length > 0) {
            setTimeout(() => this.loadPageNumbersPreview(), 60);
        }
        // If crop-pdf, load preview
        if (this.tool === 'crop-pdf' && this.files.length > 0) {
            setTimeout(() => this.loadCropPreview(), 60);
        }
        // If organize-pdf, load preview
        if (this.tool === 'organize-pdf' && this.files.length > 0) {
            setTimeout(() => this.loadOrganizePreview(), 60);
        }
        // If pdf-to-excel, load preview
        if (this.tool === 'pdf-to-excel' && this.files.length > 0) {
            setTimeout(() => this.loadExcelPdfPreview(), 60);
        }
        // If pdf-to-pptx, load preview
        if (this.tool === 'pdf-to-pptx' && this.files.length > 0) {
            setTimeout(() => this.loadPptxPdfPreview(), 60);
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
        if (this.tool === 'delete-pages') {
            this.clearDeletePagesState();
            if (this.files.length > 0) {
                setTimeout(() => this.loadDeletePagesPreview(), 60);
            }
        }
        if (this.tool === 'watermark-pdf') {
            this.clearWatermarkState();
            if (this.files.length > 0) {
                setTimeout(() => this.loadWatermarkPdfPreview(), 60);
            }
        }
        if (this.tool === 'unlock-pdf') {
            this.clearUnlockState();
            if (this.files.length > 0) {
                setTimeout(() => this.detectPdfEncryption(), 60);
            }
        }
        if (this.tool === 'compress-pdf') {
            this.clearCompressState();
            if (this.files.length > 0) {
                setTimeout(() => this.loadCompressPdfPreview(), 60);
            }
        }
        if (this.tool === 'split-pdf') {
            this.clearSplitPagesState();
            if (this.files.length > 0) {
                setTimeout(() => this.loadSplitPagesPreview(), 60);
            }
        }
        if (this.tool === 'page-numbers') {
            this.clearPageNumbersState();
            if (this.files.length > 0) {
                setTimeout(() => this.loadPageNumbersPreview(), 60);
            }
        }
        if (this.tool === 'crop-pdf') {
            this.clearCropState();
            if (this.files.length > 0) {
                setTimeout(() => this.loadCropPreview(), 60);
            }
        }
        if (this.tool === 'organize-pdf') {
            this.clearOrganizeState();
            if (this.files.length > 0) {
                setTimeout(() => this.loadOrganizePreview(), 60);
            }
        }
        if (this.tool === 'pdf-to-excel') {
            this.clearExcelState();
            if (this.files.length > 0) {
                setTimeout(() => this.loadExcelPdfPreview(), 60);
            }
        }
        if (this.tool === 'pdf-to-pptx') {
            this.clearPptxState();
            if (this.files.length > 0) {
                setTimeout(() => this.loadPptxPdfPreview(), 60);
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
        this.clearDeletePagesState();
        this.clearWatermarkState();
        this.clearUnlockState();
        this.mergeThumbnailsCache = {};
        this.draggedFileIndex = null;
        this.clearCompressState();
        this.clearSplitPagesState();
        this.clearImageToPdfState();
        this.clearWordState();
        this.clearPdfToImagesState();
        this.clearPageNumbersState();
        this.clearCropState();
        this.clearOrganizeState();
        this.clearExcelState();
        this.protectPassword = '';
        this.protectPasswordConfirm = '';
        this.showProtectPassword = false;
        this.showProtectPasswordConfirm = false;
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
        if (activeTool === 'delete-pages') {
            if (!this.canSubmitDeletePages) {
                this.error = this.selectedPagesToDelete.length === 0
                    ? 'กรุณาเลือกอย่างน้อย 1 หน้าที่ต้องการลบ'
                    : 'ไม่สามารถลบทุกหน้าได้ ต้องเหลือเอกสารอย่างน้อย 1 หน้า';
                this.isUploading = false;
                return;
            }
            const pagesStr = this.selectedPagesToDelete.join(',');
            formData.append('pages_to_delete', pagesStr);
            formData.append('config[pages_to_delete]', pagesStr);
        }
        if (activeTool === 'watermark-pdf') {
            if (this.watermarkType === 'image' && !this.watermarkImageDataUrl) {
                this.error = 'กรุณาอัปโหลดรูปภาพที่ต้องการใช้เป็นลายน้ำ';
                this.isUploading = false;
                return;
            }
            if (this.watermarkType === 'text' && !this.watermarkText.trim()) {
                this.error = 'กรุณาระบุข้อความลายน้ำ';
                this.isUploading = false;
                return;
            }
            formData.append('watermark_type', this.watermarkType);
            formData.append('config[type]', this.watermarkType);

            formData.append('watermark_opacity', (this.watermarkOpacity / 100).toString());
            formData.append('config[opacity]', (this.watermarkOpacity / 100).toString());

            formData.append('watermark_scale', (this.watermarkScale / 100).toString());
            formData.append('config[scale]', (this.watermarkScale / 100).toString());

            formData.append('watermark_position', this.watermarkPosition);
            formData.append('config[position]', this.watermarkPosition);

            formData.append('watermark_rotation', this.watermarkRotation.toString());
            formData.append('config[rotation]', this.watermarkRotation.toString());

            const pagesVal = this.watermarkPages === 'custom' ? this.watermarkCustomPages : this.watermarkPages;
            formData.append('watermark_pages', pagesVal);
            formData.append('config[pages]', pagesVal);

            if (this.watermarkType === 'image') {
                if (this.watermarkImageFile) {
                    formData.append('watermark_image', this.watermarkImageFile);
                }
                if (this.watermarkImageDataUrl) {
                    formData.append('config[watermark_image_data]', this.watermarkImageDataUrl);
                }
            } else {
                formData.append('watermark_text', this.watermarkText);
                formData.append('config[text]', this.watermarkText);
                formData.append('watermark_color', this.watermarkTextColor);
                formData.append('config[color]', this.watermarkTextColor);
            }
        }
        if (activeTool === 'protect-pdf') {
            if (!this.protectPassword) {
                this.error = 'กรุณากรอกรหัสผ่านที่ต้องการตั้ง';
                this.isUploading = false;
                return;
            }
            if (this.protectPassword !== this.protectPasswordConfirm) {
                this.error = 'รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน';
                this.isUploading = false;
                return;
            }
            formData.append('password', this.protectPassword);
            formData.append('config[password]', this.protectPassword);
        }
        if (activeTool === 'unlock-pdf') {
            if (!this.unlockPassword) {
                this.error = 'กรุณากรอกรหัสผ่านของไฟล์ PDF เพื่อปลดล็อก';
                this.isUploading = false;
                return;
            }
            formData.append('password', this.unlockPassword);
            formData.append('config[password]', this.unlockPassword);
        }
        if (activeTool === 'compress-pdf') {
            formData.append('quality', this.compressQuality);
            formData.append('config[quality]', this.compressQuality);
        }
        if (activeTool === 'split-pdf') {
            if (!this.canSubmitSplitPdf) {
                this.error = 'กรุณาเลือกอย่างน้อย 1 หน้าที่ต้องการแยก';
                this.isUploading = false;
                return;
            }
            formData.append('split_mode', this.splitMode);
            formData.append('config[split_mode]', this.splitMode);

            const pageListStr = this.selectedPagesToSplit.join(',');
            formData.append('page_list', pageListStr);
            formData.append('config[page_list]', pageListStr);

            formData.append('merge_extracted', this.splitMergeExtracted ? '1' : '0');
            formData.append('config[merge_extracted]', this.splitMergeExtracted ? '1' : '0');
        }
        if (activeTool === 'image-to-pdf') {
            formData.append('orientation', this.imageOrientation);
            formData.append('config[orientation]', this.imageOrientation);
            formData.append('page_size', this.imagePageSize);
            formData.append('config[page_size]', this.imagePageSize);
            formData.append('margin', this.imageMargin);
            formData.append('config[margin]', this.imageMargin);
        }
        if (activeTool === 'pdf-to-word') {
            formData.append('word_mode', this.wordMode);
            formData.append('config[word_mode]', this.wordMode);
            const pagesVal = this.wordPagesMode === 'custom' && this.wordCustomPages.trim() ? this.wordCustomPages.trim() : 'all';
            formData.append('word_pages', pagesVal);
            formData.append('config[word_pages]', pagesVal);
            formData.append('word_tables', this.wordDetectTables ? '1' : '0');
            formData.append('config[word_tables]', this.wordDetectTables ? '1' : '0');
            formData.append('word_keep_images', this.wordKeepImages ? '1' : '0');
            formData.append('config[word_keep_images]', this.wordKeepImages ? '1' : '0');
        }
        if (['pdf-to-jpg', 'pdf-to-png'].includes(activeTool)) {
            formData.append('image_dpi', this.imgDpi);
            formData.append('config[image_dpi]', this.imgDpi);
            formData.append('image_pages_mode', this.imgPagesMode);
            formData.append('config[image_pages_mode]', this.imgPagesMode);
            if (this.imgPagesMode === 'custom') {
                const pagesStr = this.imgSelectedPages.join(',');
                formData.append('image_selected_pages', pagesStr);
                formData.append('config[image_selected_pages]', pagesStr);
            }
        }
        if (activeTool === 'page-numbers') {
            formData.append('page_numbers_position', this.pnPosition);
            formData.append('config[position]', this.pnPosition);
            formData.append('page_numbers_format', this.pnFormat);
            formData.append('config[format]', this.pnFormat);
            formData.append('page_numbers_start', this.pnStartNum);
            formData.append('config[start]', this.pnStartNum);
            formData.append('page_numbers_skip_first', this.pnSkipFirst ? '1' : '0');
            formData.append('config[skip_first]', this.pnSkipFirst ? '1' : '0');
            formData.append('page_numbers_font_size', this.pnFontSize);
            formData.append('config[font_size]', this.pnFontSize);
            formData.append('page_numbers_color', this.pnColor);
            formData.append('config[color]', this.pnColor);
        }
        if (activeTool === 'crop-pdf') {
            formData.append('crop_mode', this.cropMode);
            formData.append('config[crop_mode]', this.cropMode);
            formData.append('crop_top', this.cropTop.toString());
            formData.append('config[crop_top]', this.cropTop.toString());
            formData.append('crop_bottom', this.cropBottom.toString());
            formData.append('config[crop_bottom]', this.cropBottom.toString());
            formData.append('crop_left', this.cropLeft.toString());
            formData.append('config[crop_left]', this.cropLeft.toString());
            formData.append('crop_right', this.cropRight.toString());
            formData.append('config[crop_right]', this.cropRight.toString());
            const pagesVal = this.cropPages === 'custom' && this.cropCustomPages.trim() ? this.cropCustomPages.trim() : 'all';
            formData.append('crop_pages', pagesVal);
            formData.append('config[crop_pages]', pagesVal);
        }
        if (activeTool === 'organize-pdf') {
            if (!this.orgPagesList || this.orgPagesList.length === 0) {
                this.error = 'กรุณาเลือกเอกสารที่มีอย่างน้อย 1 หน้า';
                this.isUploading = false;
                return;
            }
            const pagesPayload = this.orgPagesList.map(p => ({
                page: p.origPageNum,
                rotation: p.rotation || 0
            }));
            const jsonStr = JSON.stringify(pagesPayload);
            formData.append('organize_pages_data', jsonStr);
            formData.append('config[organize_pages_data]', jsonStr);
        }
        if (activeTool === 'pdf-to-excel') {
            formData.append('excel_mode', this.excelMode);
            formData.append('config[excel_mode]', this.excelMode);
            formData.append('excel_ocr_mode', this.excelMode);
            formData.append('config[excel_ocr_mode]', this.excelMode);
            formData.append('excel_table_mode', this.excelTableMode);
            formData.append('config[excel_table_mode]', this.excelTableMode);
            formData.append('excel_sheet_mode', this.excelSheetMode);
            formData.append('config[excel_sheet_mode]', this.excelSheetMode);
            formData.append('excel_pages_mode', this.excelPagesMode);
            formData.append('config[excel_pages_mode]', this.excelPagesMode);
            const pVal = this.excelPagesMode === 'custom' && this.excelCustomPages.trim() ? this.excelCustomPages.trim() : 'all';
            formData.append('excel_custom_pages', pVal);
            formData.append('config[excel_custom_pages]', pVal);
        }
        if (activeTool === 'pdf-to-pptx') {
            formData.append('pptx_mode', this.pptxMode);
            formData.append('config[pptx_mode]', this.pptxMode);
            formData.append('pptx_ratio', this.pptxRatio);
            formData.append('config[pptx_ratio]', this.pptxRatio);
            formData.append('pptx_pages_mode', this.pptxPagesMode);
            formData.append('config[pptx_pages_mode]', this.pptxPagesMode);
            const pVal = this.pptxPagesMode === 'custom' && this.pptxCustomPages.trim() ? this.pptxCustomPages.trim() : 'all';
            formData.append('pptx_custom_pages', pVal);
            formData.append('config[pptx_custom_pages]', pVal);
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
            'delete-pages': 'กำลังลบหน้าที่เลือกออกจากเอกสาร PDF...',
            'watermark-pdf': 'กำลังประทับลายน้ำลงในเอกสาร PDF...',
            'watermark-pdf': 'กำลังใส่ลายน้ำเอกสาร PDF...',
            'protect-pdf': 'กำลังตั้งรหัสผ่านป้องกัน PDF...',
            'unlock-pdf': 'กำลังปลดล็อครหัสผ่าน PDF...',
            'pdf-to-txt': 'กำลังดึงข้อความภาษาไทยออกจาก PDF (.txt)...',
            'pptx-to-pdf': 'กำลังแปลงสไลด์ PowerPoint เป็นเอกสาร PDF...',
            'word-to-pdf': 'กำลังแปลงเอกสาร Word เป็นเอกสาร PDF...',
            'excel-to-pdf': 'กำลังแปลงเอกสาร Excel เป็นเอกสาร PDF...',
            'image-to-pdf': 'กำลังแปลงรูปภาพเป็นเอกสาร PDF...',
            'page-numbers': 'กำลังใส่เลขหน้าลงในเอกสาร PDF...',
            'crop-pdf': 'กำลังครอบตัดและปรับขอบเอกสาร PDF...',
            'organize-pdf': 'กำลังจัดเรียงและประมวลผลหน้าเอกสาร PDF...',
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

    // =====================================================
    // Delete Pages Methods & Visual Editor
    // =====================================================
    clearDeletePagesState() {
        deletePdfDoc = null;
        deleteThumbnailsCache = {};
        this.deletePagesList = [];
        this.selectedPagesToDelete = [];
        this.deleteTotalPages = 0;
        this.deleteManualInput = '';
        this.deletePagesError = null;
        this.isRenderingDeletePages = false;
    },

    async loadDeletePagesPreview() {
        if (!this.files.length || !this.files[0].file) {
            this.clearDeletePagesState();
            return;
        }
        const file = this.files[0].file;
        if (file.type !== 'application/pdf' && !file.name.toLowerCase().endsWith('.pdf')) {
            return;
        }

        this.clearDeletePagesState();
        this.isRenderingDeletePages = true;

        try {
            await this.ensurePdfJs();
            const arrayBuffer = await file.arrayBuffer();
            const loadingTask = window.pdfjsLib.getDocument({
                data: arrayBuffer,
                cMapUrl: 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/cmaps/',
                cMapPacked: true,
                standardFontDataUrl: 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/standard_fonts/',
            });
            deletePdfDoc = await loadingTask.promise;
            this.deleteTotalPages = deletePdfDoc.numPages || 1;

            // Pre-populate page placeholders for instant UI feedback
            this.deletePagesList = Array.from({ length: this.deleteTotalPages }, (_, i) => ({
                pageNum: i + 1,
                dataUrl: null,
                isBlank: false
            }));

            // Render all thumbnails with lightweight resolution
            for (let p = 1; p <= this.deleteTotalPages; p++) {
                if (!deletePdfDoc || this.files.length === 0) break;
                await this.renderDeleteThumbnail(p);
            }
        } catch (e) {
            console.error('loadDeletePagesPreview error:', e);
            this.deletePagesError = 'ไม่สามารถอ่านไฟล์ PDF ได้: ' + (e.message || '');
        } finally {
            this.isRenderingDeletePages = false;
        }
    },

    async renderDeleteThumbnail(pageNum) {
        if (!deletePdfDoc) return;
        try {
            if (deleteThumbnailsCache[pageNum]) {
                const item = this.deletePagesList.find(x => x.pageNum === pageNum);
                if (item) {
                    item.dataUrl = deleteThumbnailsCache[pageNum].dataUrl;
                    item.isBlank = deleteThumbnailsCache[pageNum].isBlank;
                }
                return;
            }

            const page = await deletePdfDoc.getPage(pageNum);
            const baseViewport = page.getViewport({ scale: 1.0 });
            const maxDim = Math.max(baseViewport.width, baseViewport.height);
            // Render thumbnail at crisp resolution suited for cards (~200px)
            const scale = Math.min(1.2, Math.max(0.2, 220 / maxDim));
            const viewport = page.getViewport({ scale: scale });

            const offCanvas = document.createElement('canvas');
            offCanvas.width = Math.floor(viewport.width);
            offCanvas.height = Math.floor(viewport.height);

            const ctx = offCanvas.getContext('2d');
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, offCanvas.width, offCanvas.height);

            await page.render({
                canvasContext: ctx,
                viewport: viewport
            }).promise;

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
                ctx.strokeStyle = '#cbd5e1';
                ctx.lineWidth = 2;
                ctx.setLineDash([4, 4]);
                ctx.strokeRect(8, 8, offCanvas.width - 16, offCanvas.height - 16);

                const cx = offCanvas.width / 2;
                const cy = offCanvas.height / 2;
                ctx.fillStyle = '#64748b';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.font = 'bold 12px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Noto Sans Thai", sans-serif';
                ctx.fillText('📄 หน้าว่าง', cx, cy - 8);
                ctx.font = '10px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Noto Sans Thai", sans-serif';
                ctx.fillText('(ไม่มีเนื้อหา)', cx, cy + 10);
                ctx.restore();
            }

            const dataUrl = offCanvas.toDataURL('image/jpeg', 0.85);
            deleteThumbnailsCache[pageNum] = { dataUrl, isBlank };

            const item = this.deletePagesList.find(x => x.pageNum === pageNum);
            if (item) {
                item.dataUrl = dataUrl;
                item.isBlank = isBlank;
            }
        } catch (e) {
            console.warn('renderDeleteThumbnail error on page ' + pageNum, e);
        }
    },

    togglePageDeletion(pageNum) {
        const idx = this.selectedPagesToDelete.indexOf(pageNum);
        if (idx > -1) {
            this.selectedPagesToDelete.splice(idx, 1);
            this.deletePagesError = null;
        } else {
            if (this.selectedPagesToDelete.length + 1 >= this.deleteTotalPages) {
                this.deletePagesError = 'ไม่สามารถลบทุกหน้าได้ ต้องเหลือเอกสารอย่างน้อย 1 หน้า';
                setTimeout(() => { if (this.deletePagesError?.includes('อย่างน้อย')) this.deletePagesError = null; }, 4000);
                return;
            }
            this.selectedPagesToDelete.push(pageNum);
            this.selectedPagesToDelete.sort((a, b) => a - b);
            this.deletePagesError = null;
        }
        this.syncManualInputFromSelection();
    },

    selectEvenPages() {
        const evens = [];
        for (let p = 2; p <= this.deleteTotalPages; p += 2) {
            evens.push(p);
        }
        if (evens.length >= this.deleteTotalPages && this.deleteTotalPages > 0) {
            this.deletePagesError = 'ไม่สามารถลบทุกหน้าได้';
            return;
        }
        this.selectedPagesToDelete = evens;
        this.deletePagesError = null;
        this.syncManualInputFromSelection();
    },

    selectOddPages() {
        const odds = [];
        for (let p = 1; p <= this.deleteTotalPages; p += 2) {
            odds.push(p);
        }
        if (odds.length >= this.deleteTotalPages && this.deleteTotalPages > 0) {
            this.deletePagesError = 'ไม่สามารถลบทุกหน้าได้';
            return;
        }
        this.selectedPagesToDelete = odds;
        this.deletePagesError = null;
        this.syncManualInputFromSelection();
    },

    selectBlankPages() {
        const blanks = this.deletePagesList.filter(x => x.isBlank).map(x => x.pageNum);
        if (blanks.length === 0) {
            this.deletePagesError = 'ไม่พบหน้าว่างในไฟล์ PDF นี้';
            setTimeout(() => { if (this.deletePagesError?.includes('ไม่พบหน้าว่าง')) this.deletePagesError = null; }, 3000);
            return;
        }
        if (blanks.length >= this.deleteTotalPages && this.deleteTotalPages > 0) {
            this.deletePagesError = 'ไม่สามารถลบทุกหน้าได้ (ทุกหน้าในไฟล์เป็นหน้าว่าง)';
            return;
        }
        this.selectedPagesToDelete = blanks;
        this.deletePagesError = null;
        this.syncManualInputFromSelection();
    },

    clearPageSelection() {
        this.selectedPagesToDelete = [];
        this.deleteManualInput = '';
        this.deletePagesError = null;
    },

    syncManualInputFromSelection() {
        if (!this.selectedPagesToDelete.length) {
            this.deleteManualInput = '';
            return;
        }
        const sorted = [...this.selectedPagesToDelete].sort((a, b) => a - b);
        const ranges = [];
        let start = sorted[0];
        let end = sorted[0];

        for (let i = 1; i < sorted.length; i++) {
            if (sorted[i] === end + 1) {
                end = sorted[i];
            } else {
                ranges.push(start === end ? `${start}` : `${start}-${end}`);
                start = sorted[i];
                end = sorted[i];
            }
        }
        ranges.push(start === end ? `${start}` : `${start}-${end}`);
        this.deleteManualInput = ranges.join(', ');
    },

    handleManualPageInput(val) {
        this.deleteManualInput = val;
        if (!val.trim()) {
            this.selectedPagesToDelete = [];
            this.deletePagesError = null;
            return;
        }
        const pages = new Set();
        const parts = val.split(',');
        for (const part of parts) {
            const trimmed = part.trim();
            if (!trimmed) continue;
            if (trimmed.includes('-')) {
                const [startStr, endStr] = trimmed.split('-');
                const start = parseInt(startStr, 10);
                const end = parseInt(endStr, 10);
                if (!isNaN(start) && !isNaN(end)) {
                    const min = Math.max(1, Math.min(start, end));
                    const max = Math.min(this.deleteTotalPages, Math.max(start, end));
                    for (let p = min; p <= max; p++) {
                        pages.add(p);
                    }
                }
            } else {
                const p = parseInt(trimmed, 10);
                if (!isNaN(p) && p >= 1 && p <= this.deleteTotalPages) {
                    pages.add(p);
                }
            }
        }
        const arr = Array.from(pages).sort((a, b) => a - b);
        if (arr.length >= this.deleteTotalPages && this.deleteTotalPages > 0) {
            this.deletePagesError = 'ไม่สามารถลบทุกหน้าได้ ต้องเหลือเอกสารอย่างน้อย 1 หน้า';
        } else {
            this.deletePagesError = null;
        }
        this.selectedPagesToDelete = arr;
    },

    isPageSelectedForDeletion(pageNum) {
        return this.selectedPagesToDelete.includes(pageNum);
    },

    get remainingPagesCount() {
        return Math.max(0, this.deleteTotalPages - this.selectedPagesToDelete.length);
    },

    get canSubmitDeletePages() {
        return this.selectedPagesToDelete.length > 0 && this.selectedPagesToDelete.length < this.deleteTotalPages;
    },

    get toolButtonText() {
        if (this.tool === 'rotate-pdf' && this.hasFiles) {
            return `หมุน PDF (${this.rotationAngle}°) และบันทึก`;
        }
        if (this.tool === 'delete-pages' && this.hasFiles) {
            if (this.selectedPagesToDelete.length === 0) {
                return 'เลือกหน้าที่ต้องการลบ (คลิกที่หน้า)';
            }
            if (this.selectedPagesToDelete.length >= this.deleteTotalPages) {
                return 'ต้องเหลืออย่างน้อย 1 หน้า';
            }
            return `ลบ ${this.selectedPagesToDelete.length} หน้า และดาวน์โหลด (เหลือ ${this.remainingPagesCount} หน้า)`;
        }
        if (this.tool === 'watermark-pdf' && this.hasFiles) {
            if (this.watermarkType === 'image' && !this.watermarkImageDataUrl) {
                return 'กรุณาเลือกรูปลายน้ำ';
            }
            if (this.watermarkType === 'text' && !this.watermarkText.trim()) {
                return 'กรุณาระบุข้อความลายน้ำ';
            }
            return 'ใส่ลายน้ำและดาวน์โหลด PDF';
        }
        if (this.tool === 'protect-pdf' && this.hasFiles) {
            if (!this.protectPassword) {
                return 'กรุณาระบุรหัสผ่าน';
            }
            if (this.protectPassword !== this.protectPasswordConfirm) {
                return 'รหัสผ่านทั้งสองช่องไม่ตรงกัน';
            }
            return 'ตั้งรหัสผ่านและดาวน์โหลด PDF';
        }
        if (this.tool === 'unlock-pdf' && this.hasFiles) {
            if (!this.unlockPassword) {
                return 'กรุณากรอกรหัสผ่านเพื่อปลดล็อก';
            }
            return 'ปลดล็อกรหัสผ่านและดาวน์โหลด PDF';
        }
        if (this.tool === 'merge-pdf' && this.hasFiles) {
            if (this.files.length < 2) {
                return `ต้องการอีกอย่างน้อย 1 ไฟล์ (ปัจจุบันมี ${this.files.length} ไฟล์)`;
            }
            return `รวม ${this.files.length} ไฟล์ PDF ตามลำดับนี้`;
        }
        if (this.tool === 'compress-pdf' && this.hasFiles) {
            const labels = {
                screen: 'บีบอัดสูงสุด ~75%',
                ebook: 'บีบอัดที่แนะนำ ~55%',
                printer: 'บีบอัดน้อย/คมชัดสูง ~25%'
            };
            const label = labels[this.compressQuality] || 'บีบอัดที่แนะนำ';
            return `บีบอัด PDF (${label})`;
        }
        if (this.tool === 'split-pdf' && this.hasFiles) {
            if (this.splitMode === 'all') {
                return `แยกทุกหน้า (${this.splitTotalPages || 1} หน้า) เป็นไฟล์ Zip`;
            }
            if (this.selectedPagesToSplit.length === 0) {
                return 'กรุณาเลือกหน้าที่ต้องการแยก';
            }
            if (this.splitMergeExtracted) {
                return `แยกและรวม ${this.selectedPagesToSplit.length} หน้าเป็น 1 ไฟล์ PDF`;
            }
            return `แยก ${this.selectedPagesToSplit.length} หน้าออกเป็นไฟล์ Zip`;
        }
        if (this.tool === 'image-to-pdf' && this.hasFiles) {
            return `แปลง ${this.files.length} รูปภาพเป็น PDF`;
        }
        if (this.tool === 'pdf-to-word' && this.hasFiles) {
            if (this.wordMode === 'ocr') {
                return 'แปลงเป็น Word (ถอดข้อความ OCR)';
            }
            return 'แปลงเป็น Word (รักษา Layout & ฟอนต์)';
        }
        if (['pdf-to-jpg', 'pdf-to-png'].includes(this.tool) && this.hasFiles) {
            const ext = this.tool === 'pdf-to-png' ? 'PNG' : 'JPG';
            const dpiLabel = this.imgDpi === '300' ? ' (300 DPI - HQ)' : ' (150 DPI)';
            if (this.imgPagesMode === 'all') {
                return `แปลงทุกหน้าเป็น ${ext}${dpiLabel}`;
            }
            if (this.imgSelectedPages.length === 0) {
                return 'กรุณาเลือกหน้าที่ต้องการแปลง';
            }
            if (this.imgSelectedPages.length === 1) {
                return `แปลงหน้า ${this.imgSelectedPages[0]} เป็น ${ext}${dpiLabel}`;
            }
            return `แปลง ${this.imgSelectedPages.length} หน้าเป็น ${ext} (.ZIP)${dpiLabel}`;
        }
        if (this.tool === 'page-numbers' && this.hasFiles) {
            const pageCount = this.pnTotalPages || 1;
            return `ใส่เลขหน้าเอกสาร (${pageCount} หน้า)`;
        }
        if (this.tool === 'crop-pdf' && this.hasFiles) {
            const pageCount = this.cropTotalPages || 1;
            if (this.cropMode === 'auto-margins') {
                return `ตัดขอบขาวอัตโนมัติ (${pageCount} หน้า)`;
            }
            if (this.cropMode === 'trim-scanner') {
                return `ตัดขอบดำสแกน (${pageCount} หน้า)`;
            }
            return `ครอบตัดเอกสาร (${pageCount} หน้า)`;
        }
        if (this.tool === 'organize-pdf' && this.hasFiles) {
            const count = this.orgPagesList ? this.orgPagesList.length : (this.orgTotalPages || 1);
            return `บันทึกและดาวน์โหลด PDF ที่จัดเรียงใหม่ (${count} หน้า)`;
        }
        if (this.tool === 'pdf-to-excel' && this.hasFiles) {
            const modeMap = {
                'auto': 'ตรวจจับอัตโนมัติ',
                'lattice': 'ตารางมีเส้น',
                'stream': 'ตารางไม่มีเส้น'
            };
            const modeLabel = modeMap[this.excelTableMode] || 'ตรวจจับอัตโนมัติ';
            const sheetLabel = this.excelSheetMode === 'single' ? 'รวมชีตเดียว' : 'แยกหน้าละชีต';
            const pCount = this.excelPagesMode === 'custom' && this.excelCustomPages ? ` (หน้า ${this.excelCustomPages})` : (this.excelTotalPages ? ` (${this.excelTotalPages} หน้า)` : '');
            return `แปลงเป็น Excel .xlsx [${modeLabel} · ${sheetLabel}]${pCount}`;
        }
        if (this.tool === 'pdf-to-pptx' && this.hasFiles) {
            const modeMap = {
                'editable': 'แก้ไขได้เต็มรูปแบบ',
                'image': 'สไลด์ภาพคมชัดสูง',
                'ocr': 'OCR ภาษาไทย'
            };
            const modeLabel = modeMap[this.pptxMode] || 'แก้ไขได้';
            const ratioLabel = this.pptxRatio === '16:9' ? '16:9 จอกว้าง' : '4:3 มาตรฐาน';
            const pCount = this.pptxPagesMode === 'custom' && this.pptxCustomPages ? ` (หน้า ${this.pptxCustomPages})` : (this.pptxTotalPages ? ` (${this.pptxTotalPages} สไลด์)` : '');
            return `แปลงเป็น PowerPoint .pptx [${modeLabel} · ${ratioLabel}]${pCount}`;
        }
        return null;
    },

    // =====================================================
    // Watermark PDF Methods & Live Editor
    // =====================================================
    clearWatermarkState() {
        watermarkPdfDoc = null;
        if (watermarkRenderTask) {
            try { watermarkRenderTask.cancel(); } catch (e) {}
            watermarkRenderTask = null;
        }
        watermarkPageCache = {};
        this.watermarkTotalPages = 0;
        this.watermarkCurrentPage = 1;
        this.watermarkPdfPageUrl = null;
        this.watermarkError = null;
        this.isRenderingWatermarkPdf = false;
    },

    async loadWatermarkPdfPreview() {
        if (!this.files.length || !this.files[0].file) {
            this.clearWatermarkState();
            return;
        }
        const file = this.files[0].file;
        if (file.type !== 'application/pdf' && !file.name.toLowerCase().endsWith('.pdf')) {
            return;
        }

        this.clearWatermarkState();
        this.isRenderingWatermarkPdf = true;

        try {
            await this.ensurePdfJs();
            const arrayBuffer = await file.arrayBuffer();
            const loadingTask = window.pdfjsLib.getDocument({
                data: arrayBuffer,
                cMapUrl: 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/cmaps/',
                cMapPacked: true,
                standardFontDataUrl: 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/standard_fonts/',
            });
            watermarkPdfDoc = await loadingTask.promise;
            this.watermarkTotalPages = watermarkPdfDoc.numPages || 1;
            this.watermarkCurrentPage = 1;
            await this.renderWatermarkCurrentPage();
        } catch (e) {
            console.error('loadWatermarkPdfPreview error:', e);
            this.watermarkError = 'ไม่สามารถอ่านไฟล์ PDF ได้: ' + (e.message || '');
        } finally {
            this.isRenderingWatermarkPdf = false;
        }
    },

    async renderWatermarkCurrentPage() {
        if (!watermarkPdfDoc) return;
        const pageNum = this.watermarkCurrentPage;

        if (watermarkPageCache[pageNum]) {
            this.watermarkPdfPageUrl = watermarkPageCache[pageNum];
            this.isRenderingWatermarkPdf = false;
            return;
        }

        this.isRenderingWatermarkPdf = true;
        try {
            if (watermarkRenderTask) {
                try {
                    watermarkRenderTask.cancel();
                    await watermarkRenderTask.promise.catch(() => {});
                } catch (e) {}
                watermarkRenderTask = null;
            }

            if (this.watermarkCurrentPage !== pageNum) return;

            const page = await watermarkPdfDoc.getPage(pageNum);
            const baseViewport = page.getViewport({ scale: 1.0 });
            const maxDim = Math.max(baseViewport.width, baseViewport.height);
            const scale = Math.min(2.0, Math.max(0.6, 600 / maxDim));
            const viewport = page.getViewport({ scale });

            const offCanvas = document.createElement('canvas');
            offCanvas.width = Math.floor(viewport.width);
            offCanvas.height = Math.floor(viewport.height);

            const ctx = offCanvas.getContext('2d');
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, offCanvas.width, offCanvas.height);

            const task = page.render({
                canvasContext: ctx,
                viewport: viewport
            });
            watermarkRenderTask = task;
            await task.promise;
            if (watermarkRenderTask === task) {
                watermarkRenderTask = null;
            }

            const dataUrl = offCanvas.toDataURL('image/png');
            watermarkPageCache[pageNum] = dataUrl;

            if (this.watermarkCurrentPage === pageNum) {
                this.watermarkPdfPageUrl = dataUrl;
            }
        } catch (e) {
            if (e && (e.name === 'RenderingCancelledException' || e.message?.includes('cancelled'))) {
                return;
            }
            console.error('renderWatermarkCurrentPage error on page ' + pageNum, e);
        } finally {
            if (this.watermarkCurrentPage === pageNum) {
                this.isRenderingWatermarkPdf = false;
            }
        }
    },

    async prevWatermarkPage() {
        if (this.watermarkCurrentPage > 1 && !this.isRenderingWatermarkPdf) {
            this.watermarkCurrentPage--;
            await this.renderWatermarkCurrentPage();
        }
    },

    async nextWatermarkPage() {
        if (this.watermarkCurrentPage < this.watermarkTotalPages && !this.isRenderingWatermarkPdf) {
            this.watermarkCurrentPage++;
            await this.renderWatermarkCurrentPage();
        }
    },

    handleWatermarkImageSelect(event) {
        const file = event.target.files?.[0];
        if (!file) return;
        if (!file.type.startsWith('image/')) {
            this.error = 'กรุณาเลือกไฟล์รูปภาพที่ถูกต้อง (PNG, JPG, SVG, WEBP)';
            return;
        }
        this.watermarkImageFile = file;
        this.watermarkImageName = file.name;

        const reader = new FileReader();
        reader.onload = (e) => {
            this.watermarkImageDataUrl = e.target.result;
        };
        reader.readAsDataURL(file);
    },

    removeWatermarkImage() {
        this.watermarkImageFile = null;
        this.watermarkImageDataUrl = null;
        this.watermarkImageName = null;
    },

    setWatermarkPosition(pos) {
        this.watermarkPosition = pos;
    },

    setWatermarkRotation(deg) {
        this.watermarkRotation = deg;
    },

    get watermarkFlexClasses() {
        switch (this.watermarkPosition) {
            case 'top-left': return 'items-start justify-start p-4 sm:p-6';
            case 'top-center': return 'items-start justify-center p-4 sm:p-6';
            case 'top-right': return 'items-start justify-end p-4 sm:p-6';
            case 'center-left': return 'items-center justify-start p-4 sm:p-6';
            case 'center': return 'items-center justify-center p-4 sm:p-6';
            case 'center-right': return 'items-center justify-end p-4 sm:p-6';
            case 'bottom-left': return 'items-end justify-start p-4 sm:p-6';
            case 'bottom-center': return 'items-end justify-center p-4 sm:p-6';
            case 'bottom-right': return 'items-end justify-end p-4 sm:p-6';
            default: return 'items-center justify-center p-4 sm:p-6';
        }
    },

    // =====================================================
    // Unlock PDF Methods & Live Preview
    // =====================================================
    clearUnlockState() {
        this.unlockPassword = '';
        this.showUnlockPassword = false;
        this.isVerifyingUnlock = false;
        this.unlockVerified = false;
        this.unlockCheckMessage = '';
        this.unlockPreviewUrl = null;
        this.isPdfEncrypted = null;
    },

    async detectPdfEncryption() {
        if (!this.files.length || !this.files[0].file) {
            this.clearUnlockState();
            return;
        }
        const file = this.files[0].file;
        try {
            await this.ensurePdfJs();
            const arrayBuffer = await file.arrayBuffer();
            const loadingTask = window.pdfjsLib.getDocument({
                data: arrayBuffer.slice(0),
            });
            const doc = await loadingTask.promise;
            // If loaded without password, PDF is not encrypted!
            this.isPdfEncrypted = false;
            this.unlockVerified = true;
            this.unlockCheckMessage = 'ไฟล์นี้ไม่ได้ตั้งรหัสผ่านป้องกันไว้ สามารถเปิดใช้งานได้ทันที';
            try {
                const page = await doc.getPage(1);
                const vp = page.getViewport({ scale: 0.5 });
                const offCanvas = document.createElement('canvas');
                offCanvas.width = vp.width;
                offCanvas.height = vp.height;
                const ctx = offCanvas.getContext('2d');
                await page.render({ canvasContext: ctx, viewport: vp }).promise;
                this.unlockPreviewUrl = offCanvas.toDataURL('image/jpeg', 0.85);
            } catch (err) {}
        } catch (err) {
            if (err.name === 'PasswordException') {
                this.isPdfEncrypted = true;
                this.unlockVerified = false;
                this.unlockCheckMessage = '';
                this.unlockPreviewUrl = null;
            } else {
                console.warn('PDF detect encryption notice:', err);
            }
        }
    },

    async verifyUnlockPassword() {
        if (!this.files.length || !this.files[0].file || !this.unlockPassword) {
            return;
        }
        const file = this.files[0].file;
        this.isVerifyingUnlock = true;
        this.unlockCheckMessage = 'กำลังตรวจสอบรหัสผ่าน...';
        this.unlockVerified = false;

        try {
            await this.ensurePdfJs();
            const arrayBuffer = await file.arrayBuffer();
            const loadingTask = window.pdfjsLib.getDocument({
                data: arrayBuffer.slice(0),
                password: this.unlockPassword,
                cMapUrl: 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/cmaps/',
                cMapPacked: true,
            });
            const doc = await loadingTask.promise;
            this.unlockVerified = true;
            this.unlockCheckMessage = '✓ รหัสผ่านถูกต้อง! พร้อมปลดล็อกเอกสาร';

            // Render page 1 preview
            try {
                const page = await doc.getPage(1);
                const vp = page.getViewport({ scale: 0.5 });
                const offCanvas = document.createElement('canvas');
                offCanvas.width = vp.width;
                offCanvas.height = vp.height;
                const ctx = offCanvas.getContext('2d');
                await page.render({ canvasContext: ctx, viewport: vp }).promise;
                this.unlockPreviewUrl = offCanvas.toDataURL('image/jpeg', 0.85);
            } catch (err) {}
        } catch (err) {
            this.unlockVerified = false;
            this.unlockPreviewUrl = null;
            if (err.name === 'PasswordException') {
                this.unlockCheckMessage = 'รหัสผ่านไม่ถูกต้อง กรุณาตรวจสอบอีกครั้ง';
            } else {
                this.unlockCheckMessage = 'ไม่สามารถตรวจสอบรหัสผ่านได้: ' + (err.message || '');
            }
        } finally {
            this.isVerifyingUnlock = false;
        }
    },

    get canSubmitWatermark() {
        if (this.watermarkType === 'image') {
            return !!this.watermarkImageDataUrl;
        }
        return !!(this.watermarkText && this.watermarkText.trim().length > 0);
    },

    get canSubmitProtectPdf() {
        return this.protectPassword.length > 0 && this.protectPassword === this.protectPasswordConfirm;
    },

    get canSubmitUnlockPdf() {
        if (this.isPdfEncrypted === false) return true;
        return this.unlockPassword.trim().length > 0;
    },

    get canSubmitMergePdf() {
        return this.files.length >= 2;
    },

    // =====================================================
    // Merge PDF Methods & Reorder Helpers
    // =====================================================
    async loadMergeThumbnails() {
        if (this.tool !== 'merge-pdf' || !this.files.length) return;
        try {
            await this.ensurePdfJs();
            for (const f of this.files) {
                if (f.file && !this.mergeThumbnailsCache[f.id]) {
                    try {
                        const buf = await f.file.arrayBuffer();
                        const doc = await window.pdfjsLib.getDocument({ data: buf.slice(0) }).promise;
                        const page = await doc.getPage(1);
                        const vp = page.getViewport({ scale: 0.28 });
                        const offCanvas = document.createElement('canvas');
                        offCanvas.width = vp.width;
                        offCanvas.height = vp.height;
                        const ctx = offCanvas.getContext('2d');
                        await page.render({ canvasContext: ctx, viewport: vp }).promise;
                        this.mergeThumbnailsCache[f.id] = offCanvas.toDataURL('image/jpeg', 0.85);
                    } catch (err) {
                        console.warn('Merge thumb error for', f.name, err);
                    }
                }
            }
        } catch (e) {
            console.warn('loadMergeThumbnails error:', e);
        }
    },

    moveFileUp(index) {
        if (index > 0) {
            const item = this.files.splice(index, 1)[0];
            this.files.splice(index - 1, 0, item);
        }
    },

    moveFileDown(index) {
        if (index < this.files.length - 1) {
            const item = this.files.splice(index, 1)[0];
            this.files.splice(index + 1, 0, item);
        }
    },

    sortFilesByName() {
        this.files.sort((a, b) => (a.name || '').localeCompare(b.name || '', 'th', { numeric: true }));
    },

    reverseFilesOrder() {
        this.files.reverse();
    },

    onFileDragStart(e, index) {
        this.draggedFileIndex = index;
        e.dataTransfer.effectAllowed = 'move';
        try {
            e.dataTransfer.setData('text/plain', index);
        } catch (err) {}
    },

    onFileDragOver(e, index) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
    },

    onFileDrop(e, targetIndex) {
        e.preventDefault();
        if (this.draggedFileIndex === null || this.draggedFileIndex === undefined || this.draggedFileIndex === targetIndex) {
            return;
        }
        const item = this.files.splice(this.draggedFileIndex, 1)[0];
        this.files.splice(targetIndex, 0, item);
        this.draggedFileIndex = null;
    },

    // =====================================================
    // Compress PDF Methods & Preview
    // =====================================================
    clearCompressState() {
        this.compressThumb = null;
        this.compressTotalPages = null;
        this.compressIsLoadingPreview = false;
        this.compressQuality = 'ebook';
    },

    async loadCompressPdfPreview() {
        if (this.tool !== 'compress-pdf' || !this.files.length) return;
        const firstFile = this.files[0];
        if (!firstFile || !firstFile.file) return;
        this.compressIsLoadingPreview = true;
        try {
            await this.ensurePdfJs();
            const buf = await firstFile.file.arrayBuffer();
            const doc = await window.pdfjsLib.getDocument({ data: buf.slice(0) }).promise;
            this.compressTotalPages = doc.numPages;
            const page = await doc.getPage(1);
            const vp = page.getViewport({ scale: 0.35 });
            const offCanvas = document.createElement('canvas');
            offCanvas.width = vp.width;
            offCanvas.height = vp.height;
            const ctx = offCanvas.getContext('2d');
            await page.render({ canvasContext: ctx, viewport: vp }).promise;
            this.compressThumb = offCanvas.toDataURL('image/jpeg', 0.85);
        } catch (err) {
            console.warn('Compress PDF preview error:', err);
        } finally {
            this.compressIsLoadingPreview = false;
        }
    },

    get compressEstimatedRatio() {
        switch (this.compressQuality) {
            case 'screen': return 0.25; // ~75% reduction
            case 'ebook': return 0.45;  // ~55% reduction
            case 'printer': return 0.75; // ~25% reduction
            default: return 0.45;
        }
    },

    get compressEstimatedBytes() {
        const originalBytes = this.files[0]?.file?.size || (this.files[0]?.size || 0);
        return Math.round(originalBytes * this.compressEstimatedRatio);
    },

    get compressEstimatedSizeFormatted() {
        return this.formatSize(this.compressEstimatedBytes);
    },

    get compressSavedPercent() {
        return Math.round((1 - this.compressEstimatedRatio) * 100);
    },

    // =====================================================
    // Split PDF Methods & Visual Page Range Extractor
    // =====================================================
    clearSplitPagesState() {
        splitPdfDoc = null;
        splitThumbnailsCache = {};
        this.splitPagesList = [];
        this.selectedPagesToSplit = [];
        this.splitTotalPages = 0;
        this.splitManualInput = '';
        this.splitPagesError = null;
        this.isRenderingSplitPages = false;
        this.splitMode = 'range';
        this.splitMergeExtracted = true;
    },

    async loadSplitPagesPreview() {
        if (!this.files.length || !this.files[0].file) {
            this.clearSplitPagesState();
            return;
        }
        const file = this.files[0].file;
        if (file.type !== 'application/pdf' && !file.name.toLowerCase().endsWith('.pdf')) {
            return;
        }

        this.clearSplitPagesState();
        this.isRenderingSplitPages = true;

        try {
            await this.ensurePdfJs();
            const arrayBuffer = await file.arrayBuffer();
            const loadingTask = window.pdfjsLib.getDocument({
                data: arrayBuffer,
                cMapUrl: 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/cmaps/',
                cMapPacked: true,
                standardFontDataUrl: 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/standard_fonts/',
            });
            splitPdfDoc = await loadingTask.promise;
            this.splitTotalPages = splitPdfDoc.numPages || 1;

            // Pre-populate page placeholders for instant UI feedback
            this.splitPagesList = Array.from({ length: this.splitTotalPages }, (_, i) => ({
                pageNum: i + 1,
                dataUrl: null,
            }));

            // Pre-select page 1 by default
            this.selectedPagesToSplit = [1];
            this.splitManualInput = '1';

            // Render all thumbnails
            for (let p = 1; p <= this.splitTotalPages; p++) {
                if (!splitPdfDoc || this.files.length === 0) break;
                await this.renderSplitThumbnail(p);
            }
        } catch (e) {
            console.error('loadSplitPagesPreview error:', e);
            this.splitPagesError = 'ไม่สามารถอ่านไฟล์ PDF ได้: ' + (e.message || '');
        } finally {
            this.isRenderingSplitPages = false;
        }
    },

    async renderSplitThumbnail(pageNum) {
        if (!splitPdfDoc) return;
        try {
            if (splitThumbnailsCache[pageNum]) {
                const item = this.splitPagesList.find(x => x.pageNum === pageNum);
                if (item) {
                    item.dataUrl = splitThumbnailsCache[pageNum];
                }
                return;
            }

            const page = await splitPdfDoc.getPage(pageNum);
            const baseViewport = page.getViewport({ scale: 1.0 });
            const maxDim = Math.max(baseViewport.width, baseViewport.height);
            const targetDim = 220;
            const scale = Math.min(0.4, targetDim / maxDim);
            const viewport = page.getViewport({ scale });

            const canvas = document.createElement('canvas');
            canvas.width = viewport.width;
            canvas.height = viewport.height;
            const ctx = canvas.getContext('2d');

            await page.render({
                canvasContext: ctx,
                viewport: viewport,
            }).promise;

            const dataUrl = canvas.toDataURL('image/jpeg', 0.85);
            splitThumbnailsCache[pageNum] = dataUrl;

            const item = this.splitPagesList.find(x => x.pageNum === pageNum);
            if (item) {
                item.dataUrl = dataUrl;
            }
        } catch (err) {
            console.warn(`Error rendering split thumbnail page ${pageNum}:`, err);
        }
    },

    togglePageToSplit(pageNum) {
        const idx = this.selectedPagesToSplit.indexOf(pageNum);
        if (idx >= 0) {
            this.selectedPagesToSplit.splice(idx, 1);
        } else {
            this.selectedPagesToSplit.push(pageNum);
            this.selectedPagesToSplit.sort((a, b) => a - b);
        }
        this.splitManualInput = this.formatPagesRangeString(this.selectedPagesToSplit);
    },

    isPageSelectedToSplit(pageNum) {
        return this.selectedPagesToSplit.includes(pageNum);
    },

    onSplitManualInputChange() {
        if (!this.splitManualInput.trim()) {
            this.selectedPagesToSplit = [];
            return;
        }
        const parsed = this.parsePagesRangeString(this.splitManualInput, this.splitTotalPages);
        this.selectedPagesToSplit = parsed;
    },

    selectAllPagesToSplit() {
        this.selectedPagesToSplit = Array.from({ length: this.splitTotalPages }, (_, i) => i + 1);
        this.splitManualInput = this.formatPagesRangeString(this.selectedPagesToSplit);
    },

    deselectAllPagesToSplit() {
        this.selectedPagesToSplit = [];
        this.splitManualInput = '';
    },

    selectOddPagesToSplit() {
        this.selectedPagesToSplit = [];
        for (let i = 1; i <= this.splitTotalPages; i += 2) {
            this.selectedPagesToSplit.push(i);
        }
        this.splitManualInput = this.formatPagesRangeString(this.selectedPagesToSplit);
    },

    selectEvenPagesToSplit() {
        this.selectedPagesToSplit = [];
        for (let i = 2; i <= this.splitTotalPages; i += 2) {
            this.selectedPagesToSplit.push(i);
        }
        this.splitManualInput = this.formatPagesRangeString(this.selectedPagesToSplit);
    },

    formatPagesRangeString(pages) {
        if (!pages || !pages.length) return '';
        const sorted = [...pages].sort((a, b) => a - b);
        const ranges = [];
        let start = sorted[0];
        let end = start;

        for (let i = 1; i < sorted.length; i++) {
            if (sorted[i] === end + 1) {
                end = sorted[i];
            } else {
                ranges.push(start === end ? `${start}` : `${start}-${end}`);
                start = sorted[i];
                end = start;
            }
        }
        ranges.push(start === end ? `${start}` : `${start}-${end}`);
        return ranges.join(', ');
    },

    parsePagesRangeString(str, maxPages) {
        const parts = str.split(',').map(s => s.trim()).filter(Boolean);
        const set = new Set();

        for (const part of parts) {
            if (part.includes('-')) {
                const [startStr, endStr] = part.split('-').map(s => s.trim());
                const start = parseInt(startStr, 10);
                const end = parseInt(endStr, 10);
                if (!isNaN(start) && !isNaN(end)) {
                    const min = Math.max(1, Math.min(start, end));
                    const max = Math.min(maxPages || 9999, Math.max(start, end));
                    for (let p = min; p <= max; p++) {
                        set.add(p);
                    }
                }
            } else {
                const single = parseInt(part, 10);
                if (!isNaN(single) && single >= 1 && (!maxPages || single <= maxPages)) {
                    set.add(single);
                }
            }
        }
        return Array.from(set).sort((a, b) => a - b);
    },

    get canSubmitSplitPdf() {
        if (this.splitMode === 'all') return true;
        return this.selectedPagesToSplit.length > 0;
    },

    clearImageToPdfState() {
        this.imageThumbnailsCache = {};
        this.imageOrientation = 'auto';
        this.imagePageSize = 'fit';
        this.imageMargin = 'none';
    },

    loadImageThumbnails() {
        if (this.tool !== 'image-to-pdf') return;
        for (const f of this.files) {
            if (f.file && !this.imageThumbnailsCache[f.id]) {
                try {
                    this.imageThumbnailsCache[f.id] = URL.createObjectURL(f.file);
                } catch (e) {}
            }
        }
    },

    clearWordState() {
        this.wordMode = 'standard';
        this.wordPagesMode = 'all';
        this.wordCustomPages = '';
        this.wordDetectTables = true;
        this.wordKeepImages = true;
        this.wordDetectedType = 'checking';
        this.wordPreviewPageUrl = null;
        this.wordTotalPages = 0;
        this.isAnalyzingWordPdf = false;
    },

    async analyzePdfForWord() {
        if (this.tool !== 'pdf-to-word' || !this.files.length) return;
        const fileObj = this.files[0]?.file;
        if (!fileObj) return;

        this.isAnalyzingWordPdf = true;
        this.wordDetectedType = 'checking';

        try {
            await this.ensurePdfJs();
            const arrayBuffer = await fileObj.arrayBuffer();
            const pdf = await window.pdfjsLib.getDocument({ data: arrayBuffer.slice(0) }).promise;
            this.wordTotalPages = pdf.numPages;

            // Render Page 1 Preview
            const page = await pdf.getPage(1);
            const viewport = page.getViewport({ scale: 0.5 });
            const canvas = document.createElement('canvas');
            canvas.width = viewport.width;
            canvas.height = viewport.height;
            const ctx = canvas.getContext('2d');
            await page.render({ canvasContext: ctx, viewport: viewport }).promise;
            this.wordPreviewPageUrl = canvas.toDataURL('image/jpeg', 0.85);

            // Check if page 1 has selectable text
            const textContent = await page.getTextContent();
            let textLen = 0;
            if (textContent && textContent.items) {
                textLen = textContent.items.map(item => item.str || '').join('').trim().length;
            }

            if (textLen > 25) {
                this.wordDetectedType = 'digital';
                this.wordMode = 'standard';
            } else {
                this.wordDetectedType = 'scanned';
                this.wordMode = 'ocr';
            }
        } catch (e) {
            console.warn('analyzePdfForWord notice:', e);
            this.wordDetectedType = 'digital';
        } finally {
            this.isAnalyzingWordPdf = false;
        }
    },

    clearPdfToImagesState() {
        this.imgDpi = '150';
        this.imgPagesMode = 'all';
        this.imgSelectedPages = [];
        this.imgManualInput = '';
        this.imgPagesList = [];
        this.imgTotalPages = 0;
        this.isRenderingImgPages = false;
    },

    async loadPdfToImagesPreview() {
        if (!['pdf-to-jpg', 'pdf-to-png'].includes(this.tool) || !this.files.length) return;
        const fileObj = this.files[0]?.file;
        if (!fileObj) return;

        this.isRenderingImgPages = true;
        try {
            await this.ensurePdfJs();
            const arrayBuffer = await fileObj.arrayBuffer();
            const pdf = await window.pdfjsLib.getDocument({ data: arrayBuffer.slice(0) }).promise;
            this.imgTotalPages = pdf.numPages;

            // Default: all pages selected
            this.imgSelectedPages = Array.from({ length: this.imgTotalPages }, (_, i) => i + 1);
            this.syncImgManualInput();

            const pages = [];
            for (let i = 1; i <= this.imgTotalPages; i++) {
                pages.push({ pageNum: i, dataUrl: null });
            }
            this.imgPagesList = pages;

            for (let i = 1; i <= this.imgTotalPages; i++) {
                try {
                    const page = await pdf.getPage(i);
                    const viewport = page.getViewport({ scale: 0.28 });
                    const canvas = document.createElement('canvas');
                    canvas.width = viewport.width;
                    canvas.height = viewport.height;
                    const ctx = canvas.getContext('2d');
                    await page.render({ canvasContext: ctx, viewport: viewport }).promise;
                    this.imgPagesList[i - 1].dataUrl = canvas.toDataURL('image/jpeg', 0.8);
                } catch (e) {
                    console.warn('loadPdfToImagesPreview page render error', e);
                }
            }
        } catch (e) {
            console.error('loadPdfToImagesPreview error', e);
        } finally {
            this.isRenderingImgPages = false;
        }
    },

    toggleImgPage(pageNum) {
        if (this.imgPagesMode !== 'custom') {
            this.imgPagesMode = 'custom';
        }
        const idx = this.imgSelectedPages.indexOf(pageNum);
        if (idx > -1) {
            this.imgSelectedPages.splice(idx, 1);
        } else {
            this.imgSelectedPages.push(pageNum);
            this.imgSelectedPages.sort((a, b) => a - b);
        }
        this.syncImgManualInput();
    },

    isImgPageSelected(pageNum) {
        if (this.imgPagesMode === 'all') return true;
        return this.imgSelectedPages.includes(pageNum);
    },

    selectImgPages(type) {
        this.imgPagesMode = 'custom';
        if (type === 'all') {
            this.imgSelectedPages = Array.from({ length: this.imgTotalPages }, (_, i) => i + 1);
        } else if (type === 'none') {
            this.imgSelectedPages = [];
        } else if (type === 'odd') {
            this.imgSelectedPages = Array.from({ length: this.imgTotalPages }, (_, i) => i + 1).filter(n => n % 2 !== 0);
        } else if (type === 'even') {
            this.imgSelectedPages = Array.from({ length: this.imgTotalPages }, (_, i) => i + 1).filter(n => n % 2 === 0);
        }
        this.syncImgManualInput();
    },

    syncImgManualInput() {
        if (this.imgSelectedPages.length === 0) {
            this.imgManualInput = '';
            return;
        }
        const nums = [...this.imgSelectedPages].sort((a, b) => a - b);
        const ranges = [];
        let start = nums[0];
        let prev = nums[0];

        for (let i = 1; i < nums.length; i++) {
            if (nums[i] === prev + 1) {
                prev = nums[i];
            } else {
                ranges.push(start === prev ? `${start}` : `${start}-${prev}`);
                start = nums[i];
                prev = nums[i];
            }
        }
        ranges.push(start === prev ? `${start}` : `${start}-${prev}`);
        this.imgManualInput = ranges.join(', ');
    },

    onImgManualInputChange() {
        this.imgPagesMode = 'custom';
        const raw = this.imgManualInput;
        if (!raw.trim()) {
            this.imgSelectedPages = [];
            return;
        }

        const selected = new Set();
        const parts = raw.split(',');
        for (const p of parts) {
            const item = p.trim();
            if (item.includes('-')) {
                const [sStr, eStr] = item.split('-');
                const s = parseInt(sStr, 10);
                const e = parseInt(eStr, 10);
                if (!isNaN(s) && !isNaN(e)) {
                    const min = Math.max(1, Math.min(s, e));
                    const max = Math.min(this.imgTotalPages, Math.max(s, e));
                    for (let n = min; n <= max; n++) selected.add(n);
                }
            } else {
                const n = parseInt(item, 10);
                if (!isNaN(n) && n >= 1 && n <= this.imgTotalPages) {
                    selected.add(n);
                }
            }
        }
        this.imgSelectedPages = Array.from(selected).sort((a, b) => a - b);
    },

    get canSubmitPdfToImage() {
        if (!['pdf-to-jpg', 'pdf-to-png'].includes(this.tool)) return true;
        if (!this.hasFiles) return false;
        if (this.imgPagesMode === 'all') return true;
        return this.imgSelectedPages.length > 0;
    },

    // =====================================================
    // Page Numbers PDF Methods & Live Preview
    // =====================================================
    clearPageNumbersState() {
        this.pnPosition = 'bottom-center';
        this.pnFormat = 'n';
        this.pnStartNum = 1;
        this.pnSkipFirst = false;
        this.pnFontSize = 11;
        this.pnColor = '#333333';
        this.pnPreviewPageUrl = null;
        this.pnTotalPages = 0;
        this.isRenderingPnPages = false;
    },

    async loadPageNumbersPreview() {
        if (this.tool !== 'page-numbers' || !this.files.length) return;
        const targetFile = this.files[0]?.file;
        if (!targetFile) return;

        this.isRenderingPnPages = true;
        try {
            await this.ensurePdfJs();
            const arrayBuffer = await targetFile.arrayBuffer();
            const doc = await window.pdfjsLib.getDocument({ data: arrayBuffer.slice(0) }).promise;
            this.pnTotalPages = doc.numPages;

            const pageToRender = (this.pnSkipFirst && doc.numPages > 1) ? 2 : 1;
            const page = await doc.getPage(pageToRender);
            const baseViewport = page.getViewport({ scale: 1.0 });
            const targetDim = 520;
            const maxDim = Math.max(baseViewport.width, baseViewport.height);
            const scale = Math.min(1.0, Math.max(0.5, targetDim / maxDim));
            const viewport = page.getViewport({ scale });

            const canvas = document.createElement('canvas');
            canvas.width = viewport.width;
            canvas.height = viewport.height;
            const ctx = canvas.getContext('2d');

            await page.render({
                canvasContext: ctx,
                viewport: viewport
            }).promise;

            this.pnPreviewPageUrl = canvas.toDataURL('image/jpeg', 0.88);
        } catch (err) {
            console.warn('Page numbers preview error:', err);
        } finally {
            this.isRenderingPnPages = false;
        }
    },

    get pnFormattedPreviewText() {
        const start = parseInt(this.pnStartNum, 10) || 1;
        const total = this.pnTotalPages || 1;
        const current = (this.pnSkipFirst && total > 1) ? (start + 1) : start;

        switch (this.pnFormat) {
            case 'n-of-total':
                return `${current} / ${total}`;
            case 'page-n':
                return `หน้า ${current}`;
            case 'page-n-of-total':
                return `หน้า ${current} จาก ${total}`;
            case 'n':
            default:
                return `${current}`;
        }
    },

    get pnPositionClasses() {
        switch (this.pnPosition) {
            case 'top-left':
                return 'top-4 left-6 text-left';
            case 'top-center':
                return 'top-4 left-1/2 -translate-x-1/2 text-center';
            case 'top-right':
                return 'top-4 right-6 text-right';
            case 'bottom-left':
                return 'bottom-4 left-6 text-left';
            case 'bottom-right':
                return 'bottom-4 right-6 text-right';
            case 'bottom-center':
            default:
                return 'bottom-4 left-1/2 -translate-x-1/2 text-center';
        }
    },

    // =====================================================
    // Crop PDF Methods & Live Preview
    // =====================================================
    clearCropState() {
        this.cropMode = 'custom';
        this.cropTop = 8;
        this.cropBottom = 8;
        this.cropLeft = 8;
        this.cropRight = 8;
        this.cropPages = 'all';
        this.cropCustomPages = '';
        this.cropPreviewUrl = null;
        this.cropTotalPages = 0;
        this.cropCurrentPage = 1;
        this.isRenderingCropPreview = false;
    },

    setCropPreset(preset) {
        this.cropMode = preset;
        if (preset === 'trim-scanner') {
            this.cropTop = 4;
            this.cropBottom = 4;
            this.cropLeft = 4;
            this.cropRight = 4;
        } else if (preset === 'auto-margins') {
            this.cropTop = 0;
            this.cropBottom = 0;
            this.cropLeft = 0;
            this.cropRight = 0;
        } else if (preset === 'custom') {
            if (this.cropTop === 0 && this.cropBottom === 0 && this.cropLeft === 0 && this.cropRight === 0) {
                this.cropTop = 8;
                this.cropBottom = 8;
                this.cropLeft = 8;
                this.cropRight = 8;
            }
        }
    },

    resetCropMargins() {
        this.cropTop = 0;
        this.cropBottom = 0;
        this.cropLeft = 0;
        this.cropRight = 0;
        this.cropMode = 'custom';
    },

    async loadCropPreview() {
        if (this.tool !== 'crop-pdf' || !this.files.length) return;
        const targetFile = this.files[0]?.file;
        if (!targetFile) return;

        this.isRenderingCropPreview = true;
        try {
            await this.ensurePdfJs();
            const arrayBuffer = await targetFile.arrayBuffer();
            const doc = await window.pdfjsLib.getDocument({ data: arrayBuffer.slice(0) }).promise;
            this.cropTotalPages = doc.numPages;

            const pNum = Math.max(1, Math.min(this.cropTotalPages, this.cropCurrentPage || 1));
            const page = await doc.getPage(pNum);
            const baseViewport = page.getViewport({ scale: 1.0 });
            const targetDim = 540;
            const maxDim = Math.max(baseViewport.width, baseViewport.height);
            const scale = Math.min(1.0, Math.max(0.45, targetDim / maxDim));
            const viewport = page.getViewport({ scale });

            const canvas = document.createElement('canvas');
            canvas.width = viewport.width;
            canvas.height = viewport.height;
            const ctx = canvas.getContext('2d');

            await page.render({
                canvasContext: ctx,
                viewport: viewport
            }).promise;

            this.cropPreviewUrl = canvas.toDataURL('image/jpeg', 0.88);
        } catch (err) {
            console.warn('Crop PDF preview error:', err);
        } finally {
            this.isRenderingCropPreview = false;
        }
    },

    get cropRemainingWidthPercent() {
        return Math.max(10, 100 - (this.cropLeft + this.cropRight));
    },

    get cropRemainingHeightPercent() {
        return Math.max(10, 100 - (this.cropTop + this.cropBottom));
    },

    // =====================================================
    // Organize & Reorder PDF Methods & Preview
    // =====================================================
    clearOrganizeState() {
        this.orgPagesList = [];
        this.orgTotalPages = 0;
        this.isRenderingOrgPages = false;
        this.draggedOrgPageIndex = null;
    },

    async loadOrganizePreview() {
        if (this.tool !== 'organize-pdf' || !this.files.length) return;
        const targetFile = this.files[0]?.file;
        if (!targetFile) return;

        this.isRenderingOrgPages = true;
        try {
            await this.ensurePdfJs();
            const arrayBuffer = await targetFile.arrayBuffer();
            const doc = await window.pdfjsLib.getDocument({ data: arrayBuffer.slice(0) }).promise;
            this.orgTotalPages = doc.numPages;

            const list = [];
            for (let i = 1; i <= this.orgTotalPages; i++) {
                list.push({
                    id: 'page_' + i + '_' + Date.now() + '_' + Math.random().toString(36).substr(2, 4),
                    origPageNum: i,
                    rotation: 0,
                    dataUrl: null,
                });
            }
            this.orgPagesList = list;

            for (let i = 1; i <= this.orgTotalPages; i++) {
                try {
                    const page = await doc.getPage(i);
                    const vp = page.getViewport({ scale: 0.32 });
                    const canvas = document.createElement('canvas');
                    canvas.width = vp.width;
                    canvas.height = vp.height;
                    const ctx = canvas.getContext('2d');
                    await page.render({ canvasContext: ctx, viewport: vp }).promise;
                    const item = this.orgPagesList.find(x => x.origPageNum === i);
                    if (item) {
                        item.dataUrl = canvas.toDataURL('image/jpeg', 0.85);
                    }
                } catch (pe) {
                    console.warn('Organize page render error:', pe);
                }
            }
        } catch (err) {
            console.warn('Organize PDF preview error:', err);
        } finally {
            this.isRenderingOrgPages = false;
        }
    },

    onOrgDragStart(e, index) {
        this.draggedOrgPageIndex = index;
        e.dataTransfer.effectAllowed = 'move';
        try {
            e.dataTransfer.setData('text/plain', index);
        } catch (err) {}
    },

    onOrgDragOver(e, index) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
    },

    onOrgDrop(e, targetIndex) {
        e.preventDefault();
        if (this.draggedOrgPageIndex === null || this.draggedOrgPageIndex === undefined || this.draggedOrgPageIndex === targetIndex) {
            return;
        }
        const item = this.orgPagesList.splice(this.draggedOrgPageIndex, 1)[0];
        this.orgPagesList.splice(targetIndex, 0, item);
        this.draggedOrgPageIndex = null;
    },

    moveOrgPage(index, dir) {
        const target = index + dir;
        if (target < 0 || target >= this.orgPagesList.length) return;
        const item = this.orgPagesList.splice(index, 1)[0];
        this.orgPagesList.splice(target, 0, item);
    },

    rotateOrgPage(index) {
        if (!this.orgPagesList[index]) return;
        this.orgPagesList[index].rotation = (this.orgPagesList[index].rotation + 90) % 360;
    },

    duplicateOrgPage(index) {
        if (!this.orgPagesList[index]) return;
        const src = this.orgPagesList[index];
        const clone = {
            id: 'page_dup_' + Date.now() + '_' + Math.random().toString(36).substr(2, 4),
            origPageNum: src.origPageNum,
            rotation: src.rotation,
            dataUrl: src.dataUrl,
        };
        this.orgPagesList.splice(index + 1, 0, clone);
    },

    deleteOrgPage(index) {
        if (this.orgPagesList.length <= 1) {
            alert('ต้องเหลือเอกสารอย่างน้อย 1 หน้า');
            return;
        }
        this.orgPagesList.splice(index, 1);
    },

    reverseOrgPages() {
        this.orgPagesList = [...this.orgPagesList].reverse();
    },

    sortOrgOddEven() {
        const odds = this.orgPagesList.filter((_, idx) => idx % 2 === 0);
        const evens = this.orgPagesList.filter((_, idx) => idx % 2 === 1);
        this.orgPagesList = [...odds, ...evens];
    },

    resetOrgPages() {
        this.orgPagesList = [...this.orgPagesList].sort((a, b) => a.origPageNum - b.origPageNum);
        this.orgPagesList.forEach(p => p.rotation = 0);
    },

    // =====================================================
    // PDF to Excel Methods & Preview
    // =====================================================
    clearExcelState() {
        this.excelPreviewUrl = null;
        this.excelTotalPages = 0;
        this.excelCurrentPage = 1;
        this.isAnalyzingExcelPdf = false;
        this.excelDetectedTableType = 'checking';
    },

    async loadExcelPdfPreview() {
        if (this.tool !== 'pdf-to-excel' || !this.files.length) return;
        const targetFile = this.files[0]?.file;
        if (!targetFile) return;

        this.isAnalyzingExcelPdf = true;
        this.excelDetectedTableType = 'checking';

        try {
            await this.ensurePdfJs();
            const arrayBuffer = await targetFile.arrayBuffer();
            const doc = await window.pdfjsLib.getDocument({ data: arrayBuffer.slice(0) }).promise;
            this.excelTotalPages = doc.numPages;

            const pNum = Math.max(1, Math.min(this.excelTotalPages, this.excelCurrentPage || 1));
            const page = await doc.getPage(pNum);

            // Quick table structure inspection
            try {
                const opList = await page.getOperatorList();
                const drawOps = [window.pdfjsLib.OPS.stroke, window.pdfjsLib.OPS.fill, window.pdfjsLib.OPS.constructPath];
                let vectorLinesCount = 0;
                if (opList && opList.fnArray) {
                    for (let i = 0; i < opList.fnArray.length; i++) {
                        if (drawOps.includes(opList.fnArray[i])) {
                            vectorLinesCount++;
                        }
                    }
                }
                if (vectorLinesCount > 25) {
                    this.excelDetectedTableType = 'lattice';
                } else {
                    this.excelDetectedTableType = 'stream';
                }
            } catch (inspectErr) {
                this.excelDetectedTableType = 'lattice';
            }

            // Quick check for CID / corrupted font encoding in text content
            try {
                const textContent = await page.getTextContent();
                let hasCidOrCorrupt = false;
                if (textContent && textContent.items) {
                    for (const item of textContent.items) {
                        const str = item.str || '';
                        if (str.includes('(cid:') || str.includes('\ufffd') || /\b(6202|5202|4202|7652|6652|5652|4652)\/\d+/.test(str)) {
                            hasCidOrCorrupt = true;
                            break;
                        }
                    }
                }
                this.excelDetectedCorruptedThai = hasCidOrCorrupt;
                if (this.excelDetectedCorruptedThai) {
                    this.excelMode = 'ocr';
                }
            } catch (textErr) {
                // ignore
            }

            const baseViewport = page.getViewport({ scale: 1.0 });
            const targetDim = 520;
            const maxDim = Math.max(baseViewport.width, baseViewport.height);
            const scale = Math.min(1.2, Math.max(0.45, targetDim / maxDim));
            const viewport = page.getViewport({ scale });

            const canvas = document.createElement('canvas');
            canvas.width = viewport.width;
            canvas.height = viewport.height;
            const ctx = canvas.getContext('2d');

            await page.render({
                canvasContext: ctx,
                viewport: viewport
            }).promise;

            this.excelPreviewUrl = canvas.toDataURL('image/jpeg', 0.88);
        } catch (err) {
            console.warn('PDF to Excel preview error:', err);
        } finally {
            this.isAnalyzingExcelPdf = false;
        }
    },

    clearPptxState() {
        this.pptxPreviewUrl = null;
        this.pptxTotalPages = 0;
        this.pptxCurrentPage = 1;
        this.isAnalyzingPptxPdf = false;
        this.pptxDetectedOrientation = 'landscape';
    },

    async loadPptxPdfPreview() {
        if (this.tool !== 'pdf-to-pptx' || !this.files.length) return;
        const targetFile = this.files[0]?.file;
        if (!targetFile) return;

        this.isAnalyzingPptxPdf = true;

        try {
            await this.ensurePdfJs();
            const arrayBuffer = await targetFile.arrayBuffer();
            const doc = await window.pdfjsLib.getDocument({ data: arrayBuffer.slice(0) }).promise;
            this.pptxTotalPages = doc.numPages;

            const pNum = Math.max(1, Math.min(this.pptxTotalPages, this.pptxCurrentPage || 1));
            const page = await doc.getPage(pNum);

            const baseViewport = page.getViewport({ scale: 1.0 });
            if (baseViewport.width >= baseViewport.height) {
                this.pptxDetectedOrientation = 'landscape';
            } else {
                this.pptxDetectedOrientation = 'portrait';
            }

            const targetDim = 600;
            const maxDim = Math.max(baseViewport.width, baseViewport.height);
            const scale = Math.min(1.6, Math.max(0.45, targetDim / maxDim));
            const viewport = page.getViewport({ scale });

            const canvas = document.createElement('canvas');
            canvas.width = viewport.width;
            canvas.height = viewport.height;
            const ctx = canvas.getContext('2d');

            await page.render({
                canvasContext: ctx,
                viewport: viewport
            }).promise;

            this.pptxPreviewUrl = canvas.toDataURL('image/jpeg', 0.90);
        } catch (err) {
            console.warn('PDF to PowerPoint preview error:', err);
        } finally {
            this.isAnalyzingPptxPdf = false;
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
// Alpine Component: signPdf (Interactive PDF e-Signer)
// =====================================================
Alpine.data('signPdf', () => ({
    // PDF Document state
    pdfLoaded: false,
    pdfFile: null,
    pdfFileName: '',
    pdfBytes: null,
    totalPages: 0,
    currentPage: 1,
    zoomLevel: 1.0,
    isRenderingPage: false,
    pdfRenderError: null,
    isDraggingFile: false,

    // Dimensions of the rendered page for coordinate mapping
    canvasDisplayWidth: 600,
    canvasDisplayHeight: 850,

    // Signature Studio tabs: 'draw', 'type', 'upload', 'date'
    sigTab: 'draw',

    // 1. Draw Tab state
    drawColor: '#003399',
    penWidth: 3,
    isDrawing: false,
    hasDrawn: false,
    drawStrokes: [],
    currentStroke: [],

    // 2. Type Tab state
    typedName: '',
    typeFont: 'Dancing Script',
    typeColor: '#003399',
    typeFonts: [
        { id: 'Dancing Script', name: 'Dancing Script (ลายมือสคริปต์)', family: "'Dancing Script', cursive" },
        { id: 'Pacifico', name: 'Pacifico (ลายตวัดหนานุ่ม)', family: "'Pacifico', cursive" },
        { id: 'Great Vibes', name: 'Great Vibes (คลาสสิกหรูหรา)', family: "'Great Vibes', cursive" },
        { id: 'Alex Brush', name: 'Alex Brush (ลายเส้นบางพริ้ว)', family: "'Alex Brush', cursive" },
        { id: 'Sarabun', name: 'Sarabun (ทางการราชการไทย)', family: "'Sarabun', sans-serif" },
    ],

    // 3. Upload Tab state
    uploadedImageDataUrl: null,
    autoRemoveWhite: true,
    whiteThreshold: 225,

    // 4. Date Stamp state
    dateType: 'th_buddhist',
    dateColor: '#003399',

    // Active signature ready for placement
    activeSigDataUrl: null,
    sigScale: 1.0,

    // Placed Signatures on PDF
    placedSignatures: [],
    selectedSigId: null,

    // Dragging & Resizing state on document
    isDraggingSig: false,
    isResizingSig: false,
    dragPointer: {
        startX: 0,
        startY: 0,
        initX: 0,
        initY: 0,
        initW: 0,
        initH: 0,
        aspect: 1,
    },
    justInteractedSig: false,

    // Export state
    isExporting: false,
    exportError: null,
    exportSuccess: false,

    async init() {
        window.addEventListener('mousemove', (e) => this.onPointerMove(e));
        window.addEventListener('mouseup', () => this.onPointerUp());
        window.addEventListener('touchmove', (e) => this.onPointerMove(e), { passive: false });
        window.addEventListener('touchend', () => this.onPointerUp());

        await this.loadStagedPdf();
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

    async ensurePdfLib() {
        if (window.PDFLib) return window.PDFLib;
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = '/vendor/pdf-lib.min.js';
            script.onload = () => {
                if (window.PDFLib) resolve(window.PDFLib);
                else reject(new Error('PDFLib ไม่พร้อมทำงาน'));
            };
            script.onerror = () => {
                const cdn = document.createElement('script');
                cdn.src = 'https://cdn.jsdelivr.net/npm/pdf-lib@1.17.1/dist/pdf-lib.min.js';
                cdn.onload = () => resolve(window.PDFLib);
                cdn.onerror = () => reject(new Error('ไม่สามารถโหลดระบบสร้าง PDF ได้'));
                document.head.appendChild(cdn);
            };
            document.head.appendChild(script);
        });
    },

    async loadStagedPdf() {
        try {
            if (typeof getStagedFiles === 'function') {
                const staged = await getStagedFiles();
                if (staged && staged.length > 0) {
                    const firstPdf = staged.find(s => s.file && s.file.name && s.file.name.toLowerCase().endsWith('.pdf'));
                    if (firstPdf) {
                        await this.loadPdfFile(firstPdf.file);
                        await clearStagedFiles();
                    }
                }
            }
        } catch (e) {
            console.warn('loadStagedPdf error:', e);
        }
    },

    handlePdfDrop(event) {
        this.isDraggingFile = false;
        const file = event.dataTransfer?.files?.[0];
        if (file && file.name.toLowerCase().endsWith('.pdf')) {
            this.loadPdfFile(file);
        }
    },

    handlePdfInput(event) {
        const file = event.target?.files?.[0];
        if (file) {
            this.loadPdfFile(file);
        }
    },

    async loadPdfFile(file) {
        this.pdfFile = file;
        this.pdfFileName = file.name;
        this.isRenderingPage = true;
        this.pdfRenderError = null;
        this.placedSignatures = [];
        this.selectedSigId = null;

        try {
            await this.ensurePdfJs();
            const buffer = await file.arrayBuffer();
            this.pdfBytes = buffer;

            // Clone buffer for PDF.js to avoid worker detaching this.pdfBytes
            const loadingTask = window.pdfjsLib.getDocument({
                data: buffer.slice(0),
                cMapUrl: 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/cmaps/',
                cMapPacked: true,
                standardFontDataUrl: 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/standard_fonts/',
            });

            signPdfDoc = await loadingTask.promise;
            this.totalPages = signPdfDoc.numPages || 1;
            this.currentPage = 1;
            this.pdfLoaded = true;

            await this.renderCurrentPdfPage();
        } catch (e) {
            console.error('loadPdfFile error:', e);
            this.pdfRenderError = 'ไม่สามารถเปิดไฟล์ PDF ได้: ' + (e.message || '');
        } finally {
            this.isRenderingPage = false;
        }
    },

    async renderCurrentPdfPage() {
        if (!signPdfDoc) return;
        this.isRenderingPage = true;
        this.pdfRenderError = null;

        try {
            if (signRenderTask) {
                try {
                    signRenderTask.cancel();
                    await signRenderTask.promise.catch(() => {});
                } catch (e) {}
                signRenderTask = null;
            }

            const page = await signPdfDoc.getPage(this.currentPage);
            const baseViewport = page.getViewport({ scale: 1.0 });

            const targetWidth = Math.min(760, Math.max(340, window.innerWidth > 1024 ? 620 : window.innerWidth - 64));
            const displayScale = (targetWidth / baseViewport.width) * this.zoomLevel;
            const viewport = page.getViewport({ scale: displayScale });

            this.canvasDisplayWidth = Math.round(viewport.width);
            this.canvasDisplayHeight = Math.round(viewport.height);

            const canvas = this.$refs.pdfCanvas;
            if (!canvas) return;

            const pixelRatio = Math.min(2.0, window.devicePixelRatio || 1);
            canvas.width = Math.round(viewport.width * pixelRatio);
            canvas.height = Math.round(viewport.height * pixelRatio);

            const ctx = canvas.getContext('2d');
            ctx.setTransform(pixelRatio, 0, 0, pixelRatio, 0, 0);

            const renderContext = {
                canvasContext: ctx,
                viewport: viewport,
            };

            const task = page.render(renderContext);
            signRenderTask = task;
            await task.promise;
            if (signRenderTask === task) {
                signRenderTask = null;
            }

            for (const sig of this.placedSignatures) {
                if (sig.page === this.currentPage) {
                    sig.x = Math.round(sig.relX * this.canvasDisplayWidth);
                    sig.y = Math.round(sig.relY * this.canvasDisplayHeight);
                    sig.w = Math.round(sig.relW * this.canvasDisplayWidth);
                    sig.h = Math.round(sig.relH * this.canvasDisplayHeight);
                }
            }
        } catch (e) {
            if (e && (e.name === 'RenderingCancelledException' || e.message?.includes('cancelled'))) {
                return;
            }
            console.error('renderCurrentPdfPage error:', e);
            this.pdfRenderError = 'เกิดข้อผิดพลาดในการแสดงผลหน้าเอกสาร';
        } finally {
            this.isRenderingPage = false;
        }
    },

    async prevPage() {
        if (this.currentPage > 1 && !this.isRenderingPage) {
            this.currentPage--;
            await this.renderCurrentPdfPage();
        }
    },

    async nextPage() {
        if (this.currentPage < this.totalPages && !this.isRenderingPage) {
            this.currentPage++;
            await this.renderCurrentPdfPage();
        }
    },

    async goToPage(p) {
        const pageNum = parseInt(p, 10);
        if (pageNum >= 1 && pageNum <= this.totalPages && pageNum !== this.currentPage && !this.isRenderingPage) {
            this.currentPage = pageNum;
            await this.renderCurrentPdfPage();
        }
    },

    zoomIn() {
        if (this.zoomLevel < 2.0) {
            this.zoomLevel = Math.min(2.0, Math.round((this.zoomLevel + 0.15) * 100) / 100);
            this.renderCurrentPdfPage();
        }
    },

    zoomOut() {
        if (this.zoomLevel > 0.6) {
            this.zoomLevel = Math.max(0.6, Math.round((this.zoomLevel - 0.15) * 100) / 100);
            this.renderCurrentPdfPage();
        }
    },

    resetZoom() {
        this.zoomLevel = 1.0;
        this.renderCurrentPdfPage();
    },

    // ─── Drawing Studio ─────────────────────────────────
    startDraw(e) {
        const canvas = this.$refs.sigDrawCanvas;
        if (!canvas) return;
        this.isDrawing = true;

        const rect = canvas.getBoundingClientRect();
        const clientX = e.touches ? e.touches[0].clientX : e.clientX;
        const clientY = e.touches ? e.touches[0].clientY : e.clientY;

        const scaleX = canvas.width / rect.width;
        const scaleY = canvas.height / rect.height;

        const x = (clientX - rect.left) * scaleX;
        const y = (clientY - rect.top) * scaleY;

        this.currentStroke = [{ x, y }];
    },

    draw(e) {
        if (!this.isDrawing) return;
        const canvas = this.$refs.sigDrawCanvas;
        if (!canvas) return;

        const rect = canvas.getBoundingClientRect();
        const clientX = e.touches ? e.touches[0].clientX : e.clientX;
        const clientY = e.touches ? e.touches[0].clientY : e.clientY;

        const scaleX = canvas.width / rect.width;
        const scaleY = canvas.height / rect.height;

        const x = (clientX - rect.left) * scaleX;
        const y = (clientY - rect.top) * scaleY;

        this.currentStroke.push({ x, y });

        const ctx = canvas.getContext('2d');
        ctx.strokeStyle = this.drawColor;
        ctx.lineWidth = this.penWidth * 1.5;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';

        if (this.currentStroke.length > 1) {
            const prev = this.currentStroke[this.currentStroke.length - 2];
            ctx.beginPath();
            ctx.moveTo(prev.x, prev.y);
            ctx.lineTo(x, y);
            ctx.stroke();
        }
        this.hasDrawn = true;
    },

    stopDraw() {
        if (!this.isDrawing) return;
        this.isDrawing = false;
        if (this.currentStroke.length > 0) {
            this.drawStrokes.push({
                color: this.drawColor,
                width: this.penWidth * 1.5,
                points: this.currentStroke,
            });
            this.currentStroke = [];
            this.updateDrawPreview();
        }
    },

    redrawStrokes() {
        const canvas = this.$refs.sigDrawCanvas;
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        for (const stroke of this.drawStrokes) {
            if (!stroke.points || stroke.points.length === 0) continue;
            ctx.strokeStyle = stroke.color;
            ctx.lineWidth = stroke.width;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';

            ctx.beginPath();
            ctx.moveTo(stroke.points[0].x, stroke.points[0].y);
            for (let i = 1; i < stroke.points.length; i++) {
                ctx.lineTo(stroke.points[i].x, stroke.points[i].y);
            }
            ctx.stroke();
        }
    },

    undoDraw() {
        if (this.drawStrokes.length > 0) {
            this.drawStrokes.pop();
            this.redrawStrokes();
            this.hasDrawn = this.drawStrokes.length > 0;
            this.updateDrawPreview();
        }
    },

    clearDraw() {
        const canvas = this.$refs.sigDrawCanvas;
        if (canvas) {
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);
        }
        this.drawStrokes = [];
        this.currentStroke = [];
        this.hasDrawn = false;
        if (this.sigTab === 'draw') {
            this.activeSigDataUrl = null;
        }
    },

    updateDrawPreview() {
        const canvas = this.$refs.sigDrawCanvas;
        if (!canvas || !this.hasDrawn) {
            this.activeSigDataUrl = null;
            return;
        }
        const trimmed = this.trimCanvas(canvas);
        this.activeSigDataUrl = trimmed ? trimmed.toDataURL('image/png') : canvas.toDataURL('image/png');
    },

    // ─── Type Studio ────────────────────────────────────
    renderTypedSignature() {
        if (!this.typedName || !this.typedName.trim()) {
            if (this.sigTab === 'type') this.activeSigDataUrl = null;
            return;
        }
        const canvas = document.createElement('canvas');
        canvas.width = 800;
        canvas.height = 240;
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        const chosenFont = this.typeFonts.find(f => f.id === this.typeFont);
        const fontFamily = chosenFont ? chosenFont.family : "'Dancing Script', cursive";

        ctx.font = `64px ${fontFamily}`;
        ctx.fillStyle = this.typeColor;
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(this.typedName.trim(), canvas.width / 2, canvas.height / 2);

        const trimmed = this.trimCanvas(canvas);
        this.activeSigDataUrl = trimmed ? trimmed.toDataURL('image/png') : canvas.toDataURL('image/png');
    },

    // ─── Upload Studio ──────────────────────────────────
    handleSignatureImageUpload(event) {
        const file = event.target?.files?.[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = (e) => {
            this.uploadedImageDataUrl = e.target.result;
            this.processUploadedSignature();
        };
        reader.readAsDataURL(file);
    },

    processUploadedSignature() {
        if (!this.uploadedImageDataUrl) return;
        const img = new Image();
        img.crossOrigin = 'anonymous';
        img.onload = () => {
            const canvas = document.createElement('canvas');
            canvas.width = img.width;
            canvas.height = img.height;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0);

            if (this.autoRemoveWhite) {
                const imgData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                const d = imgData.data;
                const threshold = parseInt(this.whiteThreshold, 10) || 225;

                for (let i = 0; i < d.length; i += 4) {
                    const r = d[i];
                    const g = d[i + 1];
                    const b = d[i + 2];
                    if (r >= threshold && g >= threshold && b >= threshold) {
                        d[i + 3] = 0;
                    }
                }
                ctx.putImageData(imgData, 0, 0);
            }

            const trimmed = this.trimCanvas(canvas);
            this.activeSigDataUrl = trimmed ? trimmed.toDataURL('image/png') : canvas.toDataURL('image/png');
        };
        img.src = this.uploadedImageDataUrl;
    },

    // ─── Date Stamp Studio ──────────────────────────────
    generateDateStamp() {
        const now = new Date();
        const thaiMonths = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
        const thaiFullMonths = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
        const day = now.getDate();
        const month = now.getMonth();
        const yearCE = now.getFullYear();
        const yearBE = yearCE + 543;

        let dateStr = '';
        if (this.dateType === 'th_buddhist') {
            dateStr = `วันที่ ${day} ${thaiMonths[month]} ${yearBE}`;
        } else if (this.dateType === 'th_full') {
            dateStr = `วันที่ ${day} ${thaiFullMonths[month]} พ.ศ. ${yearBE}`;
        } else if (this.dateType === 'th_short') {
            dateStr = `${String(day).padStart(2, '0')}/${String(month + 1).padStart(2, '0')}/${yearBE}`;
        } else {
            dateStr = `${String(day).padStart(2, '0')}/${String(month + 1).padStart(2, '0')}/${yearCE}`;
        }

        const canvas = document.createElement('canvas');
        canvas.width = 500;
        canvas.height = 120;
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        ctx.font = "bold 32px 'Sarabun', sans-serif";
        ctx.fillStyle = this.dateColor;
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(dateStr, canvas.width / 2, canvas.height / 2);

        const trimmed = this.trimCanvas(canvas);
        this.activeSigDataUrl = trimmed ? trimmed.toDataURL('image/png') : canvas.toDataURL('image/png');
    },

    switchTab(tab) {
        this.sigTab = tab;
        if (tab === 'draw') {
            this.updateDrawPreview();
        } else if (tab === 'type') {
            this.renderTypedSignature();
        } else if (tab === 'upload') {
            this.processUploadedSignature();
        } else if (tab === 'date') {
            this.generateDateStamp();
        }
    },

    trimCanvas(sourceCanvas) {
        try {
            const ctx = sourceCanvas.getContext('2d');
            const w = sourceCanvas.width;
            const h = sourceCanvas.height;
            const imgData = ctx.getImageData(0, 0, w, h);
            const d = imgData.data;

            let minX = w, minY = h, maxX = 0, maxY = 0;
            let found = false;

            for (let y = 0; y < h; y++) {
                for (let x = 0; x < w; x++) {
                    const idx = (y * w + x) * 4;
                    const alpha = d[idx + 3];
                    if (alpha > 10) {
                        found = true;
                        if (x < minX) minX = x;
                        if (x > maxX) maxX = x;
                        if (y < minY) minY = y;
                        if (y > maxY) maxY = y;
                    }
                }
            }
            if (!found) return null;

            const pad = 12;
            minX = Math.max(0, minX - pad);
            minY = Math.max(0, minY - pad);
            maxX = Math.min(w, maxX + pad);
            maxY = Math.min(h, maxY + pad);

            const cropW = maxX - minX;
            const cropH = maxY - minY;

            const trimmedCanvas = document.createElement('canvas');
            trimmedCanvas.width = cropW;
            trimmedCanvas.height = cropH;
            const trimmedCtx = trimmedCanvas.getContext('2d');
            trimmedCtx.drawImage(sourceCanvas, minX, minY, cropW, cropH, 0, 0, cropW, cropH);
            return trimmedCanvas;
        } catch (e) {
            return sourceCanvas;
        }
    },

    // ─── Placing Signatures on Document ─────────────────
    placeSignature(clickedX = null, clickedY = null) {
        if (!this.pdfLoaded) {
            alert('กรุณาอัปโหลดเอกสาร PDF ก่อน');
            return;
        }
        if (!this.activeSigDataUrl) {
            alert('กรุณาสร้าง วาด หรือเลือกลายเซ็นก่อนนำไปวางบนเอกสาร');
            return;
        }

        const id = 'sig_' + Date.now() + '_' + Math.random().toString(36).substring(2, 6);
        const baseWidth = Math.round(Math.min(180, this.canvasDisplayWidth * 0.35) * this.sigScale);
        const baseHeight = Math.round(baseWidth * 0.45);

        let posX = (this.canvasDisplayWidth - baseWidth) / 2;
        let posY = (this.canvasDisplayHeight - baseHeight) / 2;

        if (clickedX !== null && clickedY !== null) {
            posX = Math.max(5, Math.min(this.canvasDisplayWidth - baseWidth - 5, clickedX - baseWidth / 2));
            posY = Math.max(5, Math.min(this.canvasDisplayHeight - baseHeight - 5, clickedY - baseHeight / 2));
        }

        posX = Math.round(posX);
        posY = Math.round(posY);

        const newSig = {
            id,
            page: this.currentPage,
            x: posX,
            y: posY,
            w: baseWidth,
            h: baseHeight,
            relX: posX / this.canvasDisplayWidth,
            relY: posY / this.canvasDisplayHeight,
            relW: baseWidth / this.canvasDisplayWidth,
            relH: baseHeight / this.canvasDisplayHeight,
            dataUrl: this.activeSigDataUrl,
            type: this.sigTab,
        };

        this.placedSignatures.push(newSig);
        this.selectedSigId = id;
    },

    handleCanvasClick(event) {
        if (this.justInteractedSig) {
            this.justInteractedSig = false;
            return;
        }
        if (this.selectedSigId) {
            this.selectedSigId = null;
            return;
        }
        if (this.activeSigDataUrl) {
            const canvas = this.$refs.pdfCanvas;
            if (!canvas) return;
            const rect = canvas.getBoundingClientRect();
            const clickX = (event.clientX - rect.left) * (this.canvasDisplayWidth / rect.width);
            const clickY = (event.clientY - rect.top) * (this.canvasDisplayHeight / rect.height);
            this.placeSignature(clickX, clickY);
        }
    },

    startDragSig(e, sig) {
        this.selectedSigId = sig.id;
        this.isDraggingSig = true;
        this.isResizingSig = false;
        this.justInteractedSig = true;

        const clientX = e.touches ? e.touches[0].clientX : e.clientX;
        const clientY = e.touches ? e.touches[0].clientY : e.clientY;

        this.dragPointer = {
            startX: clientX,
            startY: clientY,
            initX: sig.x,
            initY: sig.y,
            initW: sig.w,
            initH: sig.h,
            aspect: sig.w / Math.max(1, sig.h),
        };
    },

    startResizeSig(e, sig) {
        this.selectedSigId = sig.id;
        this.isResizingSig = true;
        this.isDraggingSig = false;
        this.justInteractedSig = true;

        const clientX = e.touches ? e.touches[0].clientX : e.clientX;
        const clientY = e.touches ? e.touches[0].clientY : e.clientY;

        this.dragPointer = {
            startX: clientX,
            startY: clientY,
            initX: sig.x,
            initY: sig.y,
            initW: sig.w,
            initH: sig.h,
            aspect: sig.w / Math.max(1, sig.h),
        };
    },

    onPointerMove(e) {
        if (!this.isDraggingSig && !this.isResizingSig) return;
        if (e.cancelable) e.preventDefault();

        const clientX = e.touches ? e.touches[0].clientX : e.clientX;
        const clientY = e.touches ? e.touches[0].clientY : e.clientY;

        const dx = clientX - this.dragPointer.startX;
        const dy = clientY - this.dragPointer.startY;

        const sig = this.placedSignatures.find(s => s.id === this.selectedSigId);
        if (!sig) return;

        if (this.isDraggingSig) {
            const maxX = Math.max(0, this.canvasDisplayWidth - sig.w);
            const maxY = Math.max(0, this.canvasDisplayHeight - sig.h);
            sig.x = Math.round(Math.max(0, Math.min(maxX, this.dragPointer.initX + dx)));
            sig.y = Math.round(Math.max(0, Math.min(maxY, this.dragPointer.initY + dy)));
            sig.relX = sig.x / this.canvasDisplayWidth;
            sig.relY = sig.y / this.canvasDisplayHeight;
        } else if (this.isResizingSig) {
            const newW = Math.max(40, Math.min(this.canvasDisplayWidth - sig.x, this.dragPointer.initW + dx));
            sig.w = Math.round(newW);
            sig.h = Math.round(newW / this.dragPointer.aspect);
            sig.relW = sig.w / this.canvasDisplayWidth;
            sig.relH = sig.h / this.canvasDisplayHeight;
        }
    },

    onPointerUp() {
        if (this.isDraggingSig || this.isResizingSig) {
            this.isDraggingSig = false;
            this.isResizingSig = false;
            setTimeout(() => { this.justInteractedSig = false; }, 80);
        }
    },

    removePlacedSignature(id) {
        this.placedSignatures = this.placedSignatures.filter(s => s.id !== id);
        if (this.selectedSigId === id) this.selectedSigId = null;
    },

    clearAllPlacedSignatures() {
        if (confirm('คุณต้องการลบลายเซ็นทั้งหมดบนเอกสารใช่หรือไม่?')) {
            this.placedSignatures = [];
            this.selectedSigId = null;
        }
    },

    get currentPageSignatures() {
        return this.placedSignatures.filter(s => s.page === this.currentPage);
    },

    get totalPlacedSignaturesCount() {
        return this.placedSignatures.length;
    },

    // ─── Export & Direct Download ───────────────────────
    async applyAndDownload() {
        if (!this.pdfBytes || this.placedSignatures.length === 0) {
            alert('กรุณาวางลายเซ็นบนเอกสารอย่างน้อย 1 ตำแหน่งก่อนดาวน์โหลด');
            return;
        }
        this.isExporting = true;
        this.exportError = null;

        try {
            await this.ensurePdfLib();
            const { PDFDocument, degrees } = window.PDFLib;

            // Always acquire a fresh, non-detached ArrayBuffer from the original File object
            let sourceBuffer = null;
            if (this.pdfFile && typeof this.pdfFile.arrayBuffer === 'function') {
                sourceBuffer = await this.pdfFile.arrayBuffer();
            } else if (this.pdfBytes && this.pdfBytes.byteLength > 0) {
                sourceBuffer = this.pdfBytes.slice(0);
            }
            if (!sourceBuffer || sourceBuffer.byteLength === 0) {
                throw new Error('ไม่พบข้อมูลเอกสาร PDF กรุณาเลือกไฟล์อีกครั้ง');
            }

            const pdfDoc = await PDFDocument.load(sourceBuffer);
            const pages = pdfDoc.getPages();

            for (const sig of this.placedSignatures) {
                if (sig.page < 1 || sig.page > pages.length) continue;
                const page = pages[sig.page - 1];
                const { width: pw, height: ph } = page.getSize();
                const angle = (page.getRotation().angle || 0) % 360;

                let img;
                if (sig.dataUrl.includes('image/png')) {
                    img = await pdfDoc.embedPng(sig.dataUrl);
                } else {
                    img = await pdfDoc.embedJpg(sig.dataUrl);
                }

                if (angle === 0) {
                    const pdfW = sig.relW * pw;
                    const pdfH = sig.relH * ph;
                    const pdfX = sig.relX * pw;
                    const pdfY = ph - (sig.relY * ph) - pdfH;
                    page.drawImage(img, { x: pdfX, y: pdfY, width: pdfW, height: pdfH });
                } else if (angle === 90) {
                    const visualW = ph;
                    const visualH = pw;
                    const imgW = sig.relW * visualW;
                    const imgH = sig.relH * visualH;
                    const visualX = sig.relX * visualW;
                    const visualY = sig.relY * visualH;
                    page.drawImage(img, {
                        x: visualY + imgH,
                        y: visualX,
                        width: imgW,
                        height: imgH,
                        rotate: degrees(90),
                    });
                } else if (angle === 180) {
                    const imgW = sig.relW * pw;
                    const imgH = sig.relH * ph;
                    page.drawImage(img, {
                        x: pw - (sig.relX * pw),
                        y: (sig.relY * ph) + imgH,
                        width: imgW,
                        height: imgH,
                        rotate: degrees(180),
                    });
                } else if (angle === 270) {
                    const visualW = ph;
                    const visualH = pw;
                    const imgW = sig.relW * visualW;
                    const imgH = sig.relH * visualH;
                    page.drawImage(img, {
                        x: pw - (sig.relY * visualH) - imgH,
                        y: ph - (sig.relX * visualW),
                        width: imgW,
                        height: imgH,
                        rotate: degrees(270),
                    });
                }
            }

            const modifiedBytes = await pdfDoc.save();
            const blob = new Blob([modifiedBytes], { type: 'application/pdf' });
            const downloadUrl = URL.createObjectURL(blob);

            const a = document.createElement('a');
            a.href = downloadUrl;
            const originalName = this.pdfFileName ? this.pdfFileName.replace(/\.pdf$/i, '') : 'document';
            a.download = `${originalName}_signed.pdf`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            setTimeout(() => URL.revokeObjectURL(downloadUrl), 8000);

            this.exportSuccess = true;
            setTimeout(() => { this.exportSuccess = false; }, 5000);
        } catch (err) {
            console.error('Export signed PDF error:', err);
            this.exportError = 'เกิดข้อผิดพลาดในการบันทึกเอกสาร: ' + (err.message || '');
        } finally {
            this.isExporting = false;
        }
    },

    resetAll() {
        if (confirm('คุณต้องการล้างข้อมูลเอกสารและลายเซ็นทั้งหมดใช่หรือไม่?')) {
            this.pdfLoaded = false;
            this.pdfFile = null;
            this.pdfFileName = '';
            this.pdfBytes = null;
            signPdfDoc = null;
            this.totalPages = 0;
            this.currentPage = 1;
            this.placedSignatures = [];
            this.selectedSigId = null;
            this.clearDraw();
            this.typedName = '';
            this.uploadedImageDataUrl = null;
            this.activeSigDataUrl = null;
        }
    },
}));

// =====================================================
// Alpine Component: pdfEditor (Pro PDF Editor Suite)
// =====================================================
Alpine.data('pdfEditor', () => ({
    // Document state
    pdfLoaded: false,
    pdfFile: null,
    pdfFileName: 'document.pdf',
    pdfBytes: null,
    totalPages: 0,
    currentPage: 1,
    zoom: 100, // percent
    unscaledWidth: 595.28, // A4 default points
    unscaledHeight: 841.89,
    displayWidth: 595,
    displayHeight: 842,
    isLoading: false,
    isExporting: false,
    loadingMessage: '',
    exportError: '',

    // Active tool: 'pointer' | 'hand' | 'edit-text' | 'text' | 'whiteout' | 'note' | 'draw' | 'highlight' | 'underline' | 'strikethrough' | 'stamp'
    activeTool: 'pointer',

    // Sidebar state
    sidebarCollapsed: false,
    activeSidebarTab: 'pages', // 'pages' | 'bookmarks' | 'search' | 'attachments' | 'comments'
    thumbnails: [],

    // Annotations: [{ id, type, page, pctX, pctY, pctW, pctH, ... }]
    annotations: [],
    selectedAnnotationId: null,

    // Detected original text blocks for the current page
    currentOriginalTextBlocks: [],
    isExtractingText: false,

    // Whiteout tool state
    isDrawingWhiteout: false,
    whiteoutStart: null,
    currentWhiteoutRect: null,

    get currentWhiteoutRectStyle() {
        if (!this.currentWhiteoutRect) return '';
        const r = this.currentWhiteoutRect;
        return `left: ${r.pctX}%; top: ${r.pctY}%; width: ${r.pctW}%; height: ${r.pctH}%;`;
    },

    // Drawing & Markup in-progress
    isDrawing: false,
    currentDrawPoints: [],
    drawColor: '#dc2626',
    drawWidth: 3,

    // Highlight tool
    highlightColor: '#fde047', // vivid yellow
    isHighlighting: false,
    highlightStart: null,

    // Text tool & edit-text settings
    textFontFamily: 'Sarabun',
    textColor: '#111827',
    textSize: 16,
    textBold: false,
    textItalic: false,
    textUnderline: false,
    textAlign: 'left',
    textBgColor: '#ffffff', // default white so it cleanly covers original PDF text!

    // Stamp settings
    activeStampPreset: 'APPROVED',
    customStampText: 'สำเนาถูกต้อง',
    stampColor: '#16a34a',

    // Sticky Note state
    noteModalOpen: false,
    activeNoteText: '',
    noteColor: '#fef08a',

    // Search state
    searchQuery: '',
    searchResults: [],
    searchResultIndex: -1,
    isSearching: false,

    // Bookmarks / Outline
    bookmarks: [],

    // Hand / Pan Tool
    isPanning: false,
    panStart: { x: 0, y: 0, scrollLeft: 0, scrollTop: 0 },

    // Dragging / Resizing annotations
    isDraggingAnnotation: false,
    dragStart: { x: 0, y: 0, origPctX: 0, origPctY: 0 },
    isResizingAnnotation: false,
    resizeStart: { x: 0, y: 0, origPctW: 0, origPctH: 0, handle: '' },

    // History stack for Undo / Redo
    history: [],
    historyIndex: -1,

    // Modals
    shareModalOpen: false,
    shareCopied: false,
    newDocModalOpen: false,

    async init() {
        if (typeof pdfjsLib !== 'undefined' && !pdfjsLib.GlobalWorkerOptions.workerSrc) {
            pdfjsLib.GlobalWorkerOptions.workerSrc = '/vendor/pdfjs/pdf.worker.min.js';
        }

        // Global hotkeys
        window.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'z') {
                if (e.shiftKey) {
                    this.redo();
                } else {
                    this.undo();
                }
                e.preventDefault();
            } else if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'y') {
                this.redo();
                e.preventDefault();
            } else if ((e.key === 'Delete' || e.key === 'Backspace') && this.selectedAnnotationId && !['INPUT', 'TEXTAREA'].includes(document.activeElement?.tagName)) {
                this.deleteSelectedAnnotation();
                e.preventDefault();
            } else if (e.key === 'Escape') {
                this.selectedAnnotationId = null;
                this.activeTool = 'pointer';
            }
        });

        // Initialize with a blank A4 page so user sees ready workspace immediately!
        await this.createNewTask(false);
    },

    // ─── FILE OPERATIONS ───
    async createNewTask(confirmReset = true) {
        if (confirmReset && this.annotations.length > 0) {
            if (!confirm('คุณต้องการสร้างเอกสารใหม่และล้างงานเดิมใช่หรือไม่?')) return;
        }

        try {
            this.isLoading = true;
            this.loadingMessage = 'กำลังสร้างเอกสารใหม่...';

            // Create blank A4 PDF via PDF-Lib
            const pdfDoc = await PDFLib.PDFDocument.create();
            pdfDoc.addPage([595.28, 841.89]); // A4 in points
            const bytes = await pdfDoc.save();

            this.pdfBytes = bytes;
            this.pdfFileName = 'Untitled-Document.pdf';
            this.pdfFile = null;
            this.annotations = [];
            this.selectedAnnotationId = null;
            this.history = [];
            this.historyIndex = -1;
            this.currentPage = 1;
            editorThumbnailsCache = {};

            await this.loadPdfFromBytes(bytes);
            this.pushHistory('สร้างเอกสารใหม่');
        } catch (err) {
            console.error('Error creating new document:', err);
        } finally {
            this.isLoading = false;
        }
    },

    triggerOpenDialog() {
        const fileInput = document.getElementById('pdfEditorFileInput');
        if (fileInput) fileInput.click();
    },

    async handleFileInput(e) {
        const file = e.target.files?.[0];
        if (!file) return;
        if (file.type !== 'application/pdf' && !file.name.toLowerCase().endsWith('.pdf')) {
            alert('กรุณาเลือกไฟล์ PDF เท่านั้น');
            return;
        }

        try {
            this.isLoading = true;
            this.loadingMessage = 'กำลังโหลดไฟล์ PDF...';
            this.pdfFileName = file.name;
            this.pdfFile = file;
            const buffer = await file.arrayBuffer();
            this.pdfBytes = new Uint8Array(buffer);
            this.annotations = [];
            this.selectedAnnotationId = null;
            this.history = [];
            this.historyIndex = -1;
            this.currentPage = 1;
            editorThumbnailsCache = {};

            await this.loadPdfFromBytes(this.pdfBytes);
            this.pushHistory('เปิดไฟล์ PDF');
        } catch (err) {
            console.error('Error opening PDF:', err);
            alert('ไม่สามารถเปิดไฟล์ PDF ได้: ' + (err.message || ''));
        } finally {
            this.isLoading = false;
            e.target.value = '';
        }
    },

    async loadPdfFromBytes(bytes) {
        try {
            const loadingTask = pdfjsLib.getDocument({
                data: bytes.slice(0),
                cMapUrl: 'https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/cmaps/',
                cMapPacked: true
            });
            editorPdfDoc = await loadingTask.promise;
            this.totalPages = editorPdfDoc.numPages || 1;
            this.pdfLoaded = true;

            // Load outline/bookmarks if any
            try {
                const outline = await editorPdfDoc.getOutline();
                this.bookmarks = outline || [];
            } catch {
                this.bookmarks = [];
            }

            await this.renderCurrentPage();
            this.renderThumbnails();
        } catch (err) {
            console.error('loadPdfFromBytes error:', err);
            throw err;
        }
    },

    // ─── RENDERING & VIEWPORT ───
    async renderCurrentPage() {
        if (!editorPdfDoc) return;
        try {
            const canvas = document.getElementById('pdfEditorCanvas');
            if (!canvas) return;

            const page = await editorPdfDoc.getPage(this.currentPage);
            const rawViewport = page.getViewport({ scale: 1.0 });
            this.unscaledWidth = rawViewport.width;
            this.unscaledHeight = rawViewport.height;

            const effectiveScale = (this.zoom / 100);
            const viewport = page.getViewport({ scale: effectiveScale });

            this.displayWidth = Math.round(viewport.width);
            this.displayHeight = Math.round(viewport.height);

            const dpr = window.devicePixelRatio || 1;
            canvas.width = Math.round(viewport.width * dpr);
            canvas.height = Math.round(viewport.height * dpr);
            canvas.style.width = `${this.displayWidth}px`;
            canvas.style.height = `${this.displayHeight}px`;

            const ctx = canvas.getContext('2d', { alpha: false });
            ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

            if (editorRenderTask) {
                try { editorRenderTask.cancel(); } catch {}
            }

            editorRenderTask = page.render({
                canvasContext: ctx,
                viewport: viewport
            });

            await editorRenderTask.promise;
            await this.extractPageTextBlocks();
        } catch (err) {
            if (err?.name !== 'RenderingCancelledException') {
                console.error('renderCurrentPage error:', err);
            }
        }
    },

    async renderThumbnails() {
        if (!editorPdfDoc) return;
        const thumbs = [];
        for (let i = 1; i <= this.totalPages; i++) {
            thumbs.push({
                page: i,
                dataUrl: editorThumbnailsCache[i] || null
            });
        }
        this.thumbnails = thumbs;

        // Render thumbnails in background
        for (let i = 1; i <= this.totalPages; i++) {
            if (!editorThumbnailsCache[i]) {
                try {
                    const page = await editorPdfDoc.getPage(i);
                    const vp = page.getViewport({ scale: 0.25 });
                    const offCanvas = document.createElement('canvas');
                    offCanvas.width = vp.width;
                    offCanvas.height = vp.height;
                    const ctx = offCanvas.getContext('2d');
                    await page.render({ canvasContext: ctx, viewport: vp }).promise;
                    const url = offCanvas.toDataURL('image/jpeg', 0.8);
                    editorThumbnailsCache[i] = url;
                    if (this.thumbnails[i - 1]) {
                        this.thumbnails[i - 1].dataUrl = url;
                    }
                } catch {}
            }
        }
    },

    goToPage(pageNum) {
        const num = Math.max(1, Math.min(this.totalPages, parseInt(pageNum) || 1));
        if (num !== this.currentPage) {
            this.currentPage = num;
            this.selectedAnnotationId = null;
            this.renderCurrentPage();
        }
    },

    prevPage() {
        if (this.currentPage > 1) this.goToPage(this.currentPage - 1);
    },

    nextPage() {
        if (this.currentPage < this.totalPages) this.goToPage(this.currentPage + 1);
    },

    setZoom(val) {
        this.zoom = Math.max(30, Math.min(300, Math.round(val)));
        this.renderCurrentPage();
    },

    zoomIn() {
        this.setZoom(this.zoom + 15);
    },

    zoomOut() {
        this.setZoom(this.zoom - 15);
    },

    fitWidth() {
        const container = document.getElementById('pdfEditorWorkspace');
        if (!container || !this.unscaledWidth) return;
        const availableWidth = container.clientWidth - 80;
        const targetZoom = Math.round((availableWidth / this.unscaledWidth) * 100);
        this.setZoom(targetZoom);
    },

    fitPage() {
        const container = document.getElementById('pdfEditorWorkspace');
        if (!container || !this.unscaledWidth || !this.unscaledHeight) return;
        const availableWidth = container.clientWidth - 80;
        const availableHeight = container.clientHeight - 80;
        const zoomW = (availableWidth / this.unscaledWidth) * 100;
        const zoomH = (availableHeight / this.unscaledHeight) * 100;
        this.setZoom(Math.min(zoomW, zoomH));
    },

    // ─── PAGE MANAGEMENT ───
    async addBlankPage(position = 'after') {
        try {
            this.isLoading = true;
            this.loadingMessage = 'กำลังแทรกหน้าว่าง...';

            const pdfDoc = await PDFLib.PDFDocument.load(this.pdfBytes.slice(0));
            const insertIndex = position === 'before' ? this.currentPage - 1 : this.currentPage;
            pdfDoc.insertPage(insertIndex, [this.unscaledWidth || 595.28, this.unscaledHeight || 841.89]);
            const newBytes = await pdfDoc.save();

            // Shift annotations that are on or after the inserted page
            const targetPage = insertIndex + 1;
            this.annotations.forEach(a => {
                if (a.page >= targetPage) {
                    a.page += 1;
                }
            });

            this.pdfBytes = newBytes;
            editorThumbnailsCache = {};
            await this.loadPdfFromBytes(this.pdfBytes);

            this.goToPage(targetPage);
            this.pushHistory('เพิ่มหน้าว่าง');
        } catch (err) {
            console.error('Error adding blank page:', err);
            alert('ไม่สามารถเพิ่มหน้าว่างได้: ' + (err.message || ''));
        } finally {
            this.isLoading = false;
        }
    },

    async deleteCurrentPage() {
        if (this.totalPages <= 1) {
            alert('เอกสารต้องมีอย่างน้อย 1 หน้า ไม่สามารถลบหน้าสุดท้ายได้');
            return;
        }

        if (!confirm(`คุณต้องการลบหน้า ${this.currentPage} ใช่หรือไม่?`)) return;

        try {
            this.isLoading = true;
            this.loadingMessage = 'กำลังลบหน้า...';

            const pdfDoc = await PDFLib.PDFDocument.load(this.pdfBytes.slice(0));
            const removeIndex = this.currentPage - 1;
            pdfDoc.removePage(removeIndex);
            const newBytes = await pdfDoc.save();

            const deletedPage = this.currentPage;
            // Remove annotations on this page and shift down subsequent pages
            this.annotations = this.annotations
                .filter(a => a.page !== deletedPage)
                .map(a => {
                    if (a.page > deletedPage) {
                        return { ...a, page: a.page - 1 };
                    }
                    return a;
                });

            this.pdfBytes = newBytes;
            editorThumbnailsCache = {};
            await this.loadPdfFromBytes(this.pdfBytes);

            const nextPage = Math.min(this.currentPage, this.totalPages);
            this.goToPage(nextPage);
            this.pushHistory('ลบหน้าเอกสาร');
        } catch (err) {
            console.error('Error deleting page:', err);
            alert('ไม่สามารถลบหน้าได้: ' + (err.message || ''));
        } finally {
            this.isLoading = false;
        }
    },

    async extractPageTextBlocks() {
        if (!editorPdfDoc) {
            this.currentOriginalTextBlocks = [];
            return;
        }
        try {
            this.isExtractingText = true;
            const page = await editorPdfDoc.getPage(this.currentPage);
            const textContent = await page.getTextContent({ normalizeWhitespace: true });
            const viewport = page.getViewport({ scale: 1.0 });

            const rawItems = [];
            for (const item of textContent.items) {
                const str = (item.str || '').trim();
                if (!str) continue;

                const transX = item.transform[4];
                const transY = item.transform[5];
                const fontSize = Math.max(9, Math.round(Math.abs(item.transform[3]) || item.height || 14));

                // Convert PDF point to Viewport point
                const [vx, vy] = viewport.convertToViewportPoint(transX, transY);
                const itemWidth = Math.max(item.width || 0, fontSize * 0.45 * str.length);

                rawItems.push({
                    str: item.str,
                    vx: vx,
                    vy: vy,
                    baselineY: vy,
                    fontSize: fontSize,
                    width: itemWidth
                });
            }

            // Sort items by baselineY (top to bottom), then by vx (left to right)
            rawItems.sort((a, b) => {
                if (Math.abs(a.baselineY - b.baselineY) > 6) {
                    return a.baselineY - b.baselineY;
                }
                return a.vx - b.vx;
            });

            // Group items into coherent lines
            const lines = [];
            let currentLine = null;

            for (const item of rawItems) {
                if (!currentLine) {
                    currentLine = {
                        text: item.str,
                        minX: item.vx,
                        maxX: item.vx + item.width,
                        baselineY: item.baselineY,
                        fontSize: item.fontSize,
                        items: [item]
                    };
                } else {
                    const yDiff = Math.abs(item.baselineY - currentLine.baselineY);
                    const xGap = item.vx - currentLine.maxX;
                    const maxGap = Math.max(25, currentLine.fontSize * 1.8);

                    if (yDiff <= Math.max(5, currentLine.fontSize * 0.45) && xGap >= -8 && xGap <= maxGap) {
                        const needsSpace = !currentLine.text.endsWith(' ') && !item.str.startsWith(' ') && xGap > 2;
                        currentLine.text += (needsSpace ? ' ' : '') + item.str;
                        currentLine.maxX = Math.max(currentLine.maxX, item.vx + item.width);
                        currentLine.fontSize = Math.max(currentLine.fontSize, item.fontSize);
                        currentLine.items.push(item);
                    } else {
                        lines.push(currentLine);
                        currentLine = {
                            text: item.str,
                            minX: item.vx,
                            maxX: item.vx + item.width,
                            baselineY: item.baselineY,
                            fontSize: item.fontSize,
                            items: [item]
                        };
                    }
                }
            }
            if (currentLine) {
                lines.push(currentLine);
            }

            // Map grouped lines to percentage coordinates on page
            this.currentOriginalTextBlocks = lines.map((line, idx) => {
                const fontH = line.fontSize;
                const topY = Math.max(0, line.baselineY - fontH * 0.95);
                const totalH = fontH * 1.35;
                const totalW = Math.max(line.maxX - line.minX, 20);

                return {
                    id: `orig_${this.currentPage}_${idx}`,
                    text: line.text,
                    pctX: Math.max(0, Math.min(96, (line.minX / viewport.width) * 100)),
                    pctY: Math.max(0, Math.min(96, (topY / viewport.height) * 100)),
                    pctW: Math.min(100, ((totalW + 6) / viewport.width) * 100),
                    pctH: Math.min(100, ((totalH + 4) / viewport.height) * 100),
                    fontSize: line.fontSize
                };
            });
        } catch (err) {
            console.warn('Error extracting page text blocks:', err);
            this.currentOriginalTextBlocks = [];
        } finally {
            this.isExtractingText = false;
        }
    },

    startEditingOriginalText(block) {
        // Check if there's already an annotation covering this block closely
        const existing = this.annotations.find(a => 
            a.page === this.currentPage &&
            a.type === 'text' &&
            Math.abs(a.pctX - block.pctX) < 3 &&
            Math.abs(a.pctY - block.pctY) < 3
        );
        if (existing) {
            this.selectedAnnotationId = existing.id;
            setTimeout(() => {
                const ta = document.querySelector(`textarea[data-ann-id="${existing.id}"]`);
                if (ta) ta.focus();
            }, 60);
            return;
        }

        const newId = 'edit_text_' + Date.now();
        const newAnn = {
            id: newId,
            type: 'text',
            page: this.currentPage,
            pctX: Math.max(0, block.pctX - 0.3),
            pctY: Math.max(0, block.pctY - 0.3),
            pctW: Math.min(100 - block.pctX, Math.max(block.pctW + 2.5, 20)),
            pctH: Math.min(100 - block.pctY, Math.max(block.pctH + 1.2, 4.2)),
            text: block.text,
            fontSize: block.fontSize || this.textSize || 16,
            fontFamily: this.textFontFamily || 'Sarabun',
            color: this.textColor || '#111827',
            bgColor: '#ffffff', // Opaque white to cover original PDF text!
            bold: false,
            italic: false,
            underline: false,
            align: 'left'
        };

        this.annotations.push(newAnn);
        this.selectedAnnotationId = newId;
        this.textSize = newAnn.fontSize;
        this.textBgColor = '#ffffff';

        // Remove block from currentOriginalTextBlocks so it doesn't overlay the active edit box
        this.currentOriginalTextBlocks = this.currentOriginalTextBlocks.filter(b => b.id !== block.id);
        this.pushHistory('แก้ไขข้อความในเอกสาร');

        // Auto-focus the newly created textarea immediately
        setTimeout(() => {
            const ta = document.querySelector(`textarea[data-ann-id="${newId}"]`);
            if (ta) {
                ta.focus();
                ta.select();
            }
        }, 60);
    },

    // ─── TOOLS & INTERACTIONS ───
    setTool(tool) {
        this.activeTool = tool;
        if (tool !== 'pointer' && tool !== 'edit-text' && tool !== 'text') {
            this.selectedAnnotationId = null;
        }
        if (['pointer', 'edit-text', 'text'].includes(tool) && this.currentOriginalTextBlocks.length === 0) {
            this.extractPageTextBlocks();
        }
    },

    get pageAnnotations() {
        return this.annotations.filter(a => a.page === this.currentPage);
    },

    get selectedAnnotation() {
        return this.annotations.find(a => a.id === this.selectedAnnotationId) || null;
    },

    handleOverlayMouseDown(e) {
        if (e.button !== 0) return; // only left click
        const overlay = document.getElementById('pdfEditorOverlay');
        if (!overlay) return;

        const rect = overlay.getBoundingClientRect();
        const clickX = e.clientX - rect.left;
        const clickY = e.clientY - rect.top;
        const pctX = (clickX / rect.width) * 100;
        const pctY = (clickY / rect.height) * 100;

        if (this.activeTool === 'hand') {
            const ws = document.getElementById('pdfEditorWorkspace');
            if (ws) {
                this.isPanning = true;
                this.panStart = {
                    x: e.clientX,
                    y: e.clientY,
                    scrollLeft: ws.scrollLeft,
                    scrollTop: ws.scrollTop
                };
            }
            return;
        }

        if (this.activeTool === 'pointer') {
            // Deselect if clicking on empty area
            if (e.target === overlay || e.target.id === 'pdfEditorCanvas' || e.target.id === 'drawingSvg') {
                this.selectedAnnotationId = null;
            }
            return;
        }

        if (this.activeTool === 'draw') {
            this.isDrawing = true;
            this.currentDrawPoints = [{ x: pctX, y: pctY }];
            return;
        }

        if (this.activeTool === 'highlight' || this.activeTool === 'underline' || this.activeTool === 'strikethrough') {
            this.isHighlighting = true;
            this.highlightStart = { x: pctX, y: pctY, clientX: e.clientX, clientY: e.clientY };
            return;
        }

        if (this.activeTool === 'whiteout') {
            this.isDrawingWhiteout = true;
            this.whiteoutStart = { x: pctX, y: pctY };
            this.currentWhiteoutRect = { pctX, pctY, pctW: 0, pctH: 0 };
            return;
        }

        if (this.activeTool === 'edit-text') {
            const newId = 'text_' + Date.now();
            this.annotations.push({
                id: newId,
                type: 'text',
                page: this.currentPage,
                pctX: Math.max(0, Math.min(80, pctX)),
                pctY: Math.max(0, Math.min(95, pctY)),
                pctW: 25,
                pctH: 4.5,
                text: 'พิมพ์ข้อความที่นี่',
                fontSize: this.textSize,
                fontFamily: this.textFontFamily || 'Sarabun',
                color: this.textColor,
                bgColor: this.textBgColor || '#ffffff',
                bold: this.textBold,
                italic: this.textItalic,
                underline: this.textUnderline,
                align: this.textAlign || 'left'
            });
            this.selectedAnnotationId = newId;
            this.pushHistory('เพิ่มข้อความ');
            return;
        }

        if (this.activeTool === 'text') {
            const newId = 'text_' + Date.now();
            this.annotations.push({
                id: newId,
                type: 'text',
                page: this.currentPage,
                pctX: Math.max(0, Math.min(80, pctX)),
                pctY: Math.max(0, Math.min(95, pctY)),
                pctW: 30,
                pctH: 5.5,
                text: 'พิมพ์ข้อความที่นี่',
                fontSize: this.textSize,
                fontFamily: this.textFontFamily || 'Sarabun',
                color: this.textColor,
                bgColor: this.textBgColor === '#ffffff' ? '#ffffff' : 'transparent',
                bold: this.textBold,
                italic: this.textItalic,
                underline: this.textUnderline,
                align: this.textAlign || 'left'
            });
            this.selectedAnnotationId = newId;
            this.activeTool = 'pointer';
            this.pushHistory('เพิ่มข้อความ');
            return;
        }

        if (this.activeTool === 'note') {
            const newId = 'note_' + Date.now();
            this.annotations.push({
                id: newId,
                type: 'note',
                page: this.currentPage,
                pctX: Math.max(0, Math.min(90, pctX)),
                pctY: Math.max(0, Math.min(90, pctY)),
                pctW: 5,
                pctH: 4,
                text: 'เพิ่มความคิดเห็นที่นี่...',
                color: this.noteColor,
                author: 'ผู้ตรวจทาน',
                createdAt: new Date().toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' })
            });
            this.selectedAnnotationId = newId;
            this.activeTool = 'pointer';
            this.openNoteEditor(newId);
            this.pushHistory('เพิ่มหมายเหตุ');
            return;
        }

        if (this.activeTool === 'stamp') {
            const newId = 'stamp_' + Date.now();
            const dateStr = new Date().toLocaleDateString('th-TH', { day: '2-digit', month: '2-digit', year: 'numeric' });
            this.annotations.push({
                id: newId,
                type: 'stamp',
                page: this.currentPage,
                pctX: Math.max(5, Math.min(75, pctX - 12)),
                pctY: Math.max(5, Math.min(85, pctY - 5)),
                pctW: 24,
                pctH: 10,
                preset: this.activeStampPreset,
                customText: this.customStampText,
                color: this.stampColor,
                date: dateStr
            });
            this.selectedAnnotationId = newId;
            this.activeTool = 'pointer';
            this.pushHistory('ประทับตรา');
            return;
        }
    },

    handleOverlayMouseMove(e) {
        const overlay = document.getElementById('pdfEditorOverlay');
        if (!overlay) return;

        if (this.isPanning) {
            const ws = document.getElementById('pdfEditorWorkspace');
            if (ws) {
                const dx = e.clientX - this.panStart.x;
                const dy = e.clientY - this.panStart.y;
                ws.scrollLeft = this.panStart.scrollLeft - dx;
                ws.scrollTop = this.panStart.scrollTop - dy;
            }
            return;
        }

        if (this.isDrawingWhiteout && this.whiteoutStart) {
            const minX = Math.min(this.whiteoutStart.x, pctX);
            const minY = Math.min(this.whiteoutStart.y, pctY);
            const w = Math.max(0.5, Math.abs(pctX - this.whiteoutStart.x));
            const h = Math.max(0.5, Math.abs(pctY - this.whiteoutStart.y));
            this.currentWhiteoutRect = { pctX: minX, pctY: minY, pctW: w, pctH: h };
            return;
        }

        if (this.isDrawing) {
            const rect = overlay.getBoundingClientRect();
            const pctX = ((e.clientX - rect.left) / rect.width) * 100;
            const pctY = ((e.clientY - rect.top) / rect.height) * 100;
            this.currentDrawPoints.push({ x: pctX, y: pctY });
            return;
        }

        if (this.isDraggingAnnotation && this.selectedAnnotationId) {
            const rect = overlay.getBoundingClientRect();
            const dx = ((e.clientX - this.dragStart.x) / rect.width) * 100;
            const dy = ((e.clientY - this.dragStart.y) / rect.height) * 100;
            const ann = this.annotations.find(a => a.id === this.selectedAnnotationId);
            if (ann) {
                ann.pctX = Math.max(0, Math.min(100 - (ann.pctW || 5), this.dragStart.origPctX + dx));
                ann.pctY = Math.max(0, Math.min(100 - (ann.pctH || 5), this.dragStart.origPctY + dy));
            }
            return;
        }

        if (this.isResizingAnnotation && this.selectedAnnotationId) {
            const rect = overlay.getBoundingClientRect();
            const dx = ((e.clientX - this.resizeStart.x) / rect.width) * 100;
            const dy = ((e.clientY - this.resizeStart.y) / rect.height) * 100;
            const ann = this.annotations.find(a => a.id === this.selectedAnnotationId);
            if (ann) {
                ann.pctW = Math.max(5, Math.min(100 - ann.pctX, this.resizeStart.origPctW + dx));
                ann.pctH = Math.max(3, Math.min(100 - ann.pctY, this.resizeStart.origPctH + dy));
            }
            return;
        }
    },

    handleOverlayMouseUp(e) {
        if (this.isPanning) {
            this.isPanning = false;
        }

        if (this.isDrawingWhiteout && this.whiteoutStart) {
            this.isDrawingWhiteout = false;
            const rect = this.currentWhiteoutRect;
            if (rect && rect.pctW > 0.5 && rect.pctH > 0.5) {
                const newId = 'whiteout_' + Date.now();
                this.annotations.push({
                    id: newId,
                    type: 'whiteout',
                    page: this.currentPage,
                    pctX: rect.pctX,
                    pctY: rect.pctY,
                    pctW: rect.pctW,
                    pctH: rect.pctH,
                    color: '#ffffff'
                });
                this.selectedAnnotationId = newId;
                this.pushHistory('ลบข้อความ (ไวท์เอาท์)');
            }
            this.whiteoutStart = null;
            this.currentWhiteoutRect = null;
            return;
        }

        if (this.isDrawing) {
            this.isDrawing = false;
            if (this.currentDrawPoints.length > 1) {
                const pathData = this.pointsToSvgPath(this.currentDrawPoints);
                this.annotations.push({
                    id: 'draw_' + Date.now(),
                    type: 'draw',
                    page: this.currentPage,
                    path: pathData,
                    color: this.drawColor,
                    strokeWidth: this.drawWidth
                });
                this.pushHistory('วาดเส้น');
            }
            this.currentDrawPoints = [];
        }

        if (this.isHighlighting && this.highlightStart) {
            this.isHighlighting = false;
            const overlay = document.getElementById('pdfEditorOverlay');
            if (overlay) {
                const rect = overlay.getBoundingClientRect();
                const endPctX = ((e.clientX - rect.left) / rect.width) * 100;
                const endPctY = ((e.clientY - rect.top) / rect.height) * 100;

                const left = Math.min(this.highlightStart.x, endPctX);
                const top = Math.min(this.highlightStart.y, endPctY);
                const width = Math.max(2, Math.abs(endPctX - this.highlightStart.x));
                const height = this.activeTool === 'underline' ? 0.5 : (this.activeTool === 'strikethrough' ? 0.4 : Math.max(1.5, Math.abs(endPctY - this.highlightStart.y)));

                this.annotations.push({
                    id: this.activeTool + '_' + Date.now(),
                    type: this.activeTool,
                    page: this.currentPage,
                    pctX: left,
                    pctY: top,
                    pctW: width,
                    pctH: height,
                    color: this.highlightColor
                });
                this.pushHistory('ไฮไลท์/ขีดเส้น');
            }
            this.highlightStart = null;
        }

        if (this.isDraggingAnnotation) {
            this.isDraggingAnnotation = false;
            this.pushHistory('ย้ายตำแหน่ง');
        }

        if (this.isResizingAnnotation) {
            this.isResizingAnnotation = false;
            this.pushHistory('ปรับขนาด');
        }
    },

    pointsToSvgPath(pts) {
        if (!pts || pts.length === 0) return '';
        let d = `M ${pts[0].x.toFixed(2)} ${pts[0].y.toFixed(2)}`;
        for (let i = 1; i < pts.length; i++) {
            d += ` L ${pts[i].x.toFixed(2)} ${pts[i].y.toFixed(2)}`;
        }
        return d;
    },

    get currentDrawSvgPath() {
        return this.pointsToSvgPath(this.currentDrawPoints);
    },

    // ─── ANNOTATION SELECTION & EDITING ───
    selectAnnotation(id, e) {
        if (e) e.stopPropagation();
        if (this.activeTool !== 'pointer') return;
        this.selectedAnnotationId = id;
    },

    startDragAnnotation(e, id) {
        if (e.button !== 0) return;
        if (this.activeTool !== 'pointer') return;
        e.stopPropagation();
        this.selectedAnnotationId = id;
        const ann = this.annotations.find(a => a.id === id);
        if (!ann) return;

        this.isDraggingAnnotation = true;
        this.dragStart = {
            x: e.clientX,
            y: e.clientY,
            origPctX: ann.pctX,
            origPctY: ann.pctY
        };
    },

    startResizeAnnotation(e, id, handle) {
        if (e.button !== 0) return;
        e.stopPropagation();
        const ann = this.annotations.find(a => a.id === id);
        if (!ann) return;

        this.isResizingAnnotation = true;
        this.resizeStart = {
            x: e.clientX,
            y: e.clientY,
            origPctW: ann.pctW || 20,
            origPctH: ann.pctH || 10,
            handle: handle
        };
    },

    deleteSelectedAnnotation() {
        if (!this.selectedAnnotationId) return;
        this.annotations = this.annotations.filter(a => a.id !== this.selectedAnnotationId);
        this.selectedAnnotationId = null;
        this.pushHistory('ลบวัตถุ');
    },

    deleteAnnotationById(id) {
        this.annotations = this.annotations.filter(a => a.id !== id);
        if (this.selectedAnnotationId === id) this.selectedAnnotationId = null;
        this.pushHistory('ลบวัตถุ');
    },

    openNoteEditor(id) {
        const ann = this.annotations.find(a => a.id === id);
        if (!ann) return;
        this.selectedAnnotationId = id;
        this.activeNoteText = ann.text || '';
        this.noteColor = ann.color || '#fef08a';
        this.noteModalOpen = true;
    },

    saveActiveNote() {
        const ann = this.annotations.find(a => a.id === this.selectedAnnotationId);
        if (ann) {
            ann.text = this.activeNoteText;
            ann.color = this.noteColor;
            this.pushHistory('แก้ไขหมายเหตุ');
        }
        this.noteModalOpen = false;
    },

    // ─── HISTORY (UNDO / REDO) ───
    pushHistory(actionDesc) {
        // Truncate redo stack
        if (this.historyIndex < this.history.length - 1) {
            this.history = this.history.slice(0, this.historyIndex + 1);
        }
        const state = JSON.stringify(this.annotations);
        this.history.push({
            action: actionDesc,
            state: state
        });
        if (this.history.length > 50) {
            this.history.shift();
        } else {
            this.historyIndex++;
        }
    },

    undo() {
        if (this.historyIndex > 0) {
            this.historyIndex--;
            const item = this.history[this.historyIndex];
            this.annotations = JSON.parse(item.state);
            this.selectedAnnotationId = null;
        }
    },

    redo() {
        if (this.historyIndex < this.history.length - 1) {
            this.historyIndex++;
            const item = this.history[this.historyIndex];
            this.annotations = JSON.parse(item.state);
            this.selectedAnnotationId = null;
        }
    },

    get canUndo() {
        return this.historyIndex > 0;
    },

    get canRedo() {
        return this.historyIndex < this.history.length - 1;
    },

    // ─── IN-DOCUMENT SEARCH ───
    async performSearch() {
        const query = (this.searchQuery || '').trim().toLowerCase();
        if (!query || !editorPdfDoc) {
            this.searchResults = [];
            this.searchResultIndex = -1;
            return;
        }

        try {
            this.isSearching = true;
            const results = [];
            for (let i = 1; i <= this.totalPages; i++) {
                const page = await editorPdfDoc.getPage(i);
                const textContent = await page.getTextContent();
                const fullText = textContent.items.map(item => item.str).join(' ');
                let pos = fullText.toLowerCase().indexOf(query);
                while (pos !== -1) {
                    const snippetStart = Math.max(0, pos - 25);
                    const snippetEnd = Math.min(fullText.length, pos + query.length + 25);
                    const snippet = (snippetStart > 0 ? '...' : '') +
                        fullText.substring(snippetStart, snippetEnd) +
                        (snippetEnd < fullText.length ? '...' : '');

                    results.push({
                        page: i,
                        snippet: snippet
                    });
                    pos = fullText.toLowerCase().indexOf(query, pos + query.length);
                }
            }

            this.searchResults = results;
            this.searchResultIndex = results.length > 0 ? 0 : -1;
            if (results.length > 0) {
                this.goToPage(results[0].page);
            }
        } catch (err) {
            console.error('Search error:', err);
        } finally {
            this.isSearching = false;
        }
    },

    nextSearchResult() {
        if (this.searchResults.length === 0) return;
        this.searchResultIndex = (this.searchResultIndex + 1) % this.searchResults.length;
        this.goToPage(this.searchResults[this.searchResultIndex].page);
    },

    prevSearchResult() {
        if (this.searchResults.length === 0) return;
        this.searchResultIndex = (this.searchResultIndex - 1 + this.searchResults.length) % this.searchResults.length;
        this.goToPage(this.searchResults[this.searchResultIndex].page);
    },

    // ─── PRINT & SHARE ───
    async printDocument() {
        window.print();
    },

    shareDocument() {
        this.shareModalOpen = true;
    },

    copyShareLink() {
        navigator.clipboard.writeText(window.location.href);
        this.shareCopied = true;
        setTimeout(() => { this.shareCopied = false; }, 2500);
    },

    // ─── EXPORT & DOWNLOAD ───
    async saveAndDownloadPdf() {
        if (!this.pdfBytes) return;
        try {
            this.isExporting = true;
            this.exportError = '';

            const pdfDoc = await PDFLib.PDFDocument.load(this.pdfBytes.slice(0));
            const pages = pdfDoc.getPages();

            // Render annotations on each page
            for (let i = 0; i < pages.length; i++) {
                const pageNum = i + 1;
                const page = pages[i];
                const pageAnns = this.annotations.filter(a => a.page === pageNum);
                if (pageAnns.length === 0) continue;

                const pw = page.getWidth();
                const ph = page.getHeight();

                // High-resolution canvas for rendering annotations
                const scaleFactor = 2;
                const offCanvas = document.createElement('canvas');
                offCanvas.width = pw * scaleFactor;
                offCanvas.height = ph * scaleFactor;
                const ctx = offCanvas.getContext('2d');
                ctx.scale(scaleFactor, scaleFactor);

                for (const ann of pageAnns) {
                    const x = (ann.pctX / 100) * pw;
                    const y = (ann.pctY / 100) * ph;
                    const w = ((ann.pctW || 10) / 100) * pw;
                    const h = ((ann.pctH || 5) / 100) * ph;

                    if (ann.type === 'draw') {
                        ctx.save();
                        ctx.strokeStyle = ann.color || '#dc2626';
                        ctx.lineWidth = ann.strokeWidth || 3;
                        ctx.lineCap = 'round';
                        ctx.lineJoin = 'round';
                        const p = new Path2D();
                        // Convert pct path to canvas pixels
                        const commands = ann.path.split(/(?=[LMCZ])/);
                        commands.forEach(cmd => {
                            const type = cmd[0];
                            const coords = cmd.slice(1).trim().split(/\s+/).map(Number);
                            if (type === 'M' && coords.length >= 2) {
                                p.moveTo((coords[0] / 100) * pw, (coords[1] / 100) * ph);
                            } else if (type === 'L' && coords.length >= 2) {
                                p.lineTo((coords[0] / 100) * pw, (coords[1] / 100) * ph);
                            }
                        });
                        ctx.stroke(p);
                        ctx.restore();
                    } else if (ann.type === 'highlight') {
                        ctx.save();
                        ctx.fillStyle = ann.color || '#fde047';
                        ctx.globalAlpha = 0.45;
                        ctx.fillRect(x, y, w, h);
                        ctx.restore();
                    } else if (ann.type === 'underline') {
                        ctx.save();
                        ctx.strokeStyle = ann.color || '#dc2626';
                        ctx.lineWidth = 2;
                        ctx.beginPath();
                        ctx.moveTo(x, y + h);
                        ctx.lineTo(x + w, y + h);
                        ctx.stroke();
                        ctx.restore();
                    } else if (ann.type === 'strikethrough') {
                        ctx.save();
                        ctx.strokeStyle = ann.color || '#dc2626';
                        ctx.lineWidth = 2;
                        ctx.beginPath();
                        ctx.moveTo(x, y + h / 2);
                        ctx.lineTo(x + w, y + h / 2);
                        ctx.stroke();
                        ctx.restore();
                    } else if (ann.type === 'whiteout') {
                        ctx.save();
                        ctx.fillStyle = ann.color || '#ffffff';
                        ctx.fillRect(x, y, w, h);
                        ctx.restore();
                    } else if (ann.type === 'text') {
                        ctx.save();
                        if (ann.bgColor && ann.bgColor !== 'transparent') {
                            ctx.fillStyle = ann.bgColor;
                            ctx.fillRect(x, y, w, h);
                        }
                        const fontStyle = `${ann.italic ? 'italic ' : ''}${ann.bold ? 'bold ' : ''}`;
                        const fontFam = ann.fontFamily || 'Sarabun';
                        const fSize = ann.fontSize || 16;
                        ctx.font = `${fontStyle}${fSize}px '${fontFam}', sans-serif`;
                        ctx.fillStyle = ann.color || '#111827';
                        ctx.textBaseline = 'top';
                        ctx.textAlign = ann.align || 'left';

                        let drawX = x;
                        if (ann.align === 'center') {
                            drawX = x + w / 2;
                        } else if (ann.align === 'right') {
                            drawX = x + w;
                        }

                        const lines = (ann.text || '').split('\n');
                        let lineY = y + 1;
                        const lineHeight = fSize * 1.3;
                        lines.forEach(line => {
                            ctx.fillText(line, drawX, lineY);
                            if (ann.underline) {
                                const metrics = ctx.measureText(line);
                                ctx.strokeStyle = ann.color || '#111827';
                                ctx.lineWidth = Math.max(1, fSize * 0.07);
                                ctx.beginPath();
                                let ulX = drawX;
                                if (ann.align === 'center') ulX = drawX - metrics.width / 2;
                                else if (ann.align === 'right') ulX = drawX - metrics.width;
                                ctx.moveTo(ulX, lineY + fSize + 1);
                                ctx.lineTo(ulX + metrics.width, lineY + fSize + 1);
                                ctx.stroke();
                            }
                            lineY += lineHeight;
                        });
                        ctx.restore();
                    } else if (ann.type === 'note') {
                        ctx.save();
                        // Draw cute sticky note badge
                        ctx.fillStyle = ann.color || '#fef08a';
                        ctx.strokeStyle = '#ca8a04';
                        ctx.lineWidth = 1;
                        ctx.beginPath();
                        ctx.roundRect(x, y, 28, 28, 4);
                        ctx.fill();
                        ctx.stroke();
                        ctx.font = '16px sans-serif';
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'middle';
                        ctx.fillText('💬', x + 14, y + 14);
                        ctx.restore();
                    } else if (ann.type === 'stamp') {
                        ctx.save();
                        ctx.translate(x + w / 2, y + h / 2);
                        ctx.rotate(-5 * Math.PI / 180); // tilt 5 deg
                        ctx.strokeStyle = ann.color || '#16a34a';
                        ctx.fillStyle = ann.color || '#16a34a';
                        ctx.lineWidth = 2.5;

                        const sw = w;
                        const sh = h;
                        ctx.beginPath();
                        ctx.roundRect(-sw / 2, -sh / 2, sw, sh, 6);
                        ctx.stroke();

                        ctx.beginPath();
                        ctx.roundRect(-sw / 2 + 3, -sh / 2 + 3, sw - 6, sh - 6, 4);
                        ctx.stroke();

                        const stampLabel = ann.preset === 'CUSTOM' ? (ann.customText || 'สำเนาถูกต้อง') :
                            (ann.preset === 'APPROVED' ? 'APPROVED' :
                            (ann.preset === 'DRAFT' ? 'DRAFT' :
                            (ann.preset === 'CONFIDENTIAL' ? 'CONFIDENTIAL' :
                            (ann.preset === 'VERIFIED' ? 'สำเนาถูกต้อง' : ann.preset))));

                        ctx.font = `bold ${Math.max(12, Math.round(sh * 0.35))}px 'Sarabun', sans-serif`;
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'middle';
                        ctx.fillText(stampLabel, 0, -sh * 0.12);

                        if (ann.date) {
                            ctx.font = `${Math.max(8, Math.round(sh * 0.2))}px 'Sarabun', sans-serif`;
                            ctx.fillText(ann.date, 0, sh * 0.25);
                        }
                        ctx.restore();
                    }
                }

                // Embed canvas as PNG
                const pngDataUrl = offCanvas.toDataURL('image/png');
                const pngBytes = Uint8Array.from(atob(pngDataUrl.split(',')[1]), c => c.charCodeAt(0));
                const pngImage = await pdfDoc.embedPng(pngBytes);
                page.drawImage(pngImage, {
                    x: 0,
                    y: 0,
                    width: pw,
                    height: ph
                });
            }

            const outPdfBytes = await pdfDoc.save();
            const blob = new Blob([outPdfBytes], { type: 'application/pdf' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            const originalName = this.pdfFileName.replace(/\.pdf$/i, '');
            a.download = `${originalName}-edited.pdf`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        } catch (err) {
            console.error('Error saving PDF:', err);
            this.exportError = 'เกิดข้อผิดพลาดในการบันทึกเอกสาร: ' + (err.message || '');
            alert(this.exportError);
        } finally {
            this.isExporting = false;
        }
    }
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
