<?php

use App\Http\Controllers\Api\AttachmentUploadController;
use App\Http\Middleware\EnforceApiTokenPermissions;
use App\Mcp\Servers\VersionTrackerServer;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/versiontracker', VersionTrackerServer::class)->middleware(['auth:sanctum', EnforceApiTokenPermissions::class.':mcp', 'throttle:api'])->name('mcp.versiontracker');
Route::post('/mcp/uploads/{upload}', AttachmentUploadController::class)->middleware(['auth:sanctum', EnforceApiTokenPermissions::class.':mcp', 'throttle:api', SubstituteBindings::class])->name('mcp.uploads.transfer');
