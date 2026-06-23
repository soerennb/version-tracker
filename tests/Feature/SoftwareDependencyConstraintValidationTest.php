<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Software;
use App\Models\SoftwareDependency;
use App\Models\User;
use App\Models\Version;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SoftwareDependencyConstraintValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_accepts_version_constraints_for_the_dependency_target(): void
    {
        $this->actAsDependencyManager();

        $software = Software::factory()->create();
        $dependencyTarget = Software::factory()->create();
        $minVersion = Version::factory()->create([
            'software_id' => $dependencyTarget->id,
            'version_number' => '1.0.0',
        ]);
        $maxVersion = Version::factory()->create([
            'software_id' => $dependencyTarget->id,
            'version_number' => '2.0.0',
        ]);

        $this->postJson('/api/software-dependencies', [
            'software_id' => $software->id,
            'depends_on_software_id' => $dependencyTarget->id,
            'min_version_id' => $minVersion->id,
            'max_version_id' => $maxVersion->id,
            'dependency_type' => 'runtime',
        ])->assertCreated();

        $this->assertDatabaseHas('software_dependencies', [
            'software_id' => $software->id,
            'depends_on_software_id' => $dependencyTarget->id,
            'min_version_id' => $minVersion->id,
            'max_version_id' => $maxVersion->id,
        ]);
    }

    public function test_store_rejects_version_constraints_from_another_software(): void
    {
        $this->actAsDependencyManager();

        $software = Software::factory()->create();
        $dependencyTarget = Software::factory()->create();
        $otherSoftwareVersion = Version::factory()->create();

        $this->postJson('/api/software-dependencies', [
            'software_id' => $software->id,
            'depends_on_software_id' => $dependencyTarget->id,
            'min_version_id' => $otherSoftwareVersion->id,
            'dependency_type' => 'runtime',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('min_version_id');
    }

    public function test_update_rejects_version_constraints_from_another_software(): void
    {
        $this->actAsDependencyManager();

        $software = Software::factory()->create();
        $dependencyTarget = Software::factory()->create();
        $validVersion = Version::factory()->create([
            'software_id' => $dependencyTarget->id,
        ]);
        $otherSoftwareVersion = Version::factory()->create();
        $dependency = SoftwareDependency::factory()->create([
            'software_id' => $software->id,
            'depends_on_software_id' => $dependencyTarget->id,
            'min_version_id' => $validVersion->id,
            'max_version_id' => null,
        ]);

        $this->putJson('/api/software-dependencies/'.$dependency->id, [
            'max_version_id' => $otherSoftwareVersion->id,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('max_version_id');
    }

    public function test_store_rejects_indirect_dependency_cycles(): void
    {
        $this->actAsDependencyManager();

        $softwareA = Software::factory()->create();
        $softwareB = Software::factory()->create();
        $softwareC = Software::factory()->create();

        SoftwareDependency::factory()->create([
            'software_id' => $softwareA->id,
            'depends_on_software_id' => $softwareB->id,
            'min_version_id' => null,
            'max_version_id' => null,
        ]);
        SoftwareDependency::factory()->create([
            'software_id' => $softwareB->id,
            'depends_on_software_id' => $softwareC->id,
            'min_version_id' => null,
            'max_version_id' => null,
        ]);

        $this->postJson('/api/software-dependencies', [
            'software_id' => $softwareC->id,
            'depends_on_software_id' => $softwareA->id,
            'dependency_type' => 'runtime',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('depends_on_software_id');
    }

    public function test_update_rejects_indirect_dependency_cycles(): void
    {
        $this->actAsDependencyManager();

        $softwareA = Software::factory()->create();
        $softwareB = Software::factory()->create();
        $softwareC = Software::factory()->create();
        $softwareD = Software::factory()->create();

        SoftwareDependency::factory()->create([
            'software_id' => $softwareA->id,
            'depends_on_software_id' => $softwareB->id,
            'min_version_id' => null,
            'max_version_id' => null,
        ]);
        SoftwareDependency::factory()->create([
            'software_id' => $softwareB->id,
            'depends_on_software_id' => $softwareC->id,
            'min_version_id' => null,
            'max_version_id' => null,
        ]);
        $dependency = SoftwareDependency::factory()->create([
            'software_id' => $softwareC->id,
            'depends_on_software_id' => $softwareD->id,
            'min_version_id' => null,
            'max_version_id' => null,
        ]);

        $this->putJson('/api/software-dependencies/'.$dependency->id, [
            'depends_on_software_id' => $softwareA->id,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('depends_on_software_id');
    }

    protected function actAsDependencyManager(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::VIEWER,
            'abilities' => ['manage_dependencies'],
        ]);

        Sanctum::actingAs($user);
    }
}
