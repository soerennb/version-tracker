<?php

use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\FileAttachmentController;
use App\Http\Controllers\Api\ImpactAnalysisController;
use App\Http\Controllers\Api\SoftwareController;
use App\Http\Controllers\Api\SoftwareDependencyController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\TextContentController;
use App\Http\Controllers\Api\VersionController;
use App\Http\Controllers\Public\CompareController;
use App\Http\Controllers\Public\ProductController;
use App\Http\Controllers\Public\ReleaseController;
use App\Http\Controllers\Public\TimelineController;
use Illuminate\Support\Facades\Route;

Route::prefix('public')->middleware('throttle:api')->group(function () {
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/{software}', [ProductController::class, 'show']);
    Route::get('releases/{version}', [ReleaseController::class, 'show']);
    Route::get('compare', CompareController::class);
    Route::get('timeline', [TimelineController::class, 'index']);
});

Route::middleware(['throttle:api', 'auth:sanctum'])->group(function () {
    Route::apiResource('softwares', SoftwareController::class);
    Route::get('softwares/{software}/versions', [SoftwareController::class, 'versions']);

    Route::apiResource('versions', VersionController::class);
    Route::post('versions/{version}/approve', [VersionController::class, 'approve']);
    Route::post('versions/{version}/reject', [VersionController::class, 'reject']);

    Route::apiResource('versions.text-contents', TextContentController::class)
        ->shallow();

    Route::apiResource('versions.file-attachments', FileAttachmentController::class)
        ->shallow();

    Route::apiResource('software-dependencies', SoftwareDependencyController::class);

    Route::prefix('impact')->group(function () {
        Route::get('software/{software}', [ImpactAnalysisController::class, 'software']);
        Route::get('versions/{version}', [ImpactAnalysisController::class, 'version']);
        Route::get('vulnerabilities/{vulnerability}', [ImpactAnalysisController::class, 'vulnerability']);
    });

    Route::apiResource('audit-logs', AuditLogController::class)->only(['index', 'show']);
    Route::apiResource('subscriptions', SubscriptionController::class)->only(['index', 'store', 'destroy']);

    Route::prefix('export')->group(function () {
        Route::get('versions/csv', [ExportController::class, 'versionsCsv']);
        Route::get('versions/pdf', [ExportController::class, 'versionsPdf']);
        Route::get('software/csv', [ExportController::class, 'softwareCsv']);
        Route::get('audit-logs/csv', [ExportController::class, 'auditLogsCsv']);
    });
});
