<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessPdfJob;
use App\Models\PdfJob;
use App\Models\UploadedFile;
use App\Services\PdfProcessingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileController extends Controller
{
    /**
     * Handle file upload and dispatch a processing job.
     */
    public function upload(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json([
                'error' => 'กรุณาเข้าสู่ระบบก่อนใช้งานฟังก์ชั่น',
                'login_url' => route('login'),
            ], 401);
        }

        $plan = $user->getActivePlan();
        $maxMb = $plan ? $plan->max_file_size_mb : 10;

        $maxKb = $maxMb * 1024;
        $allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'jpg', 'jpeg', 'png', 'webp'];

        $request->validate([
            'files' => ['required', 'array', 'min:1', 'max:20'],
            'files.*' => [
                'required',
                'file',
                "max:{$maxKb}",
                function ($attribute, $file, $fail) use ($allowedExtensions) {
                    $ext = strtolower($file->getClientOriginalExtension());
                    if (! in_array($ext, $allowedExtensions)) {
                        $fail('รองรับเฉพาะไฟล์เอกสารและรูปภาพที่กำหนดเท่านั้น');
                    }
                },
            ],
            'tool' => ['required', 'string', 'max:100'],
        ], [
            'files.required' => 'กรุณาเลือกไฟล์ที่ต้องการประมวลผล (หรือไฟล์อาจมีขนาดใหญ่เกินกว่าที่เซิร์ฟเวอร์รับได้)',
            'files.*.max' => "ขนาดไฟล์แต่ละไฟล์ต้องไม่เกิน {$maxMb} MB (อัปเกรดเป็น Pro เพื่อเพิ่มขนาดไฟล์)",
        ]);

        // Check daily usage limit for authenticated users on limited plans
        if ($user && $plan && ! $plan->hasUnlimitedConversions()) {
            $remaining = $user->getRemainingDailyConversions($request->tool);
            if ($remaining <= 0) {
                return response()->json([
                    'error' => 'คุณใช้งานครบ '.$plan->daily_conversions.' ครั้งแล้ววันนี้ อัปเกรดเป็น Pro เพื่อใช้งานไม่จำกัด',
                    'upgrade_url' => route('billing.index'),
                ], 429);
            }
        }

        $uploadedIds = [];
        $retentionHours = $plan?->file_retention_hours ?? 2;
        $expiresAt = now()->addHours($retentionHours);

        foreach ($request->file('files') as $file) {
            $storageKey = 'uploads/'.date('Y/m').'/'.Str::ulid().'.'.$file->getClientOriginalExtension();

            $file->storeAs('', $storageKey, 'local');

            $uploaded = UploadedFile::create([
                'user_id' => $user?->id,
                'session_id' => $user ? null : $request->session()->getId(),
                'original_name' => $file->getClientOriginalName(),
                'storage_key' => $storageKey,
                'storage_disk' => 'local',
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'file_hash' => hash_file('sha256', $file->getRealPath()),
                'expires_at' => $expiresAt,
            ]);

            $uploadedIds[] = $uploaded->id;
        }

        $config = $request->input('config', []);
        if (is_string($config)) {
            $config = json_decode($config, true) ?: [];
        }
        if ($request->has('degrees')) {
            $config['degrees'] = (int) $request->input('degrees');
        }
        if ($request->has('pages_to_delete')) {
            $pagesVal = $request->input('pages_to_delete');
            $config['pages_to_delete'] = is_array($pagesVal) ? implode(',', $pagesVal) : (string) $pagesVal;
        }
        if ($request->hasFile('watermark_image')) {
            $wmFile = $request->file('watermark_image');
            $wmKey = 'uploads/watermarks/'.\Illuminate\Support\Str::ulid().'.'.$wmFile->getClientOriginalExtension();
            $wmFile->storeAs('', $wmKey, 'local');
            $config['watermark_image_path'] = $wmKey;
        }
        foreach (['watermark_type', 'watermark_text', 'watermark_opacity', 'watermark_scale', 'watermark_position', 'watermark_rotation', 'watermark_pages', 'watermark_color'] as $wmParam) {
            if ($request->has($wmParam)) {
                $short = str_replace('watermark_', '', $wmParam);
                $config[$short] = $request->input($wmParam);
            }
        }
        if ($request->filled('password')) {
            $config['password'] = (string) $request->input('password');
        }
        if ($request->filled('quality')) {
            $config['quality'] = (string) $request->input('quality');
        }
        if ($request->filled('split_mode')) {
            $config['split_mode'] = (string) $request->input('split_mode');
        }
        if ($request->has('page_list')) {
            $config['page_list'] = (string) $request->input('page_list');
        }
        if ($request->has('merge_extracted')) {
            $config['merge_extracted'] = filter_var($request->input('merge_extracted'), FILTER_VALIDATE_BOOLEAN);
        }
        foreach (['orientation', 'page_size', 'margin'] as $imgParam) {
            if ($request->filled($imgParam)) {
                $config[$imgParam] = (string) $request->input($imgParam);
            }
        }
        foreach (['word_mode', 'word_pages', 'word_tables', 'word_keep_images'] as $wparam) {
            if ($request->has($wparam)) {
                $config[$wparam] = (string) $request->input($wparam);
            }
        }
        foreach (['image_dpi', 'image_pages_mode', 'image_selected_pages'] as $iparam) {
            if ($request->has($iparam)) {
                $config[$iparam] = $request->input($iparam);
            }
        }

        // Create the processing job record
        $pdfJob = PdfJob::create([
            'user_id' => $user?->id,
            'session_id' => $user ? null : $request->session()->getId(),
            'input_file_ids' => $uploadedIds,
            'tool_name' => $request->tool,
            'tool_config' => $config,
            'status' => PdfJob::STATUS_QUEUED,
            'queue_name' => 'default',
        ]);

        // Try direct processing so user gets instant results without waiting in queue
        try {
            $processor = app(PdfProcessingService::class);
            (new ProcessPdfJob($pdfJob))->handle($processor);
            $pdfJob->refresh();
            $pdfJob->load('outputFile');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Synchronous processing failed: '.$e->getMessage());
            $pdfJob->markAsFailed($e->getMessage());
            $pdfJob->refresh();
        }

        $responseData = [
            'job_id' => $pdfJob->id,
            'status' => $pdfJob->status,
            'status_url' => route('api.jobs.status', $pdfJob),
        ];

        $outputFile = $pdfJob->outputFile ?: ($pdfJob->output_file_id ? UploadedFile::find($pdfJob->output_file_id) : null);
        if ($pdfJob->isComplete() && $outputFile) {
            $responseData['download_url'] = $outputFile->getTemporaryUrl();
            $responseData['file_name'] = $outputFile->original_name;
            $responseData['file_size'] = $outputFile->getFileSizeForHumans();
        }

        if ($pdfJob->isFailed()) {
            $responseData['error'] = $pdfJob->error_message ?: 'เกิดข้อผิดพลาดในการประมวลผลไฟล์';
        }

        return response()->json($responseData, 201);
    }

    /**
     * Get job status (polled by Alpine.js jobPoller component).
     */
    public function jobStatus(PdfJob $job): JsonResponse
    {
        // Auto-process immediately if worker has not picked it up yet
        if ($job->status === PdfJob::STATUS_QUEUED) {
            try {
                $processor = app(PdfProcessingService::class);
                (new ProcessPdfJob($job))->handle($processor);
                $job->refresh();
                $job->load('outputFile');
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Poll auto-process failed: '.$e->getMessage());
                $job->markAsFailed($e->getMessage());
                $job->refresh();
            }
        }

        $response = [
            'id' => $job->id,
            'status' => $job->status,
            'progress' => $job->progress,
            'tool_name' => $job->tool_name,
            'created_at' => $job->created_at,
        ];

        $outputFile = $job->outputFile ?: ($job->output_file_id ? UploadedFile::find($job->output_file_id) : null);
        if ($job->isComplete() && $outputFile) {
            $response['download_url'] = $outputFile->getTemporaryUrl();
            $response['file_name'] = $outputFile->original_name;
            $response['file_size'] = $outputFile->getFileSizeForHumans();
            if (isset($outputFile->metadata['extracted_text'])) {
                $response['extracted_text'] = $outputFile->metadata['extracted_text'];
            }
        }

        if ($job->isFailed()) {
            $response['error_message'] = $job->error_message ?: 'เกิดข้อผิดพลาดในการประมวลผลไฟล์';
        }

        return response()->json($response);
    }

    /**
     * Serve a file download via signed URL (for local disk).
     */
    public function download(UploadedFile $file): StreamedResponse
    {
        if ($file->isExpired()) {
            abort(410, 'ไฟล์นี้หมดอายุแล้ว กรุณาแปลงใหม่อีกครั้ง');
        }

        return Storage::disk($file->storage_disk)->download(
            $file->storage_key,
            $file->original_name,
            ['Content-Type' => $file->mime_type]
        );
    }
}
