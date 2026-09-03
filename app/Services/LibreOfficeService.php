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

        $result = Process::timeout($this->timeoutSeconds)->run([
            $this->binaryPath,
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
        $this->ensureBinaryExists();

        $formatMap = [
            'docx' => 'docx:MS Word 2007 XML',
            'xlsx' => 'xlsx:Calc MS Excel 2007 XML',
            'pptx' => 'pptx:Impress MS PowerPoint 2007 XML',
            'txt' => 'txt:Text',
            'html' => 'html:HTML (StarWriter)',
        ];

        $convertTo = $formatMap[$targetFormat] ?? $targetFormat;

        $result = Process::timeout($this->timeoutSeconds)->run([
            $this->binaryPath,
            '--headless',
            '--norestore',
            '--infilter=impress_pdf_import',
            '--convert-to', $convertTo,
            '--outdir', $outputDir,
            $inputPdf,
        ]);

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
