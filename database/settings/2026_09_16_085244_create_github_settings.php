<?php

use App\Settings\GitHubSettings;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $defaults = GitHubSettings::defaults();

        foreach ($defaults as $name => $value) {
            $this->migrator->add('github.'.$name, $value);
        }
    }
};
