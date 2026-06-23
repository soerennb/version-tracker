<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportController extends Controller
{
    public function __construct(protected ExportService $exportService) {}

    public function versionsCsv(Request $request): BinaryFileResponse
    {
        Gate::authorize('export_data');

        $path = $this->exportService->exportVersionsToCsv(filters: $request->only($this->versionFilterKeys()));

        return response()->download($path, basename($path));
    }

    public function versionsPdf(Request $request): BinaryFileResponse
    {
        Gate::authorize('export_data');

        $path = $this->exportService->exportVersionsToPdf(filters: $request->only($this->versionFilterKeys()));

        return response()->download($path, basename($path));
    }

    public function softwareCsv(): BinaryFileResponse
    {
        Gate::authorize('export_data');

        $path = $this->exportService->exportSoftwareToCsv();

        return response()->download($path, basename($path));
    }

    public function auditLogsCsv(Request $request): BinaryFileResponse
    {
        Gate::authorize('view_audit_logs');

        $path = $this->exportService->exportAuditLogsToCsv($request->get('from'), $request->get('to'));

        return response()->download($path, basename($path));
    }

    /**
     * @return array<int, string>
     */
    protected function versionFilterKeys(): array
    {
        return [
            'software_id',
            'date_from',
            'date_to',
            'status',
            'approval_status',
            'security',
            'compliance_status',
        ];
    }
}
