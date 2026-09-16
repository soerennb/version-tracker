<?php

use App\Settings\GeneralSettings;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $defaults = GeneralSettings::defaults();

        foreach ($defaults as $name => $value) {
            $this->migrator->add('general.'.$name, $value);
        }
    }
};
