<?php

namespace App\Services;

use App\Models\PdfJob;
use App\Models\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * OcrService
 *
 * Two OCR backends:
 *  1. Google Cloud Vision API (Premium – Pro plan)
 *  2. Tesseract CLI (Free fallback – basic accuracy)
 */
class OcrService
{
    public function __construct() {}

    /**
     * Run OCR on a PdfJob and return the output UploadedFile.
     */
    public function process(PdfJob $job): UploadedFile
    {
        $user = $job->user;
        $plan = $user?->getActivePlan();

        // Choose backend based on plan and credentials
        if ($plan?->has_ocr && config('services.google_vision.credentials')) {
            try {
                return $this->processWithGoogleVision($job);
            } catch (\Throwable $e) {
                Log::warning('Google Vision OCR failed, falling back to Tesseract: '.$e->getMessage(), [
                    'job_id' => $job->id,
                ]);
            }
        }

        return $this->processWithTesseract($job);
    }

    // ─────────────────────────────────────────────────────────────
    // Google Cloud Vision API
    // ─────────────────────────────────────────────────────────────

    private function processWithGoogleVision(PdfJob $job): UploadedFile
    {
        $inputFile = $this->getInputFile($job);
        $config = $job->tool_config ?? [];
        $rawLangs = $this->normalizeLanguages($config['languages'] ?? null, ['th', 'en']);
        $languages = $this->mapToGoogleVision($rawLangs);
        $tmpDir = $this->makeTmpDir();

        try {
            $inputPath = Storage::disk($inputFile->storage_disk)->path($inputFile->storage_key);

            // If PDF, convert to images first
            $imagePaths = $inputFile->isPdf()
                ? $this->pdfToImages($inputPath, $tmpDir)
                : [$inputPath];

            $fullText = '';
            $totalConfidence = 0;
            $pageCount = 0;

            foreach ($imagePaths as $imagePath) {
                ['text' => $text, 'confidence' => $conf] = $this->callGoogleVisionApi($imagePath, $languages);
                $fullText .= $text."\n\n";
                $totalConfidence += $conf;
                $pageCount++;
            }

            $avgConfidence = $pageCount > 0 ? round($totalConfidence / $pageCount) : 0;
            $fullText = trim($fullText);

            Log::info('OCR (Google Vision) complete', [
                'job_id' => $job->id,
                'pages' => $pageCount,
                'avg_confidence' => $avgConfidence,
                'char_count' => strlen($fullText),
            ]);

            return $this->storeTextResult($job, $fullText, $inputFile, ['engine' => 'google_vision', 'confidence' => $avgConfidence]);
        } finally {
            $this->cleanTmpDir($tmpDir);
        }
    }

    private function callGoogleVisionApi(string $imagePath, array $languages): array
    {
        $imageData = base64_encode(file_get_contents($imagePath));
        $apiKey = config('services.google_vision.credentials');

        $response = Http::timeout(45)
            ->post("https://vision.googleapis.com/v1/images:annotate?key={$apiKey}", [
                'requests' => [[
                    'image' => ['content' => $imageData],
                    'features' => [['type' => 'DOCUMENT_TEXT_DETECTION', 'maxResults' => 1]],
                    'imageContext' => [
                        'languageHints' => $languages,
                    ],
                ]],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Google Vision API error: '.$response->body());
        }

        $data = $response->json();
        $annotation = $data['responses'][0]['fullTextAnnotation'] ?? null;

        if (! $annotation) {
            return ['text' => '', 'confidence' => 0];
        }

        $text = $annotation['text'] ?? '';
        $confidence = 0;
        if (isset($annotation['pages'])) {
            $confs = collect($annotation['pages'])
                ->flatMap(fn ($page) => collect($page['blocks'] ?? []))
                ->pluck('confidence')
                ->filter();
            $confidence = $confs->isNotEmpty() ? round($confs->avg() * 100) : 95;
        }

        return ['text' => $text, 'confidence' => $confidence];
    }

    // ─────────────────────────────────────────────────────────────
    // Tesseract (Free Fallback)
    // ─────────────────────────────────────────────────────────────

    private function processWithTesseract(PdfJob $job): UploadedFile
    {
        $inputFile = $this->getInputFile($job);
        $config = $job->tool_config ?? [];
        $rawLangs = $this->normalizeLanguages($config['languages'] ?? null, ['th', 'en']);
        $tessLangs = $this->mapToTesseract($rawLangs);

        $available = $this->getAvailableTesseractLanguages();
        $targetLangs = ! empty($available) ? array_values(array_intersect($tessLangs, $available)) : $tessLangs;
        if (empty($targetLangs)) {
            $targetLangs = in_array('tha', $available) ? ['tha'] : (in_array('eng', $available) ? ['eng'] : ['eng']);
        }
        $langStr = implode('+', $targetLangs);

        $tmpDir = $this->makeTmpDir();

        try {
            $inputPath = Storage::disk($inputFile->storage_disk)->path($inputFile->storage_key);
            $imagePaths = $inputFile->isPdf()
                ? $this->pdfToImages($inputPath, $tmpDir)
                : [$inputPath];

            $fullText = '';

            foreach ($imagePaths as $imagePath) {
                $outputBase = $tmpDir.DIRECTORY_SEPARATOR.'page_'.uniqid();

                $result = Process::timeout(180)->run([
                    'tesseract', $imagePath, $outputBase,
                    '-l', $langStr,
                    '--psm', '3',
                    'txt',
                ]);

                // If combination failed, fallback to single language (tha or eng)
                if (! $result->successful() && ! file_exists($outputBase.'.txt')) {
                    Log::warning("Tesseract failed with -l {$langStr}, falling back", [
                        'stderr' => $result->errorOutput(),
                    ]);
                    $fallbackLang = in_array('tha', $available) ? 'tha' : 'eng';
                    $result = Process::timeout(180)->run([
                        'tesseract', $imagePath, $outputBase,
                        '-l', $fallbackLang,
                        '--psm', '3',
                        'txt',
                    ]);
                }

                if (file_exists($outputBase.'.txt')) {
                    $pageText = file_get_contents($outputBase.'.txt');
                    $fullText .= $pageText."\n\n";
                }
            }

            $trimmed = trim($fullText);
            if (empty($trimmed)) {
                $trimmed = "ไม่พบข้อความที่สามารถตรวจจับได้ในไฟล์นี้ หรือความละเอียดของรูปภาพต่ำเกินไป";
            }

            Log::info('OCR (Tesseract) complete', [
                'job_id' => $job->id,
                'pages' => count($imagePaths),
                'char_count' => strlen($trimmed),
                'languages' => $langStr,
            ]);

            return $this->storeTextResult($job, $trimmed, $inputFile, ['engine' => 'tesseract', 'languages' => $langStr]);
        } finally {
            $this->cleanTmpDir($tmpDir);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Language Helpers
    // ─────────────────────────────────────────────────────────────

    /**
     * Normalize languages to array of codes from various formats (array, JSON string, comma-separated string).
     */
    private function normalizeLanguages(mixed $languages, array $default = ['th', 'en']): array
    {
        if (is_null($languages) || $languages === '') {
            return $default;
        }

        if (is_string($languages)) {
            $decoded = json_decode($languages, true);
            if (is_array($decoded)) {
                $languages = $decoded;
            } else {
                $languages = array_map('trim', explode(',', $languages));
            }
        }

        if (! is_array($languages)) {
            return $default;
        }

        $clean = array_values(array_filter($languages, fn ($l) => is_string($l) && trim($l) !== ''));

        return empty($clean) ? $default : $clean;
    }

    /**
     * Map language codes to Tesseract 3-letter codes.
     */
    private function mapToTesseract(array $languages): array
    {
        $map = [
            'th' => 'tha',
            'tha' => 'tha',
            'en' => 'eng',
            'eng' => 'eng',
            'zh' => 'chi_sim',
            'cn' => 'chi_sim',
            'chi' => 'chi_sim',
            'chi_sim' => 'chi_sim',
            'ja' => 'jpn',
            'jp' => 'jpn',
            'jpn' => 'jpn',
            'ko' => 'kor',
            'kr' => 'kor',
            'kor' => 'kor',
            'ar' => 'ara',
            'sa' => 'ara',
            'ara' => 'ara',
        ];

        $result = [];
        foreach ($languages as $lang) {
            $lower = strtolower(trim($lang));
            $result[] = $map[$lower] ?? $lower;
        }

        return array_values(array_unique($result ?: ['tha', 'eng']));
    }

    /**
     * Map language codes to Google Vision language hints (ISO 639-1).
     */
    private function mapToGoogleVision(array $languages): array
    {
        $map = [
            'tha' => 'th',
            'th' => 'th',
            'eng' => 'en',
            'en' => 'en',
            'chi_sim' => 'zh',
            'chi' => 'zh',
            'cn' => 'zh',
            'zh' => 'zh',
            'jpn' => 'ja',
            'jp' => 'ja',
            'ja' => 'ja',
            'kor' => 'ko',
            'kr' => 'ko',
            'ko' => 'ko',
            'ara' => 'ar',
            'sa' => 'ar',
            'ar' => 'ar',
        ];

        $result = [];
        foreach ($languages as $lang) {
            $lower = strtolower(trim($lang));
            $result[] = $map[$lower] ?? $lower;
        }

        return array_values(array_unique($result ?: ['th', 'en']));
    }

    /**
     * Get installed language codes on the server's Tesseract.
     */
    private function getAvailableTesseractLanguages(): array
    {
        try {
            $res = Process::timeout(5)->run(['tesseract', '--list-langs']);
            if ($res->successful()) {
                $lines = explode("\n", trim($res->output()));
                $langs = [];
                foreach ($lines as $line) {
                    $l = trim($line);
                    if ($l && ! str_contains($l, 'List of available') && $l !== 'osd') {
                        $langs[] = $l;
                    }
                }
                if (! empty($langs)) {
                    return $langs;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Unable to list tesseract langs: '.$e->getMessage());
        }

        return ['tha', 'eng'];
    }

    // ─────────────────────────────────────────────────────────────
    // Conversion & File Output
    // ─────────────────────────────────────────────────────────────

    private function pdfToImages(string $pdfPath, string $outputDir): array
    {
        // Use png16m (RGB without alpha) to ensure clean background for OCR
        $result = Process::timeout(180)->run([
            'gs', '-dBATCH', '-dNOPAUSE', '-q',
            '-sDEVICE=png16m', '-r200',
            "-sOutputFile={$outputDir}/page_%04d.png",
            $pdfPath,
        ]);

        if (! $result->successful()) {
            throw new RuntimeException('PDF to image conversion failed: '.$result->errorOutput());
        }

        $images = glob($outputDir.DIRECTORY_SEPARATOR.'page_*.png');
        sort($images);

        return $images;
    }

    private function storeTextResult(PdfJob $job, string $text, UploadedFile $inputFile, array $meta = []): UploadedFile
    {
        $tmpDir = $this->makeTmpDir();
        $basename = pathinfo($inputFile->original_name, PATHINFO_FILENAME);
        $config = $job->tool_config ?? [];
        $format = strtolower($config['format'] ?? 'txt');

        // Store extracted text in metadata for direct API retrieval
        $meta['extracted_text'] = mb_substr($text, 0, 20000);

        $txtFile = $tmpDir.DIRECTORY_SEPARATOR.$basename.'_ocr.txt';
        file_put_contents($txtFile, $text);

        $outputPath = $txtFile;
        $outputName = $basename.'_ocr.txt';
        $mime = 'text/plain';

        if ($format === 'docx') {
            $docxFile = $tmpDir.DIRECTORY_SEPARATOR.$basename.'_ocr.docx';
            try {
                $res = Process::timeout(60)->run([
                    config('pdf2word.libreoffice_path', 'soffice'),
                    '--headless', '--norestore',
                    '--convert-to', 'docx',
                    '--outdir', $tmpDir,
                    $txtFile,
                ]);
                if ($res->successful() && file_exists($docxFile)) {
                    $outputPath = $docxFile;
                    $outputName = $basename.'_ocr.docx';
                    $mime = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
                }
            } catch (\Throwable $e) {
                Log::warning('Converting OCR text to docx failed, falling back to txt: '.$e->getMessage());
            }
        } elseif ($format === 'pdf') {
            $pdfFile = $tmpDir.DIRECTORY_SEPARATOR.$basename.'_ocr.pdf';
            try {
                $res = Process::timeout(60)->run([
                    config('pdf2word.libreoffice_path', 'soffice'),
                    '--headless', '--norestore',
                    '--convert-to', 'pdf',
                    '--outdir', $tmpDir,
                    $txtFile,
                ]);
                if ($res->successful() && file_exists($pdfFile)) {
                    $outputPath = $pdfFile;
                    $outputName = $basename.'_ocr.pdf';
                    $mime = 'application/pdf';
                }
            } catch (\Throwable $e) {
                Log::warning('Converting OCR text to pdf failed, falling back to txt: '.$e->getMessage());
            }
        }

        $storageKey = 'outputs/'.date('Y/m').'/'.Str::ulid().'/'.$outputName;
        $disk = 'local';
        $retentionHours = $job->user?->getActivePlan()?->file_retention_hours ?? 2;
        $expiresAt = now()->addHours($retentionHours);
        $fileSize = filesize($outputPath);

        Storage::disk($disk)->put($storageKey, file_get_contents($outputPath));

        if ($job->user) {
            $job->user->increment('storage_used', $fileSize);
        }

        $file = UploadedFile::create([
            'user_id' => $job->user_id,
            'session_id' => $job->session_id,
            'original_name' => $outputName,
            'storage_key' => $storageKey,
            'storage_disk' => $disk,
            'file_size' => $fileSize,
            'mime_type' => $mime,
            'file_hash' => hash('sha256', $text),
            'metadata' => $meta,
            'expires_at' => $expiresAt,
        ]);

        $this->cleanTmpDir($tmpDir);

        return $file;
    }

    private function getInputFile(PdfJob $job): UploadedFile
    {
        $fileId = $job->input_file_ids[0] ?? null;

        if (! $fileId || ! ($file = UploadedFile::find($fileId))) {
            throw new RuntimeException('No input file found for OCR job');
        }

        return $file;
    }

    private function makeTmpDir(): string
    {
        $dir = storage_path('app/tmp/'.Str::ulid());
        mkdir($dir, 0755, true);

        return $dir;
    }

    private function cleanTmpDir(string $dir): void
    {
        if (is_dir($dir)) {
            \Illuminate\Support\Facades\File::deleteDirectory($dir);
        }
    }
}
