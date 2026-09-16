<?php

use App\Settings\GovernanceSettings;
use App\Settings\SecuritySettings;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $governance = GovernanceSettings::defaults();
        $security = SecuritySettings::defaults();

        foreach (['require_sbom', 'sbom_max_age_days', 'block_active_exploits'] as $name) {
            if (! $this->migrator->exists('governance.'.$name)) {
                $this->migrator->add('governance.'.$name, $governance[$name]);
            }
        }

        foreach (['sbom_max_kb', 'sbom_max_components'] as $name) {
            if (! $this->migrator->exists('security.'.$name)) {
                $this->migrator->add('security.'.$name, $security[$name]);
            }
        }
    }
};
