<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class SecuritySettings extends Settings
{
    public int $api_rate_limit_per_minute = 60;

    public bool $force_hsts = false;

    public int $upload_max_kb = 10240;

    /**
     * @var array<int, string>
     */
    public array $upload_allowed_extensions = [
        'pdf', 'txt', 'csv', 'json', 'xml', 'md', 'doc', 'docx', 'xls', 'xlsx', 'png', 'jpg', 'jpeg', 'zip',
    ];

    public static function group(): string
    {
        return 'security';
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'api_rate_limit_per_minute' => (int) config('security.api_rate_limit_per_minute', 60),
            'force_hsts' => (bool) config('security.force_hsts', false),
            'upload_max_kb' => (int) config('security.upload_max_kb', 10240),
            'upload_allowed_extensions' => config('security.upload_allowed_extensions', [
                'pdf', 'txt', 'csv', 'json', 'xml', 'md', 'doc', 'docx', 'xls', 'xlsx', 'png', 'jpg', 'jpeg', 'zip',
            ]),
        ];
    }
}
