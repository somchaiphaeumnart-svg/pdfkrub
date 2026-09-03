<?php

namespace App\Jobs;

use App\Models\PdfJob;
use App\Models\UploadedFile;
use App\Services\PdfProcessingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessPdfJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum number of retry attempts before giving up.
     */
    public int $tries = 2;

    /**
     * Maximum execution time in seconds (5 minutes).
     */
    public int $timeout = 300;

    public function __construct(
        public readonly PdfJob $pdfJob
    ) {}

    public function handle(PdfProcessingService $processor): void
    {
        $job = $this->pdfJob;

        Log::info('ProcessPdfJob started', [
            'job_id' => $job->id,
            'tool' => $job->tool_name,
            'user_id' => $job->user_id,
        ]);

        try {
            $job->markAsProcessing();

            /** @var UploadedFile $outputFile */
            $outputFile = $processor->process($job);

            $job->markAsComplete($outputFile->id);

            Log::info('ProcessPdfJob completed', [
                'job_id' => $job->id,
                'tool' => $job->tool_name,
                'output_file' => $outputFile->original_name,
                'output_size' => $outputFile->file_size,
            ]);
        } catch (\Throwable $e) {
            Log::error('ProcessPdfJob failed', [
                'job_id' => $job->id,
                'tool' => $job->tool_name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $job->markAsFailed($e->getMessage());

            // Re-throw so Laravel queue marks the job as failed
            throw $e;
        }
    }

    /**
     * Handle job failure after all retries exhausted.
     */
    public function failed(\Throwable $e): void
    {
        $this->pdfJob->markAsFailed(
            'ประมวลผลไม่สำเร็จหลังจากพยายาม '.$this->tries." ครั้ง\n".$e->getMessage()
        );
    }
}
