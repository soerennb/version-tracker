<?php

namespace App\Services;

use App\Enums\VulnerabilitySeverity;
use App\Enums\VulnerabilityStatus;
use App\Exports\AuditLogExport;
use App\Exports\SoftwareExport;
use App\Exports\VersionsExport;
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
