<?php

namespace Tests\Feature;

use App\Enums\VersionStatus;
use App\Enums\VulnerabilitySeverity;
use App\Enums\VulnerabilityStatus;
use App\Models\Software;
use App\Models\SoftwareDependency;
use App\Models\Version;
use App\Models\Vulnerability;
use App\Services\DependencyMapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DependencyMapServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_dependency_map_marks_direction_and_risk(): void
    {
        $core = Software::factory()->create(['name' => 'Core API']);
        $auth = Software::factory()->create(['name' => 'Auth Service']);
        $frontend = Software::factory()->create(['name' => 'Frontend']);

        $coreVersion = Version::factory()->for($core)->create([
            'status' => VersionStatus::PUBLISHED,
            'eol_date' => now()->addDays(15),
        ]);
        Version::factory()->for($auth)->create([
            'status' => VersionStatus::PUBLISHED,
            'eol_date' => null,
        ]);

        Vulnerability::factory()->for($coreVersion, 'affectedVersion')->create([
            'severity' => VulnerabilitySeverity::HIGH,
            'status' => VulnerabilityStatus::OPEN,
        ]);

        SoftwareDependency::factory()->create([
            'software_id' => $core->id,
            'depends_on_software_id' => $auth->id,
            'min_version_id' => null,
            'max_version_id' => null,
            'dependency_type' => 'runtime',
        ]);
        SoftwareDependency::factory()->create([
            'software_id' => $frontend->id,
            'depends_on_software_id' => $core->id,
            'min_version_id' => null,
            'max_version_id' => null,
            'dependency_type' => 'runtime',
        ]);

        $map = app(DependencyMapService::class)->build($core->id);
        $nodes = collect($map['nodes'])->keyBy('id');
        $edges = collect($map['edges']);

        $this->assertSame($core->id, $map['selected_id']);
        $this->assertSame(3, $map['stats']['nodes']);
        $this->assertSame(2, $map['stats']['edges']);
        $this->assertTrue($nodes[$core->id]['has_vulnerability']);
        $this->assertTrue($nodes[$core->id]['has_eol_risk']);
        $this->assertSame('outgoing', $edges->firstWhere('to', $auth->id)['direction']);
        $this->assertSame('incoming', $edges->firstWhere('from', $frontend->id)['direction']);
    }
}
