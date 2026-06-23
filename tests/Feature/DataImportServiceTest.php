<?php

namespace Tests\Feature;

use App\Models\Software;
use App\Models\Version;
use App\Models\Vulnerability;
use App\Services\DataImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataImportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_json_import_supports_dry_run_without_writing(): void
    {
        Software::factory()->create(['name' => 'Core API']);

        $payload = json_encode([
            'versions' => [
                [
                    'software_name' => 'Core API',
                    'version_number' => '1.2.0',
                    'release_date' => '2026-03-01',
                ],
            ],
        ]);

        $result = app(DataImportService::class)->importJson($payload, dryRun: true);

        $this->assertSame(1, $result['created']);
        $this->assertSame([], $result['errors']);
        $this->assertSame(0, Version::query()->count());
    }

    public function test_csv_import_creates_software(): void
    {
        $csv = "name,description,status,compliance_status\nCore API,Primary API,active,compliant\n";

        $result = app(DataImportService::class)->importCsv('software', $csv);

        $this->assertSame(1, $result['created']);
        $this->assertDatabaseHas('software', [
            'name' => 'Core API',
            'description' => 'Primary API',
        ]);
    }

    public function test_import_reports_errors_and_avoids_partial_imports(): void
    {
        $software = Software::factory()->create(['name' => 'Core API']);
        Version::factory()->for($software)->create(['version_number' => '1.0.0']);

        $result = app(DataImportService::class)->importPayload([
            'software' => [
                ['name' => 'New App'],
            ],
            'vulnerabilities' => [
                [
                    'software_name' => 'Core API',
                    'version_number' => '9.9.9',
                    'cve_id' => 'CVE-2026-7001',
                ],
            ],
        ]);

        $this->assertNotEmpty($result['errors']);
        $this->assertDatabaseMissing('software', ['name' => 'New App']);
        $this->assertSame(0, Vulnerability::query()->count());
    }
}
