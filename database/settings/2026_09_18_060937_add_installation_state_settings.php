<?php

use App\Settings\InstallationState;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        foreach (InstallationState::defaults() as $name => $value) {
            $this->migrator->add('installation.'.$name, $value);
        }
    }
};
