<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\UserInvitation;
use App\Notifications\UserInvitationNotification;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AccountApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_visitor_can_register_and_receives_a_verification_notification(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.test',
            'password' => 'Secure-password-123',
            'password_confirmation' => 'Secure-password-123',
        ]);

        $user = User::query()->where('email', 'ada@example.test')->firstOrFail();

        $response->assertCreated()
            ->assertJsonPath('data.email', 'ada@example.test')
            ->assertJsonPath('data.role', UserRole::VIEWER->value)
            ->assertJsonPath('data.email_verified', false)
            ->assertJsonPath('email_verification_required', true);
        Notification::assertSentTo($user, VerifyEmail::class);
        $this->assertAuthenticatedAs($user);
    }

    public function test_unverified_accounts_can_log_in_but_cannot_access_verified_features(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create([
            'email' => 'unverified@example.test',
            'password' => 'Secure-password-123',
        ]);

        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'Secure-password-123',
        ])->assertOk()
            ->assertJsonPath('data.email_verified', false)
            ->assertJsonPath('email_verification_required', true);

        $this->getJson('/api/notifications')->assertForbidden();
    }

    public function test_signed_verification_link_marks_the_authenticated_user_as_verified(): void
    {
        $user = User::factory()->unverified()->create();
        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->actingAs($user)->get($url)
            ->assertRedirect('/account/verify?verified=1');

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_password_reset_uses_the_builtin_broker_and_accepts_a_valid_token(): void
    {
        Notification::fake();
        $user = User::factory()->create([
            'email' => 'reset@example.test',
            'password' => 'old-password-123',
        ]);

        $this->postJson('/api/auth/forgot-password', [
            'email' => $user->email,
        ])->assertAccepted();
        Notification::assertSentTo($user, ResetPassword::class);

        $token = Password::createToken($user);

        $this->postJson('/api/auth/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertOk();

        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'new-password-123',
        ])->assertOk();
    }

    public function test_admin_can_create_an_invitation_and_only_an_invitation_token_can_be_accepted(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $this->postJson('/api/invitations', [
            'email' => 'invited@example.test',
            'name' => 'Invited User',
        ])->assertCreated()
            ->assertJsonPath('data.email', 'invited@example.test');

        Notification::assertSentOnDemand(UserInvitationNotification::class, function (UserInvitationNotification $notification, array $channels, object $notifiable): bool {
            return $notification->invitation->email === 'invited@example.test'
                && $notifiable->routes['mail'] === 'invited@example.test';
        });

        $rawToken = 'invitation-token-123';
        $invitation = UserInvitation::factory()->create([
            'email' => 'accepted@example.test',
            'name' => 'Accepted User',
            'token_hash' => hash('sha256', $rawToken),
            'invited_by' => $admin->id,
        ]);

        $this->getJson('/api/invitations/'.$rawToken)
            ->assertOk()
            ->assertJsonPath('data.email', $invitation->email);

        $this->postJson('/api/invitations/'.$rawToken.'/accept', [
            'password' => 'invited-password-123',
            'password_confirmation' => 'invited-password-123',
        ])->assertCreated()
            ->assertJsonPath('data.email', 'accepted@example.test')
            ->assertJsonPath('data.email_verified', true);

        $this->assertDatabaseHas('user_invitations', [
            'id' => $invitation->id,
        ]);
        $this->assertNotNull($invitation->fresh()->accepted_at);
        $this->assertDatabaseHas('users', [
            'email' => 'accepted@example.test',
        ]);
    }

    public function test_non_admins_cannot_create_invitations_and_expired_tokens_are_rejected(): void
    {
        $viewer = User::factory()->create();
        Sanctum::actingAs($viewer);

        $this->postJson('/api/invitations', [
            'email' => 'blocked@example.test',
        ])->assertForbidden();

        $token = 'expired-invitation';
        UserInvitation::factory()->create([
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->subMinute(),
        ]);

        $this->getJson('/api/invitations/'.$token)->assertNotFound();
    }
}
