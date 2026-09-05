<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ToolController;
use App\Models\Plan;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Public pages
Route::get('/', function () {
    $plans = Plan::active()->get();

    return view('home', compact('plans'));
})->name('home');



// Static pages (stub views for now)
Route::get('/about', fn () => view('pages.about'))->name('about');
Route::get('/pricing', fn () => view('pages.pricing', ['plans' => Plan::active()->get()]))->name('pricing');
Route::get('/blog', fn () => view('pages.blog'))->name('blog');
Route::get('/contact', fn () => view('pages.contact'))->name('contact');
Route::get('/privacy', fn () => view('pages.privacy'))->name('privacy');
Route::get('/terms', fn () => view('pages.terms'))->name('terms');
Route::get('/cookies', fn () => view('pages.cookies'))->name('cookies');
Route::get('/pdpa', fn () => view('pages.pdpa'))->name('pdpa');

// Tools index
Route::get('/tools', [ToolController::class, 'index'])->name('tools');

// Individual tools — PDF editing (Requires authentication)
Route::prefix('tools')->name('tools.')->middleware(['auth'])->group(function () {
    Route::get('/pdf-to-word', [ToolController::class, 'pdfToWord'])->name('pdf-to-word');
    Route::get('/pdf-to-excel', [ToolController::class, 'pdfToExcel'])->name('pdf-to-excel');
    Route::get('/pdf-to-pptx', [ToolController::class, 'pdfToPptx'])->name('pdf-to-pptx');
    Route::get('/pdf-to-jpg', [ToolController::class, 'pdfToJpg'])->name('pdf-to-jpg');
    Route::get('/pdf-to-png', [ToolController::class, 'pdfToPng'])->name('pdf-to-png');
    Route::get('/pdf-to-txt', [ToolController::class, 'pdfToTxt'])->name('pdf-to-txt');

    Route::get('/word-to-pdf', [ToolController::class, 'wordToPdf'])->name('word-to-pdf');
    Route::get('/excel-to-pdf', [ToolController::class, 'excelToPdf'])->name('excel-to-pdf');
    Route::get('/pptx-to-pdf', [ToolController::class, 'pptxToPdf'])->name('pptx-to-pdf');
    Route::get('/image-to-pdf', [ToolController::class, 'imageToPdf'])->name('image-to-pdf');

    Route::get('/merge-pdf', [ToolController::class, 'mergePdf'])->name('merge-pdf');
    Route::get('/split-pdf', [ToolController::class, 'splitPdf'])->name('split-pdf');
    Route::get('/compress-pdf', [ToolController::class, 'compressPdf'])->name('compress-pdf');
    Route::get('/rotate-pdf', [ToolController::class, 'rotatePdf'])->name('rotate-pdf');
    Route::get('/delete-pages', [ToolController::class, 'deletePages'])->name('delete-pages');
    Route::get('/crop-pdf', [ToolController::class, 'cropPdf'])->name('crop-pdf');
    Route::get('/watermark-pdf', [ToolController::class, 'watermarkPdf'])->name('watermark-pdf');
    Route::get('/protect-pdf', [ToolController::class, 'protectPdf'])->name('protect-pdf');
    Route::get('/unlock-pdf', [ToolController::class, 'unlockPdf'])->name('unlock-pdf');
    Route::get('/sign-pdf', [ToolController::class, 'signPdf'])->name('sign-pdf');
    Route::get('/ocr-pdf', [ToolController::class, 'ocrPdf'])->name('ocr-pdf');
    Route::get('/ai-summary', [ToolController::class, 'aiSummary'])->name('ai-summary');
});

// Template library
Route::get('/templates', fn () => view('templates.index'))->name('templates');

// XML Sitemap for Search Engines
Route::get('/sitemap.xml', function () {
    $tools = [
        'pdf-to-word', 'pdf-to-excel', 'pdf-to-pptx', 'pdf-to-jpg', 'pdf-to-png', 'pdf-to-txt',
        'word-to-pdf', 'excel-to-pdf', 'pptx-to-pdf', 'image-to-pdf',
        'merge-pdf', 'split-pdf', 'compress-pdf', 'rotate-pdf', 'delete-pages', 'crop-pdf',
        'watermark-pdf', 'protect-pdf', 'unlock-pdf', 'sign-pdf', 'ocr-pdf', 'ai-summary',
    ];
    $pages = ['', 'tools', 'templates', 'pricing', 'about', 'contact', 'pdpa', 'privacy', 'terms'];

    $xml = '<?xml version="1.0" encoding="UTF-8"?>';
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

    foreach ($pages as $p) {
        $xml .= '<url><loc>'.url('/'.$p).'</loc><changefreq>weekly</changefreq><priority>'.($p === '' ? '1.0' : '0.8').'</priority></url>';
    }
    foreach ($tools as $t) {
        $xml .= '<url><loc>'.route('tools.'.$t).'</loc><changefreq>monthly</changefreq><priority>0.9</priority></url>';
    }

    $xml .= '</urlset>';

    return response($xml, 200)->header('Content-Type', 'application/xml');
})->name('sitemap');

// File management API (Requires authentication) with rate-limiting
Route::post('/files/upload', [FileController::class, 'upload'])
    ->name('files.upload')
    ->middleware(['auth', 'throttle:60,1']);

Route::get('/api/jobs/{job}', [FileController::class, 'jobStatus'])
    ->name('api.jobs.status')
    ->middleware(['auth']);

// Download route (works for local disk outputs)
Route::get('/files/download/{file}', [FileController::class, 'download'])
    ->name('files.download')
    ->middleware(['auth']);

// Authenticated routes
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/files', [DashboardController::class, 'files'])->name('dashboard.files');
    Route::delete('/dashboard/files/{file}', [DashboardController::class, 'deleteFile'])->name('dashboard.files.delete');

    // Profile & Password Management
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
    Route::get('/billing/upgrade/{plan}', [BillingController::class, 'upgrade'])->name('billing.upgrade');
    Route::get('/billing/downgrade/{plan}', fn (Plan $plan) => redirect()->route('billing.upgrade', $plan))->name('billing.downgrade');
    Route::post('/billing/charge', [BillingController::class, 'charge'])->name('billing.charge');
    Route::get('/billing/charge/{chargeId}/status', [BillingController::class, 'checkPromptPayStatus'])->name('billing.charge.status');
    Route::post('/billing/cancel', [BillingController::class, 'cancelSubscription'])->name('billing.cancel');
});

// Admin routes
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');
    Route::get('/admin/users', [AdminController::class, 'users'])->name('admin.users.index');
    Route::put('/admin/users/{user}', [AdminController::class, 'updateUser'])->name('admin.users.update');
    Route::post('/admin/users/{user}/assign-plan', [AdminController::class, 'assignPlan'])->name('admin.users.assign-plan');
    Route::delete('/admin/users/{user}', [AdminController::class, 'deleteUser'])->name('admin.users.delete');
});

// Omise Webhook
Route::post('/billing/webhook/omise', [BillingController::class, 'webhook'])->name('billing.webhook.omise');

// Auth routes with rate-limiting
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])
        ->name('login.post')
        ->middleware('throttle:5,1');

    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])
        ->name('register.post')
        ->middleware('throttle:10,1');

    // Google OAuth
    Route::get('/auth/google', [SocialiteController::class, 'redirectToGoogle'])->name('auth.google');
    Route::get('/auth/google/callback', [SocialiteController::class, 'handleGoogleCallback'])->name('auth.google.callback');

    // Password reset stub
    Route::get('/forgot-password', fn () => view('auth.forgot-password'))->name('password.request');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');
