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
use ZipArchive;

/**
 * AiSummaryService
 *
 * Extracts text from PDF / Word / Text documents and uses AI (Gemini / OpenAI / Smart Extractive)
 * to generate a structured executive summary, key takeaways, and action items.
 */
class AiSummaryService
{
    public function __construct(
        private readonly OcrService $ocrService
    ) {}

    /**
     * Process an AI Summary Job.
     */
    public function process(PdfJob $job): UploadedFile
    {
        $inputFile = $this->getInputFile($job);
        $config = $job->tool_config ?? [];
        $tmpDir = $this->makeTmpDir();

        try {
            $inputPath = Storage::disk($inputFile->storage_disk)->path($inputFile->storage_key);

            // 1. Extract raw text from document
            $rawText = $this->extractText($inputPath, $inputFile, $tmpDir);

            if (empty(trim($rawText))) {
                throw new RuntimeException('ไม่สามารถดึงข้อความจากเอกสารนี้ได้ (อาจเป็นไฟล์ว่าง หรือไฟล์รูปภาพสแกนที่ไม่มีตัวอักษร)');
            }

            // 2. Generate summary using AI
            $summary = $this->generateSummary($rawText, $config, $inputFile->original_name);

            // 3. Store the result
            return $this->storeSummaryResult($job, $summary, $inputFile, $config);
        } finally {
            $this->cleanTmpDir($tmpDir);
        }
    }

    /**
     * Extract raw text from different file types.
     */
    private function extractText(string $filePath, UploadedFile $file, string $tmpDir): string
    {
        $ext = strtolower(pathinfo($file->original_name, PATHINFO_EXTENSION));

        if ($ext === 'txt') {
            return (string) @file_get_contents($filePath);
        }

        if (in_array($ext, ['docx', 'doc'])) {
            return $this->extractFromDocx($filePath, $tmpDir);
        }

        if ($ext === 'pdf') {
            return $this->extractFromPdf($filePath, $tmpDir);
        }

        // Image files
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            return $this->extractFromImage($filePath, $tmpDir);
        }

        return (string) @file_get_contents($filePath);
    }

    /**
     * Extract text from PDF via pdftotext, pdf2txt, or fallback to OCR.
     */
    private function extractFromPdf(string $pdfPath, string $tmpDir): string
    {
        $txtOut = $tmpDir.DIRECTORY_SEPARATOR.'extracted.txt';

        // 1. Try pdftotext (fastest & cleanest text extraction from searchable PDFs)
        try {
            $res = Process::timeout(60)->run(['pdftotext', '-layout', $pdfPath, $txtOut]);
            if ($res->successful() && file_exists($txtOut)) {
                $text = (string) file_get_contents($txtOut);
                if (mb_strlen(trim($text)) >= 50) {
                    return $text;
                }
            }
        } catch (\Throwable $e) {
            // pdftotext might not be installed or failed
        }

        // 2. Try Python pypdf / pymupdf if available
        try {
            $pyRes = Process::timeout(60)->run([
                'python3', '-c',
                "import pypdf; reader = pypdf.PdfReader('{$pdfPath}'); text = '\n'.join([page.extract_text() or '' for page in reader.pages]); print(text)"
            ]);
            if ($pyRes->successful() && mb_strlen(trim($pyRes->output())) >= 50) {
                return $pyRes->output();
            }
        } catch (\Throwable $e) {
            // ignore
        }

        // 3. Fallback to LibreOffice pdftotext conversion
        try {
            $loRes = Process::timeout(90)->run([
                config('pdf2word.libreoffice_path', 'soffice'),
                '--headless', '--norestore',
                '--convert-to', 'txt:Text',
                '--outdir', $tmpDir,
                $pdfPath,
            ]);
            $loTxt = $tmpDir.DIRECTORY_SEPARATOR.pathinfo($pdfPath, PATHINFO_FILENAME).'.txt';
            if (file_exists($loTxt)) {
                $text = (string) file_get_contents($loTxt);
                if (mb_strlen(trim($text)) >= 50) {
                    return $text;
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        // 4. If text is still empty (scanned image PDF), run OCR on first few pages
        return $this->extractViaOcr($pdfPath, $tmpDir);
    }

    /**
     * Extract text from DOCX directly using ZipArchive.
     */
    private function extractFromDocx(string $filePath, string $tmpDir): string
    {
        $zip = new ZipArchive();
        if ($zip->open($filePath) === true) {
            $xml = $zip->getFromName('word/document.xml');
            $zip->close();
            if ($xml) {
                $clean = strip_tags(str_replace(['</w:p>', '</w:tr>'], ["\n", "\n"], $xml));
                return html_entity_decode($clean, ENT_QUOTES | ENT_XML1, 'UTF-8');
            }
        }

        // Fallback to LibreOffice txt conversion
        try {
            Process::timeout(60)->run([
                config('pdf2word.libreoffice_path', 'soffice'),
                '--headless', '--norestore',
                '--convert-to', 'txt:Text',
                '--outdir', $tmpDir,
                $filePath,
            ]);
            $txtFile = $tmpDir.DIRECTORY_SEPARATOR.pathinfo($filePath, PATHINFO_FILENAME).'.txt';
            if (file_exists($txtFile)) {
                return (string) file_get_contents($txtFile);
            }
        } catch (\Throwable $e) {}

        return '';
    }

    /**
     * Extract text from Image or Scanned PDF using Tesseract OCR.
     */
    private function extractFromImage(string $imagePath, string $tmpDir): string
    {
        $out = $tmpDir.DIRECTORY_SEPARATOR.'img_ocr';
        try {
            $res = Process::timeout(90)->run([
                'tesseract', $imagePath, $out,
                '-l', 'tha+eng',
                '--psm', '3',
                'txt',
            ]);
            if (file_exists($out.'.txt')) {
                return (string) file_get_contents($out.'.txt');
            }
        } catch (\Throwable $e) {}

        return '';
    }

    private function extractViaOcr(string $pdfPath, string $tmpDir): string
    {
        // Convert first 10 pages of PDF to images for OCR
        try {
            Process::timeout(90)->run([
                'gs', '-dBATCH', '-dNOPAUSE', '-q',
                '-sDEVICE=png16m', '-r150',
                "-sOutputFile={$tmpDir}/page_%03d.png",
                $pdfPath,
            ]);

            $images = glob($tmpDir.'/page_*.png');
            sort($images);
            // Limit to first 10 pages for summarization speed
            $images = array_slice($images, 0, 10);

            $fullText = '';
            foreach ($images as $img) {
                $out = $tmpDir.'/ocr_'.uniqid();
                Process::timeout(60)->run([
                    'tesseract', $img, $out,
                    '-l', 'tha+eng',
                    '--psm', '3',
                    'txt',
                ]);
                if (file_exists($out.'.txt')) {
                    $fullText .= file_get_contents($out.'.txt')."\n";
                }
            }

            return $fullText;
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * Generate summary using Gemini AI, OpenAI, or Fallback NLP.
     */
    private function generateSummary(string $rawText, array $config, string $fileName): string
    {
        $length = $config['length'] ?? 'standard'; // short, standard, detailed
        $focus = $config['focus'] ?? 'general';   // general, action_items, key_points, numbers
        $geminiKey = config('services.gemini.api_key');
        $openAiKey = config('services.openai.api_key');

        // Truncate text to avoid token limits (first ~25,000 characters is plenty for a full summary)
        $trimmedText = mb_substr(trim($rawText), 0, 25000);

        // 1. Try Gemini AI
        if (! empty($geminiKey)) {
            try {
                return $this->summarizeWithGemini($trimmedText, $length, $focus, $fileName, $geminiKey);
            } catch (\Throwable $e) {
                Log::warning('Gemini AI summary failed: '.$e->getMessage().', trying fallback');
            }
        }

        // 2. Try OpenAI
        if (! empty($openAiKey)) {
            try {
                return $this->summarizeWithOpenAi($trimmedText, $length, $focus, $fileName, $openAiKey);
            } catch (\Throwable $e) {
                Log::warning('OpenAI summary failed: '.$e->getMessage().', trying fallback');
            }
        }

        // 3. Smart Extractive Summarizer (No external API needed)
        return $this->summarizeExtractively($trimmedText, $length, $focus, $fileName);
    }

    /**
     * Call Google Gemini API to summarize.
     */
    private function summarizeWithGemini(string $text, string $length, string $focus, string $fileName, string $apiKey): string
    {
        $prompt = $this->buildPrompt($text, $length, $focus, $fileName);
        $model = config('services.gemini.model', 'gemini-1.5-flash');

        $response = Http::timeout(60)
            ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.2,
                    'maxOutputTokens' => 2048,
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Gemini API Error: '.$response->body());
        }

        $data = $response->json();
        $summary = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

        if (empty(trim($summary))) {
            throw new RuntimeException('Gemini returned empty response');
        }

        return trim($summary);
    }

    /**
     * Call OpenAI API to summarize.
     */
    private function summarizeWithOpenAi(string $text, string $length, string $focus, string $fileName, string $apiKey): string
    {
        $prompt = $this->buildPrompt($text, $length, $focus, $fileName);
        $model = config('services.openai.model', 'gpt-4o-mini');

        $response = Http::timeout(60)
            ->withToken($apiKey)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'คุณคือผู้ช่วย AI สรุปเอกสารระดับมืออาชีพ กรุณาสรุปสาระสำคัญจากเอกสารเป็นภาษาไทยอย่างกระชับ ชัดเจน ถูกต้อง และจัดโครงสร้าง Markdown ให้อ่านง่าย',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => 0.2,
                'max_tokens' => 2000,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('OpenAI API Error: '.$response->body());
        }

        $data = $response->json();
        $summary = $data['choices'][0]['message']['content'] ?? '';

        return trim($summary);
    }

    private function buildPrompt(string $text, string $length, string $focus, string $fileName): string
    {
        $lengthDesc = match ($length) {
            'short' => 'สรุปแบบสั้นกระชับ (Quick Summary) ไม่เกิน 5-7 ข้อความหลัก',
            'detailed' => 'สรุปแบบละเอียด ครอบคลุมทุกหัวข้อและประเด็นสำคัญของเอกสาร',
            default => 'สรุปแบบมาตรฐาน ชัดเจน ตรงประเด็น พอดีคำ',
        };

        $focusDesc = match ($focus) {
            'action_items' => 'เน้นสิ่งที่ต้องทำต่อ (Action Items), ผู้รับผิดชอบ และกำหนดเวลาเป็นหลัก',
            'numbers' => 'เน้นข้อมูลสถิติ ตัวเลข งบประมาณ และตัวชี้วัดสำคัญเป็นหลัก',
            default => 'สรุปภาพรวมครอบคลุมทุกประเด็นอย่างสมดุล',
        };

        return <<<PROMPT
คุณคือผู้เชี่ยวชาญด้านการวิเคราะห์และสรุปเอกสาร (AI Document Summarizer)
กรุณาวิเคราะห์และสรุปเนื้อหาจากเอกสารชื่อ: "{$fileName}" 

เงื่อนไขการสรุป:
- ระดับความยาว: {$lengthDesc}
- จุดเน้น: {$focusDesc}
- ใช้ภาษาไทยที่สุภาพ เป็นมืออาชีพ ชัดเจน เข้าใจง่าย ไม่แต่งเติมข้อมูลที่ไม่มีในเอกสาร

กรุณาจัดรูปแบบผลลัพธ์ด้วย Markdown ตามโครงสร้างนี้:
# 📑 สรุปสาระสำคัญ: {$fileName}

## 📌 ภาพรวมเอกสาร (Executive Summary)
(สรุปใจความสำคัญใน 2-3 ประโยค)

## 🔑 ประเด็นสำคัญ (Key Takeaways)
(สรุปประเด็นหลักเป็นข้อๆ พร้อมขยายความสั้นๆ)

## 📊 ข้อมูลสำคัญ & ตัวเลข (Key Data & Numbers)
(สรุปตัวเลข วันที่ สถิติ หรือข้อกำหนดสำคัญ ถ้ามี)

## ✅ สิ่งที่ต้องดำเนินการต่อ (Action Items / Next Steps)
(สรุปสิ่งที่ต้องทำ หรือขั้นตอนถัดไป ถ้ามี)

เนื้อหาเอกสาร:
---
{$text}
---
PROMPT;
    }

    /**
     * Smart Extractive / Structural NLP Summarizer in pure PHP.
     * Guaranteed to work fast and 100% offline without any external API keys!
     */
    private function summarizeExtractively(string $text, string $length, string $focus, string $fileName): string
    {
        // Normalize whitespace and split into lines
        $lines = preg_split('/\r\n|\r|\n/', $text);
        $cleanLines = array_values(array_filter(array_map('trim', $lines), fn ($l) => mb_strlen($l) > 3));

        $totalWords = str_word_count($text) ?: count($cleanLines);
        $approxPages = max(1, round(mb_strlen($text) / 1800));

        // Detect potential headings / bullet points / numbers
        $headings = [];
        $keyBullets = [];
        $numberStats = [];
        $actions = [];

        $actionKeywords = ['ต้อง', 'ควร', 'ให้ดำเนินการ', 'กำหนดให้', 'แนวทาง', 'ขั้นตอน', 'มาตรการ', 'มอบหมาย', 'จัดทำ', 'พัฒนา'];
        $numberPattern = '/\b(\d{1,3}(,\d{3})*(\.\d+)?|\d+)\s*(บาท|คน|เปอร์เซ็นต์|%|ปี|วัน|เดือน|ฉบับ|ราย|แห่ง|โครงการ)/u';

        foreach ($cleanLines as $line) {
            // Detect lines with numbers/statistics
            if (preg_match($numberPattern, $line, $matches)) {
                if (count($numberStats) < 6 && mb_strlen($line) < 150) {
                    $numberStats[] = $line;
                }
            }

            // Detect action items
            foreach ($actionKeywords as $kw) {
                if (mb_strpos($line, $kw) !== false && mb_strlen($line) < 180) {
                    if (count($actions) < 6 && ! in_array($line, $actions)) {
                        $actions[] = $line;
                    }
                    break;
                }
            }

            // Detect headings or structured bullet points
            if (preg_match('/^(หมวด|บทที่|ข้อที่|ส่วนที่|\d+\.|\([0-9]\)|•|-)\s*(.+)/u', $line)) {
                if (count($keyBullets) < 8 && mb_strlen($line) < 160) {
                    $keyBullets[] = $line;
                }
            } elseif (mb_strlen($line) < 60 && ! preg_match('/[.,:;]$/u', $line)) {
                if (count($headings) < 4) {
                    $headings[] = $line;
                }
            }
        }

        // Executive overview from first few descriptive paragraphs
        $overviewLines = [];
        foreach ($cleanLines as $line) {
            if (mb_strlen($line) >= 40 && mb_strlen($line) <= 250) {
                $overviewLines[] = $line;
                if (count($overviewLines) >= 3) break;
            }
        }
        $overview = ! empty($overviewLines) ? implode("\n\n", $overviewLines) : mb_substr($text, 0, 300).'...';

        // Build Markdown response
        $output = "# 📑 สรุปสาระสำคัญ: {$fileName}\n\n";
        $output .= "> ℹ️ *เอกสารมีความยาวประมาณ {$approxPages} หน้า (ประมาณ " . number_format(mb_strlen($text)) . " ตัวอักษร)*\n\n";

        $output .= "## 📌 ภาพรวมเอกสาร (Executive Summary)\n";
        $output .= $overview . "\n\n";

        if (! empty($headings)) {
            $output .= "## 📑 โครงสร้างหัวข้อหลักในเอกสาร\n";
            foreach ($headings as $h) {
                $output .= "- **" . $h . "**\n";
            }
            $output .= "\n";
        }

        $output .= "## 🔑 ประเด็นสำคัญ (Key Highlights)\n";
        if (! empty($keyBullets)) {
            foreach ($keyBullets as $b) {
                $output .= "- " . ltrim($b, '-•* ') . "\n";
            }
        } else {
            // Pick informative sentences
            $sampleSentences = array_slice($cleanLines, 2, 5);
            foreach ($sampleSentences as $s) {
                $output .= "- " . $s . "\n";
            }
        }
        $output .= "\n";

        if (! empty($numberStats)) {
            $output .= "## 📊 ข้อมูลสำคัญ & สถิติที่ระบุในเอกสาร\n";
            foreach ($numberStats as $n) {
                $output .= "- " . $n . "\n";
            }
            $output .= "\n";
        }

        if (! empty($actions)) {
            $output .= "## ✅ ข้อกำหนดและสิ่งที่ต้องดำเนินการ (Action Items)\n";
            foreach ($actions as $a) {
                $output .= "- [ ] " . $a . "\n";
            }
            $output .= "\n";
        }

        return trim($output);
    }

    /**
     * Store the generated summary into output file and metadata.
     */
    private function storeSummaryResult(PdfJob $job, string $summary, UploadedFile $inputFile, array $config): UploadedFile
    {
        $tmpDir = $this->makeTmpDir();
        $basename = pathinfo($inputFile->original_name, PATHINFO_FILENAME);
        $outputName = $basename.'_summary.txt';
        $outputPath = $tmpDir.DIRECTORY_SEPARATOR.$outputName;

        file_put_contents($outputPath, $summary);

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
            'file_hash' => hash('sha256', $summary),
            'metadata' => [
                'extracted_text' => $summary,
                'type' => 'ai_summary',
            ],
            'expires_at' => $expiresAt,
        ]);

        $this->cleanTmpDir($tmpDir);

        return $file;
    }

    private function getInputFile(PdfJob $job): UploadedFile
    {
        $fileId = $job->input_file_ids[0] ?? null;
        if (! $fileId || ! ($file = UploadedFile::find($fileId))) {
            throw new RuntimeException('No input file found for AI Summary job');
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
