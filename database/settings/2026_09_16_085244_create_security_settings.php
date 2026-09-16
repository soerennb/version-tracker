<?php

use App\Settings\SecuritySettings;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $defaults = SecuritySettings::defaults();

        foreach ($defaults as $name => $value) {
            $this->migrator->add('security.'.$name, $value);
        }
    }
};
