<?php

namespace App\Http\Controllers;

use App\Models\PdfJob;
use App\Models\UploadedFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $recentJobs = $user->pdfJobs()
            ->with('outputFile')
            ->latest()
            ->limit(10)
            ->get();

        $plan = $user->getActivePlan();

        $stats = [
            'total_jobs' => $user->pdfJobs()->count(),
            'completed_jobs' => $user->pdfJobs()->where('status', PdfJob::STATUS_DONE)->count(),
            'storage_used' => $user->storage_used,
            'storage_used_formatted' => $this->formatBytes($user->storage_used),
            'plan' => $plan,
            'daily_remaining' => $plan->hasUnlimitedConversions()
                ? 'ไม่จำกัด'
                : 'เหลือ '.$user->getRemainingDailyConversions().' / '.$plan->daily_conversions.' ครั้ง',
        ];

        return view('dashboard.index', compact('recentJobs', 'stats'));
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $index = (int) floor(log($bytes, 1024));
        $index = min($index, count($units) - 1);

        return round($bytes / (1024 ** $index), 2).' '.$units[$index];
    }

    public function files(): View
    {
        $files = auth()->user()
            ->uploadedFiles()
            ->whereNull('deleted_at')
            ->latest()
            ->paginate(20);

        return view('dashboard.files', compact('files'));
    }

    public function deleteFile(UploadedFile $file): RedirectResponse|JsonResponse
    {
        abort_unless($file->user_id === auth()->id(), 403);

        $file->delete();

        if (request()->wantsJson()) {
            return response()->json(['message' => 'ลบไฟล์เรียบร้อย']);
        }

        return redirect()->route('dashboard.files')->with('success', 'ลบไฟล์เรียบร้อย');
    }
}
