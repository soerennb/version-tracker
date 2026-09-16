<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class OperationsSettings extends Settings
{
    public bool $scheduler_enabled = true;

    public string $system_timezone = 'UTC';

    public int $public_runtime_cache_ttl_seconds = 60;

    public static function group(): string
    {
        return 'operations';
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'scheduler_enabled' => true,
            'system_timezone' => (string) config('app.timezone', 'UTC'),
            'public_runtime_cache_ttl_seconds' => 60,
        ];
    }
}
