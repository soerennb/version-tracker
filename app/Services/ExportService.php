<?php

namespace App\Services;

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

    public function exportVersionsToCsv(?Collection $versions = null): string
    {
        $filename = $this->buildFilename('versions', 'csv');
        Excel::store(new VersionsExport($versions), $filename, $this->disk());

        return $this->path($filename);
    }

    public function exportVersionsToPdf(?Collection $versions = null): string
    {
        $filename = $this->buildFilename('versions', 'pdf');

        $data = [
            'versions' => $versions ?? Version::with(['software', 'textContents'])->get(),
            'generated_at' => now(),
        ];

        $pdf = Pdf::loadView('exports.versions-pdf', $data)->setPaper('a4');

        Storage::disk($this->disk())->put($filename, $pdf->output());

        return $this->path($filename);
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
