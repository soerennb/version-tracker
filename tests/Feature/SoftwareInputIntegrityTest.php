<?php

namespace Tests\Feature;

use App\Enums\SoftwareStatus;
use App\Enums\UserRole;
use App\Models\Software;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SoftwareInputIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_ignores_client_supplied_software_version_metadata(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::VIEWER,
            'abilities' => ['create_software'],
        ]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/softwares', [
            'name' => 'SecurityTracker',
            'description' => 'desc',
            'status' => SoftwareStatus::ACTIVE->value,
            'current_version' => '999.0.0',
            'last_release_date' => '2030-01-01',
            'license_type' => 'MIT',
            'compliance_status' => 'unknown',
            'github_repo_url' => 'https://example.com/repo',
        ])->assertCreated();

        $id = (int) $response->json('data.id');
        $software = Software::query()->findOrFail($id);

        $this->assertNull($software->current_version);
        $this->assertNull($software->last_release_date);
    }

    public function test_update_ignores_client_supplied_software_version_metadata(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::VIEWER,
            'abilities' => ['edit_software'],
        ]);
        Sanctum::actingAs($user);

        $software = Software::factory()->create([
            'current_version' => '1.0.0',
            'last_release_date' => '2025-12-01',
        ]);

        $this->putJson('/api/softwares/'.$software->id, [
            'name' => $software->name,
            'description' => $software->description,
            'status' => SoftwareStatus::ACTIVE->value,
            'current_version' => '9.9.9',
            'last_release_date' => '2030-01-01',
            'license_type' => $software->license_type,
            'compliance_status' => 'unknown',
            'github_repo_url' => 'https://example.com/updated-repo',
        ])->assertOk();

        $software->refresh();

        $this->assertSame('1.0.0', $software->current_version);
        $this->assertSame('2025-12-01', $software->last_release_date?->toDateString());
    }

    public function test_store_rejects_invalid_compliance_status(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::VIEWER,
            'abilities' => ['create_software'],
        ]);
        Sanctum::actingAs($user);

        $this->postJson('/api/softwares', [
            'name' => 'SecurityTracker',
            'description' => 'desc',
            'status' => SoftwareStatus::ACTIVE->value,
            'license_type' => 'MIT',
            'compliance_status' => 'unclear',
            'github_repo_url' => 'https://example.com/repo',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('compliance_status');
    }
}
