<?php

namespace Tests\Feature;

use App\Enums\ApprovalStatus;
use App\Enums\DeploymentEventType;
use App\Enums\DeploymentStatus;
use App\Enums\UserRole;
use App\Enums\VersionStatus;
use App\Models\Deployment;
use App\Models\DeploymentEvent;
use App\Models\Environment;
use App\Models\Software;
use App\Models\User;
use App\Models\Version;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeploymentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_record_a_complete_deployment_workflow(): void
    {
        $this->actingAsDeploymentUser([
            'create_deployments',
            'approve_deployments',
            'execute_deployments',
        ]);
        $software = Software::factory()->create();
        $version = Version::factory()->for($software)->create([
            'status' => VersionStatus::DRAFT,
            'approval_status' => ApprovalStatus::APPROVED,
        ]);
        $environment = Environment::factory()->create([
            'name' => 'Test',
            'code' => 'test',
            'is_production' => false,
        ]);

        $response = $this->postJson('/api/deployments', [
            'software_id' => $software->id,
            'version_id' => $version->id,
            'environment_id' => $environment->id,
            'external_reference' => 'ci-1001',
            'change_reference' => 'CHG-1001',
        ]);

        $response->assertCreated()->assertJsonPath('data.status', DeploymentStatus::PLANNED->value);
        $deployment = Deployment::query()->findOrFail($response->json('data.id'));

        $this->postJson("/api/deployments/{$deployment->id}/approve", ['comment' => 'Change approved'])
            ->assertOk()
            ->assertJsonPath('data.status', DeploymentStatus::APPROVED->value);
        $this->postJson("/api/deployments/{$deployment->id}/start", ['comment' => 'Execution started'])
            ->assertOk()
            ->assertJsonPath('data.status', DeploymentStatus::IN_PROGRESS->value);
        $this->postJson("/api/deployments/{$deployment->id}/succeed", ['result' => 'Deployment completed'])
            ->assertOk()
            ->assertJsonPath('data.status', DeploymentStatus::SUCCEEDED->value);

        $this->assertDatabaseHas('deployments', [
            'id' => $deployment->id,
            'status' => DeploymentStatus::SUCCEEDED->value,
            'change_reference' => 'CHG-1001',
        ]);
        $this->assertSame(4, DeploymentEvent::query()->where('deployment_id', $deployment->id)->count());
        $this->assertDatabaseHas('deployment_events', [
            'deployment_id' => $deployment->id,
            'type' => DeploymentEventType::SUCCEEDED->value,
        ]);
    }

    public function test_production_deployment_requires_a_published_version(): void
    {
        $this->actingAsDeploymentUser(['create_deployments']);
        $software = Software::factory()->create();
        $version = Version::factory()->for($software)->create([
            'status' => VersionStatus::DRAFT,
            'approval_status' => ApprovalStatus::APPROVED,
        ]);
        $environment = Environment::factory()->create(['is_production' => true]);

        $this->postJson('/api/deployments', [
            'software_id' => $software->id,
            'version_id' => $version->id,
            'environment_id' => $environment->id,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('version_id');

        $this->assertDatabaseCount('deployments', 0);
    }

    public function test_external_reference_makes_ci_retries_idempotent(): void
    {
        $this->actingAsDeploymentUser(['create_deployments']);
        $software = Software::factory()->create();
        $version = Version::factory()->for($software)->create([
            'status' => VersionStatus::DRAFT,
            'approval_status' => ApprovalStatus::APPROVED,
        ]);
        $environment = Environment::factory()->create(['is_production' => false]);
        $payload = [
            'software_id' => $software->id,
            'version_id' => $version->id,
            'environment_id' => $environment->id,
            'external_reference' => 'pipeline-42',
        ];

        $first = $this->postJson('/api/deployments', $payload)->assertCreated();
        $second = $this->postJson('/api/deployments', $payload)->assertOk();

        $this->assertSame($first->json('data.id'), $second->json('data.id'));
        $this->assertDatabaseCount('deployments', 1);
    }

    public function test_successful_rollback_closes_the_original_deployment_as_rolled_back(): void
    {
        $this->actingAsDeploymentUser([
            'create_deployments',
            'approve_deployments',
            'execute_deployments',
        ]);
        $software = Software::factory()->create();
        $currentVersion = Version::factory()->for($software)->create([
            'status' => VersionStatus::DRAFT,
            'approval_status' => ApprovalStatus::APPROVED,
        ]);
        $rollbackVersion = Version::factory()->for($software)->create([
            'status' => VersionStatus::DRAFT,
            'approval_status' => ApprovalStatus::APPROVED,
        ]);
        $environment = Environment::factory()->create(['is_production' => false]);
        $deployment = Deployment::query()->findOrFail($this->postJson('/api/deployments', [
            'software_id' => $software->id,
            'version_id' => $currentVersion->id,
            'environment_id' => $environment->id,
        ])->assertCreated()->json('data.id'));

        $this->postJson("/api/deployments/{$deployment->id}/approve")->assertOk();
        $this->postJson("/api/deployments/{$deployment->id}/start")->assertOk();
        $this->postJson("/api/deployments/{$deployment->id}/succeed", ['result' => 'Current version deployed'])->assertOk();

        $rollback = $this->postJson("/api/deployments/{$deployment->id}/rollback", [
            'rollback_version_id' => $rollbackVersion->id,
            'external_reference' => 'rollback-42',
        ])->assertCreated();
        $rollbackDeployment = Deployment::query()->findOrFail($rollback->json('data.id'));

        $this->postJson("/api/deployments/{$rollbackDeployment->id}/approve")->assertOk();
        $this->postJson("/api/deployments/{$rollbackDeployment->id}/start")->assertOk();
        $this->postJson("/api/deployments/{$rollbackDeployment->id}/succeed", ['result' => 'Rollback completed'])->assertOk();

        $this->assertDatabaseHas('deployments', [
            'id' => $deployment->id,
            'status' => DeploymentStatus::ROLLED_BACK->value,
        ]);
        $this->assertDatabaseHas('deployment_events', [
            'deployment_id' => $deployment->id,
            'type' => DeploymentEventType::ROLLED_BACK->value,
        ]);
    }

    public function test_failed_and_canceled_deployments_record_their_outcomes(): void
    {
        $this->actingAsDeploymentUser([
            'create_deployments',
            'approve_deployments',
            'execute_deployments',
        ]);
        $software = Software::factory()->create();
        $version = Version::factory()->for($software)->create([
            'status' => VersionStatus::DRAFT,
            'approval_status' => ApprovalStatus::APPROVED,
        ]);
        $environment = Environment::factory()->create(['is_production' => false]);
        $payload = [
            'software_id' => $software->id,
            'version_id' => $version->id,
            'environment_id' => $environment->id,
        ];

        $canceled = Deployment::query()->findOrFail($this->postJson('/api/deployments', $payload)->assertCreated()->json('data.id'));
        $this->postJson("/api/deployments/{$canceled->id}/cancel", ['comment' => 'Window was withdrawn'])
            ->assertOk()
            ->assertJsonPath('data.status', DeploymentStatus::CANCELED->value);

        $failed = Deployment::query()->findOrFail($this->postJson('/api/deployments', $payload)->assertCreated()->json('data.id'));
        $this->postJson("/api/deployments/{$failed->id}/approve")->assertOk();
        $this->postJson("/api/deployments/{$failed->id}/start")->assertOk();
        $this->postJson("/api/deployments/{$failed->id}/fail", ['result' => 'Health check failed'])
            ->assertOk()
            ->assertJsonPath('data.status', DeploymentStatus::FAILED->value);

        $this->assertDatabaseHas('deployments', [
            'id' => $canceled->id,
            'status' => DeploymentStatus::CANCELED->value,
            'result' => 'Window was withdrawn',
        ]);
        $this->assertDatabaseHas('deployments', [
            'id' => $failed->id,
            'status' => DeploymentStatus::FAILED->value,
            'result' => 'Health check failed',
        ]);
    }

    public function test_terminal_deployment_is_immutable_but_can_receive_a_correction_event(): void
    {
        $this->actingAsDeploymentUser([
            'create_deployments',
            'approve_deployments',
            'execute_deployments',
            'correct_deployments',
        ]);
        $software = Software::factory()->create();
        $version = Version::factory()->for($software)->create([
            'status' => VersionStatus::DRAFT,
            'approval_status' => ApprovalStatus::APPROVED,
        ]);
        $environment = Environment::factory()->create(['is_production' => false]);
        $deployment = Deployment::query()->findOrFail($this->postJson('/api/deployments', [
            'software_id' => $software->id,
            'version_id' => $version->id,
            'environment_id' => $environment->id,
        ])->assertCreated()->json('data.id'));

        $this->postJson("/api/deployments/{$deployment->id}/approve")->assertOk();
        $this->postJson("/api/deployments/{$deployment->id}/start")->assertOk();
        $this->postJson("/api/deployments/{$deployment->id}/succeed", ['result' => 'Original result'])->assertOk();

        $this->putJson("/api/deployments/{$deployment->id}", ['notes' => 'Illegal mutation'])
            ->assertForbidden();
        $this->postJson("/api/deployments/{$deployment->id}/correct", [
            'reason' => 'Correcting the recorded result',
            'changes' => ['result' => 'Corrected result'],
        ])->assertOk();

        $this->assertDatabaseHas('deployments', [
            'id' => $deployment->id,
            'result' => 'Original result',
        ]);
        $this->assertDatabaseHas('deployment_events', [
            'deployment_id' => $deployment->id,
            'type' => DeploymentEventType::CORRECTED->value,
            'comment' => 'Correcting the recorded result',
        ]);
    }

    public function test_api_tokens_require_the_separate_approval_permission(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::VIEWER,
            'abilities' => ['view_deployments', 'create_deployments'],
        ]);
        $token = $user->createToken('CI', [
            'access_rest',
            'view_deployments',
            'create_deployments',
        ])->plainTextToken;
        $software = Software::factory()->create();
        $version = Version::factory()->for($software)->create([
            'status' => VersionStatus::DRAFT,
            'approval_status' => ApprovalStatus::APPROVED,
        ]);
        $environment = Environment::factory()->create(['is_production' => false]);

        $deployment = Deployment::query()->findOrFail($this->withToken($token)->postJson('/api/deployments', [
            'software_id' => $software->id,
            'version_id' => $version->id,
            'environment_id' => $environment->id,
        ])->assertCreated()->json('data.id'));

        $this->withToken($token)
            ->postJson("/api/deployments/{$deployment->id}/approve")
            ->assertForbidden();
    }

    /**
     * @param  array<int, string>  $abilities
     */
    protected function actingAsDeploymentUser(array $abilities): User
    {
        $user = User::factory()->create([
            'role' => UserRole::VIEWER,
            'abilities' => array_values(array_unique(['view_deployments', ...$abilities])),
        ]);

        Sanctum::actingAs($user);

        return $user;
    }
}
