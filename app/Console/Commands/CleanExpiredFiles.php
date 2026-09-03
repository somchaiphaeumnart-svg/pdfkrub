<?php

namespace App\Console\Commands;

use App\Models\UploadedFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CleanExpiredFiles extends Command
{
    protected $signature = 'pdf2word:clean-files {--dry-run : แสดงรายการที่จะลบ โดยไม่ลบจริง}';

    protected $description = 'ลบไฟล์ที่หมดอายุออกจาก Storage และฐานข้อมูล';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        $this->info('🔍 ค้นหาไฟล์ที่หมดอายุ...');

        $expiredFiles = UploadedFile::expired()
            ->whereNull('deleted_at')
            ->get();

        if ($expiredFiles->isEmpty()) {
            $this->info('✅ ไม่มีไฟล์ที่หมดอายุ');

            return self::SUCCESS;
        }

        $this->info("📂 พบ {$expiredFiles->count()} ไฟล์ที่หมดอายุ");

        $deletedCount = 0;
        $failedCount = 0;

        $bar = $this->output->createProgressBar($expiredFiles->count());
        $bar->start();

        foreach ($expiredFiles as $file) {
            if ($isDryRun) {
                $this->line("\n  [DRY RUN] จะลบ: {$file->original_name} ({$file->getFileSizeForHumans()}) — หมดอายุ: {$file->expires_at}");
                $deletedCount++;
                $bar->advance();

                continue;
            }

            try {
                // Delete from object storage
                if (Storage::disk($file->storage_disk)->exists($file->storage_key)) {
                    Storage::disk($file->storage_disk)->delete($file->storage_key);
                }

                // Soft-delete the DB record
                $file->delete();
                $deletedCount++;

                Log::info('pdf2word: deleted expired file', [
                    'file_id' => $file->id,
                    'user_id' => $file->user_id,
                    'original_name' => $file->original_name,
                    'file_size' => $file->file_size,
                    'expires_at' => $file->expires_at,
                ]);
            } catch (\Exception $e) {
                $failedCount++;
                Log::error('pdf2word: failed to delete expired file', [
                    'file_id' => $file->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        if ($isDryRun) {
            $this->info("🔎 [DRY RUN] จะลบ {$deletedCount} ไฟล์");
        } else {
            $this->info("✅ ลบสำเร็จ: {$deletedCount} ไฟล์");
            if ($failedCount > 0) {
                $this->warn("⚠️  ลบไม่สำเร็จ: {$failedCount} ไฟล์ (ดู logs)");
            }
        }

        return self::SUCCESS;
    }
}
