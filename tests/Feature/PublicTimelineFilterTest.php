<?php

namespace Tests\Feature;

use App\Enums\SupportStatus;
use App\Enums\VersionStatus;
use App\Enums\VulnerabilityStatus;
use App\Models\Software;
use App\Models\TextContent;
use App\Models\Version;
use App\Models\Vulnerability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicTimelineFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_matches_product_version_and_release_notes(): void
    {
        $software = Software::factory()->create(['name' => 'Orion Platform']);
        $release = Version::factory()->for($software)->create([
            'status' => VersionStatus::PUBLISHED,
            'version_number' => '8.4.2',
        ]);
        TextContent::factory()->for($release)->create(['content' => 'Introduces delegated approval.']);
        Version::factory()->create(['status' => VersionStatus::PUBLISHED, 'version_number' => '1.0.0']);

        foreach (['Orion', '8.4', 'delegated'] as $search) {
            $this->getJson('/api/public/timeline?q='.urlencode($search))
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.id', $release->id);
        }
    }

    public function test_filters_can_be_combined_by_product_date_support_and_security(): void
    {
        $software = Software::factory()->create();
        $matching = Version::factory()->for($software)->create([
            'status' => VersionStatus::PUBLISHED,
            'release_date' => '2026-04-15',
            'support_status' => SupportStatus::MAINTENANCE,
        ]);
        Vulnerability::factory()->for($matching, 'affectedVersion')->create(['status' => VulnerabilityStatus::OPEN]);
        Version::factory()->for($software)->create([
            'status' => VersionStatus::PUBLISHED,
            'release_date' => '2025-01-01',
            'support_status' => SupportStatus::SUPPORTED,
        ]);

        $query = http_build_query([
            'software' => $software->id,
            'date_from' => '2026-01-01',
            'date_to' => '2026-12-31',
            'support' => 'maintenance',
            'security' => 'attention',
        ]);

        $this->getJson("/api/public/timeline?{$query}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matching->id)
            ->assertJsonPath('data.0.open_vulnerabilities', 1);
    }

    public function test_invalid_filter_values_are_rejected(): void
    {
        $this->getJson('/api/public/timeline?security=unknown&date_from=nope')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['security', 'date_from']);
    }
}
