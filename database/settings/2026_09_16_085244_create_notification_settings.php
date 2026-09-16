<?php

use App\Settings\NotificationSettings;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $defaults = NotificationSettings::defaults();

        foreach ($defaults as $name => $value) {
            $this->migrator->add('notifications.'.$name, $value);
        }
    }
};
