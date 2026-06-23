<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Software;
use App\Models\User;
use App\Models\Version;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VersionSemverValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_accepts_pre_release_and_build_semver_versions(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::VIEWER,
            'abilities' => ['create_versions'],
        ]);
        Sanctum::actingAs($user);

        $software = Software::factory()->create();

        $this->postJson('/api/versions', [
            'software_id' => $software->id,
            'version_number' => '1.2.3-rc.1+build.5',
            'release_date' => '2026-02-01',
        ])->assertCreated();

        $this->assertDatabaseHas('versions', [
            'software_id' => $software->id,
            'version_number' => '1.2.3-rc.1+build.5',
        ]);
    }

    public function test_update_accepts_build_metadata_semver_versions(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::VIEWER,
            'abilities' => ['edit_versions'],
        ]);
        Sanctum::actingAs($user);

        $version = Version::factory()->create();

        $this->putJson('/api/versions/'.$version->id, [
            'version_number' => '2.0.0+build.10',
            'release_date' => '2026-03-01',
        ])->assertOk();

        $this->assertSame('2.0.0+build.10', $version->refresh()->version_number);
    }

    public function test_store_rejects_non_semver_versions(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::VIEWER,
            'abilities' => ['create_versions'],
        ]);
        Sanctum::actingAs($user);

        $software = Software::factory()->create();

        $this->postJson('/api/versions', [
            'software_id' => $software->id,
            'version_number' => '1.2',
            'release_date' => '2026-02-01',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('version_number');
    }
}
