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

    // Processing state
    isUploading: false,
    uploadProgress: 0,
    jobId: null,
    jobStatus: null,      // queued|processing|done|failed
    jobProgress: 0,
    downloadUrl: null,
    downloadFileName: null,
    downloadFileSize: null,
    errorMessage: null,
    pollTimer: null,

    async init() {
        // If on a tool page, auto-restore staged files from the homepage
        if (!this.isHome) {
            await this.loadStagedFiles();
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
    },

    clearAll() {
        this.files = [];
        this.error = null;
        this.reset();
        clearStagedFiles();
    },

    reset() {
        this.jobId = null;
        this.jobStatus = null;
        this.jobProgress = 0;
        this.downloadUrl = null;
        this.downloadFileName = null;
        this.errorMessage = null;
        this.isUploading = false;
        if (this.pollTimer) clearInterval(this.pollTimer);
    },

    // Upload files and start processing
    async startConversion(toolName) {
        if (!this.hasFiles || this.isUploading) return;

        this.isUploading = true;
        this.error = null;

        const formData = new FormData();
        this.files.forEach(f => formData.append('files[]', f.file));
        formData.append('tool', toolName || this.tool || 'unknown');
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

        try {
            const response = await fetch('/files/upload', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData,
            });

            let data = {};
            const contentType = response.headers.get('content-type') || '';
            if (contentType.includes('application/json')) {
                data = await response.json();
            } else {
                if (response.status === 413) {
                    throw new Error('ไฟล์มีขนาดรวมใหญ่เกินกว่าที่เซิร์ฟเวอร์รองรับ (เกินขีดจำกัด)');
                }
                throw new Error(`เกิดข้อผิดพลาด (${response.status}) กรุณาลองใหม่อีกครั้ง`);
            }

            if (!response.ok) {
                let msg = data.error || data.message;
                if (!msg && data.errors) {
                    msg = Object.values(data.errors).flat().join(' | ');
                }
                if (msg && msg.toLowerCase().includes('post data is too large')) {
                    msg = 'ไฟล์ที่อัปโหลดมีขนาดรวมใหญ่เกินกว่าที่เซิร์ฟเวอร์กำหนด (กรุณาลดขนาดไฟล์ หรือแบ่งรวมทีละชุด)';
                } else if (msg && msg.toLowerCase().includes('upload failed')) {
                    msg = 'อัปโหลดไฟล์ไม่สำเร็จ กรุณาลองใหม่อีกครั้ง';
                }
                throw new Error(msg || `การอัปโหลดขัดข้อง (${response.status})`);
            }

            this.jobId = data.job_id;
            this.jobStatus = data.status;

            if (data.status === 'done' && data.download_url) {
                this.jobProgress = 100;
                this.isUploading = false;
                this.downloadUrl = data.download_url;
                this.downloadFileName = data.file_name || 'merged.pdf';
                this.downloadFileSize = data.file_size || '';
            } else {
                this.startPolling(data.status_url);
            }
        } catch (err) {
            this.error = err.message;
            this.isUploading = false;
        }
    },

    startPolling(statusUrl) {
        this.jobProgress = 20;
        let pollCount = 0;
        this.pollTimer = setInterval(async () => {
            try {
                pollCount++;
                if (pollCount > 45) { // 45 * 1.2s ≈ 50 seconds timeout
                    clearInterval(this.pollTimer);
                    this.isUploading = false;
                    this.jobStatus = 'failed';
                    this.errorMessage = 'การประมวลผลใช้เวลานานเกินไป กรุณากดปุ่ม "ลองใหม่อีกครั้ง" ด้านล่าง';
                    return;
                }

                if (this.jobProgress < 90) {
                    this.jobProgress += Math.floor(Math.random() * 8) + 6;
                    if (this.jobProgress > 90) this.jobProgress = 90;
                }

                const res = await fetch(statusUrl, {
                    headers: { 'Accept': 'application/json' }
                });

                if (!res.ok) {
                    if (res.status === 403) {
                        clearInterval(this.pollTimer);
                        this.isUploading = false;
                        this.jobStatus = 'failed';
                        this.errorMessage = 'เซสชันหมดอายุ กรุณารีเฟรชหน้าเว็บแล้วลองใหม่อีกครั้ง';
                    }
                    return;
                }

                const data = await res.json();

                this.jobStatus = data.status;
                if (data.progress && data.progress > this.jobProgress) {
                    this.jobProgress = data.progress;
                }

                if (data.status === 'done') {
                    this.jobProgress = 100;
                    clearInterval(this.pollTimer);
                    this.isUploading = false;
                    this.downloadUrl = data.download_url;
                    this.downloadFileName = data.file_name;
                    this.downloadFileSize = data.file_size;
                } else if (data.status === 'failed') {
                    clearInterval(this.pollTimer);
                    this.isUploading = false;
                    this.errorMessage = data.error_message || 'เกิดข้อผิดพลาดในการประมวลผล';
                }
            } catch (e) {
                console.error('Poll error:', e);
            }
        }, 1200);
    },

    formatSize(bytes) {
        const units = ['B', 'KB', 'MB', 'GB'];
        let size = bytes, unit = 0;
        while (size >= 1024 && unit < units.length - 1) { size /= 1024; unit++; }
        return `${size.toFixed(1)} ${units[unit]}`;
    },

    get hasFiles() { return this.files.length > 0; },
    get totalSize() { return this.formatSize(this.files.reduce((s, f) => s + f.file.size, 0)); },
    get isProcessing() { return this.isUploading || ['queued', 'processing'].includes(this.jobStatus); },
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
Alpine.start();
