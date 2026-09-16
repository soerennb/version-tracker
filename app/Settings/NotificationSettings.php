<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class NotificationSettings extends Settings
{
    public bool $release_approved_enabled = true;

    /**
     * @var array<int, string>
     */
    public array $release_approved_channels = ['mail', 'database'];

    public bool $release_published_enabled = true;

    /**
     * @var array<int, string>
     */
    public array $release_published_channels = ['mail', 'database'];

    public bool $security_alert_enabled = true;

    /**
     * @var array<int, string>
     */
    public array $security_alert_channels = ['mail', 'database'];

    public bool $fix_available_enabled = true;

    /**
     * @var array<int, string>
     */
    public array $fix_available_channels = ['mail', 'database'];

    public bool $lifecycle_alert_enabled = true;

    /**
     * @var array<int, string>
     */
    public array $lifecycle_alert_channels = ['mail', 'database'];

    /**
     * @var array<int, int>
     */
    public array $eol_alert_windows = [7, 30, 90];

    public int $eol_alert_horizon_days = 90;

    public string $lifecycle_alert_time = '08:00';

    public string $lifecycle_alert_timezone = 'UTC';

    public string $mail_from_address = 'hello@example.com';

    public string $mail_from_name = 'VersionTracker';

    public static function group(): string
    {
        return 'notifications';
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'release_approved_enabled' => true,
            'release_approved_channels' => ['mail', 'database'],
            'release_published_enabled' => true,
            'release_published_channels' => ['mail', 'database'],
            'security_alert_enabled' => true,
            'security_alert_channels' => ['mail', 'database'],
            'fix_available_enabled' => true,
            'fix_available_channels' => ['mail', 'database'],
            'lifecycle_alert_enabled' => true,
            'lifecycle_alert_channels' => ['mail', 'database'],
            'eol_alert_windows' => [7, 30, 90],
            'eol_alert_horizon_days' => 90,
            'lifecycle_alert_time' => '08:00',
            'lifecycle_alert_timezone' => (string) config('app.timezone', 'UTC'),
            'mail_from_address' => (string) config('mail.from.address', 'hello@example.com'),
            'mail_from_name' => (string) config('mail.from.name', 'VersionTracker'),
        ];
    }
}
