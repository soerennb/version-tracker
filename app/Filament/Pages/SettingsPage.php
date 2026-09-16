<?php

namespace App\Filament\Pages;

use App\Helpers\AuditHelper;
use App\Services\RuntimeSettings;
use Filament\Pages\SettingsPage as BaseSettingsPage;
use Throwable;

abstract class SettingsPage extends BaseSettingsPage
{
    protected static string $requiredAbility = 'manage_settings';

    public static function canAccess(): bool
    {
        return auth()->user()?->can(static::$requiredAbility) ?? false;
    }

    public function canEdit(): bool
    {
        return auth()->user()?->can(static::$requiredAbility) ?? false;
    }

    public function save(): void
    {
        $before = $this->settingsSnapshot();

        parent::save();

        app(RuntimeSettings::class)->forgetPublicRuntimeCache();

        $after = $this->settingsSnapshot();
        $changedBefore = $this->redact($before);
        $changedAfter = $this->redact($after);

        if ($changedBefore === $changedAfter) {
            return;
        }

        AuditHelper::logAction(
            auth()->user(),
            'settings.updated',
            'settings',
            0,
            $changedBefore,
            $changedAfter,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function settingsSnapshot(): array
    {
        try {
            return app(static::getSettings())->toArray();
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function redact(array $values): array
    {
        return collect($values)
            ->mapWithKeys(function (mixed $value, string $key): array {
                if (str($key)->contains(['password', 'secret', 'token', 'key'], ignoreCase: true)) {
                    return [$key => filled($value) ? '[configured]' : '[not configured]'];
                }

                return [$key => $value];
            })
            ->all();
    }
}
