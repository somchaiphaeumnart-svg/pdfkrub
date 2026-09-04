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

        $profileDir = rtrim($outputDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'lo_profile';
        if (! is_dir($profileDir)) {
            @mkdir($profileDir, 0755, true);
        }

        $result = Process::timeout($this->timeoutSeconds)->run([
            $this->binaryPath,
            "-env:UserInstallation=file://{$profileDir}",
            '--headless',
            '--norestore',
            '--convert-to', 'pdf',
            '--outdir', $outputDir,
            $inputPath,
        ]);

        if (! $result->successful()) {
            Log::error('LibreOffice convert-to-pdf failed', [
                'input' => $inputPath,
                'stderr' => $result->errorOutput(),
                'exit_code' => $result->exitCode(),
            ]);
            throw new RuntimeException('LibreOffice failed: '.$result->errorOutput());
        }

        // LibreOffice names the output file the same as input, with .pdf extension
        $basename = pathinfo($inputPath, PATHINFO_FILENAME);
        $outputPath = rtrim($outputDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$basename.'.pdf';

        if (! file_exists($outputPath)) {
            throw new RuntimeException("LibreOffice output not found: {$outputPath}");
        }

        return $outputPath;
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

        $this->ensureBinaryExists();

        $infilter = match ($targetFormat) {
            'docx' => 'writer_pdf_import',
            'pptx' => 'impress_pdf_import',
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

        $profileDir = rtrim($outputDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'lo_profile';
        if (! is_dir($profileDir)) {
            @mkdir($profileDir, 0755, true);
        }

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
