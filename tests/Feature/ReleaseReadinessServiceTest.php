<?php

namespace Tests\Feature;

use App\Enums\Language;
use App\Enums\SoftwareStatus;
use App\Enums\SupportStatus;
use App\Enums\VersionStatus;
use App\Enums\VulnerabilitySeverity;
use App\Enums\VulnerabilityStatus;
use App\Models\FileAttachment;
use App\Models\Software;
use App\Models\SoftwareDependency;
use App\Models\TextContent;
use App\Models\Version;
use App\Models\Vulnerability;
use App\Services\ReleaseReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReleaseReadinessServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_release_is_ready_when_required_checks_pass(): void
    {
        $dependencyTarget = Software::factory()->create([
            'status' => SoftwareStatus::ACTIVE->value,
        ]);
        Version::factory()->create([
            'software_id' => $dependencyTarget->id,
            'version_number' => '1.0.0',
            'status' => VersionStatus::PUBLISHED->value,
        ]);
        $software = Software::factory()->create([
            'status' => SoftwareStatus::ACTIVE->value,
        ]);
        SoftwareDependency::factory()->create([
            'software_id' => $software->id,
            'depends_on_software_id' => $dependencyTarget->id,
            'min_version_id' => null,
            'max_version_id' => null,
        ]);
        $version = Version::factory()->create([
            'software_id' => $software->id,
            'support_status' => SupportStatus::SUPPORTED->value,
        ]);

        foreach (Language::cases() as $language) {
            TextContent::factory()->create([
                'version_id' => $version->id,
                'language' => $language->value,
            ]);
        }

        FileAttachment::factory()->create([
            'version_id' => $version->id,
        ]);

        $readiness = app(ReleaseReadinessService::class)->evaluate($version);

        $this->assertTrue($readiness['is_ready']);
        $this->assertSame(100, $readiness['score']);
        $this->assertSame([], $readiness['blockers']);
    }

    public function test_release_readiness_reports_blockers(): void
    {
        $dependencyTarget = Software::factory()->create([
            'status' => SoftwareStatus::INACTIVE->value,
        ]);
        Version::factory()->create([
            'software_id' => $dependencyTarget->id,
            'version_number' => '1.0.0',
            'status' => VersionStatus::DRAFT->value,
        ]);
        $software = Software::factory()->create([
            'status' => SoftwareStatus::ACTIVE->value,
        ]);
        SoftwareDependency::factory()->create([
            'software_id' => $software->id,
            'depends_on_software_id' => $dependencyTarget->id,
            'min_version_id' => null,
            'max_version_id' => null,
        ]);
        $version = Version::factory()->create([
            'software_id' => $software->id,
            'support_status' => null,
            'eol_date' => null,
            'lts_date' => null,
        ]);
        TextContent::factory()->create([
            'version_id' => $version->id,
            'language' => Language::DE->value,
        ]);
        Vulnerability::factory()->create([
            'affected_version_id' => $version->id,
            'severity' => VulnerabilitySeverity::HIGH->value,
            'status' => VulnerabilityStatus::OPEN->value,
        ]);

        $readiness = app(ReleaseReadinessService::class)->evaluate($version);
        $blockerCodes = collect($readiness['blockers'])->pluck('code')->all();

        $this->assertFalse($readiness['is_ready']);
        $this->assertSame(0, $readiness['score']);
        $this->assertContains('missing_required_content', $blockerCodes);
        $this->assertContains('blocking_vulnerabilities', $blockerCodes);
        $this->assertContains('invalid_dependencies', $blockerCodes);
        $this->assertContains('missing_attachments', $blockerCodes);
        $this->assertContains('missing_lifecycle', $blockerCodes);
    }
}
