<?php

namespace App\Console\Commands;

use App\Models\PdfJob;
use App\Services\PdfProcessingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

class DiagnosePdf extends Command
{
    protected $signature = 'pdf:diagnose';
    protected $description = 'Diagnose PDF processing tools and process pending jobs';

    public function handle(PdfProcessingService $processor): int
    {
        $this->info('=== PDFkrub System Diagnostic ===');
        
        // 1. Check tools
        $tools = ['gs' => 'Ghostscript', 'pdfunite' => 'Poppler Utils', 'qpdf' => 'QPDF', 'soffice' => 'LibreOffice', 'python3' => 'Python 3'];
        foreach ($tools as $bin => $name) {
            $check = Process::run(['which', $bin]);
            if ($check->successful()) {
                $path = trim($check->output());
                $this->info("  [OK] {$name} found at: {$path}");
            } else {
                $this->warn("  [MISSING] {$name} ({$bin}) NOT found");
            }
        }

        // 2. Check PHP limits
        $this->info('');
        $this->info('=== PHP Configuration ===');
        $this->info('  post_max_size: ' . ini_get('post_max_size'));
        $this->info('  upload_max_filesize: ' . ini_get('upload_max_filesize'));
        $this->info('  memory_limit: ' . ini_get('memory_limit'));

        // 3. Check Pending Jobs
        $this->info('');
        $this->info('=== Pending Jobs in Database ===');
        $queuedJobs = PdfJob::where('status', PdfJob::STATUS_QUEUED)->get();
        $this->info("  Total queued jobs: {$queuedJobs->count()}");

        foreach ($queuedJobs as $job) {
            $this->comment("  Processing queued Job #{$job->id} ({$job->tool_name})...");
            try {
                (new \App\Jobs\ProcessPdfJob($job))->handle($processor);
                $job->refresh();
                if ($job->isComplete()) {
                    $this->info("    -> SUCCESS! Output file: " . ($job->outputFile?->original_name ?? 'done'));
                } else {
                    $this->error("    -> Finished with status: {$job->status}");
                }
            } catch (\Throwable $e) {
                $this->error("    -> FAILED: " . $e->getMessage());
            }
        }

        return 0;
    }
}
