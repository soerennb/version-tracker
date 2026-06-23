<?php

namespace Tests\Feature;

use App\Enums\ApprovalStatus;
use App\Enums\RejectReason;
use App\Enums\ReviewAction;
use App\Enums\UserRole;
use App\Enums\VersionStatus;
use App\Models\AuditLog;
use App\Models\Software;
use App\Models\User;
use App\Models\Version;
use App\Models\VersionReview;
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

    public function test_reject_stores_reason_without_overwriting_support_status(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::VIEWER,
            'abilities' => ['edit_versions'],
        ]);
        Sanctum::actingAs($user);

        $version = Version::factory()->create([
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

        $version = Version::factory()->create([
            'approval_status' => ApprovalStatus::PENDING->value,
        ]);

        $this->postJson('/api/versions/'.$version->id.'/approve')
            ->assertOk();

        $review = VersionReview::query()->where('version_id', $version->id)->firstOrFail();

        $this->assertSame(ReviewAction::APPROVED, $review->action);
        $this->assertSame($user->id, $review->user_id);
        $this->assertTrue(AuditLog::query()
            ->where('model_type', VersionReview::class)
            ->where('model_id', $review->id)
            ->where('action', 'version_review.created')
            ->exists());
    }

    public function test_critical_release_creator_cannot_self_approve_when_four_eyes_is_required(): void
    {
        config(['release_governance.require_four_eyes_for_critical_releases' => true]);

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
        config(['release_governance.require_four_eyes_for_critical_releases' => true]);

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
            'approval_status' => ApprovalStatus::PENDING,
        ]);

        Sanctum::actingAs($reviewer);

        $this->postJson('/api/versions/'.$version->id.'/approve')
            ->assertOk();

        $this->assertSame(ApprovalStatus::APPROVED, $version->refresh()->approval_status);
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
}
