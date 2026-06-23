<?php

namespace Tests\Feature;

use App\Enums\SupportStatus;
use App\Enums\VersionStatus;
use App\Models\Version;
use App\Services\LifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LifecycleServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_lifecycle_service_finds_upcoming_eol_versions_and_stats(): void
    {
        $upcoming = Version::factory()->create([
            'status' => VersionStatus::PUBLISHED,
            'eol_date' => now()->addDays(30),
            'support_status' => SupportStatus::MAINTENANCE,
        ]);
        Version::factory()->create([
            'status' => VersionStatus::PUBLISHED,
            'eol_date' => now()->addDays(120),
            'support_status' => SupportStatus::DEPRECATED,
        ]);
        Version::factory()->create([
            'status' => VersionStatus::DRAFT,
            'eol_date' => now()->addDays(10),
            'support_status' => SupportStatus::EOL,
        ]);

        $service = app(LifecycleService::class);

        $this->assertTrue($service->upcomingEol()->contains($upcoming));
        $this->assertSame(1, $service->dashboardStats()['upcoming_eol']);
        $this->assertSame(1, $service->dashboardStats()['maintenance']);
        $this->assertSame(1, $service->dashboardStats()['deprecated']);
        $this->assertSame(1, $service->dashboardStats()['eol']);
    }
}
