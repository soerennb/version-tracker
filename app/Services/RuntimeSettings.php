<?php

namespace App\Services;

use App\Enums\RegistrationMode;
use App\Enums\VulnerabilitySeverity;
use App\Settings\AccessSettings;
use App\Settings\GeneralSettings;
use App\Settings\GitHubSettings;
use App\Settings\GovernanceSettings;
use App\Settings\NotificationSettings;
use App\Settings\OperationsSettings;
use App\Settings\SecuritySettings;
use DateTimeZone;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rules\Password;
use Spatie\LaravelSettings\Settings;
use Throwable;

class RuntimeSettings
{
    /**
     * @var array<string, Settings>
     */
    private array $resolved = [];

    public function general(): GeneralSettings
    {
        return $this->resolve(GeneralSettings::class, GeneralSettings::defaults());
    }

    public function access(): AccessSettings
    {
        return $this->resolve(AccessSettings::class, AccessSettings::defaults());
    }

    public function notifications(): NotificationSettings
    {
        return $this->resolve(NotificationSettings::class, NotificationSettings::defaults());
    }

    public function governance(): GovernanceSettings
    {
        return $this->resolve(GovernanceSettings::class, GovernanceSettings::defaults());
    }

    public function github(): GitHubSettings
    {
        return $this->resolve(GitHubSettings::class, GitHubSettings::defaults());
    }

    public function security(): SecuritySettings
    {
        return $this->resolve(SecuritySettings::class, SecuritySettings::defaults());
    }

    public function operations(): OperationsSettings
    {
        return $this->resolve(OperationsSettings::class, OperationsSettings::defaults());
    }

    public function registrationMode(): RegistrationMode
    {
        return RegistrationMode::tryFrom($this->access()->registration_mode) ?? RegistrationMode::Open;
    }

    public function registrationAllowed(): bool
    {
        return $this->registrationMode() === RegistrationMode::Open;
    }

    public function invitationRegistrationAllowed(): bool
    {
        return $this->registrationMode() !== RegistrationMode::Disabled;
    }

    public function passwordRule(): Password
    {
        $settings = $this->access();
        $rule = Password::min($settings->password_min_length);

        if ($settings->password_require_mixed_case) {
            $rule->mixedCase();
        }

        if ($settings->password_require_numbers) {
            $rule->numbers();
        }

        if ($settings->password_require_symbols) {
            $rule->symbols();
        }

        return $rule;
    }

    public function publicFeatureEnabled(string $feature): bool
    {
        return match ($feature) {
            'catalog' => $this->general()->public_catalog_enabled,
            'search' => $this->general()->public_search_enabled,
            'products' => $this->general()->public_products_enabled,
            'timeline' => $this->general()->public_timeline_enabled,
            'security' => $this->general()->public_security_enabled,
            'compare' => $this->general()->public_compare_enabled,
            default => false,
        };
    }

    public function isBlockingSeverity(VulnerabilitySeverity|string|null $severity): bool
    {
        $value = $severity instanceof VulnerabilitySeverity ? $severity->value : $severity;

        return is_string($value)
            && in_array($value, $this->governance()->blocking_vulnerability_severities, true);
    }

    public function publicRuntime(): array
    {
        $general = $this->general();
        $access = $this->access();

        return [
            'application' => [
                'name' => $general->application_name,
                'tagline' => [
                    'de' => $general->tagline_de,
                    'en' => $general->tagline_en,
                ],
                'intro' => [
                    'de' => $general->intro_de,
                    'en' => $general->intro_en,
                ],
                'footer' => [
                    'de' => $general->footer_de,
                    'en' => $general->footer_en,
                ],
                'support_url' => $general->support_url ?: null,
            ],
            'locale' => [
                'default' => $general->default_locale,
                'fallback' => $general->fallback_locale,
                'supported' => ['de', 'en'],
            ],
            'features' => [
                'catalog' => $general->public_catalog_enabled,
                'search' => $general->public_search_enabled,
                'products' => $general->public_products_enabled,
                'timeline' => $general->public_timeline_enabled,
                'security' => $general->public_security_enabled,
                'compare' => $general->public_compare_enabled,
            ],
            'access' => [
                'registration_mode' => $this->registrationMode()->value,
                'registration_allowed' => $this->registrationAllowed(),
                'invitation_registration_allowed' => $this->invitationRegistrationAllowed(),
                'email_verification_required' => $access->email_verification_required,
            ],
        ];
    }

    public function applyRequestSettings(): void
    {
        $general = $this->general();
        $access = $this->access();
        $notifications = $this->notifications();
        $governance = $this->governance();
        $security = $this->security();
        $operations = $this->operations();

        $systemTimezone = $this->validTimezone($operations->system_timezone, (string) config('app.timezone', 'UTC'));

        config([
            'app.name' => $general->application_name,
            'app.locale' => $general->default_locale,
            'app.fallback_locale' => $general->fallback_locale,
            'app.timezone' => $systemTimezone,
            'mail.from.address' => $notifications->mail_from_address,
            'mail.from.name' => $notifications->mail_from_name,
            'session.lifetime' => $access->session_lifetime_minutes,
            'session.expire_on_close' => $access->session_expire_on_close,
            'auth.passwords.users.expire' => $access->password_reset_expire_minutes,
            'auth.passwords.users.throttle' => $access->password_reset_throttle_seconds,
            'auth.password_timeout' => $access->password_confirmation_timeout_seconds,
            'security.api_rate_limit_per_minute' => $security->api_rate_limit_per_minute,
            'security.force_hsts' => $security->force_hsts,
            'security.upload_max_kb' => $security->upload_max_kb,
            'security.upload_allowed_extensions' => $security->upload_allowed_extensions,
            'security.risk_intelligence_enabled' => $security->risk_intelligence_enabled,
            'release_governance.require_four_eyes_for_critical_releases' => $governance->require_four_eyes_for_critical_releases,
        ]);

        date_default_timezone_set($systemTimezone);
    }

    public function forgetPublicRuntimeCache(): void
    {
        Cache::forget('public-runtime');
        $this->resolved = [];
    }

    /**
     * @param  class-string<Settings>  $class
     * @param  array<string, mixed>  $defaults
     */
    private function resolve(string $class, array $defaults): Settings
    {
        if (isset($this->resolved[$class])) {
            return $this->resolved[$class];
        }

        try {
            $settings = app($class);
            $resolved = new $class($settings->toArray());
        } catch (Throwable) {
            $resolved = new $class($defaults);
        }

        $this->resolved[$class] = $resolved;

        return $resolved;
    }

    private function validTimezone(string $timezone, string $fallback): string
    {
        try {
            new DateTimeZone($timezone);

            return $timezone;
        } catch (Throwable) {
            return $fallback;
        }
    }
}
