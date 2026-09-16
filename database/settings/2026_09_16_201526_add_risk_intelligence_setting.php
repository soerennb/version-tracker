<?php

use App\Settings\SecuritySettings;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        if (! $this->migrator->exists('security.risk_intelligence_enabled')) {
            $this->migrator->add(
                'security.risk_intelligence_enabled',
                SecuritySettings::defaults()['risk_intelligence_enabled'],
            );
        }
    }
};
