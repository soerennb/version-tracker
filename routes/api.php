<?php

use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\FileAttachmentController;
use App\Http\Controllers\Api\SoftwareController;
use App\Http\Controllers\Api\SoftwareDependencyController;
use App\Http\Controllers\Api\TextContentController;
use App\Http\Controllers\Api\VersionController;
use App\Http\Controllers\Public\TimelineController;
use Illuminate\Support\Facades\Route;

Route::prefix('public')->group(function () {
    Route::get('timeline', [TimelineController::class, 'index']);
});

Route::middleware('auth:sanctum')->group(function () {
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

    Route::apiResource('audit-logs', AuditLogController::class)->only(['index', 'show']);

    Route::prefix('export')->group(function () {
        Route::get('versions/csv', [ExportController::class, 'versionsCsv']);
        Route::get('versions/pdf', [ExportController::class, 'versionsPdf']);
        Route::get('software/csv', [ExportController::class, 'softwareCsv']);
        Route::get('audit-logs/csv', [ExportController::class, 'auditLogsCsv']);
    });
});
