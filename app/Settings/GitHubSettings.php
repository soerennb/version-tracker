<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GitHubSettings extends Settings
{
    public bool $sync_enabled = false;

    public int $timeout = 10;

    public int $max_pages = 10;

    public string $sync_time = '02:00';

    public string $sync_timezone = 'UTC';

    public bool $import_releases = true;

    public bool $import_tags = true;

    public static function group(): string
    {
        return 'github';
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'sync_enabled' => (bool) config('services.github.sync_enabled', false),
            'timeout' => (int) config('services.github.timeout', 10),
            'max_pages' => (int) config('services.github.max_pages', 10),
            'sync_time' => '02:00',
            'sync_timezone' => (string) config('app.timezone', 'UTC'),
            'import_releases' => true,
            'import_tags' => true,
        ];
    }
}
