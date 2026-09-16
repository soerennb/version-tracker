<?php

namespace Tests\Feature;

use App\Enums\VersionStatus;
use App\Enums\VulnerabilitySeverity;
use App\Enums\VulnerabilityStatus;
use App\Models\Software;
use App\Models\Version;
use App\Models\Vulnerability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSecurityApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_security_feed_exposes_only_advisories_for_published_versions(): void
    {
        $software = Software::factory()->create(['name' => 'Signal Core']);
        $published = Version::factory()->for($software)->create(['status' => VersionStatus::PUBLISHED]);
        $draft = Version::factory()->for($software)->create(['status' => VersionStatus::DRAFT]);

        Vulnerability::factory()->for($published, 'affectedVersion')->create([
            'cve_id' => 'CVE-2026-9001',
            'severity' => VulnerabilitySeverity::CRITICAL,
            'status' => VulnerabilityStatus::OPEN,
        ]);
        Vulnerability::factory()->for($draft, 'affectedVersion')->create(['cve_id' => 'CVE-2026-9002']);
        Vulnerability::factory()->for($published, 'affectedVersion')->create([
            'cve_id' => 'CVE-2026-9003',
            'status' => VulnerabilityStatus::FALSE_POSITIVE,
        ]);

        $this->getJson('/api/public/security')
            ->assertOk()
            ->assertJsonPath('summary.total', 1)
            ->assertJsonPath('summary.open', 1)
            ->assertJsonPath('summary.critical', 1)
            ->assertJsonPath('data.0.cve_id', 'CVE-2026-9001')
            ->assertJsonPath('data.0.software.name', 'Signal Core')
            ->assertJsonPath('data.0.version.number', $published->version_number)
            ->assertJsonMissing(['cve_id' => 'CVE-2026-9002'])
            ->assertJsonMissing(['cve_id' => 'CVE-2026-9003']);
    }

    public function test_public_security_feed_can_filter_and_paginate(): void
    {
        $software = Software::factory()->create();
        $version = Version::factory()->for($software)->create(['status' => VersionStatus::PUBLISHED]);

        Vulnerability::factory()->for($version, 'affectedVersion')->create([
            'cve_id' => 'CVE-2026-9101',
            'severity' => VulnerabilitySeverity::HIGH,
            'status' => VulnerabilityStatus::OPEN,
        ]);
        Vulnerability::factory()->for($version, 'affectedVersion')->create([
            'cve_id' => 'CVE-2026-9102',
            'severity' => VulnerabilitySeverity::LOW,
            'status' => VulnerabilityStatus::OPEN,
        ]);

        $this->getJson('/api/public/security?severity=high&per_page=1')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.cve_id', 'CVE-2026-9101');
    }

    public function test_public_security_feed_rejects_unknown_filters(): void
    {
        $this->getJson('/api/public/security?status=false_positive')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }
}
