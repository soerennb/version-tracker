<?php

namespace Tests\Feature;

use App\Enums\Language;
use App\Enums\SupportStatus;
use App\Enums\VersionStatus;
use App\Enums\VulnerabilitySeverity;
use App\Enums\VulnerabilityStatus;
use App\Models\Software;
use App\Models\TextContent;
use App\Models\Version;
use App\Models\Vulnerability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicDecisionSupportApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_overview_returns_aggregated_metrics_and_published_data(): void
    {
        $software = Software::factory()->create(['name' => 'Signal Core']);
        $version = Version::factory()->for($software)->create([
            'status' => VersionStatus::PUBLISHED,
            'support_status' => SupportStatus::SUPPORTED,
        ]);
        TextContent::factory()->for($version)->create(['language' => Language::DE]);

        Vulnerability::factory()->for($version, 'affectedVersion')->create([
            'severity' => VulnerabilitySeverity::CRITICAL,
            'status' => VulnerabilityStatus::OPEN,
        ]);

        $this->getJson('/api/public/overview?locale=de')
            ->assertOk()
            ->assertJsonPath('data.metrics.products', 1)
            ->assertJsonPath('data.metrics.releases', 1)
            ->assertJsonPath('data.metrics.open_security', 1)
            ->assertJsonPath('data.metrics.critical_security', 1)
            ->assertJsonPath('data.products.0.name', 'Signal Core')
            ->assertJsonPath('data.latest_releases.0.content_locale', 'de');
    }

    public function test_products_support_server_side_search_and_pagination(): void
    {
        $matching = Software::factory()->create(['name' => 'Aurora Runtime']);
        Version::factory()->for($matching)->create(['status' => VersionStatus::PUBLISHED]);

        $other = Software::factory()->create(['name' => 'Beacon Gateway']);
        Version::factory()->for($other)->create(['status' => VersionStatus::PUBLISHED]);

        $this->getJson('/api/public/products?q=aurora&per_page=1')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('data.0.name', 'Aurora Runtime');
    }

    public function test_release_locale_contract_exposes_preferred_note_and_fallback(): void
    {
        $release = Version::factory()->create(['status' => VersionStatus::PUBLISHED]);
        TextContent::factory()->for($release)->create(['language' => Language::DE, 'title' => 'Deutsche Hinweise']);
        TextContent::factory()->for($release)->create(['language' => Language::EN, 'title' => 'English notes']);

        $this->getJson('/api/public/releases/'.$release->id.'?locale=en')
            ->assertOk()
            ->assertJsonPath('data.preferred_note.language', 'en')
            ->assertJsonPath('data.preferred_note.title', 'English notes')
            ->assertJsonPath('data.fallback_used', false);

        $onlyGerman = Version::factory()->create(['status' => VersionStatus::PUBLISHED]);
        TextContent::factory()->for($onlyGerman)->create(['language' => Language::DE, 'title' => 'Nur Deutsch']);

        $this->getJson('/api/public/releases/'.$onlyGerman->id.'?locale=en')
            ->assertOk()
            ->assertJsonPath('data.content_locale', 'de')
            ->assertJsonPath('data.fallback_used', true);
    }

    public function test_advisory_detail_exposes_affected_releases_and_remediation(): void
    {
        $software = Software::factory()->create(['name' => 'Signal Core']);
        $affected = Version::factory()->for($software)->create([
            'status' => VersionStatus::PUBLISHED,
            'version_number' => '1.0.0',
        ]);
        $fixed = Version::factory()->for($software)->create([
            'status' => VersionStatus::PUBLISHED,
            'version_number' => '1.0.1',
        ]);
        $vulnerability = Vulnerability::factory()->for($affected, 'affectedVersion')->create([
            'cve_id' => 'CVE-2026-7001',
            'severity' => VulnerabilitySeverity::HIGH,
            'status' => VulnerabilityStatus::OPEN,
            'fixed_version_id' => $fixed->id,
        ]);

        $this->getJson('/api/public/security/'.$vulnerability->id)
            ->assertOk()
            ->assertJsonPath('data.cve_id', 'CVE-2026-7001')
            ->assertJsonPath('data.remediation.fixed_version', '1.0.1')
            ->assertJsonPath('data.affected_releases.0.product.name', 'Signal Core')
            ->assertJsonPath('data.affected_releases.0.version', '1.0.0');
    }

    public function test_global_search_returns_products_releases_and_security_results(): void
    {
        $software = Software::factory()->create(['name' => 'Searchable Platform']);
        $release = Version::factory()->for($software)->create([
            'status' => VersionStatus::PUBLISHED,
            'version_number' => '8.4.2',
        ]);
        TextContent::factory()->for($release)->create([
            'language' => Language::DE,
            'title' => 'Delegated approval',
            'content' => 'Searchable release notes.',
        ]);
        Vulnerability::factory()->for($release, 'affectedVersion')->create([
            'cve_id' => 'CVE-2026-7002',
            'description' => 'Searchable security advisory.',
            'status' => VulnerabilityStatus::OPEN,
        ]);

        $this->getJson('/api/public/search?q=searchable')
            ->assertOk()
            ->assertJsonPath('data.products.0.name', 'Searchable Platform')
            ->assertJsonPath('data.releases.0.version', '8.4.2')
            ->assertJsonPath('data.security.0.cve_id', 'CVE-2026-7002');

    }
}
