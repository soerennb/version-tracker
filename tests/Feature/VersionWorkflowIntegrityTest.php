<?php

namespace Tests\Feature;

use App\Enums\ApprovalStatus;
use App\Enums\UserRole;
use App\Enums\VersionStatus;
use App\Models\Software;
use App\Models\User;
use App\Models\Version;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VersionWorkflowIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_ignores_client_supplied_status_and_approval_status(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::VIEWER,
            'abilities' => ['create_versions'],
        ]);
        Sanctum::actingAs($user);

        $software = Software::factory()->create();

        $this->postJson('/api/versions', [
            'software_id' => $software->id,
            'version_number' => '1.2.3',
            'release_date' => '2026-02-01',
            'status' => VersionStatus::PUBLISHED->value,
            'approval_status' => ApprovalStatus::APPROVED->value,
        ])->assertCreated();

        /** @var Version $version */
        $version = Version::query()->latest('id')->firstOrFail();
        $this->assertSame(VersionStatus::DRAFT, $version->status);
        $this->assertSame(ApprovalStatus::PENDING, $version->approval_status);
    }

    public function test_update_ignores_client_supplied_status_and_approval_status(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::VIEWER,
            'abilities' => ['edit_versions'],
        ]);
        Sanctum::actingAs($user);

        $version = Version::factory()->create([
            'status' => VersionStatus::DRAFT->value,
            'approval_status' => ApprovalStatus::PENDING->value,
        ]);

        $this->putJson('/api/versions/'.$version->id, [
            'version_number' => '2.0.0',
            'release_date' => '2026-02-10',
            'status' => VersionStatus::PUBLISHED->value,
            'approval_status' => ApprovalStatus::APPROVED->value,
        ])->assertOk();

        $version->refresh();

        $this->assertSame('2.0.0', $version->version_number);
        $this->assertSame(VersionStatus::DRAFT, $version->status);
        $this->assertSame(ApprovalStatus::PENDING, $version->approval_status);
    }
}
