<?php

namespace Tests\Feature;

use App\Enums\SoftwareStatus;
use App\Enums\VersionStatus;
use App\Enums\VulnerabilitySeverity;
use App\Enums\VulnerabilityStatus;
use App\Models\Software;
use App\Models\SoftwareDependency;
use App\Models\Version;
use App\Models\Vulnerability;
use App\Services\DependencyHealthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DependencyHealthServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_dependency_health_marks_unsafe_dependencies(): void
    {
        $dependency = $this->dependency();

        Vulnerability::factory()->for($dependency->dependsOnSoftware->versions->first(), 'affectedVersion')->create([
            'severity' => VulnerabilitySeverity::HIGH,
            'status' => VulnerabilityStatus::OPEN,
        ]);
        $dependency->unsetRelations();

        $health = app(DependencyHealthService::class)->evaluate($dependency);

        $this->assertSame('unsafe', $health['status']);
    }

    public function test_dependency_health_marks_outdated_dependencies(): void
    {
        $dependency = $this->dependency();
        Version::factory()->for($dependency->dependsOnSoftware)->create([
            'version_number' => '2.0.0',
            'status' => VersionStatus::PUBLISHED,
            'eol_date' => null,
        ]);

        $health = app(DependencyHealthService::class)->evaluate($dependency);

        $this->assertSame('outdated', $health['status']);
    }

    public function test_dependency_health_marks_healthy_dependencies(): void
    {
        $health = app(DependencyHealthService::class)->evaluate($this->dependency());

        $this->assertSame('healthy', $health['status']);
    }

    private function dependency(): SoftwareDependency
    {
        $software = Software::factory()->create(['status' => SoftwareStatus::ACTIVE]);
        $target = Software::factory()->create(['status' => SoftwareStatus::ACTIVE]);
        $targetVersion = Version::factory()->for($target)->create([
            'version_number' => '1.0.0',
            'status' => VersionStatus::PUBLISHED,
            'eol_date' => null,
        ]);

        return SoftwareDependency::factory()->create([
            'software_id' => $software->id,
            'depends_on_software_id' => $target->id,
            'min_version_id' => null,
            'max_version_id' => $targetVersion->id,
            'dependency_type' => 'runtime',
        ]);
    }
}
