<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $application_name = 'VersionTracker';

    public string $tagline_de = 'Release intelligence';

    public string $tagline_en = 'Release intelligence';

    public string $intro_de = 'Versionen, Supportfenster und Sicherheitslage an einem Ort.';

    public string $intro_en = 'Versions, support windows, and security posture in one place.';

    public string $footer_de = 'Freigegebene Versionen. Klare Signale.';

    public string $footer_en = 'Published versions. Clear signals.';

    public ?string $support_url = null;

    public string $default_locale = 'de';

    public string $fallback_locale = 'en';

    public bool $public_catalog_enabled = true;

    public bool $public_search_enabled = true;

    public bool $public_products_enabled = true;

    public bool $public_timeline_enabled = true;

    public bool $public_security_enabled = true;

    public bool $public_compare_enabled = true;

    public static function group(): string
    {
        return 'general';
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'application_name' => (string) config('app.name', 'VersionTracker'),
            'tagline_de' => 'Release intelligence',
            'tagline_en' => 'Release intelligence',
            'intro_de' => 'Versionen, Supportfenster und Sicherheitslage an einem Ort.',
            'intro_en' => 'Versions, support windows, and security posture in one place.',
            'footer_de' => 'Freigegebene Versionen. Klare Signale.',
            'footer_en' => 'Published versions. Clear signals.',
            'support_url' => null,
            'default_locale' => (string) config('app.locale', 'de'),
            'fallback_locale' => (string) config('app.fallback_locale', 'en'),
            'public_catalog_enabled' => true,
            'public_search_enabled' => true,
            'public_products_enabled' => true,
            'public_timeline_enabled' => true,
            'public_security_enabled' => true,
            'public_compare_enabled' => true,
        ];
    }
}
