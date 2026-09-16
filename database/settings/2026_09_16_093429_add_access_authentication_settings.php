<?php

use App\Settings\AccessSettings;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $defaults = AccessSettings::defaults();

        foreach ([
            'password_confirmation_timeout_seconds',
            'email_verification_expire_minutes',
        ] as $name) {
            $this->migrator->add('access.'.$name, $defaults[$name]);
        }
    }
};
