<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $defaults = [
            'registration_mode' => 'open',
            'email_verification_required' => true,
            'invitation_expiry_days' => 7,
            'password_min_length' => 12,
            'password_require_mixed_case' => false,
            'password_require_numbers' => false,
            'password_require_symbols' => false,
            'password_reset_expire_minutes' => 60,
            'password_reset_throttle_seconds' => 60,
            'auth_rate_limit_per_minute' => 5,
            'verification_rate_limit_per_minute' => 6,
            'session_lifetime_minutes' => (int) config('session.lifetime', 120),
            'session_expire_on_close' => (bool) config('session.expire_on_close', false),
        ];

        foreach ($defaults as $name => $value) {
            $this->migrator->add('access.'.$name, $value);
        }
    }
};
