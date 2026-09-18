<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use App\Settings\GeneralSettings;
use App\Settings\GitHubSettings;
use App\Settings\InstallationState;
use App\Settings\NotificationSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Throwable;

class SetupService
{
    public function isAvailable(): bool
    {
        if (! config('installation.web_setup_enabled', true) || ! filled(config('installation.setup_token'))) {
            return false;
        }

        try {
            $state = app(InstallationState::class);

            return ! $state->isCompleted() && ! User::query()->exists();
        } catch (Throwable) {
            return false;
        }
    }

    public function hasValidToken(string $token): bool
    {
        $configuredToken = (string) config('installation.setup_token', '');

        return filled($configuredToken) && hash_equals($configuredToken, $token);
    }

    /**
     * @param  array{admin_name: string, admin_email: string, password: string, application_name?: string|null, support_url?: string|null, default_locale?: string, fallback_locale?: string, mail_from_address?: string|null, mail_from_name?: string|null, github_sync_enabled?: bool}  $data
     */
    public function complete(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            if (! $this->isAvailable() || User::query()->lockForUpdate()->exists()) {
                abort(404);
            }

            $user = User::query()->create([
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'password' => Hash::make($data['password']),
                'role' => UserRole::ADMIN,
                'email_verified_at' => now(),
            ]);

            $general = app(GeneralSettings::class);
            $general->fill(array_filter([
                'application_name' => $data['application_name'] ?? null,
                'support_url' => $data['support_url'] ?? null,
                'default_locale' => $data['default_locale'] ?? null,
                'fallback_locale' => $data['fallback_locale'] ?? null,
            ], static fn (mixed $value): bool => $value !== null));
            $general->save();

            $notifications = app(NotificationSettings::class);
            $notifications->fill(array_filter([
                'mail_from_address' => $data['mail_from_address'] ?? null,
                'mail_from_name' => $data['mail_from_name'] ?? null,
            ], static fn (mixed $value): bool => $value !== null));
            $notifications->save();

            $github = app(GitHubSettings::class);
            $github->sync_enabled = (bool) ($data['github_sync_enabled'] ?? false);
            $github->save();

            app(InstallationState::class)->complete();

            return $user;
        });
    }
}
