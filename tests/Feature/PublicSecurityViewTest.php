<?php

namespace Tests\Feature;

use App\Enums\VersionStatus;
use App\Enums\VulnerabilityStatus;
use App\Models\Software;
use App\Models\Version;
use App\Models\Vulnerability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSecurityViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_security_view_only_shows_published_advisories(): void
    {
        $this->withoutVite();

        $software = Software::factory()->create(['name' => 'Core API']);
        $publishedVersion = Version::factory()->for($software)->create([
            'version_number' => '1.0.0',
            'status' => VersionStatus::PUBLISHED,
        ]);
        $draftVersion = Version::factory()->for($software)->create([
            'version_number' => '2.0.0',
            'status' => VersionStatus::DRAFT,
        ]);

        Vulnerability::factory()->for($publishedVersion, 'affectedVersion')->create([
            'cve_id' => 'CVE-2026-5001',
            'description' => 'Published advisory',
            'status' => VulnerabilityStatus::OPEN,
        ]);
        Vulnerability::factory()->for($draftVersion, 'affectedVersion')->create([
            'cve_id' => 'CVE-2026-5002',
            'description' => 'Draft advisory',
            'status' => VulnerabilityStatus::OPEN,
        ]);
        Vulnerability::factory()->for($publishedVersion, 'affectedVersion')->create([
            'cve_id' => 'CVE-2026-5003',
            'description' => 'False positive advisory',
            'status' => VulnerabilityStatus::FALSE_POSITIVE,
        ]);

        $this->get('/security')
            ->assertOk()
            ->assertSee('CVE-2026-5001')
            ->assertSee('Published advisory')
            ->assertDontSee('CVE-2026-5002')
            ->assertDontSee('CVE-2026-5003')
            ->assertDontSee('Draft advisory')
            ->assertDontSee('False positive advisory');
    }
}
