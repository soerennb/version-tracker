<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\UserResource;
use App\Models\AttachmentUpload;
use App\Models\AuditLog;
use App\Models\NotificationDelivery;
use App\Models\Software;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserInvitation;
use App\Models\Version;
use App\Models\VersionReview;
use App\Notifications\UserInvitationNotification;
use App\Services\InvitationService;
use App\Services\UserManagementService;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentUserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_admin_can_see_the_user_resource_and_invitation_area(): void
    {
        $admin = User::factory()->admin()->create();
        $editor = User::factory()->editor()->create();
        $this->actingAs($admin);

        $this->assertTrue(UserResource::canViewAny());

        Livewire::test(ListUsers::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$admin, $editor])
            ->assertActionExists('inviteUser');
    }

    public function test_admin_can_create_a_user_with_a_hashed_password(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'New Editor',
                'email' => 'new-editor@example.test',
                'password' => 'Secure-password-123',
                'password_confirmation' => 'Secure-password-123',
                'role' => UserRole::EDITOR->value,
                'abilities' => ['view_software'],
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified()
            ->assertRedirect();

        $created = User::query()->where('email', 'new-editor@example.test')->firstOrFail();

        $this->assertTrue(Hash::check('Secure-password-123', $created->password));
        $this->assertSame(UserRole::EDITOR, $created->role);
        $this->assertTrue($created->isActive());
    }

    public function test_non_admin_managers_cannot_assign_the_admin_role(): void
    {
        $manager = User::factory()->editor()->create(['abilities' => ['manage_users']]);

        $this->expectException(AuthorizationException::class);

        app(UserManagementService::class)->create($manager, [
            'name' => 'Escalated User',
            'email' => 'escalated@example.test',
            'password' => 'Secure-password-123',
            'role' => UserRole::ADMIN->value,
            'abilities' => [],
            'is_active' => true,
        ]);
    }

    public function test_login_records_last_login_and_inactive_users_cannot_authenticate(): void
    {
        $active = User::factory()->admin()->create(['password' => 'Secure-password-123']);

        $this->postJson('/api/auth/login', [
            'email' => $active->email,
            'password' => 'Secure-password-123',
        ])->assertOk();

        $this->assertNotNull($active->fresh()->last_login_at);

        $inactive = User::factory()->admin()->create([
            'email' => 'inactive@example.test',
            'password' => 'Secure-password-123',
            'is_active' => false,
        ]);

        $this->postJson('/api/auth/login', [
            'email' => $inactive->email,
            'password' => 'Secure-password-123',
        ])->assertUnprocessable();

        $this->actingAs($inactive)->getJson('/api/softwares')->assertForbidden();

        $token = $inactive->createToken('Inactive', ['access_rest', 'view_software'])->plainTextToken;
        auth()->forgetGuards();

        $this->withToken($token)->getJson('/api/softwares')->assertForbidden();
    }

    public function test_the_last_active_admin_cannot_be_deactivated_or_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $secondAdmin = User::factory()->admin()->create();
        $service = app(UserManagementService::class);

        $service->setActive($admin, $secondAdmin, false);

        $this->expectException(AuthorizationException::class);
        $service->setActive($admin, $admin, false);
    }

    public function test_deleting_a_user_removes_account_data_but_preserves_domain_history(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->editor()->create();
        $software = Software::factory()->create([
            'created_by' => $target->id,
            'updated_by' => $target->id,
        ]);
        $version = Version::factory()->for($software)->create(['created_by' => $target->id]);
        $review = VersionReview::factory()->for($version)->create(['user_id' => $target->id]);
        $subscription = Subscription::factory()->create([
            'user_id' => $target->id,
            'software_id' => $software->id,
        ]);
        $delivery = NotificationDelivery::factory()->create(['user_id' => $target->id]);
        $invitation = UserInvitation::factory()->create(['invited_by' => $target->id]);
        $audit = AuditLog::factory()->create(['user_id' => $target->id]);
        $target->notifications()->create([
            'id' => (string) str()->uuid(),
            'type' => 'test-notification',
            'data' => [],
        ]);
        DB::table('sessions')->insert([
            'id' => 'target-session',
            'user_id' => $target->id,
            'payload' => 'payload',
            'last_activity' => now()->timestamp,
        ]);
        DB::table('password_reset_tokens')->insert([
            'email' => $target->email,
            'token' => 'hashed-token',
            'created_at' => now(),
        ]);
        $upload = AttachmentUpload::factory()->create([
            'user_id' => $target->id,
            'version_id' => $version->id,
        ]);
        Storage::fake('local');
        Storage::disk('local')->put('mcp-uploads/'.$upload->id.'/release.zip', 'temporary upload');
        $target->createToken('Target token', ['access_rest', 'view_software']);

        app(UserManagementService::class)->delete($admin, $target);

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
        $this->assertDatabaseMissing('subscriptions', ['id' => $subscription->id]);
        $this->assertDatabaseMissing('notification_deliveries', ['id' => $delivery->id]);
        $this->assertDatabaseMissing('notifications', ['notifiable_id' => $target->id]);
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $target->id]);
        $this->assertDatabaseMissing('attachment_uploads', ['id' => $upload->id]);
        Storage::disk('local')->assertMissing('mcp-uploads/'.$upload->id.'/release.zip');
        $this->assertDatabaseMissing('sessions', ['id' => 'target-session']);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $target->email]);
        $this->assertDatabaseHas('software', ['id' => $software->id, 'created_by' => null, 'updated_by' => null]);
        $this->assertDatabaseHas('versions', ['id' => $version->id, 'created_by' => null]);
        $this->assertDatabaseHas('version_reviews', ['id' => $review->id, 'user_id' => null]);
        $this->assertDatabaseHas('user_invitations', ['id' => $invitation->id, 'invited_by' => null]);
        $this->assertDatabaseHas('audit_logs', ['id' => $audit->id, 'user_id' => null]);
    }

    public function test_invitation_is_created_in_the_user_list_and_can_be_revoked(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(ListUsers::class)
            ->callAction('inviteUser', data: [
                'email' => 'invited-from-filament@example.test',
                'name' => 'Invited from Filament',
            ])
            ->assertHasNoFormErrors();

        $invitation = UserInvitation::query()->where('email', 'invited-from-filament@example.test')->firstOrFail();
        $this->assertTrue($invitation->isUsable());
        Notification::assertSentOnDemand(UserInvitationNotification::class);

        app(InvitationService::class)->revoke($admin, $invitation);

        $this->assertSame('revoked', $invitation->fresh()->status()->value);
        $this->assertFalse($invitation->fresh()->isUsable());
    }
}
