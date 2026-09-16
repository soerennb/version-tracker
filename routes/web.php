<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Public\DownloadController;
use App\Http\Controllers\PublicSecurityController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::get('/account/verify-email/{id}/{hash}', [AuthController::class, 'verify'])
    ->middleware(['auth', 'signed', 'throttle:verification'])
    ->name('verification.verify');

Route::get('/security', [PublicSecurityController::class, 'index'])
    ->middleware('public.feature:security')
    ->name('public.security');
Route::get('/downloads/{version}/{fileAttachment}', DownloadController::class)
    ->middleware('throttle:api')
    ->name('public.download');

Route::middleware('auth')->prefix('admin/exports')->name('admin.exports.')->group(function () {
    Route::get('versions.csv', [ExportController::class, 'versionsCsv'])->name('versions.csv');
    Route::get('versions.pdf', [ExportController::class, 'versionsPdf'])->name('versions.pdf');
    Route::get('software.csv', [ExportController::class, 'softwareCsv'])->name('software.csv');
    Route::get('audit-logs.csv', [ExportController::class, 'auditLogsCsv'])->name('audit-logs.csv');
});

Route::view('/{any}', 'welcome')->where('any', '^(?!admin|api).*$');
