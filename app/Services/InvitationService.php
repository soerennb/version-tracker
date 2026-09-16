<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Helpers\AuditHelper;
use App\Models\User;
use App\Models\UserInvitation;
use App\Notifications\UserInvitationNotification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InvitationService
{
    public function __construct(private readonly RuntimeSettings $runtimeSettings) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $actor, array $data): UserInvitation
    {
        $this->authorize($actor);
        $this->assertRegistrationAllowed();

        $email = Str::lower(trim((string) $data['email']));
        $this->assertEmailAvailable($email);
        $token = Str::random(64);

        $invitation = DB::transaction(function () use ($actor, $data, $email, $token): UserInvitation {
            UserInvitation::query()
                ->where('email', $email)
                ->whereNull('accepted_at')
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);

            return UserInvitation::query()->create([
                'email' => $email,
                'name' => filled($data['name'] ?? null) ? trim((string) $data['name']) : null,
                'token_hash' => hash('sha256', $token),
                'invited_by' => $actor->id,
                'expires_at' => now()->addDays($this->runtimeSettings->access()->invitation_expiry_days),
            ]);
        });

        $this->notify($invitation, $token);

        AuditHelper::logAction(
            $actor,
            'user_invitation.created',
            UserInvitation::class,
            (int) $invitation->getKey(),
            [],
            ['email' => $invitation->email, 'expires_at' => $invitation->expires_at?->toISOString()],
        );

        return $invitation;
    }

    public function resend(User $actor, UserInvitation $invitation): void
    {
        $this->authorize($actor);
        $this->assertRegistrationAllowed();

        if ($invitation->accepted_at !== null) {
            throw new AuthorizationException(__('filament.users.invitation_already_accepted'));
        }

        $this->assertEmailAvailable($invitation->email);
        $token = Str::random(64);
        $before = $invitation->status()->value;

        $invitation->forceFill([
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDays($this->runtimeSettings->access()->invitation_expiry_days),
            'revoked_at' => null,
        ])->save();

        $this->notify($invitation, $token);

        AuditHelper::logAction(
            $actor,
            'user_invitation.resent',
            UserInvitation::class,
            (int) $invitation->getKey(),
            ['status' => $before],
            ['status' => $invitation->fresh()->status()->value, 'expires_at' => $invitation->expires_at?->toISOString()],
        );
    }

    public function revoke(User $actor, UserInvitation $invitation): void
    {
        $this->authorize($actor);

        if (! $invitation->isUsable()) {
            return;
        }

        $invitation->forceFill(['revoked_at' => now()])->save();

        AuditHelper::logAction(
            $actor,
            'user_invitation.revoked',
            UserInvitation::class,
            (int) $invitation->getKey(),
            ['status' => 'open'],
            ['status' => 'revoked'],
        );
    }

    public function findUsable(string $token): ?UserInvitation
    {
        $invitation = UserInvitation::query()
            ->where('token_hash', hash('sha256', $token))
            ->first();

        return $invitation?->isUsable() ? $invitation : null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function accept(string $token, array $data): User
    {
        $invitation = $this->findUsable($token);

        if (! $invitation) {
            throw (new ModelNotFoundException)->setModel(UserInvitation::class);
        }

        return DB::transaction(function () use ($data, $invitation): User {
            $lockedInvitation = UserInvitation::query()->lockForUpdate()->find($invitation->getKey());

            if (! $lockedInvitation?->isUsable()) {
                throw (new ModelNotFoundException)->setModel(UserInvitation::class);
            }

            if (User::query()->where('email', $lockedInvitation->email)->exists()) {
                throw ValidationException::withMessages([
                    'email' => __('auth.invitation.email_exists'),
                ]);
            }

            $user = User::query()->create([
                'name' => $data['name'] ?? $lockedInvitation->name ?? Str::before($lockedInvitation->email, '@'),
                'email' => $lockedInvitation->email,
                'password' => $data['password'],
                'role' => UserRole::VIEWER,
                'is_active' => true,
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();

            $lockedInvitation->forceFill(['accepted_at' => now()])->save();

            return $user;
        });
    }

    private function authorize(User $actor): void
    {
        if (! $actor->hasAbility('manage_users')) {
            throw new AuthorizationException;
        }
    }

    private function assertRegistrationAllowed(): void
    {
        if (! $this->runtimeSettings->invitationRegistrationAllowed()) {
            throw new AuthorizationException;
        }
    }

    private function assertEmailAvailable(string $email): void
    {
        if (User::query()->where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => __('validation.unique', ['attribute' => 'email']),
            ]);
        }
    }

    private function notify(UserInvitation $invitation, string $token): void
    {
        Notification::route('mail', $invitation->email)
            ->notify(new UserInvitationNotification($invitation, $token));
    }
}
