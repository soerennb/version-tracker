<?php

use App\Settings\GovernanceSettings;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $defaults = GovernanceSettings::defaults();

        foreach ($defaults as $name => $value) {
            $this->migrator->add('governance.'.$name, $value);
        }
    }
};
