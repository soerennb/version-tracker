<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(AuditLog::class, 'audit_log');
    }

    public function index(Request $request): JsonResponse
    {
        $logs = AuditLog::query()
            ->with('user')
            ->when($request->filled('action'), fn ($query) => $query->where('action', $request->string('action')))
            ->when($request->filled('model_type'), fn ($query) => $query->where('model_type', $request->string('model_type')))
            ->latest('created_at')
            ->paginate(50)
            ->appends($request->query());

        return AuditLogResource::collection($logs)->response();
    }

    public function show(AuditLog $auditLog): JsonResponse
    {
        return AuditLogResource::make($auditLog->load('user'))->response();
    }
}
