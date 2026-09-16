<?php

use App\Settings\OperationsSettings;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $defaults = OperationsSettings::defaults();

        foreach ($defaults as $name => $value) {
            $this->migrator->add('operations.'.$name, $value);
        }
    }
};
