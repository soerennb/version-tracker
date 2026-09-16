<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class AccessSettings extends Settings
{
    public string $registration_mode = 'open';

    public bool $email_verification_required = true;

    public int $invitation_expiry_days = 7;

    public int $password_min_length = 12;

    public bool $password_require_mixed_case = false;

    public bool $password_require_numbers = false;

    public bool $password_require_symbols = false;

    public int $password_reset_expire_minutes = 60;

    public int $password_reset_throttle_seconds = 60;

    public int $password_confirmation_timeout_seconds = 10800;

    public int $email_verification_expire_minutes = 1440;

    public int $auth_rate_limit_per_minute = 5;

    public int $verification_rate_limit_per_minute = 6;

    public int $session_lifetime_minutes = 120;

    public bool $session_expire_on_close = false;

    public static function group(): string
    {
        return 'access';
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'registration_mode' => 'open',
            'email_verification_required' => true,
            'invitation_expiry_days' => 7,
            'password_min_length' => 12,
            'password_require_mixed_case' => false,
            'password_require_numbers' => false,
            'password_require_symbols' => false,
            'password_reset_expire_minutes' => 60,
            'password_reset_throttle_seconds' => 60,
            'password_confirmation_timeout_seconds' => (int) config('auth.password_timeout', 10800),
            'email_verification_expire_minutes' => 1440,
            'auth_rate_limit_per_minute' => 5,
            'verification_rate_limit_per_minute' => 6,
            'session_lifetime_minutes' => (int) config('session.lifetime', 120),
            'session_expire_on_close' => (bool) config('session.expire_on_close', false),
        ];
    }
}
