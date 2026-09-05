<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use RuntimeException;

/**
 * LibreOfficeService
 *
 * Wraps headless LibreOffice conversion commands.
 * Requires LibreOffice to be installed on the server.
 * Windows path configured via LIBREOFFICE_PATH env var.
 */
class LibreOfficeService
{
    private string $binaryPath;

    private int $timeoutSeconds;

    public function __construct()
    {
        $this->binaryPath = config('pdf2word.libreoffice_path', 'soffice');
        $this->timeoutSeconds = 120;
    }

    /**
     * Convert a file to PDF using LibreOffice.
     *
     * @param  string  $inputPath  Absolute path to the input file
     * @param  string  $outputDir  Directory to write the resulting PDF
     * @return string Path to the generated PDF
     */
    public function convertToPdf(string $inputPath, string $outputDir): string
    {
        $this->ensureBinaryExists();

        $profileDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'lo_profile_'.uniqid();
        if (! is_dir($profileDir)) {
            @mkdir($profileDir, 0755, true);
        }

        try {
            $ext = strtolower(pathinfo($inputPath, PATHINFO_EXTENSION));
            $convertArg = 'pdf';
            if (in_array($ext, ['ppt', 'pptx'])) {
                $convertArg = 'pdf:impress_pdf_Export';
            } elseif (in_array($ext, ['doc', 'docx'])) {
                $convertArg = 'pdf:writer_pdf_Export';
            } elseif (in_array($ext, ['xls', 'xlsx'])) {
                $convertArg = 'pdf:calc_pdf_Export';
            }

            $result = Process::timeout($this->timeoutSeconds)->run([
                $this->binaryPath,
                "-env:UserInstallation=file://{$profileDir}",
                '--headless',
                '--norestore',
                '--convert-to', $convertArg,
                '--outdir', $outputDir,
                $inputPath,
            ]);

            $basename = pathinfo($inputPath, PATHINFO_FILENAME);
            $outputPath = rtrim($outputDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$basename.'.pdf';

            // If format-specific export filter didn't produce the file, try standard '--convert-to pdf'
            if (! file_exists($outputPath) || filesize($outputPath) === 0) {
                $result2 = Process::timeout($this->timeoutSeconds)->run([
                    $this->binaryPath,
                    "-env:UserInstallation=file://{$profileDir}",
                    '--headless',
                    '--norestore',
                    '--convert-to', 'pdf',
                    '--outdir', $outputDir,
                    $inputPath,
                ]);
            }

            if (! file_exists($outputPath) || filesize($outputPath) === 0) {
                Log::error('LibreOffice convert-to-pdf failed', [
                    'input' => $inputPath,
                    'stderr' => $result->errorOutput(),
                    'exit_code' => $result->exitCode(),
                ]);
                throw new RuntimeException('ไม่สามารถแปลงไฟล์เป็น PDF ได้ กรุณาตรวจสอบว่าไฟล์ไม่เสียหายและไม่มีรหัสผ่าน');
            }

            return $outputPath;
        } finally {
            if (is_dir($profileDir)) {
                \Illuminate\Support\Facades\File::deleteDirectory($profileDir);
            }
        }
    }

    /**
     * Convert PDF pages to a target format (docx, xlsx, pptx).
     *
     * @param  string  $inputPdf  Absolute path to the PDF
     * @param  string  $targetFormat  'docx' | 'xlsx' | 'pptx' | 'txt'
     * @param  string  $outputDir  Directory to write the result
     * @return string Path to the generated file
     */
    public function convertFromPdf(string $inputPdf, string $targetFormat, string $outputDir): string
    {
        // For Excel (xlsx), use specialized table extraction (Python / pdftotext)
        // LibreOffice does NOT natively support converting PDF to XLSX directly
        if ($targetFormat === 'xlsx') {
            return $this->convertPdfToExcel($inputPdf, $outputDir);
        }

        // For PowerPoint (pptx), use high-fidelity slide conversion with LibreOffice fallback
        if ($targetFormat === 'pptx') {
            return $this->convertPdfToPptx($inputPdf, $outputDir);
        }

        // For Text (txt), use specialized unicode text extraction
        if ($targetFormat === 'txt') {
            return $this->convertPdfToTxt($inputPdf, $outputDir);
        }

        $this->ensureBinaryExists();

        $infilter = match ($targetFormat) {
            'docx' => 'writer_pdf_import',
            default => null,
        };

        // Try pdf2docx first for DOCX if available (highest quality layout & font retention)
        if ($targetFormat === 'docx') {
            $basename = pathinfo($inputPdf, PATHINFO_FILENAME);
            $outputPath = rtrim($outputDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$basename.'.docx';
            
            $pythonCmd = file_exists('/opt/pdf2docx-env/bin/python3') 
                ? '/opt/pdf2docx-env/bin/python3' 
                : 'python3';

            $pyResult = Process::timeout($this->timeoutSeconds)->run([
                $pythonCmd,
                '-c',
                "from pdf2docx import Converter; cv = Converter('{$inputPdf}'); cv.convert('{$outputPath}'); cv.close()"
            ]);

            if ($pyResult->successful() && file_exists($outputPath)) {
                return $outputPath;
            }
        }

        $profileDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'lo_profile_'.uniqid();
        if (! is_dir($profileDir)) {
            @mkdir($profileDir, 0755, true);
        }

        try {
            $cmd = [
                $this->binaryPath,
                "-env:UserInstallation=file://{$profileDir}",
                '--headless',
                '--norestore',
            ];

            if ($infilter) {
                $cmd[] = "--infilter={$infilter}";
            }

            $cmd[] = '--convert-to';
            $cmd[] = $targetFormat;
            $cmd[] = '--outdir';
            $cmd[] = $outputDir;
            $cmd[] = $inputPdf;

            $result = Process::timeout($this->timeoutSeconds)->run($cmd);

            if (! $result->successful()) {
                Log::error('LibreOffice convert-from-pdf failed', [
                    'input' => $inputPdf,
                    'target_format' => $targetFormat,
                    'stderr' => $result->errorOutput(),
                ]);
                throw new RuntimeException('LibreOffice failed: '.$result->errorOutput());
            }

            $basename = pathinfo($inputPdf, PATHINFO_FILENAME);
            $outputPath = rtrim($outputDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$basename.'.'.$targetFormat;

            if (! file_exists($outputPath)) {
                throw new RuntimeException("LibreOffice output not found: {$outputPath}");
            }

            return $outputPath;
        } finally {
            if (is_dir($profileDir)) {
                \Illuminate\Support\Facades\File::deleteDirectory($profileDir);
            }
        }
    }

    /**
     * Convert PDF to PowerPoint (.pptx) using python slide extraction or LibreOffice fallback.
     */
    public function convertPdfToPptx(string $inputPdf, string $outputDir): string
    {
        $basename = pathinfo($inputPdf, PATHINFO_FILENAME);
        $outputPath = rtrim($outputDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$basename.'.pptx';

        $pythonCmd = file_exists('/opt/pdf2docx-env/bin/python3')
            ? '/opt/pdf2docx-env/bin/python3'
            : 'python3';

        $scriptPath = base_path('scripts/pdf_to_pptx.py');

        if (file_exists($scriptPath)) {
            $result = Process::timeout($this->timeoutSeconds)->run([
                $pythonCmd,
                $scriptPath,
                $inputPdf,
                $outputPath,
            ]);

            if ($result->successful() && file_exists($outputPath) && filesize($outputPath) > 0) {
                return $outputPath;
            }

            Log::warning('Python pdf_to_pptx failed, trying system python or LibreOffice fallback', [
                'input' => $inputPdf,
                'exit_code' => $result->exitCode(),
                'stderr' => $result->errorOutput(),
                'stdout' => $result->output(),
            ]);

            if ($pythonCmd !== 'python3') {
                $sysResult = Process::timeout($this->timeoutSeconds)->run([
                    'python3',
                    $scriptPath,
                    $inputPdf,
                    $outputPath,
                ]);

                if ($sysResult->successful() && file_exists($outputPath) && filesize($outputPath) > 0) {
                    return $outputPath;
                }
            }
        }

        // Fallback: Try LibreOffice Impress
        if ($this->isAvailable()) {
            $profileDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'lo_profile_'.uniqid();
            if (! is_dir($profileDir)) {
                @mkdir($profileDir, 0755, true);
            }

            try {
                $cmd = [
                    $this->binaryPath,
                    "-env:UserInstallation=file://{$profileDir}",
                    '--headless',
                    '--norestore',
                    '--infilter=impress_pdf_import',
                    '--convert-to',
                    'pptx',
                    '--outdir',
                    $outputDir,
                    $inputPdf,
                ];

                $result = Process::timeout($this->timeoutSeconds)->run($cmd);

                if ($result->successful() && file_exists($outputPath) && filesize($outputPath) > 0) {
                    return $outputPath;
                }

                // Try without infilter
                $cmd2 = [
                    $this->binaryPath,
                    "-env:UserInstallation=file://{$profileDir}",
                    '--headless',
                    '--norestore',
                    '--convert-to',
                    'pptx',
                    '--outdir',
                    $outputDir,
                    $inputPdf,
                ];
                $result2 = Process::timeout($this->timeoutSeconds)->run($cmd2);

                if ($result2->successful() && file_exists($outputPath) && filesize($outputPath) > 0) {
                    return $outputPath;
                }
            } finally {
                if (is_dir($profileDir)) {
                    \Illuminate\Support\Facades\File::deleteDirectory($profileDir);
                }
            }
        }

        if (! file_exists($outputPath) || filesize($outputPath) === 0) {
            throw new RuntimeException('ไม่สามารถแปลงไฟล์เป็น PowerPoint (.pptx) ได้ กรุณาลองใหม่อีกครั้ง');
        }

        return $outputPath;
    }

    /**
     * Convert PDF to Plain Text (.txt) using pdftotext, python extractor, or LibreOffice fallback.
     */
    public function convertPdfToTxt(string $inputPdf, string $outputDir): string
    {
        $basename = pathinfo($inputPdf, PATHINFO_FILENAME);
        $outputPath = rtrim($outputDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$basename.'.txt';

        // 1. Try pdftotext CLI (fastest, high Unicode Thai fidelity)
        try {
            $cmd = ['pdftotext', '-layout', '-enc', 'UTF-8', $inputPdf, $outputPath];
            $res = Process::timeout(60)->run($cmd);
            if (! $res->successful() || ! file_exists($outputPath) || filesize($outputPath) === 0) {
                // Try without -enc
                $res = Process::timeout(60)->run(['pdftotext', '-layout', $inputPdf, $outputPath]);
            }
            if (file_exists($outputPath) && filesize($outputPath) > 0) {
                $content = (string) file_get_contents($outputPath);
                if (mb_strlen(trim($content)) >= 10) {
                    return $outputPath;
                }
            }
        } catch (\Throwable $e) {
            // pdftotext not available or failed
        }

        // 2. Try Python extractor script
        $pythonCmd = file_exists('/opt/pdf2docx-env/bin/python3')
            ? '/opt/pdf2docx-env/bin/python3'
            : 'python3';

        $scriptPath = base_path('scripts/pdf_to_txt.py');
        if (file_exists($scriptPath)) {
            try {
                $result = Process::timeout($this->timeoutSeconds)->run([
                    $pythonCmd,
                    $scriptPath,
                    $inputPdf,
                    $outputPath,
                ]);

                if ($result->successful() && file_exists($outputPath) && filesize($outputPath) > 0) {
                    $content = (string) file_get_contents($outputPath);
                    if (mb_strlen(trim($content)) >= 10) {
                        return $outputPath;
                    }
                }

                if ($pythonCmd !== 'python3') {
                    $sysResult = Process::timeout($this->timeoutSeconds)->run([
                        'python3',
                        $scriptPath,
                        $inputPdf,
                        $outputPath,
                    ]);

                    if ($sysResult->successful() && file_exists($outputPath) && filesize($outputPath) > 0) {
                        $content = (string) file_get_contents($outputPath);
                        if (mb_strlen(trim($content)) >= 10) {
                            return $outputPath;
                        }
                    }
                }
            } catch (\Throwable $e) {
                // script failed
            }
        }

        // 3. Fallback: Try LibreOffice with proper text filter
        if ($this->isAvailable()) {
            $profileDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'lo_profile_'.uniqid();
            if (! is_dir($profileDir)) {
                @mkdir($profileDir, 0755, true);
            }

            try {
                $cmd = [
                    $this->binaryPath,
                    "-env:UserInstallation=file://{$profileDir}",
                    '--headless',
                    '--norestore',
                    '--infilter=writer_pdf_import',
                    '--convert-to',
                    'txt:Text (encoded):UTF8',
                    '--outdir',
                    $outputDir,
                    $inputPdf,
                ];

                $res = Process::timeout($this->timeoutSeconds)->run($cmd);
                if ($res->successful() && file_exists($outputPath) && filesize($outputPath) > 0) {
                    return $outputPath;
                }

                // Try with simple txt:Text
                $cmd2 = [
                    $this->binaryPath,
                    "-env:UserInstallation=file://{$profileDir}",
                    '--headless',
                    '--norestore',
                    '--convert-to',
                    'txt:Text',
                    '--outdir',
                    $outputDir,
                    $inputPdf,
                ];
                $res2 = Process::timeout($this->timeoutSeconds)->run($cmd2);
                if ($res2->successful() && file_exists($outputPath) && filesize($outputPath) > 0) {
                    return $outputPath;
                }
            } finally {
                if (is_dir($profileDir)) {
                    \Illuminate\Support\Facades\File::deleteDirectory($profileDir);
                }
            }
        }

        // 4. Guarantee file exists so it never throws output not found
        if (! file_exists($outputPath) || filesize($outputPath) === 0) {
            file_put_contents($outputPath, "ไม่พบข้อความที่สามารถอ่านได้ในไฟล์ PDF นี้ หรือไฟล์นี้เป็นรูปภาพที่ต้องใช้เครื่องมือ OCR ภาษาไทย");
        }

        return $outputPath;
    }

    /**
     * Convert PDF to Excel (.xlsx) using python table extraction or pdftotext fallback.
     */
    public function convertPdfToExcel(string $inputPdf, string $outputDir): string
    {
        $basename = pathinfo($inputPdf, PATHINFO_FILENAME);
        $outputPath = rtrim($outputDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$basename.'.xlsx';

        $pythonCmd = file_exists('/opt/pdf2docx-env/bin/python3')
            ? '/opt/pdf2docx-env/bin/python3'
            : 'python3';

        $scriptPath = base_path('scripts/pdf_to_excel.py');

        if (file_exists($scriptPath)) {
            $result = Process::timeout($this->timeoutSeconds)->run([
                $pythonCmd,
                $scriptPath,
                $inputPdf,
                $outputPath,
            ]);

            if ($result->successful() && file_exists($outputPath) && filesize($outputPath) > 0) {
                return $outputPath;
            }

            Log::warning('Python pdf_to_excel via env python failed, trying system python', [
                'input' => $inputPdf,
                'exit_code' => $result->exitCode(),
                'stderr' => $result->errorOutput(),
                'stdout' => $result->output(),
            ]);

            if ($pythonCmd !== 'python3') {
                $sysResult = Process::timeout($this->timeoutSeconds)->run([
                    'python3',
                    $scriptPath,
                    $inputPdf,
                    $outputPath,
                ]);

                if ($sysResult->successful() && file_exists($outputPath) && filesize($outputPath) > 0) {
                    return $outputPath;
                }
            }
        }

        if (! file_exists($outputPath) || filesize($outputPath) === 0) {
            throw new RuntimeException('ไม่สามารถสร้างไฟล์ Excel ได้ กรุณาตรวจสอบว่าไฟล์ PDF มีข้อความหรือตารางที่ถูกต้อง');
        }

        return $outputPath;
    }

    /**
     * Check if LibreOffice is available on this system.
     */
    public function isAvailable(): bool
    {
        try {
            $result = Process::timeout(10)->run([$this->binaryPath, '--version']);

            return $result->successful();
        } catch (\Exception) {
            return false;
        }
    }

    private function ensureBinaryExists(): void
    {
        if (! $this->isAvailable()) {
            throw new RuntimeException(
                "LibreOffice ไม่พบในระบบ กรุณาติดตั้ง LibreOffice หรือตั้งค่า LIBREOFFICE_PATH ใน .env\n"
                ."Path ปัจจุบัน: {$this->binaryPath}"
            );
        }
    }
}
