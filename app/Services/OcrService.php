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

        // Choose backend based on plan
        if ($plan?->has_ocr && config('services.google_vision.credentials')) {
            return $this->processWithGoogleVision($job);
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
        $languages = $config['languages'] ?? ['th', 'en'];
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

            return $this->storeTextResult($job, $fullText, $inputFile, ['confidence' => $avgConfidence]);
        } finally {
            $this->cleanTmpDir($tmpDir);
        }
    }

    private function callGoogleVisionApi(string $imagePath, array $languages): array
    {
        $imageData = base64_encode(file_get_contents($imagePath));
        $apiKey = config('services.google_vision.credentials');

        $response = Http::timeout(30)
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
        // Calculate average confidence from pages
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
        $languages = $config['languages'] ?? ['tha', 'eng'];
        $tmpDir = $this->makeTmpDir();

        try {
            $inputPath = Storage::disk($inputFile->storage_disk)->path($inputFile->storage_key);
            $imagePaths = $inputFile->isPdf()
                ? $this->pdfToImages($inputPath, $tmpDir)
                : [$inputPath];

            $fullText = '';

            foreach ($imagePaths as $imagePath) {
                $outputBase = $tmpDir.DIRECTORY_SEPARATOR.'page_'.uniqid();
                $langStr = implode('+', $languages);

                $result = Process::timeout(120)->run([
                    'tesseract', $imagePath, $outputBase,
                    '-l', $langStr,
                    '--psm', '3', // Fully automatic page segmentation
                    'txt',
                ]);

                if ($result->successful() && file_exists($outputBase.'.txt')) {
                    $fullText .= file_get_contents($outputBase.'.txt')."\n\n";
                }
            }

            Log::info('OCR (Tesseract) complete', [
                'job_id' => $job->id,
                'pages' => count($imagePaths),
                'char_count' => strlen($fullText),
            ]);

            return $this->storeTextResult($job, trim($fullText), $inputFile, ['engine' => 'tesseract']);
        } finally {
            $this->cleanTmpDir($tmpDir);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    private function pdfToImages(string $pdfPath, string $outputDir): array
    {
        $result = Process::timeout(120)->run([
            'gs', '-dBATCH', '-dNOPAUSE', '-q',
            '-sDEVICE=pngalpha', '-r200',
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
        $outputName = $basename.'_ocr.txt';
        $outputPath = $tmpDir.DIRECTORY_SEPARATOR.$outputName;

        file_put_contents($outputPath, $text);

        // Re-use PdfProcessingService's storeOutput logic via storage
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
            'mime_type' => 'text/plain',
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
            foreach (glob($dir.'/*') as $f) {
                if (is_file($f)) {
                    unlink($f);
                }
            }
            rmdir($dir);
        }
    }
}
