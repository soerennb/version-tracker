<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Version;
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

    public function deploymentsCsv(Request $request): BinaryFileResponse
    {
        Gate::authorize('export_deployments');

        $path = $this->exportService->exportDeploymentsToCsv(filters: $request->only($this->deploymentFilterKeys()));

        return response()->download($path, basename($path));
    }

    public function compliance(Version $version): BinaryFileResponse
    {
        Gate::authorize('export_compliance');
        Gate::authorize('viewReadiness', $version);

        $path = $this->exportService->exportCompliancePackage($version);

        return response()->download($path, basename($path), [
            'Content-Type' => 'application/json',
        ]);
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

    /**
     * @return array<int, string>
     */
    protected function deploymentFilterKeys(): array
    {
        return [
            'software_id',
            'version_id',
            'environment_id',
            'status',
            'date_from',
            'date_to',
        ];
    }
}
