<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Helpers\AuditHelper;
use App\Models\AttachmentUpload;
use App\Models\User;
use App\Models\UserInvitation;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserManagementService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $actor, array $data): User
    {
        $this->authorizeManager($actor);

        if (! filled($data['password'] ?? null)) {
            throw ValidationException::withMessages([
                'password' => __('validation.required', ['attribute' => __('filament.users.password')]),
            ]);
        }

        $user = User::query()->create($this->prepareData($actor, $data));

        AuditHelper::logAction(
            $actor,
            'user.created',
            User::class,
            (int) $user->getKey(),
            [],
            $this->safeSnapshot($user),
        );

        return $user;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $actor, User $target, array $data): User
    {
        $this->authorizeManager($actor);

        $before = $this->safeSnapshot($target);
        $prepared = $this->prepareData($actor, $data, $target);

        if (! filled($prepared['password'] ?? null)) {
            unset($prepared['password']);
        }

        if (Str::lower((string) ($prepared['email'] ?? $target->email)) !== Str::lower($target->email)) {
            $prepared['email_verified_at'] = null;
        }

        $target->forceFill($prepared)->save();
        $after = $this->safeSnapshot($target->fresh());

        if ($before !== $after) {
            AuditHelper::logAction(
                $actor,
                'user.updated',
                User::class,
                (int) $target->getKey(),
                $before,
                $after,
            );
        }

        return $target->fresh();
    }

    public function setActive(User $actor, User $target, bool $active): void
    {
        $this->authorizeManager($actor);

        if (! $active && ! $this->canChangeActiveState($actor, $target, false)) {
            throw new AuthorizationException(__('filament.users.cannot_deactivate_account'));
        }

        if ((bool) $target->is_active === $active) {
            return;
        }

        $before = $this->safeSnapshot($target);
        $target->forceFill(['is_active' => $active])->save();

        AuditHelper::logAction(
            $actor,
            $active ? 'user.activated' : 'user.deactivated',
            User::class,
            (int) $target->getKey(),
            $before,
            $this->safeSnapshot($target->fresh()),
        );
    }

    public function verifyEmail(User $actor, User $target): void
    {
        $this->authorizeManager($actor);

        if ($target->hasVerifiedEmail()) {
            return;
        }

        $target->forceFill(['email_verified_at' => now()])->save();

        AuditHelper::logAction(
            $actor,
            'user.email_verified',
            User::class,
            (int) $target->getKey(),
            ['email_verified' => false],
            ['email_verified' => true],
        );
    }

    public function resendVerification(User $actor, User $target): void
    {
        $this->authorizeManager($actor);

        if ($target->hasVerifiedEmail()) {
            return;
        }

        $target->sendEmailVerificationNotification();

        AuditHelper::logAction(
            $actor,
            'user.verification_sent',
            User::class,
            (int) $target->getKey(),
        );
    }

    public function sendPasswordReset(User $actor, User $target): void
    {
        $this->authorizeManager($actor);

        Password::sendResetLink(['email' => $target->email]);

        AuditHelper::logAction(
            $actor,
            'user.password_reset_requested',
            User::class,
            (int) $target->getKey(),
        );
    }

    public function revokeTokens(User $actor, User $target): int
    {
        $this->authorizeManager($actor);

        $count = $target->tokens()->count();
        $target->tokens()->delete();

        if ($count > 0) {
            AuditHelper::logAction(
                $actor,
                'user.tokens_revoked',
                User::class,
                (int) $target->getKey(),
                ['token_count' => $count],
                [],
            );
        }

        return $count;
    }

    public function canDelete(User $actor, User $target): bool
    {
        return $actor->hasAbility('manage_users')
            && $actor->isNot($target)
            && (! $target->isActive()
                || $target->role !== UserRole::ADMIN
                || $this->activeAdminCount() > 1);
    }

    public function canChangeActiveState(User $actor, User $target, bool $active): bool
    {
        if (! $actor->hasAbility('manage_users')) {
            return false;
        }

        if ($active) {
            return true;
        }

        return $actor->isNot($target)
            && (! $target->isActive()
                || $target->role !== UserRole::ADMIN
                || $this->activeAdminCount() > 1);
    }

    public function delete(User $actor, User $target): void
    {
        if (! $this->canDelete($actor, $target)) {
            throw new AuthorizationException(__('filament.users.cannot_delete_account'));
        }

        $snapshot = $this->safeSnapshot($target);
        $uploadDirectories = [];

        DB::transaction(function () use ($actor, $snapshot, $target, &$uploadDirectories): void {
            AuditHelper::logAction(
                $actor,
                'user.deleted',
                User::class,
                (int) $target->getKey(),
                $snapshot,
                [],
            );

            $target->subscriptions()->delete();
            $target->notificationDeliveries()->delete();
            $target->notifications()->delete();
            $uploadDirectories = AttachmentUpload::query()
                ->where('user_id', $target->id)
                ->pluck('id')
                ->map(static fn (mixed $id): string => (string) $id)
                ->all();
            AttachmentUpload::query()->where('user_id', $target->id)->delete();
            $target->tokens()->delete();

            UserInvitation::query()
                ->where('invited_by', $target->id)
                ->update(['invited_by' => null]);

            DB::table('password_reset_tokens')->where('email', $target->email)->delete();
            DB::table('sessions')->where('user_id', $target->id)->delete();

            $target->delete();
        });

        foreach ($uploadDirectories as $uploadId) {
            Storage::disk('local')->deleteDirectory('mcp-uploads/'.$uploadId);
        }
    }

    /**
     * @return list<string>
     */
    public function assignableAbilities(?User $actor): array
    {
        if (! $actor instanceof User || ! $actor->hasAbility('manage_users')) {
            return [];
        }

        return array_values(array_filter(
            config('authorization.abilities', []),
            fn (mixed $ability): bool => is_string($ability) && $actor->hasAbility($ability),
        ));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function prepareData(User $actor, array $data, ?User $target = null): array
    {
        $role = $this->resolveRole($data['role'] ?? UserRole::VIEWER->value);
        $this->assertRoleAssignable($actor, $role);

        $requestedAbilities = is_array($data['abilities'] ?? null) ? $data['abilities'] : [];
        $abilities = $this->normalizeAbilities($actor, $requestedAbilities);

        if ($target instanceof User) {
            $preservedAbilities = array_values(array_diff(
                is_array($target->abilities) ? $target->abilities : [],
                $this->assignableAbilities($actor),
            ));
            $abilities = array_values(array_unique([...$preservedAbilities, ...$abilities]));

            $isActive = (bool) ($data['is_active'] ?? $target->is_active);
            if ($target->is($actor) && ! $isActive) {
                throw new AuthorizationException(__('filament.users.cannot_deactivate_account'));
            }

            if ($target->isActive() && $target->role === UserRole::ADMIN && (! $isActive || $role !== UserRole::ADMIN)) {
                $this->assertAnotherActiveAdminExists($target);
            }
        }

        $data['name'] = trim((string) ($data['name'] ?? ''));
        $data['email'] = Str::lower(trim((string) ($data['email'] ?? '')));
        $data['role'] = $role;
        $data['abilities'] = $abilities === [] ? null : $abilities;
        $data['is_active'] = (bool) ($data['is_active'] ?? $target?->is_active ?? true);

        unset($data['password_confirmation'], $data['last_login_at']);

        return $data;
    }

    /**
     * @param  array<int, mixed>  $requested
     * @return list<string>
     */
    private function normalizeAbilities(User $actor, array $requested): array
    {
        $requested = array_values(array_unique(array_filter($requested, 'is_string')));
        $allowed = $this->assignableAbilities($actor);
        $invalid = array_values(array_diff($requested, $allowed));

        if ($invalid !== []) {
            throw ValidationException::withMessages([
                'abilities' => __('filament.users.unauthorized_abilities'),
            ]);
        }

        return $requested;
    }

    private function resolveRole(mixed $role): UserRole
    {
        if ($role instanceof UserRole) {
            return $role;
        }

        $resolved = UserRole::tryFrom((string) $role);

        if (! $resolved) {
            throw ValidationException::withMessages([
                'role' => __('filament.users.invalid_role'),
            ]);
        }

        return $resolved;
    }

    private function assertRoleAssignable(User $actor, UserRole $role): void
    {
        if ($role === UserRole::ADMIN && ! $actor->isAdmin()) {
            throw new AuthorizationException(__('filament.users.cannot_assign_admin'));
        }
    }

    private function authorizeManager(User $actor): void
    {
        if (! $actor->hasAbility('manage_users')) {
            throw new AuthorizationException;
        }
    }

    private function assertAnotherActiveAdminExists(User $target): void
    {
        if ($this->activeAdminCount(except: $target) < 1) {
            throw new AuthorizationException(__('filament.users.cannot_remove_last_admin'));
        }
    }

    private function activeAdminCount(?User $except = null): int
    {
        return User::query()
            ->where('is_active', true)
            ->where('role', UserRole::ADMIN->value)
            ->when($except, fn ($query) => $query->whereKeyNot($except->getKey()))
            ->count();
    }

    /**
     * @return array<string, mixed>
     */
    private function safeSnapshot(User $user): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role instanceof UserRole ? $user->role->value : (string) $user->role,
            'abilities' => is_array($user->abilities) ? $user->abilities : [],
            'is_active' => $user->isActive(),
            'email_verified' => $user->hasVerifiedEmail(),
        ];
    }
}
