<?php

namespace Tests\Feature;

use App\Enums\ApprovalStatus;
use App\Enums\Language;
use App\Enums\RejectReason;
use App\Enums\ReviewAction;
use App\Enums\UserRole;
use App\Enums\VersionStatus;
use App\Models\AuditLog;
use App\Models\FileAttachment;
use App\Models\Software;
use App\Models\TextContent;
use App\Models\User;
use App\Models\Version;
use App\Models\VersionReview;
use App\Services\RuntimeSettings;
use App\Settings\GovernanceSettings;
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
        $this->assertSame($user->id, $version->created_by);
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

    public function test_editing_an_approved_draft_invalidates_its_approval(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::VIEWER,
            'abilities' => ['edit_versions'],
        ]);
        Sanctum::actingAs($user);

        $version = Version::factory()->create([
            'status' => VersionStatus::DRAFT,
            'approval_status' => ApprovalStatus::APPROVED,
        ]);

        $this->putJson('/api/versions/'.$version->id, [
            'version_number' => '2.0.0',
            'release_date' => '2026-02-10',
        ])->assertOk();

        $this->assertSame(ApprovalStatus::PENDING, $version->refresh()->approval_status);
        $this->assertDatabaseHas('version_reviews', [
            'version_id' => $version->id,
            'action' => ReviewAction::COMMENT->value,
            'comment' => __('versions.governance.approval_invalidated'),
        ]);
    }

    public function test_published_release_cannot_be_edited_through_the_version_api(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::VIEWER,
            'abilities' => ['edit_versions'],
        ]);
        Sanctum::actingAs($user);

        $version = Version::factory()->create([
            'status' => VersionStatus::PUBLISHED,
            'approval_status' => ApprovalStatus::APPROVED,
        ]);

        $this->putJson('/api/versions/'.$version->id, [
            'version_number' => '2.0.0',
            'release_date' => '2026-02-10',
        ])->assertForbidden();

        $this->assertSame($version->version_number, $version->refresh()->version_number);
    }

    public function test_reject_stores_reason_without_overwriting_support_status(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::VIEWER,
            'abilities' => ['edit_versions'],
        ]);
        Sanctum::actingAs($user);

        $version = Version::factory()->create([
            'status' => VersionStatus::DRAFT->value,
            'support_status' => 'supported',
            'approval_status' => ApprovalStatus::PENDING->value,
        ]);

        $this->postJson('/api/versions/'.$version->id.'/reject', [
            'reason' => 'Release notes are incomplete.',
            'reject_reason' => RejectReason::MISSING_CONTENT->value,
        ])->assertOk();

        $version->refresh();

        $this->assertSame('supported', $version->support_status?->value);
        $this->assertSame('Release notes are incomplete.', $version->rejection_reason);
        $this->assertSame(ApprovalStatus::REJECTED, $version->approval_status);

        $review = VersionReview::query()->where('version_id', $version->id)->firstOrFail();

        $this->assertSame(ReviewAction::REJECTED, $review->action);
        $this->assertSame(RejectReason::MISSING_CONTENT, $review->reject_reason);
        $this->assertSame('Release notes are incomplete.', $review->comment);
        $this->assertDatabaseHas('audit_logs', [
            'model_type' => VersionReview::class,
            'model_id' => $review->id,
            'action' => 'version_review.created',
        ]);
    }

    public function test_reject_requires_structured_reason(): void
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

        $this->postJson('/api/versions/'.$version->id.'/reject', [
            'reason' => 'No category supplied.',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('reject_reason');
    }

    public function test_approve_creates_review_history(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::VIEWER,
            'abilities' => ['approve_versions'],
        ]);
        Sanctum::actingAs($user);

        $version = $this->createReadyDraftVersion([
            'status' => VersionStatus::DRAFT->value,
            'approval_status' => ApprovalStatus::PENDING->value,
        ]);

        $this->postJson('/api/versions/'.$version->id.'/approve')
            ->assertOk();

        $review = VersionReview::query()->where('version_id', $version->id)->firstOrFail();

        $this->assertSame(ReviewAction::APPROVED, $review->action);
        $this->assertSame($user->id, $review->user_id);
        $this->assertSame(VersionStatus::DRAFT, $version->refresh()->status);
        $this->assertSame(ApprovalStatus::APPROVED, $version->approval_status);
        $this->assertTrue(AuditLog::query()
            ->where('model_type', VersionReview::class)
            ->where('model_id', $review->id)
            ->where('action', 'version_review.created')
            ->exists());
    }

    public function test_critical_release_creator_cannot_self_approve_when_four_eyes_is_required(): void
    {
        $this->enableFourEyesApproval();

        $creator = User::factory()->create([
            'role' => UserRole::VIEWER,
            'abilities' => ['create_versions', 'approve_versions'],
        ]);
        Sanctum::actingAs($creator);

        $software = Software::factory()->create();
        Version::factory()->for($software)->create([
            'version_number' => '1.0.0',
            'status' => VersionStatus::PUBLISHED,
            'approval_status' => ApprovalStatus::APPROVED,
        ]);

        $this->postJson('/api/versions', [
            'software_id' => $software->id,
            'version_number' => '2.0.0',
            'release_date' => '2026-02-01',
        ])->assertCreated();

        $version = Version::query()
            ->where('version_number', '2.0.0')
            ->firstOrFail();

        $this->postJson('/api/versions/'.$version->id.'/approve')
            ->assertForbidden();

        $this->assertSame(ApprovalStatus::PENDING, $version->refresh()->approval_status);
    }

    public function test_independent_reviewer_can_approve_critical_release(): void
    {
        $this->enableFourEyesApproval();

        $creator = User::factory()->create();
        $reviewer = User::factory()->create([
            'role' => UserRole::VIEWER,
            'abilities' => ['approve_versions'],
        ]);

        $software = Software::factory()->create();
        Version::factory()->for($software)->create([
            'version_number' => '1.0.0',
            'status' => VersionStatus::PUBLISHED,
            'approval_status' => ApprovalStatus::APPROVED,
        ]);
        $version = Version::factory()->for($software)->create([
            'created_by' => $creator->id,
            'version_number' => '2.0.0',
            'status' => VersionStatus::DRAFT,
            'approval_status' => ApprovalStatus::PENDING,
            'support_status' => 'supported',
        ]);
        TextContent::factory()->for($version)->create(['language' => Language::DE]);
        TextContent::factory()->for($version)->create(['language' => Language::EN]);
        FileAttachment::factory()->for($version)->create();

        Sanctum::actingAs($reviewer);

        $this->postJson('/api/versions/'.$version->id.'/approve')
            ->assertOk();

        $this->assertSame(ApprovalStatus::APPROVED, $version->refresh()->approval_status);
        $this->assertSame(VersionStatus::DRAFT, $version->status);
    }

    public function test_approval_requires_readiness_and_keeps_release_pending(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::VIEWER,
            'abilities' => ['approve_versions'],
        ]);
        Sanctum::actingAs($user);

        $version = Version::factory()->create([
            'status' => VersionStatus::DRAFT,
            'approval_status' => ApprovalStatus::PENDING,
        ]);

        $this->postJson('/api/versions/'.$version->id.'/approve')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['version', 'readiness']);

        $version->refresh();

        $this->assertSame(VersionStatus::DRAFT, $version->status);
        $this->assertSame(ApprovalStatus::PENDING, $version->approval_status);
        $this->assertDatabaseCount('version_reviews', 0);
    }

    public function test_admin_can_override_readiness_and_publish_in_a_separate_step(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $software = Software::factory()->create();
        $version = Version::factory()->for($software)->create([
            'version_number' => '3.0.0',
            'status' => VersionStatus::DRAFT,
            'approval_status' => ApprovalStatus::PENDING,
        ]);

        $this->postJson('/api/versions/'.$version->id.'/approve', [
            'override' => true,
            'override_reason' => 'Emergency release is covered by an external validation record.',
        ])->assertOk();

        $version->refresh();

        $this->assertSame(VersionStatus::DRAFT, $version->status);
        $this->assertSame(ApprovalStatus::APPROVED, $version->approval_status);

        $approval = VersionReview::query()
            ->where('version_id', $version->id)
            ->where('action', ReviewAction::APPROVED->value)
            ->firstOrFail();

        $this->assertTrue($approval->metadata['overridden']);
        $this->assertSame('Emergency release is covered by an external validation record.', $approval->comment);

        $this->postJson('/api/versions/'.$version->id.'/publish')
            ->assertOk();

        $version->refresh();

        $this->assertSame(VersionStatus::PUBLISHED, $version->status);
        $this->assertSame('3.0.0', $software->refresh()->current_version);
        $this->assertDatabaseHas('version_reviews', [
            'version_id' => $version->id,
            'action' => ReviewAction::PUBLISHED->value,
        ]);
    }

    public function test_readiness_override_requires_explicit_permission(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::VIEWER,
            'abilities' => ['approve_versions'],
        ]);
        Sanctum::actingAs($user);

        $version = Version::factory()->create([
            'status' => VersionStatus::DRAFT,
            'approval_status' => ApprovalStatus::PENDING,
        ]);

        $this->postJson('/api/versions/'.$version->id.'/approve', [
            'override' => true,
            'override_reason' => 'A documented emergency exception.',
        ])->assertForbidden();

        $this->assertSame(ApprovalStatus::PENDING, $version->refresh()->approval_status);
    }

    public function test_publish_requires_an_approved_draft(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::VIEWER,
            'abilities' => ['publish_versions'],
        ]);
        Sanctum::actingAs($user);

        $version = Version::factory()->create([
            'status' => VersionStatus::DRAFT,
            'approval_status' => ApprovalStatus::PENDING,
        ]);

        $this->postJson('/api/versions/'.$version->id.'/publish')
            ->assertForbidden();

        $this->assertSame(VersionStatus::DRAFT, $version->refresh()->status);
    }

    public function test_store_rejects_duplicate_version_number_for_same_software(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::VIEWER,
            'abilities' => ['create_versions'],
        ]);
        Sanctum::actingAs($user);

        $software = Software::factory()->create();
        Version::factory()->for($software)->create([
            'version_number' => '1.2.3',
        ]);

        $this->postJson('/api/versions', [
            'software_id' => $software->id,
            'version_number' => '1.2.3',
            'release_date' => '2026-02-01',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('version_number');
    }

    private function createReadyDraftVersion(array $attributes = []): Version
    {
        $version = Version::factory()->create(array_merge([
            'status' => VersionStatus::DRAFT,
            'approval_status' => ApprovalStatus::PENDING,
            'support_status' => 'supported',
        ], $attributes));

        TextContent::factory()->for($version)->create(['language' => Language::DE]);
        TextContent::factory()->for($version)->create(['language' => Language::EN]);
        FileAttachment::factory()->for($version)->create();

        return $version;
    }

    public function test_store_rejects_invalid_support_status(): void
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
            'support_status' => 'maybe',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('support_status');
    }

    private function enableFourEyesApproval(): void
    {
        app(GovernanceSettings::class)
            ->fill(['require_four_eyes_for_critical_releases' => true])
            ->save();
        app(RuntimeSettings::class)->forgetPublicRuntimeCache();
    }
}
