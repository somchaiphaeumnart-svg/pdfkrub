<?php

namespace App\Services;

use App\Models\PdfJob;
use App\Models\UploadedFile;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * PdfProcessingService
 *
 * Orchestrates all PDF tool operations.
 * Each method takes a PdfJob, processes it, saves the output file,
 * and returns the output UploadedFile model.
 */
class PdfProcessingService
{
    public function __construct(
        private readonly LibreOfficeService $libreOffice,
        private readonly OcrService $ocr,
        private readonly AiSummaryService $aiSummary
    ) {}

    /**
     * Dispatch the correct processor based on tool_name.
     *
     * @return UploadedFile The output file record
     */
    public function process(PdfJob $job): UploadedFile
    {
        return match ($job->tool_name) {
            'pdf-to-word' => $this->pdfToOffice($job, 'docx'),
            'pdf-to-excel' => $this->pdfToOffice($job, 'xlsx'),
            'pdf-to-pptx' => $this->pdfToOffice($job, 'pptx'),
            'pdf-to-txt' => $this->pdfToOffice($job, 'txt'),
            'word-to-pdf', 'excel-to-pdf', 'pptx-to-pdf' => $this->officeToPdf($job),
            'merge-pdf' => $this->mergePdf($job),
            'split-pdf' => $this->splitPdf($job),
            'compress-pdf' => $this->compressPdf($job),
            'image-to-pdf' => $this->imageToPdf($job),
            'pdf-to-jpg' => $this->pdfToImages($job, 'jpg'),
            'pdf-to-png' => $this->pdfToImages($job, 'png'),
            'rotate-pdf' => $this->rotatePdf($job),
            'delete-pages' => $this->deletePages($job),
            'watermark-pdf' => $this->watermarkPdf($job),
            'protect-pdf' => $this->protectPdf($job),
            'unlock-pdf' => $this->unlockPdf($job),
            'ocr-pdf' => $this->ocr->process($job),
            'ai-summary' => $this->aiSummary->process($job),
            default => throw new RuntimeException("Unknown tool: {$job->tool_name}"),
        };
    }

    // =========================================================
    // PDF → Office (via LibreOffice)
    // =========================================================

    private function pdfToOffice(PdfJob $job, string $format): UploadedFile
    {
        $inputFile = $this->getInputFile($job);
        $inputPath = Storage::disk($inputFile->storage_disk)->path($inputFile->storage_key);
        $tmpDir = $this->makeTmpDir();

        try {
            $outputPath = $this->libreOffice->convertFromPdf($inputPath, $format, $tmpDir);
            $originalName = pathinfo($inputFile->original_name, PATHINFO_FILENAME).'.'.$format;

            return $this->storeOutput($job, $outputPath, $originalName, $this->mimeFor($format));
        } finally {
            $this->cleanTmpDir($tmpDir);
        }
    }

    // =========================================================
    // Office → PDF (via LibreOffice)
    // =========================================================

    private function officeToPdf(PdfJob $job): UploadedFile
    {
        $inputFile = $this->getInputFile($job);
        $inputPath = Storage::disk($inputFile->storage_disk)->path($inputFile->storage_key);
        $tmpDir = $this->makeTmpDir();

        try {
            $outputPath = $this->libreOffice->convertToPdf($inputPath, $tmpDir);
            $originalName = pathinfo($inputFile->original_name, PATHINFO_FILENAME).'.pdf';

            return $this->storeOutput($job, $outputPath, $originalName, 'application/pdf');
        } finally {
            $this->cleanTmpDir($tmpDir);
        }
    }

    // =========================================================
    // Merge PDF (using Ghostscript)
    // =========================================================

    private function mergePdf(PdfJob $job): UploadedFile
    {
        $inputFiles = UploadedFile::findMany($job->input_file_ids);
        $tmpDir = $this->makeTmpDir();
        $outputPath = $tmpDir.DIRECTORY_SEPARATOR.'merged.pdf';

        // Build absolute paths in the order provided by input_file_ids
        $inputPaths = collect($job->input_file_ids)
            ->map(fn ($id) => $inputFiles->firstWhere('id', $id))
            ->filter()
            ->map(fn ($f) => Storage::disk($f->storage_disk)->path($f->storage_key))
            ->values()
            ->toArray();

        if (count($inputPaths) < 2) {
            throw new RuntimeException('ต้องเลือกอย่างน้อย 2 ไฟล์สำหรับการรวม PDF');
        }

        try {
            $merged = false;
            $lastError = '';

            // 1. Try Ghostscript (gs)
            $gsCheck = Process::run(['which', 'gs']);
            if ($gsCheck->successful()) {
                $result = Process::timeout(120)->run(array_merge(
                    ['gs', '-dBATCH', '-dNOPAUSE', '-q', '-sDEVICE=pdfwrite',
                        '-dPDFSETTINGS=/default', "-sOutputFile={$outputPath}"],
                    $inputPaths
                ));
                if ($result->successful() && file_exists($outputPath) && filesize($outputPath) > 0) {
                    $merged = true;
                } else {
                    $lastError = $result->errorOutput();
                }
            }

            // 2. Try pdfunite (from poppler-utils)
            if (! $merged) {
                $puCheck = Process::run(['which', 'pdfunite']);
                if ($puCheck->successful()) {
                    $result = Process::timeout(120)->run(array_merge(
                        ['pdfunite'],
                        $inputPaths,
                        [$outputPath]
                    ));
                    if ($result->successful() && file_exists($outputPath) && filesize($outputPath) > 0) {
                        $merged = true;
                    } else {
                        $lastError = $result->errorOutput();
                    }
                }
            }

            // 3. Try qpdf
            if (! $merged) {
                $qpdfCheck = Process::run(['which', 'qpdf']);
                if ($qpdfCheck->successful()) {
                    $result = Process::timeout(120)->run(array_merge(
                        ['qpdf', '--empty', '--pages'],
                        $inputPaths,
                        ['--', $outputPath]
                    ));
                    if ($result->successful() && file_exists($outputPath) && filesize($outputPath) > 0) {
                        $merged = true;
                    } else {
                        $lastError = $result->errorOutput();
                    }
                }
            }

            if (! $merged) {
                throw new RuntimeException('ไม่สามารถรวมไฟล์ PDF ได้ กรุณาติดตั้ง ghostscript หรือ poppler-utils บนเซิร์ฟเวอร์ (Error: '.$lastError.')');
            }

            return $this->storeOutput($job, $outputPath, 'merged.pdf', 'application/pdf');
        } finally {
            $this->cleanTmpDir($tmpDir);
        }
    }

    // =========================================================
    // Split PDF (using Ghostscript)
    // =========================================================

    private function splitPdf(PdfJob $job): UploadedFile
    {
        $inputFile = $this->getInputFile($job);
        $inputPath = Storage::disk($inputFile->storage_disk)->path($inputFile->storage_key);
        $config = $job->tool_config ?? [];
        $tmpDir = $this->makeTmpDir();

        // Split into individual pages
        $outputPattern = $tmpDir.DIRECTORY_SEPARATOR.'page_%04d.pdf';

        try {
            $result = Process::timeout(120)->run([
                'gs', '-dBATCH', '-dNOPAUSE', '-q', '-sDEVICE=pdfwrite',
                "-sOutputFile={$outputPattern}", $inputPath,
            ]);

            if (! $result->successful()) {
                throw new RuntimeException('Ghostscript split failed: '.$result->errorOutput());
            }

            // Zip all pages and return the zip
            $pages = glob($tmpDir.DIRECTORY_SEPARATOR.'page_*.pdf');
            sort($pages);

            if (count($pages) === 0) {
                throw new RuntimeException('Split ไม่พบไฟล์ผลลัพธ์');
            }

            // If only 1 page, return it directly
            if (count($pages) === 1) {
                return $this->storeOutput($job, $pages[0], 'page_1.pdf', 'application/pdf');
            }

            // Multiple pages → zip
            $zipPath = $tmpDir.DIRECTORY_SEPARATOR.'pages.zip';
            $zip = new \ZipArchive;
            $zip->open($zipPath, \ZipArchive::CREATE);
            foreach ($pages as $idx => $page) {
                $zip->addFile($page, 'page_'.str_pad($idx + 1, 4, '0', STR_PAD_LEFT).'.pdf');
            }
            $zip->close();

            return $this->storeOutput($job, $zipPath, 'pages.zip', 'application/zip');
        } finally {
            $this->cleanTmpDir($tmpDir);
        }
    }

    // =========================================================
    // Compress PDF (Ghostscript)
    // =========================================================

    private function compressPdf(PdfJob $job): UploadedFile
    {
        $inputFile = $this->getInputFile($job);
        $inputPath = Storage::disk($inputFile->storage_disk)->path($inputFile->storage_key);
        $config = $job->tool_config ?? [];
        $quality = $config['quality'] ?? 'ebook'; // screen|ebook|printer|prepress
        $tmpDir = $this->makeTmpDir();
        $outputPath = $tmpDir.DIRECTORY_SEPARATOR.'compressed.pdf';

        try {
            $result = Process::timeout(120)->run([
                'gs', '-dBATCH', '-dNOPAUSE', '-q', '-sDEVICE=pdfwrite',
                "-dPDFSETTINGS=/{$quality}", '-dCompatibilityLevel=1.4',
                "-sOutputFile={$outputPath}", $inputPath,
            ]);

            if (! $result->successful()) {
                throw new RuntimeException('Ghostscript compress failed: '.$result->errorOutput());
            }

            $basename = pathinfo($inputFile->original_name, PATHINFO_FILENAME);

            return $this->storeOutput($job, $outputPath, "{$basename}_compressed.pdf", 'application/pdf');
        } finally {
            $this->cleanTmpDir($tmpDir);
        }
    }

    // =========================================================
    // Image → PDF (using Ghostscript / convert)
    // =========================================================

    private function imageToPdf(PdfJob $job): UploadedFile
    {
        $inputFiles = UploadedFile::findMany($job->input_file_ids);
        $tmpDir = $this->makeTmpDir();
        $outputPath = $tmpDir.DIRECTORY_SEPARATOR.'output.pdf';

        $inputPaths = collect($job->input_file_ids)
            ->map(fn ($id) => $inputFiles->firstWhere('id', $id))
            ->filter()
            ->map(fn ($f) => Storage::disk($f->storage_disk)->path($f->storage_key))
            ->values()
            ->toArray();

        if (empty($inputPaths)) {
            throw new RuntimeException('ไม่พบไฟล์รูปภาพที่ต้องการแปลง');
        }

        try {
            $converted = false;
            $scriptPath = base_path('scripts/image_to_pdf.py');
            $pythonCmd = file_exists('/opt/pdf2docx-env/bin/python3')
                ? '/opt/pdf2docx-env/bin/python3'
                : 'python3';

            // 1. Try Python image_to_pdf script (Pillow / PyMuPDF / img2pdf / pure-python)
            if (file_exists($scriptPath)) {
                $pyResult = Process::timeout(120)->run(array_merge(
                    [$pythonCmd, $scriptPath, $outputPath],
                    $inputPaths
                ));

                if ($pyResult->successful() && file_exists($outputPath) && filesize($outputPath) > 0) {
                    $converted = true;
                } elseif ($pythonCmd !== 'python3') {
                    $sysResult = Process::timeout(120)->run(array_merge(
                        ['python3', $scriptPath, $outputPath],
                        $inputPaths
                    ));
                    if ($sysResult->successful() && file_exists($outputPath) && filesize($outputPath) > 0) {
                        $converted = true;
                    }
                }
            }

            // 2. Try ImageMagick CLI (convert / magick / gm)
            if (! $converted) {
                foreach (['magick', 'convert', 'gm'] as $bin) {
                    $which = Process::run(['which', $bin]);
                    if ($which->successful()) {
                        $cmd = $bin === 'gm' ? ['gm', 'convert'] : [$bin];
                        $cmd = array_merge($cmd, $inputPaths, [$outputPath]);
                        $imRes = Process::timeout(120)->run($cmd);
                        if ($imRes->successful() && file_exists($outputPath) && filesize($outputPath) > 0) {
                            $converted = true;
                            break;
                        }
                    }
                }
            }

            // 3. Try LibreOffice Writer fallback
            if (! $converted && $this->libreOffice->isAvailable()) {
                try {
                    $loPdf = $this->libreOffice->convertToPdf($inputPaths[0], $tmpDir);
                    if (file_exists($loPdf) && filesize($loPdf) > 0) {
                        $outputPath = $loPdf;
                        $converted = true;
                    }
                } catch (\Throwable $e) {
                    // ignore
                }
            }

            if (! $converted || ! file_exists($outputPath) || filesize($outputPath) === 0) {
                throw new RuntimeException('ไม่สามารถแปลงรูปภาพเป็น PDF ได้ กรุณาลองใหม่อีกครั้ง');
            }

            $firstFile = $inputFiles->first();
            $outName = (count($inputPaths) === 1 && $firstFile)
                ? pathinfo($firstFile->original_name, PATHINFO_FILENAME).'.pdf'
                : 'images.pdf';

            return $this->storeOutput($job, $outputPath, $outName, 'application/pdf');
        } finally {
            $this->cleanTmpDir($tmpDir);
        }
    }

    // =========================================================
    // PDF → Images (Ghostscript)
    // =========================================================

    private function pdfToImages(PdfJob $job, string $format): UploadedFile
    {
        $inputFile = $this->getInputFile($job);
        $inputPath = Storage::disk($inputFile->storage_disk)->path($inputFile->storage_key);
        $tmpDir = $this->makeTmpDir();
        $device = $format === 'png' ? 'pngalpha' : 'jpeg';
        $outputPattern = $tmpDir.DIRECTORY_SEPARATOR."page_%04d.{$format}";

        try {
            $result = Process::timeout(120)->run([
                'gs', '-dBATCH', '-dNOPAUSE', '-q',
                "-sDEVICE={$device}", '-r150',
                "-sOutputFile={$outputPattern}", $inputPath,
            ]);

            if (! $result->successful()) {
                throw new RuntimeException("PDF to {$format} failed: ".$result->errorOutput());
            }

            $images = glob($tmpDir.DIRECTORY_SEPARATOR."page_*.{$format}");
            sort($images);

            if (count($images) === 0) {
                throw new RuntimeException('No images generated from PDF');
            }

            if (count($images) === 1) {
                return $this->storeOutput($job, $images[0], "page_1.{$format}", "image/{$format}");
            }

            // Multiple pages → zip
            $zipPath = $tmpDir.DIRECTORY_SEPARATOR."images_{$format}.zip";
            $zip = new \ZipArchive;
            $zip->open($zipPath, \ZipArchive::CREATE);
            foreach ($images as $idx => $img) {
                $zip->addFile($img, 'page_'.str_pad($idx + 1, 4, '0', STR_PAD_LEFT).'.'.$format);
            }
            $zip->close();

            return $this->storeOutput($job, $zipPath, "images_{$format}.zip", 'application/zip');
        } finally {
            $this->cleanTmpDir($tmpDir);
        }
    }

    // =========================================================
    // Rotate PDF (Ghostscript)
    // =========================================================

    private function rotatePdf(PdfJob $job): UploadedFile
    {
        $inputFile = $this->getInputFile($job);
        $inputPath = Storage::disk($inputFile->storage_disk)->path($inputFile->storage_key);
        $config = $job->tool_config ?? [];
        $degrees = (int) ($config['degrees'] ?? 90);
        $tmpDir = $this->makeTmpDir();
        $outputPath = $tmpDir.DIRECTORY_SEPARATOR.'rotated.pdf';

        try {
            $rotated = false;
            $scriptPath = base_path('scripts/rotate_pdf.py');
            $pythonCmd = file_exists('/opt/pdf2docx-env/bin/python3')
                ? '/opt/pdf2docx-env/bin/python3'
                : 'python3';

            // 1. Try Python script (PyMuPDF / pypdf)
            if (file_exists($scriptPath)) {
                $pyResult = Process::timeout(60)->run([
                    $pythonCmd,
                    $scriptPath,
                    $inputPath,
                    $outputPath,
                    (string) $degrees,
                ]);

                if ($pyResult->successful() && file_exists($outputPath) && filesize($outputPath) > 0) {
                    $rotated = true;
                } elseif ($pythonCmd !== 'python3') {
                    $sysResult = Process::timeout(60)->run([
                        'python3',
                        $scriptPath,
                        $inputPath,
                        $outputPath,
                        (string) $degrees,
                    ]);
                    if ($sysResult->successful() && file_exists($outputPath) && filesize($outputPath) > 0) {
                        $rotated = true;
                    }
                }
            }

            // 2. Try qpdf CLI
            if (! $rotated) {
                $sign = $degrees >= 0 ? '+' : '-';
                $degVal = abs($degrees) % 360;
                $qpdfCheck = Process::run(['which', 'qpdf']);
                if ($qpdfCheck->successful()) {
                    $res = Process::timeout(60)->run([
                        'qpdf',
                        "--rotate={$sign}{$degVal}",
                        $inputPath,
                        $outputPath,
                    ]);
                    if ($res->successful() && file_exists($outputPath) && filesize($outputPath) > 0) {
                        $rotated = true;
                    }
                }
            }

            if (! $rotated || ! file_exists($outputPath) || filesize($outputPath) === 0) {
                throw new RuntimeException('ไม่สามารถหมุนหน้าเอกสาร PDF ได้ กรุณาลองใหม่อีกครั้ง');
            }

            $basename = pathinfo($inputFile->original_name, PATHINFO_FILENAME);

            return $this->storeOutput($job, $outputPath, "{$basename}_rotated.pdf", 'application/pdf');
        } finally {
            $this->cleanTmpDir($tmpDir);
        }
    }

    // =========================================================
    // Delete PDF Pages
    // =========================================================

    private function deletePages(PdfJob $job): UploadedFile
    {
        $inputFile = $this->getInputFile($job);
        $inputPath = Storage::disk($inputFile->storage_disk)->path($inputFile->storage_key);

        $config = $job->tool_config ?? [];
        $pagesToDelete = $config['pages_to_delete'] ?? '';
        if (is_array($pagesToDelete)) {
            $pagesToDelete = implode(',', $pagesToDelete);
        }
        $pagesToDelete = trim((string) $pagesToDelete);

        if (empty($pagesToDelete)) {
            throw new RuntimeException('กรุณาระบุหน้าที่ต้องการลบ');
        }

        $tmpDir = $this->makeTmpDir();
        $outputPath = $tmpDir.DIRECTORY_SEPARATOR.'deleted_pages.pdf';

        try {
            $deleted = false;
            $scriptPath = base_path('scripts/delete_pdf_pages.py');
            $pythonCmd = file_exists('/opt/pdf2docx-env/bin/python3')
                ? '/opt/pdf2docx-env/bin/python3'
                : 'python3';

            if (file_exists($scriptPath)) {
                $pyResult = Process::timeout(60)->run([
                    $pythonCmd,
                    $scriptPath,
                    $inputPath,
                    $outputPath,
                    $pagesToDelete,
                ]);

                if ($pyResult->successful() && file_exists($outputPath) && filesize($outputPath) > 0) {
                    $deleted = true;
                } elseif ($pythonCmd !== 'python3') {
                    $sysResult = Process::timeout(60)->run([
                        'python3',
                        $scriptPath,
                        $inputPath,
                        $outputPath,
                        $pagesToDelete,
                    ]);
                    if ($sysResult->successful() && file_exists($outputPath) && filesize($outputPath) > 0) {
                        $deleted = true;
                    }
                }
            }

            if (! $deleted || ! file_exists($outputPath) || filesize($outputPath) === 0) {
                throw new RuntimeException('ไม่สามารถลบหน้าเอกสาร PDF ได้ กรุณาลองใหม่อีกครั้ง');
            }

            $basename = pathinfo($inputFile->original_name, PATHINFO_FILENAME);

            return $this->storeOutput($job, $outputPath, "{$basename}_edited.pdf", 'application/pdf');
        } finally {
            $this->cleanTmpDir($tmpDir);
        }
    }

    // =========================================================
    // Watermark PDF (Ghostscript)
    // =========================================================

    private function watermarkPdf(PdfJob $job): UploadedFile
    {
        $inputFile = $this->getInputFile($job);
        $inputPath = Storage::disk($inputFile->storage_disk)->path($inputFile->storage_key);
        $config = $job->tool_config ?? [];
        $tmpDir = $this->makeTmpDir();
        $outputPath = $tmpDir.DIRECTORY_SEPARATOR.'watermarked.pdf';

        // Prepare image if uploaded as file or sent as base64 dataUrl
        $imagePath = null;
        if (! empty($config['watermark_image_path'])) {
            $imagePath = Storage::disk('local')->path($config['watermark_image_path']);
        } elseif (! empty($config['image_path'])) {
            $imagePath = Storage::disk('local')->path($config['image_path']);
        } elseif (! empty($config['watermark_image_data']) && str_starts_with($config['watermark_image_data'], 'data:image')) {
            $data = $config['watermark_image_data'];
            $commaPos = strpos($data, ',');
            if ($commaPos !== false) {
                $binary = base64_decode(substr($data, $commaPos + 1));
                $imagePath = $tmpDir.DIRECTORY_SEPARATOR.'wm_img.png';
                file_put_contents($imagePath, $binary);
            }
        }

        $wmConfig = [
            'type' => (! empty($imagePath) && file_exists($imagePath)) ? 'image' : ($config['type'] ?? 'text'),
            'image_path' => $imagePath,
            'text' => $config['text'] ?? 'WATERMARK',
            'opacity' => floatval($config['opacity'] ?? 0.35),
            'scale' => floatval($config['scale'] ?? 0.4),
            'position' => $config['position'] ?? 'center',
            'rotation' => floatval($config['rotation'] ?? 0),
            'pages' => $config['pages'] ?? 'all',
            'color' => $config['color'] ?? '#dc2626',
        ];

        $configPath = $tmpDir.DIRECTORY_SEPARATOR.'wm_config.json';
        file_put_contents($configPath, json_encode($wmConfig, JSON_UNESCAPED_UNICODE));

        try {
            $applied = false;
            $scriptPath = base_path('scripts/watermark_pdf.py');
            $pythonCmd = file_exists('/opt/pdf2docx-env/bin/python3')
                ? '/opt/pdf2docx-env/bin/python3'
                : 'python3';

            if (file_exists($scriptPath)) {
                $pyResult = Process::timeout(60)->run([
                    $pythonCmd,
                    $scriptPath,
                    $inputPath,
                    $outputPath,
                    $configPath,
                ]);

                if ($pyResult->successful() && file_exists($outputPath) && filesize($outputPath) > 0) {
                    $applied = true;
                } elseif ($pythonCmd !== 'python3') {
                    $sysResult = Process::timeout(60)->run([
                        'python3',
                        $scriptPath,
                        $inputPath,
                        $outputPath,
                        $configPath,
                    ]);
                    if ($sysResult->successful() && file_exists($outputPath) && filesize($outputPath) > 0) {
                        $applied = true;
                    }
                }
            }

            if (! $applied || ! file_exists($outputPath) || filesize($outputPath) === 0) {
                throw new RuntimeException('ไม่สามารถใส่ลายน้ำในเอกสาร PDF ได้ กรุณาลองใหม่อีกครั้ง');
            }

            $basename = pathinfo($inputFile->original_name, PATHINFO_FILENAME);

            return $this->storeOutput($job, $outputPath, "{$basename}_watermarked.pdf", 'application/pdf');
        } finally {
            $this->cleanTmpDir($tmpDir);
        }
    }

    // =========================================================
    // Protect PDF (add password via Ghostscript)
    // =========================================================

    private function protectPdf(PdfJob $job): UploadedFile
    {
        $inputFile = $this->getInputFile($job);
        $inputPath = Storage::disk($inputFile->storage_disk)->path($inputFile->storage_key);
        $config = $job->tool_config ?? [];
        $userPassword = $config['password'] ?? '';
        $tmpDir = $this->makeTmpDir();
        $outputPath = $tmpDir.DIRECTORY_SEPARATOR.'protected.pdf';

        try {
            $result = Process::timeout(60)->run([
                'gs', '-dBATCH', '-dNOPAUSE', '-q', '-sDEVICE=pdfwrite',
                '-dEncryptionR=3', '-dKeyLength=128',
                "-sUserPassword={$userPassword}",
                "-sOutputFile={$outputPath}", $inputPath,
            ]);

            if (! $result->successful()) {
                throw new RuntimeException('Protect failed: '.$result->errorOutput());
            }

            $basename = pathinfo($inputFile->original_name, PATHINFO_FILENAME);

            return $this->storeOutput($job, $outputPath, "{$basename}_protected.pdf", 'application/pdf');
        } finally {
            $this->cleanTmpDir($tmpDir);
        }
    }

    // =========================================================
    // Unlock PDF (remove password via Ghostscript)
    // =========================================================

    private function unlockPdf(PdfJob $job): UploadedFile
    {
        $inputFile = $this->getInputFile($job);
        $inputPath = Storage::disk($inputFile->storage_disk)->path($inputFile->storage_key);
        $config = $job->tool_config ?? [];
        $password = $config['password'] ?? '';
        $tmpDir = $this->makeTmpDir();
        $outputPath = $tmpDir.DIRECTORY_SEPARATOR.'unlocked.pdf';

        try {
            $result = Process::timeout(60)->run([
                'gs', '-dBATCH', '-dNOPAUSE', '-q', '-sDEVICE=pdfwrite',
                "-sPDFPassword={$password}",
                "-sOutputFile={$outputPath}", $inputPath,
            ]);

            if (! $result->successful()) {
                throw new RuntimeException('Unlock failed — รหัสผ่านไม่ถูกต้อง หรือไฟล์ไม่ได้มีรหัสผ่าน');
            }

            $basename = pathinfo($inputFile->original_name, PATHINFO_FILENAME);

            return $this->storeOutput($job, $outputPath, "{$basename}_unlocked.pdf", 'application/pdf');
        } finally {
            $this->cleanTmpDir($tmpDir);
        }
    }

    // =========================================================
    // Helpers
    // =========================================================

    private function getInputFile(PdfJob $job): UploadedFile
    {
        $fileId = $job->input_file_ids[0] ?? null;

        if (! $fileId) {
            throw new RuntimeException('No input files provided');
        }

        $file = UploadedFile::find($fileId);

        if (! $file) {
            throw new RuntimeException("Input file not found: {$fileId}");
        }

        return $file;
    }

    /**
     * Store the output file in the same storage disk, create an UploadedFile record.
     */
    private function storeOutput(PdfJob $job, string $localPath, string $originalName, string $mimeType): UploadedFile
    {
        $storageKey = 'outputs/'.date('Y/m').'/'.Str::ulid().'/'.$originalName;
        $disk = 'local';

        // Determine retention period based on user's plan
        $user = $job->user;
        $retentionHours = $user?->getActivePlan()->file_retention_hours ?? 2;
        $expiresAt = now()->addHours($retentionHours);

        // Store file
        Storage::disk($disk)->put($storageKey, file_get_contents($localPath));

        $fileSize = filesize($localPath);

        // Update user storage usage
        if ($user) {
            $user->increment('storage_used', $fileSize);
        }

        return UploadedFile::create([
            'user_id' => $job->user_id,
            'session_id' => $job->session_id,
            'original_name' => $originalName,
            'storage_key' => $storageKey,
            'storage_disk' => $disk,
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
            'file_hash' => hash_file('sha256', $localPath),
            'expires_at' => $expiresAt,
        ]);
    }

    private function makeTmpDir(): string
    {
        $tmpDir = storage_path('app/tmp/'.Str::ulid());
        mkdir($tmpDir, 0755, true);

        return $tmpDir;
    }

    private function cleanTmpDir(string $tmpDir): void
    {
        if (is_dir($tmpDir)) {
            \Illuminate\Support\Facades\File::deleteDirectory($tmpDir);
        }
    }

    private function mimeFor(string $format): string
    {
        return match ($format) {
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'txt' => 'text/plain',
            'zip' => 'application/zip',
            default => 'application/octet-stream',
        };
    }
}
