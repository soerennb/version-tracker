<?php

namespace Tests\Feature;

use App\Enums\SupportStatus;
use App\Enums\VersionStatus;
use App\Enums\VulnerabilitySeverity;
use App\Enums\VulnerabilityStatus;
use App\Models\FileAttachment;
use App\Models\Software;
use App\Models\TextContent;
use App\Models\Version;
use App\Models\Vulnerability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_only_lists_products_with_published_versions(): void
    {
        $visible = Software::factory()->create(['name' => 'Visible']);
        Version::factory()->for($visible)->create([
            'status' => VersionStatus::PUBLISHED,
            'version_number' => '2.1.0',
            'support_status' => SupportStatus::SUPPORTED,
        ]);

        $hidden = Software::factory()->create(['name' => 'Hidden']);
        Version::factory()->for($hidden)->create(['status' => VersionStatus::DRAFT]);

        $this->getJson('/api/public/products')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Visible')
            ->assertJsonPath('data.0.current_release.version', '2.1.0')
            ->assertJsonMissing(['name' => 'Hidden']);
    }

    public function test_detail_exposes_release_health_without_internal_paths_or_drafts(): void
    {
        $software = Software::factory()->create(['name' => 'Governed Product']);
        $published = Version::factory()->for($software)->create([
            'status' => VersionStatus::PUBLISHED,
            'version_number' => '3.4.0',
            'release_date' => '2026-06-01',
            'support_status' => SupportStatus::MAINTENANCE,
            'eol_date' => '2027-06-01',
        ]);
        Version::factory()->for($software)->create([
            'status' => VersionStatus::DRAFT,
            'version_number' => '4.0.0-secret',
            'release_date' => '2026-06-20',
        ]);
        TextContent::factory()->for($published)->create([
            'title' => 'Stable release',
            'content' => 'Public release notes.',
        ]);
        FileAttachment::factory()->for($published)->create([
            'filename' => 'product.zip',
            'file_path' => 'private/releases/product.zip',
        ]);
        Vulnerability::factory()->for($published, 'affectedVersion')->create([
            'severity' => VulnerabilitySeverity::CRITICAL,
            'status' => VulnerabilityStatus::OPEN,
        ]);
        Vulnerability::factory()->for($published, 'affectedVersion')->create([
            'severity' => VulnerabilitySeverity::HIGH,
            'status' => VulnerabilityStatus::FALSE_POSITIVE,
        ]);

        $this->getJson("/api/public/products/{$software->id}")
            ->assertOk()
            ->assertJsonPath('data.current_release.version', '3.4.0')
            ->assertJsonPath('data.current_release.support_status', 'maintenance')
            ->assertJsonPath('data.current_release.eol_date', '2027-06-01')
            ->assertJsonPath('data.security.open', 1)
            ->assertJsonPath('data.security.critical', 1)
            ->assertJsonPath('data.releases.0.downloads.0.filename', 'product.zip')
            ->assertJsonMissing(['version' => '4.0.0-secret'])
            ->assertJsonMissing(['file_path' => 'private/releases/product.zip']);
    }

    public function test_detail_returns_not_found_when_product_has_no_published_version(): void
    {
        $software = Software::factory()->create();
        Version::factory()->for($software)->create(['status' => VersionStatus::DRAFT]);

        $this->getJson("/api/public/products/{$software->id}")->assertNotFound();
    }
}
