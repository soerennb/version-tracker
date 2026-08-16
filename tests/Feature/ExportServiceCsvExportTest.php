<?php

namespace Tests\Feature;

use App\Services\ExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ExportServiceCsvExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_csv_exports_are_stored_on_the_exports_disk(): void
    {
        config()->set('filesystems.disks.exports', [
            'driver' => 'local',
            'root' => storage_path('app/exports'),
        ]);

        Storage::fake('exports');

        $exportService = app(ExportService::class);
        $versionsPath = $exportService->exportVersionsToCsv();
        $softwarePath = $exportService->exportSoftwareToCsv();
        $auditLogsPath = $exportService->exportAuditLogsToCsv();

        $versionsFile = $this->relativePath($versionsPath);
        $softwareFile = $this->relativePath($softwarePath);
        $auditLogsFile = $this->relativePath($auditLogsPath);

        Storage::disk('exports')->assertExists([$versionsFile, $softwareFile, $auditLogsFile]);
        $this->assertStringContainsString('Software', Storage::disk('exports')->get($versionsFile));
        $this->assertStringContainsString('Name', Storage::disk('exports')->get($softwareFile));
        $this->assertStringContainsString('Action', Storage::disk('exports')->get($auditLogsFile));
    }

    private function relativePath(string $path): string
    {
        return ltrim(Str::after($path, Storage::disk('exports')->path('')), DIRECTORY_SEPARATOR);
    }
}
