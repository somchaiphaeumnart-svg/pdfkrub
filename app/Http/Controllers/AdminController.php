<?php

namespace App\Http\Controllers;

use App\Models\PdfJob;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\UploadedFile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function index(): View
    {
        // Metric cards
        $totalUsers = User::count();
        $newUsersToday = User::whereDate('created_at', today())->count();

        $activeSubscriptions = Subscription::where('status', 'active')->count();
        $totalJobs = PdfJob::count();
        $completedJobs = PdfJob::where('status', PdfJob::STATUS_DONE)->count();
        $failedJobs = PdfJob::where('status', PdfJob::STATUS_FAILED)->count();
        $successRate = $totalJobs > 0 ? round(($completedJobs / $totalJobs) * 100, 1) : 100;

        $totalStorageBytes = UploadedFile::sum('file_size');
        $totalFiles = UploadedFile::count();

        // Subscriptions breakdown by plan
        $plansBreakdown = Plan::withCount(['subscriptions' => function ($q) {
            $q->where('status', 'active');
        }])->get();

        // Top Tools
        $topTools = PdfJob::select('tool_name', DB::raw('count(*) as count'))
            ->groupBy('tool_name')
            ->orderByDesc('count')
            ->limit(6)
            ->get();

        // Recent Jobs Stream
        $recentJobs = PdfJob::with(['user', 'outputFile'])
            ->latest()
            ->limit(15)
            ->get();

        // Recent Users
        $recentUsers = User::with('plan')
            ->latest()
            ->limit(8)
            ->get();

        return view('admin.index', compact(
            'totalUsers',
            'newUsersToday',
            'activeSubscriptions',
            'totalJobs',
            'completedJobs',
            'failedJobs',
            'successRate',
            'totalStorageBytes',
            'totalFiles',
            'plansBreakdown',
            'topTools',
            'recentJobs',
            'recentUsers'
        ));
    }
}
