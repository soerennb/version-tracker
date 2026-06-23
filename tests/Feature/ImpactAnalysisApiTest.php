<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Software;
use App\Models\SoftwareDependency;
use App\Models\User;
use App\Models\Version;
use App\Models\Vulnerability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ImpactAnalysisApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_software_impact_returns_transitive_dependents(): void
    {
        $this->actAsImpactViewer();

        $core = Software::factory()->create(['name' => 'Core']);
        $app = Software::factory()->create(['name' => 'App']);
        $portal = Software::factory()->create(['name' => 'Portal']);

        SoftwareDependency::factory()->create([
            'software_id' => $app->id,
            'depends_on_software_id' => $core->id,
            'min_version_id' => null,
            'max_version_id' => null,
        ]);
        SoftwareDependency::factory()->create([
            'software_id' => $portal->id,
            'depends_on_software_id' => $app->id,
            'min_version_id' => null,
            'max_version_id' => null,
        ]);

        $response = $this->getJson('/api/impact/software/'.$core->id)
            ->assertOk();

        $response->assertJsonPath('data.type', 'software')
            ->assertJsonPath('data.target.name', 'Core')
            ->assertJsonPath('data.affected_software.0.software.name', 'App')
            ->assertJsonPath('data.affected_software.0.depth', 1)
            ->assertJsonPath('data.affected_software.1.software.name', 'Portal')
            ->assertJsonPath('data.affected_software.1.depth', 2);
    }

    public function test_version_impact_respects_dependency_version_constraints(): void
    {
        $this->actAsImpactViewer();

        $core = Software::factory()->create(['name' => 'Core']);
        $matchingVersion = Version::factory()->create([
            'software_id' => $core->id,
            'version_number' => '1.5.0',
        ]);
        $outsideVersion = Version::factory()->create([
            'software_id' => $core->id,
            'version_number' => '3.0.0',
        ]);
        $minVersion = Version::factory()->create([
            'software_id' => $core->id,
            'version_number' => '1.0.0',
        ]);
        $maxVersion = Version::factory()->create([
            'software_id' => $core->id,
            'version_number' => '2.0.0',
        ]);
        $app = Software::factory()->create(['name' => 'App']);

        SoftwareDependency::factory()->create([
            'software_id' => $app->id,
            'depends_on_software_id' => $core->id,
            'min_version_id' => $minVersion->id,
            'max_version_id' => $maxVersion->id,
        ]);

        $this->getJson('/api/impact/versions/'.$matchingVersion->id)
            ->assertOk()
            ->assertJsonPath('data.affected_software.0.software.name', 'App');

        $this->getJson('/api/impact/versions/'.$outsideVersion->id)
            ->assertOk()
            ->assertJsonCount(0, 'data.affected_software');
    }

    public function test_vulnerability_impact_uses_affected_version(): void
    {
        $this->actAsImpactViewer();

        $core = Software::factory()->create(['name' => 'Core']);
        $version = Version::factory()->create([
            'software_id' => $core->id,
            'version_number' => '1.0.0',
        ]);
        $app = Software::factory()->create(['name' => 'App']);
        SoftwareDependency::factory()->create([
            'software_id' => $app->id,
            'depends_on_software_id' => $core->id,
            'min_version_id' => null,
            'max_version_id' => null,
        ]);
        $vulnerability = Vulnerability::factory()->create([
            'affected_version_id' => $version->id,
            'cve_id' => 'CVE-2026-12345',
        ]);

        $this->getJson('/api/impact/vulnerabilities/'.$vulnerability->id)
            ->assertOk()
            ->assertJsonPath('data.type', 'vulnerability')
            ->assertJsonPath('data.target.cve_id', 'CVE-2026-12345')
            ->assertJsonPath('data.affected_software.0.software.name', 'App');
    }

    protected function actAsImpactViewer(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::VIEWER,
        ]);

        Sanctum::actingAs($user);
    }
}
