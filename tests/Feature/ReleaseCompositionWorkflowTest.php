<?php

namespace Tests\Feature;

use App\Enums\ApprovalStatus;
use App\Enums\UserRole;
use App\Enums\VersionStatus;
use App\Models\ComponentVersion;
use App\Models\Customer;
use App\Models\Environment;
use App\Models\Software;
use App\Models\TrackedComponent;
use App\Models\User;
use App\Models\Version;
use App\Services\ReleaseReadinessService;
use App\Services\VersionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReleaseCompositionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_composition_is_required_and_published_composition_is_public_and_immutable(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::ADMIN]));
        $software = Software::factory()->create(['tracks_release_composition' => true]);
        $release = Version::factory()->for($software)->create(['status' => VersionStatus::DRAFT, 'approval_status' => ApprovalStatus::APPROVED]);
        $baseline = $this->componentVersion('baseline', '2026-09');
        $eforms = $this->componentVersion('eforms_component', '7.2');
        $sdk = $this->componentVersion('eforms_sdk', '1.13.0');
        $interface = $this->componentVersion('interface', '2026-09');
        $secondInterface = ComponentVersion::factory()->for($interface->component, 'component')->create(['version_label' => '2026-10']);

        $this->assertContains('missing_release_composition', collect(app(ReleaseReadinessService::class)->evaluate($release)['blockers'])->pluck('code')->all());
        $payload = [
            'baseline_version_id' => $baseline->id,
            'eforms_component_version_id' => $eforms->id,
            'active_eforms_sdk_version_id' => $sdk->id,
            'supported_interface_version_ids' => [$sdk->id, $interface->id, $secondInterface->id],
        ];
        $this->putJson("/api/versions/{$release->id}/composition", [...$payload, 'supported_interface_version_ids' => [$interface->id]])
            ->assertUnprocessable()->assertJsonValidationErrors('active_eforms_sdk_version_id');
        $this->putJson("/api/versions/{$release->id}/composition", $payload)
            ->assertOk()->assertJsonPath('data.baseline.version', '2026-09')->assertJsonCount(3, 'data.supported_interfaces');
        $this->assertSame(ApprovalStatus::PENDING, $release->fresh()->approval_status);
        $this->assertNotContains('missing_release_composition', collect(app(ReleaseReadinessService::class)->evaluate($release->fresh())['blockers'])->pluck('code')->all());
        $this->getJson("/api/public/releases/{$release->id}")->assertNotFound();

        $release->forceFill(['status' => VersionStatus::PUBLISHED, 'approval_status' => ApprovalStatus::APPROVED])->save();
        $this->getJson("/api/public/releases/{$release->id}")
            ->assertOk()->assertJsonPath('data.composition.active_eforms_sdk.version', '1.13.0')
            ->assertJsonPath('data.composition.ted.status', 'unknown')
            ->assertJsonMissingPath('data.customer');
        $this->putJson("/api/component-versions/{$sdk->id}", [
            'ted_acceptance_status' => 'not_accepted',
            'ted_checked_at' => '2026-09-20',
        ])->assertOk();
        $this->getJson("/api/public/releases/{$release->id}")
            ->assertJsonPath('data.composition.ted.status', 'not_accepted')
            ->assertJsonPath('data.composition.ted.checked_at', '2026-09-20');
        $this->getJson("/api/public/products/{$software->id}")
            ->assertOk()->assertJsonPath('data.releases.0.composition.baseline.version', '2026-09');
        $this->putJson("/api/versions/{$release->id}/composition", $payload)->assertForbidden();

        $nextRelease = Version::factory()->for($software)->create(['status' => VersionStatus::PUBLISHED, 'approval_status' => ApprovalStatus::APPROVED]);
        $nextBaseline = ComponentVersion::factory()->for($baseline->component, 'component')->create(['version_label' => '2026-10']);
        $nextComposition = $nextRelease->composition()->create([
            'baseline_version_id' => $nextBaseline->id,
            'eforms_component_version_id' => $eforms->id,
            'active_eforms_sdk_version_id' => $sdk->id,
        ]);
        $nextComposition->supportedInterfaces()->create(['component_version_id' => $sdk->id]);
        $this->getJson("/api/public/compare?left={$release->id}&right={$nextRelease->id}")
            ->assertOk()->assertJsonPath('data.left.composition.baseline.version', '2026-09')
            ->assertJsonPath('data.right.composition.baseline.version', '2026-10');
    }

    public function test_token_without_edit_versions_cannot_change_composition(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        Sanctum::actingAs($user, ['access_rest', 'view_versions']);
        $release = Version::factory()->create(['status' => VersionStatus::DRAFT]);

        $this->withHeader('Authorization', 'Bearer test-token')
            ->putJson("/api/versions/{$release->id}/composition", [])
            ->assertForbidden();
    }

    public function test_existing_published_release_remains_idempotent_after_tracking_is_enabled(): void
    {
        $software = Software::factory()->create(['tracks_release_composition' => true]);
        $release = Version::factory()->for($software)->create([
            'status' => VersionStatus::PUBLISHED,
            'approval_status' => ApprovalStatus::APPROVED,
        ]);

        $this->assertSame($release->id, app(VersionService::class)->publish($release)->id);
    }

    public function test_installed_release_combines_fixed_release_and_matching_customer_customization(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::ADMIN]));
        $customer = Customer::factory()->create();
        $otherCustomer = Customer::factory()->create();
        $environment = Environment::factory()->create(['customer_id' => $customer->id]);
        $software = Software::factory()->create(['tracks_release_composition' => true]);
        $release = Version::factory()->for($software)->create(['status' => VersionStatus::PUBLISHED, 'approval_status' => ApprovalStatus::APPROVED]);
        $baseline = $this->componentVersion('baseline', 'B-4');
        $eforms = $this->componentVersion('eforms_component', '7.2');
        $sdk = $this->componentVersion('eforms_sdk', '1.13');
        $composition = $release->composition()->create([
            'baseline_version_id' => $baseline->id,
            'eforms_component_version_id' => $eforms->id,
            'active_eforms_sdk_version_id' => $sdk->id,
        ]);
        $composition->supportedInterfaces()->create(['component_version_id' => $sdk->id]);
        $customization = $this->componentVersion('customization', 'C-3', $customer);
        $wrongCustomization = $this->componentVersion('customization', 'C-4', $otherCustomer);
        $payload = ['software_id' => $software->id, 'version_id' => $release->id, 'environment_id' => $environment->id];

        $this->postJson('/api/deployments', $payload + ['customization_version_id' => $wrongCustomization->id])
            ->assertUnprocessable()->assertJsonValidationErrors('customization_version_id');
        $deploymentId = $this->postJson('/api/deployments', $payload + ['customization_version_id' => $customization->id])
            ->assertCreated()->assertJsonPath('data.release_composition.baseline.version', 'B-4')->json('data.id');
        $this->postJson("/api/deployments/{$deploymentId}/approve")->assertOk();
        $this->postJson("/api/deployments/{$deploymentId}/start")->assertOk();
        $this->postJson("/api/deployments/{$deploymentId}/succeed", ['result' => 'Installed'])->assertOk();
        $this->getJson("/api/environments/{$environment->id}/installed/{$software->id}")
            ->assertOk()->assertJsonPath('data.customization_version.version_label', 'C-3')
            ->assertJsonPath('data.release_composition.baseline.version', 'B-4');

        $failedId = $this->postJson('/api/deployments', $payload + ['customization_version_id' => $customization->id])
            ->assertCreated()->json('data.id');
        $this->postJson("/api/deployments/{$failedId}/approve")->assertOk();
        $this->postJson("/api/deployments/{$failedId}/start")->assertOk();
        $this->postJson("/api/deployments/{$failedId}/fail", ['result' => 'Installation failed'])->assertOk();
        $this->getJson("/api/environments/{$environment->id}/installed/{$software->id}")
            ->assertJsonPath('data.id', $deploymentId);

        $nextRelease = Version::factory()->for($software)->create(['status' => VersionStatus::PUBLISHED, 'approval_status' => ApprovalStatus::APPROVED]);
        $nextComposition = $nextRelease->composition()->create([
            'baseline_version_id' => $baseline->id,
            'eforms_component_version_id' => $eforms->id,
            'active_eforms_sdk_version_id' => $sdk->id,
        ]);
        $nextComposition->supportedInterfaces()->create(['component_version_id' => $sdk->id]);
        $nextId = $this->postJson('/api/deployments', [
            'software_id' => $software->id,
            'version_id' => $nextRelease->id,
            'environment_id' => $environment->id,
            'customization_version_id' => $customization->id,
        ])->assertCreated()->json('data.id');
        $this->postJson("/api/deployments/{$nextId}/approve")->assertOk();
        $this->postJson("/api/deployments/{$nextId}/start")->assertOk();
        $this->postJson("/api/deployments/{$nextId}/succeed", ['result' => 'Upgraded'])->assertOk();

        $rollbackId = $this->postJson("/api/deployments/{$nextId}/rollback", ['rollback_version_id' => $release->id])
            ->assertCreated()->assertJsonPath('data.customization_version.version_label', 'C-3')->json('data.id');
        $this->postJson("/api/deployments/{$rollbackId}/approve")->assertOk();
        $this->postJson("/api/deployments/{$rollbackId}/start")->assertOk();
        $this->postJson("/api/deployments/{$rollbackId}/succeed", ['result' => 'Restored'])->assertOk();
        $this->getJson("/api/environments/{$environment->id}/installed/{$software->id}")
            ->assertJsonPath('data.id', $rollbackId)
            ->assertJsonPath('data.version.version_number', $release->version_number);
    }

    public function test_admin_can_open_installed_releases_page(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->actingAs($user);
        Environment::factory()->create();

        $this->get('/admin/installed-releases')->assertOk()->assertSee(__('filament.composition.installed_releases'));
    }

    public function test_admin_can_open_component_catalog_and_release_editor(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->actingAs($user);
        $software = Software::factory()->create(['tracks_release_composition' => true]);
        $release = Version::factory()->for($software)->create(['status' => VersionStatus::DRAFT, 'approval_status' => ApprovalStatus::PENDING]);

        $this->get('/admin/tracked-components/create')->assertOk();
        $this->get('/admin/component-versions/create')->assertOk();
        $this->get("/admin/versions/{$release->id}/edit")->assertOk()->assertSee(__('filament.composition.edit_release'));
    }

    public function test_ted_acceptance_is_maintained_manually_for_eforms_sdk_versions(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::ADMIN]));
        $sdk = $this->componentVersion('eforms_sdk', '1.13.0');
        $baseline = $this->componentVersion('baseline', 'B-1');

        $this->putJson("/api/component-versions/{$sdk->id}", ['ted_acceptance_status' => 'accepted'])
            ->assertUnprocessable()->assertJsonValidationErrors('ted_checked_at');
        $this->putJson("/api/component-versions/{$sdk->id}", [
            'ted_acceptance_status' => 'accepted',
            'ted_checked_at' => '2026-09-20',
        ])->assertOk()->assertJsonPath('data.ted.status', 'accepted')->assertJsonPath('data.ted.checked_at', '2026-09-20');
        $this->putJson("/api/component-versions/{$baseline->id}", [
            'ted_acceptance_status' => 'accepted',
            'ted_checked_at' => '2026-09-20',
        ])->assertUnprocessable()->assertJsonValidationErrors('ted_acceptance_status');
    }

    private function componentVersion(string $kind, string $label, ?Customer $customer = null): ComponentVersion
    {
        $component = TrackedComponent::factory()->create(['kind' => $kind, 'customer_id' => $customer?->id]);

        return ComponentVersion::factory()->for($component, 'component')->create(['version_label' => $label]);
    }
}
