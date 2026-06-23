<?php

namespace Tests\Feature;

use App\Enums\ApprovalStatus;
use App\Enums\ComplianceStatus;
use App\Enums\VersionStatus;
use App\Enums\VulnerabilitySeverity;
use App\Enums\VulnerabilityStatus;
use App\Models\Software;
use App\Models\Version;
use App\Models\Vulnerability;
use App\Services\ExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportServiceFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_version_export_filters_by_product_status_approval_security_and_compliance(): void
    {
        $matchingSoftware = Software::factory()->create([
            'compliance_status' => ComplianceStatus::COMPLIANT,
        ]);
        $otherSoftware = Software::factory()->create([
            'compliance_status' => ComplianceStatus::NON_COMPLIANT,
        ]);
        $matchingVersion = Version::factory()->for($matchingSoftware)->create([
            'release_date' => '2026-02-01',
            'status' => VersionStatus::PUBLISHED,
            'approval_status' => ApprovalStatus::APPROVED,
        ]);
        Version::factory()->for($otherSoftware)->create([
            'release_date' => '2026-02-01',
            'status' => VersionStatus::PUBLISHED,
            'approval_status' => ApprovalStatus::APPROVED,
        ]);
        Vulnerability::factory()->for($matchingVersion, 'affectedVersion')->create([
            'severity' => VulnerabilitySeverity::HIGH,
            'status' => VulnerabilityStatus::OPEN,
        ]);

        $versions = app(ExportService::class)->filteredVersions([
            'software_id' => $matchingSoftware->id,
            'date_from' => '2026-01-01',
            'date_to' => '2026-12-31',
            'status' => VersionStatus::PUBLISHED->value,
            'approval_status' => ApprovalStatus::APPROVED->value,
            'security' => 'open_high_critical',
            'compliance_status' => ComplianceStatus::COMPLIANT->value,
        ]);

        $this->assertCount(1, $versions);
        $this->assertTrue($versions->first()->is($matchingVersion));
    }

    public function test_version_export_security_without_vulnerabilities_filter(): void
    {
        $cleanVersion = Version::factory()->create();
        $vulnerableVersion = Version::factory()->create();
        Vulnerability::factory()->for($vulnerableVersion, 'affectedVersion')->create();

        $versions = app(ExportService::class)->filteredVersions([
            'security' => 'without_vulnerabilities',
        ]);

        $this->assertTrue($versions->contains($cleanVersion));
        $this->assertFalse($versions->contains($vulnerableVersion));
    }
}
