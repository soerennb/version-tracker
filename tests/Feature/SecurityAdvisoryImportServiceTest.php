<?php

namespace Tests\Feature;

use App\Enums\ExploitabilityStatus;
use App\Enums\VulnerabilitySeverity;
use App\Models\Software;
use App\Models\Version;
use App\Models\Vulnerability;
use App\Services\FixtureSecurityAdvisoryProvider;
use App\Services\SecurityAdvisoryImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityAdvisoryImportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_fixture_provider_imports_security_advisories(): void
    {
        $software = Software::factory()->create(['name' => 'Core API']);
        $affectedVersion = Version::factory()->for($software)->create(['version_number' => '1.2.0']);
        $fixedVersion = Version::factory()->for($software)->create(['version_number' => '1.2.1']);

        $provider = new FixtureSecurityAdvisoryProvider([
            [
                'cve_id' => 'cve-2026-4001',
                'software_name' => 'Core API',
                'version_number' => $affectedVersion->version_number,
                'fixed_version' => $fixedVersion->version_number,
                'severity' => 'high',
                'cvss_score' => 8.8,
                'description' => 'Remote code execution in release artifact parser.',
                'source' => 'OSV',
                'source_url' => 'https://osv.dev/vulnerability/CVE-2026-4001',
                'affected_range' => '<1.2.1',
                'exploitability' => 'proof_of_concept',
                'published_date' => '2026-02-01',
            ],
        ]);

        $result = app(SecurityAdvisoryImportService::class)->importFromProvider($provider);

        $this->assertSame(1, $result['imported']);
        $this->assertSame(0, $result['updated']);
        $this->assertSame(0, $result['skipped']);

        $vulnerability = Vulnerability::query()->firstOrFail();

        $this->assertSame('CVE-2026-4001', $vulnerability->cve_id);
        $this->assertSame($affectedVersion->id, $vulnerability->affected_version_id);
        $this->assertSame($fixedVersion->id, $vulnerability->fixed_version_id);
        $this->assertSame(VulnerabilitySeverity::HIGH, $vulnerability->severity);
        $this->assertSame('8.8', $vulnerability->cvss_score);
        $this->assertSame(ExploitabilityStatus::PROOF_OF_CONCEPT, $vulnerability->exploitability);
    }

    public function test_import_updates_existing_advisory_for_same_cve_and_version(): void
    {
        $software = Software::factory()->create(['name' => 'Core API']);
        $version = Version::factory()->for($software)->create(['version_number' => '1.2.0']);
        Vulnerability::factory()->for($version, 'affectedVersion')->create([
            'cve_id' => 'CVE-2026-4001',
            'severity' => VulnerabilitySeverity::MEDIUM,
            'description' => 'Old description',
        ]);

        $result = app(SecurityAdvisoryImportService::class)->import([
            [
                'cve_id' => 'CVE-2026-4001',
                'software_name' => 'Core API',
                'version_number' => '1.2.0',
                'severity' => 'critical',
                'description' => 'Updated description',
            ],
        ]);

        $this->assertSame(0, $result['imported']);
        $this->assertSame(1, $result['updated']);
        $this->assertSame(1, Vulnerability::query()->count());
        $this->assertSame(VulnerabilitySeverity::CRITICAL, Vulnerability::query()->firstOrFail()->severity);
        $this->assertSame('Updated description', Vulnerability::query()->firstOrFail()->description);
    }

    public function test_import_supports_dry_run_and_collects_errors(): void
    {
        $software = Software::factory()->create(['name' => 'Core API']);
        Version::factory()->for($software)->create(['version_number' => '1.2.0']);

        $result = app(SecurityAdvisoryImportService::class)->import([
            [
                'cve_id' => 'CVE-2026-4001',
                'software_name' => 'Core API',
                'version_number' => '1.2.0',
            ],
            [
                'cve_id' => 'CVE-2026-4002',
                'software_name' => 'Missing App',
                'version_number' => '1.0.0',
            ],
        ], dryRun: true);

        $this->assertSame(1, $result['imported']);
        $this->assertSame(1, $result['skipped']);
        $this->assertStringContainsString('Software not found', $result['errors'][0]);
        $this->assertSame(0, Vulnerability::query()->count());
    }
}
