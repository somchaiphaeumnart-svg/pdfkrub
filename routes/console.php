<?php

use App\Console\Commands\CleanExpiredFiles;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| pdf2word — Scheduled Tasks
|--------------------------------------------------------------------------
*/

// ลบไฟล์หมดอายุ: ทุก 1 ชั่วโมง ตรวจหาและลบไฟล์ที่ expires_at ผ่านแล้ว
Schedule::command(CleanExpiredFiles::class)
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/clean-files.log'));

// ทำความสะอาด expired sessions ทุกวัน
Schedule::command('session:gc')
    ->daily()
    ->runInBackground();
