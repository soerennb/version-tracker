<?php

use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ComponentVersionController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DeploymentController;
use App\Http\Controllers\Api\EnvironmentController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\FileAttachmentController;
use App\Http\Controllers\Api\ImpactAnalysisController;
use App\Http\Controllers\Api\InstalledReleaseController;
use App\Http\Controllers\Api\InvitationController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ReleaseCompositionController;
use App\Http\Controllers\Api\SbomController;
use App\Http\Controllers\Api\SoftwareController;
use App\Http\Controllers\Api\SoftwareDependencyController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\TextContentController;
use App\Http\Controllers\Api\TrackedComponentController;
use App\Http\Controllers\Api\VersionController;
use App\Http\Controllers\Api\VulnerabilityController;
use App\Http\Controllers\Public\CompareController;
use App\Http\Controllers\Public\OverviewController;
use App\Http\Controllers\Public\ProductController;
use App\Http\Controllers\Public\ReleaseController;
use App\Http\Controllers\Public\RuntimeController;
use App\Http\Controllers\Public\SearchController;
use App\Http\Controllers\Public\SecurityController;
use App\Http\Controllers\Public\TimelineController;
use App\Http\Middleware\EnforceApiTokenPermissions;
use Illuminate\Support\Facades\Route;

Route::prefix('public')->middleware('throttle:api')->group(function () {
    Route::get('overview', OverviewController::class)->middleware('public.feature:catalog');
    Route::get('runtime', RuntimeController::class);
    Route::get('search', SearchController::class)->middleware('public.feature:search');
    Route::get('products', [ProductController::class, 'index'])->middleware('public.feature:products');
    Route::get('products/{software}', [ProductController::class, 'show'])->middleware('public.feature:products');
    Route::get('releases/{version}', [ReleaseController::class, 'show'])->middleware('public.feature:catalog');
    Route::get('security', SecurityController::class)->middleware('public.feature:security');
    Route::get('security/{vulnerability}', [SecurityController::class, 'show'])->middleware('public.feature:security');
    Route::get('compare', CompareController::class)->middleware('public.feature:compare');
    Route::get('timeline', [TimelineController::class, 'index'])->middleware('public.feature:timeline');
});

Route::prefix('auth')->middleware('throttle:auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
});

Route::prefix('invitations')->middleware('throttle:auth')->group(function () {
    Route::get('{token}', [InvitationController::class, 'show']);
    Route::post('{token}/accept', [InvitationController::class, 'accept']);
});

Route::middleware(['throttle:api', 'auth:sanctum', EnforceApiTokenPermissions::class])->group(function () {
    Route::prefix('auth')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('email/verification-notification', [AuthController::class, 'resendVerification'])
            ->middleware('throttle:verification');
    });

    Route::middleware('verified')->group(function () {
        Route::post('invitations', [InvitationController::class, 'store']);
        Route::get('notifications', [NotificationController::class, 'index']);
        Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('notifications/{notification}/read', [NotificationController::class, 'read']);
        Route::post('notifications/read-all', [NotificationController::class, 'readAll']);
    });

    Route::apiResource('softwares', SoftwareController::class);
    Route::get('softwares/{software}/versions', [SoftwareController::class, 'versions']);

    Route::apiResource('environments', EnvironmentController::class)->only(['index', 'store', 'show', 'update']);
    Route::apiResource('customers', CustomerController::class)->only(['index', 'store', 'update']);
    Route::apiResource('tracked-components', TrackedComponentController::class)->only(['index', 'store', 'update']);
    Route::apiResource('component-versions', ComponentVersionController::class)->only(['index', 'store', 'update']);
    Route::get('versions/{version}/composition', [ReleaseCompositionController::class, 'show']);
    Route::put('versions/{version}/composition', [ReleaseCompositionController::class, 'update']);
    Route::get('environments/{environment}/installed/{software}', [InstalledReleaseController::class, 'show']);

    Route::apiResource('deployments', DeploymentController::class)->only(['index', 'store', 'show', 'update']);
    Route::post('deployments/{deployment}/approve', [DeploymentController::class, 'approve']);
    Route::post('deployments/{deployment}/start', [DeploymentController::class, 'start']);
    Route::post('deployments/{deployment}/succeed', [DeploymentController::class, 'succeed']);
    Route::post('deployments/{deployment}/fail', [DeploymentController::class, 'fail']);
    Route::post('deployments/{deployment}/cancel', [DeploymentController::class, 'cancel']);
    Route::post('deployments/{deployment}/rollback', [DeploymentController::class, 'rollback']);
    Route::post('deployments/{deployment}/correct', [DeploymentController::class, 'correct']);

    Route::apiResource('versions', VersionController::class);
    Route::post('versions/{version}/approve', [VersionController::class, 'approve']);
    Route::post('versions/{version}/publish', [VersionController::class, 'publish']);
    Route::post('versions/{version}/reject', [VersionController::class, 'reject']);

    Route::apiResource('versions.text-contents', TextContentController::class)
        ->shallow();

    Route::apiResource('versions.file-attachments', FileAttachmentController::class)
        ->shallow();

    Route::get('versions/{version}/sboms', [SbomController::class, 'index']);
    Route::post('versions/{version}/sboms', [SbomController::class, 'store']);
    Route::get('sboms/{sbomDocument}', [SbomController::class, 'show']);
    Route::post('sboms/{sbomDocument}/enrich', [SbomController::class, 'enrich']);
    Route::get('versions/{version}/readiness', [SbomController::class, 'readiness']);
    Route::get('versions/{version}/exceptions', [SbomController::class, 'exceptions']);
    Route::post('versions/{version}/exceptions', [SbomController::class, 'storeException']);
    Route::delete('release-exceptions/{releaseException}', [SbomController::class, 'destroyException']);

    Route::apiResource('software-dependencies', SoftwareDependencyController::class);
    Route::apiResource('vulnerabilities', VulnerabilityController::class);

    Route::prefix('impact')->group(function () {
        Route::get('software/{software}', [ImpactAnalysisController::class, 'software']);
        Route::get('versions/{version}', [ImpactAnalysisController::class, 'version']);
        Route::get('vulnerabilities/{vulnerability}', [ImpactAnalysisController::class, 'vulnerability']);
    });

    Route::apiResource('audit-logs', AuditLogController::class)->only(['index', 'show']);
    Route::apiResource('subscriptions', SubscriptionController::class)
        ->only(['index', 'store', 'destroy'])
        ->middleware('verified');

    Route::prefix('export')->group(function () {
        Route::get('versions/csv', [ExportController::class, 'versionsCsv']);
        Route::get('versions/pdf', [ExportController::class, 'versionsPdf']);
        Route::get('software/csv', [ExportController::class, 'softwareCsv']);
        Route::get('audit-logs/csv', [ExportController::class, 'auditLogsCsv']);
        Route::get('deployments/csv', [ExportController::class, 'deploymentsCsv']);
        Route::get('versions/{version}/compliance', [ExportController::class, 'compliance']);
    });
});
