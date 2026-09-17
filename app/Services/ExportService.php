<?php

namespace App\Services;

use App\Enums\VulnerabilitySeverity;
use App\Enums\VulnerabilityStatus;
use App\Exports\AuditLogExport;
use App\Exports\DeploymentExport;
use App\Exports\SoftwareExport;
use App\Exports\VersionsExport;
use App\Models\Deployment;
use App\Models\Version;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ExportService
{
    protected string $directory = 'exports';

    public function exportVersionsToCsv(?Collection $versions = null, array $filters = []): string
    {
        $filename = $this->buildFilename('versions', 'csv');
        Excel::store(new VersionsExport($versions ?? $this->filteredVersions($filters)), $filename, $this->disk());

        return $this->path($filename);
    }

    public function exportVersionsToPdf(?Collection $versions = null, array $filters = []): string
    {
        $filename = $this->buildFilename('versions', 'pdf');

        $data = [
            'versions' => $versions ?? $this->filteredVersions($filters),
            'generated_at' => now(),
        ];

        $pdf = Pdf::loadView('exports.versions-pdf', $data)->setPaper('a4');

        Storage::disk($this->disk())->put($filename, $pdf->output());

        return $this->path($filename);
    }

    public function filteredVersions(array $filters = []): Collection
    {
        return Version::query()
            ->with(['software', 'textContents', 'fileAttachments', 'vulnerabilities'])
            ->when($filters['software_id'] ?? null, fn ($query, $softwareId) => $query->where('software_id', $softwareId))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('release_date', '>=', Carbon::parse($date)->toDateString()))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('release_date', '<=', Carbon::parse($date)->toDateString()))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['approval_status'] ?? null, fn ($query, $approvalStatus) => $query->where('approval_status', $approvalStatus))
            ->when($filters['compliance_status'] ?? null, fn ($query, $complianceStatus) => $query->whereHas('software', fn ($query) => $query->where('compliance_status', $complianceStatus)))
            ->when($filters['security'] ?? null, function ($query, string $security): void {
                match ($security) {
                    'with_vulnerabilities' => $query->whereHas('vulnerabilities'),
                    'without_vulnerabilities' => $query->whereDoesntHave('vulnerabilities'),
                    'open_high_critical' => $query->whereHas('vulnerabilities', fn ($query) => $query
                        ->where('status', VulnerabilityStatus::OPEN->value)
                        ->whereIn('severity', [VulnerabilitySeverity::HIGH->value, VulnerabilitySeverity::CRITICAL->value])),
                    default => null,
                };
            })
            ->orderByDesc('release_date')
            ->get();
    }

    public function exportSoftwareToCsv(): string
    {
        $filename = $this->buildFilename('software', 'csv');
        Excel::store(new SoftwareExport, $filename, $this->disk());

        return $this->path($filename);
    }

    public function exportAuditLogsToCsv(?string $from = null, ?string $to = null): string
    {
        $filename = $this->buildFilename('audit-logs', 'csv');
        $export = new AuditLogExport(
            $from ? Carbon::parse($from) : null,
            $to ? Carbon::parse($to) : null
        );

        Excel::store($export, $filename, $this->disk());

        return $this->path($filename);
    }

    public function exportDeploymentsToCsv(?Collection $deployments = null, array $filters = []): string
    {
        $filename = $this->buildFilename('deployments', 'csv');
        Excel::store(new DeploymentExport($deployments ?? $this->filteredDeployments($filters)), $filename, $this->disk());

        return $this->path($filename);
    }

    public function filteredDeployments(array $filters = []): Collection
    {
        return Deployment::query()
            ->with(['software', 'version', 'environment', 'creator', 'approver', 'executor'])
            ->with('events.actor')
            ->withCount('events')
            ->when($filters['software_id'] ?? null, fn ($query, $softwareId) => $query->where('software_id', $softwareId))
            ->when($filters['version_id'] ?? null, fn ($query, $versionId) => $query->where('version_id', $versionId))
            ->when($filters['environment_id'] ?? null, fn ($query, $environmentId) => $query->where('environment_id', $environmentId))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', Carbon::parse($date)->toDateString()))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', Carbon::parse($date)->toDateString()))
            ->latest('created_at')
            ->get();
    }

    public function exportCompliancePackage(Version $version): string
    {
        $version->load([
            'software',
            'sbomDocuments.components',
            'sbomDocuments.findings',
            'releaseExceptions.owner',
            'fileAttachments',
            'vulnerabilities',
        ]);

        $payload = [
            'schema_version' => 1,
            'generated_at' => now()->toISOString(),
            'version' => [
                'id' => $version->id,
                'software_id' => $version->software_id,
                'software' => $version->software?->name,
                'version_number' => $version->version_number,
                'release_date' => $version->release_date?->toDateString(),
                'status' => $version->status?->value,
                'approval_status' => $version->approval_status?->value,
            ],
            'readiness' => app(ReleaseReadinessService::class)->evaluate($version),
            'artifacts' => $version->fileAttachments->map(fn ($attachment): array => [
                'id' => $attachment->id,
                'filename' => $attachment->filename,
                'artifact_type' => $attachment->artifact_type,
                'mime_type' => $attachment->mime_type,
                'size' => $attachment->size,
                'checksum' => $attachment->checksum,
                'checksum_algorithm' => $attachment->checksum_algorithm,
                'verification_status' => $attachment->verification_status,
            ])->values()->all(),
            'sboms' => $version->sbomDocuments->map(fn ($document): array => [
                'id' => $document->id,
                'filename' => $document->filename,
                'format' => $document->format,
                'spec_version' => $document->spec_version,
                'serial_number' => $document->serial_number,
                'document_hash' => $document->document_hash,
                'source' => $document->source,
                'status' => $document->status,
                'parsed_at' => $document->parsed_at?->toISOString(),
                'components' => $document->components->map(fn ($component): array => [
                    'bom_ref' => $component->bom_ref,
                    'package_type' => $component->package_type,
                    'group_name' => $component->group_name,
                    'name' => $component->name,
                    'version' => $component->version,
                    'purl' => $component->purl,
                    'cpe' => $component->cpe,
                    'supplier' => $component->supplier,
                    'licenses' => $component->licenses ?? [],
                    'hashes' => $component->hashes ?? [],
                    'properties' => $component->properties ?? [],
                ])->values()->all(),
                'findings' => $document->findings->map(fn ($finding): array => [
                    'external_id' => $finding->external_id,
                    'source' => $finding->source,
                    'severity' => $finding->severity?->value,
                    'cvss_score' => $finding->cvss_score,
                    'epss_score' => $finding->epss_score,
                    'is_kev' => $finding->is_kev,
                    'risk_score' => $finding->risk_score,
                    'risk_factors' => $finding->risk_factors ?? [],
                    'exploitability' => $finding->exploitability?->value,
                    'status' => $finding->status?->value,
                    'component_id' => $finding->sbom_component_id,
                    'first_seen_at' => $finding->first_seen_at?->toISOString(),
                    'last_seen_at' => $finding->last_seen_at?->toISOString(),
                ])->values()->all(),
            ])->values()->all(),
            'release_exceptions' => $version->releaseExceptions->map(fn ($exception): array => [
                'id' => $exception->id,
                'check_code' => $exception->check_code,
                'reason' => $exception->reason,
                'expires_at' => $exception->expires_at?->toISOString(),
                'approved_at' => $exception->approved_at?->toISOString(),
                'revoked_at' => $exception->revoked_at?->toISOString(),
                'owner' => $exception->owner?->name,
            ])->values()->all(),
        ];

        $filename = $this->buildFilename('compliance', 'json');
        Storage::disk($this->disk())->put($filename, json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));

        return $this->path($filename);
    }

    protected function buildFilename(string $prefix, string $extension): string
    {
        return sprintf('%s/%s-%s.%s', trim($this->directory, '/'), $prefix, now()->format('Ymd-His').'-'.Str::random(6), $extension);
    }

    protected function path(string $filename): string
    {
        return Storage::disk($this->disk())->path($filename);
    }

    protected function disk(): string
    {
        return config('filesystems.disks.exports') ? 'exports' : config('filesystems.default', 'local');
    }
}
