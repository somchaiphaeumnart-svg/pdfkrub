<?php

use App\Http\Controllers\FileController;
use App\Http\Controllers\ToolController;
use App\Models\Plan;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — pdf2word Developer & Enterprise REST API (v1)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // System Health Check
    Route::get('/health', function () {
        return response()->json([
            'status' => 'healthy',
            'app' => config('app.name'),
            'timestamp' => now()->toIso8601String(),
            'version' => '1.0.0',
        ]);
    })->name('api.v1.health');

    // Available Plans
    Route::get('/plans', function () {
        return response()->json([
            'data' => Plan::active()->get(),
        ]);
    })->name('api.v1.plans');

    // Available Tools Registry
    Route::get('/tools', function () {
        return response()->json([
            'data' => ToolController::allTools(),
        ]);
    })->name('api.v1.tools');

    // Document Conversion Endpoint
    Route::post('/convert', [FileController::class, 'upload'])->name('api.v1.convert');

    // Job Status & Result Retrieval
    Route::get('/jobs/{job}', [FileController::class, 'jobStatus'])->name('api.v1.jobs.status');
});
