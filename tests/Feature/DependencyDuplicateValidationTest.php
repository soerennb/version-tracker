<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Software;
use App\Models\SoftwareDependency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DependencyDuplicateValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_rejects_duplicate_unscoped_dependency_with_field_error(): void
    {
        $this->actAsDependencyManager();
        [$software, $dependencyTarget] = $this->softwarePair();

        SoftwareDependency::factory()->create([
            'software_id' => $software->id,
            'depends_on_software_id' => $dependencyTarget->id,
            'applies_to_version_id' => null,
            'min_version_id' => null,
            'max_version_id' => null,
            'dependency_type' => 'runtime',
        ]);

        $this->postJson('/api/software-dependencies', [
            'software_id' => $software->id,
            'depends_on_software_id' => $dependencyTarget->id,
            'dependency_type' => 'runtime',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('scope_key');

        $this->assertDatabaseCount('software_dependencies', 1);
    }

    public function test_store_accepts_same_relationship_with_a_different_dependency_type(): void
    {
        $this->actAsDependencyManager();
        [$software, $dependencyTarget] = $this->softwarePair();

        SoftwareDependency::factory()->create([
            'software_id' => $software->id,
            'depends_on_software_id' => $dependencyTarget->id,
            'applies_to_version_id' => null,
            'min_version_id' => null,
            'max_version_id' => null,
            'dependency_type' => 'runtime',
        ]);

        $this->postJson('/api/software-dependencies', [
            'software_id' => $software->id,
            'depends_on_software_id' => $dependencyTarget->id,
            'dependency_type' => 'build',
        ])->assertCreated();

        $this->assertDatabaseCount('software_dependencies', 2);
    }

    public function test_store_accepts_duplicate_relationship_for_a_different_version_scope(): void
    {
        $this->actAsDependencyManager();
        [$software, $dependencyTarget] = $this->softwarePair();
        $version = $software->versions()->create([
            'version_number' => '1.0.0',
            'release_date' => today(),
        ]);

        SoftwareDependency::factory()->create([
            'software_id' => $software->id,
            'depends_on_software_id' => $dependencyTarget->id,
            'applies_to_version_id' => null,
            'min_version_id' => null,
            'max_version_id' => null,
            'dependency_type' => 'runtime',
        ]);

        $this->postJson('/api/software-dependencies', [
            'software_id' => $software->id,
            'depends_on_software_id' => $dependencyTarget->id,
            'applies_to_version_id' => $version->id,
            'dependency_type' => 'runtime',
        ])->assertCreated();

        $this->assertDatabaseCount('software_dependencies', 2);
    }

    public function test_update_rejects_a_scope_that_belongs_to_another_dependency(): void
    {
        $this->actAsDependencyManager();
        [$software, $dependencyTarget] = $this->softwarePair();

        SoftwareDependency::factory()->create([
            'software_id' => $software->id,
            'depends_on_software_id' => $dependencyTarget->id,
            'applies_to_version_id' => null,
            'min_version_id' => null,
            'max_version_id' => null,
            'dependency_type' => 'runtime',
        ]);
        $dependencyToUpdate = SoftwareDependency::factory()->create([
            'software_id' => $software->id,
            'depends_on_software_id' => $dependencyTarget->id,
            'applies_to_version_id' => null,
            'min_version_id' => null,
            'max_version_id' => null,
            'dependency_type' => 'build',
        ]);

        $this->putJson('/api/software-dependencies/'.$dependencyToUpdate->id, [
            'depends_on_software_id' => $dependencyTarget->id,
            'dependency_type' => 'runtime',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('scope_key');

        $this->assertDatabaseHas('software_dependencies', [
            'id' => $dependencyToUpdate->id,
            'dependency_type' => 'build',
        ]);
    }

    /**
     * @return array{0: Software, 1: Software}
     */
    private function softwarePair(): array
    {
        return [Software::factory()->create(), Software::factory()->create()];
    }

    private function actAsDependencyManager(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::VIEWER,
            'abilities' => ['manage_dependencies'],
        ]);

        Sanctum::actingAs($user);
    }
}
