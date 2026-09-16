<?php

namespace Tests\Feature;

use App\Enums\RegistrationMode;
use App\Filament\Pages\ManageAccessSettings;
use App\Filament\Pages\ManageGeneralSettings;
use App\Filament\Pages\ManageGitHubSettings;
use App\Filament\Pages\ManageGovernanceSettings;
use App\Filament\Pages\ManageNotificationSettings;
use App\Filament\Pages\ManageOperationsSettings;
use App\Filament\Pages\ManageSecuritySettings;
use App\Models\User;
use App\Services\RuntimeSettings;
use App\Settings\AccessSettings;
use App\Settings\GeneralSettings;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class RuntimeSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_runtime_configuration_comes_from_database_settings(): void
    {
        $settings = app(GeneralSettings::class);
        $settings->fill([
            'application_name' => 'Release Desk',
            'tagline_de' => 'Release Desk DE',
            'tagline_en' => 'Release Desk EN',
            'intro_de' => 'Individuelle Einleitung',
            'intro_en' => 'Custom introduction',
            'footer_de' => 'Individueller Footer',
            'footer_en' => 'Custom footer',
            'support_url' => 'https://support.example.test',
            'default_locale' => 'de',
            'fallback_locale' => 'en',
            'public_catalog_enabled' => true,
            'public_search_enabled' => false,
            'public_products_enabled' => false,
            'public_timeline_enabled' => true,
            'public_security_enabled' => true,
            'public_compare_enabled' => true,
        ])->save();
        app(RuntimeSettings::class)->forgetPublicRuntimeCache();

        $this->getJson('/api/public/runtime')
            ->assertOk()
            ->assertJsonPath('data.application.name', 'Release Desk')
            ->assertJsonPath('data.application.tagline.en', 'Release Desk EN')
            ->assertJsonPath('data.features.search', false)
            ->assertJsonPath('data.features.products', false);
        $this->getJson('/api/public/overview')
            ->assertOk()
            ->assertJsonCount(0, 'data.products');
        $this->getJson('/api/public/products')->assertNotFound();
        $this->getJson('/api/public/search?q=release')->assertNotFound();
    }

    public function test_registration_mode_can_disable_public_registration(): void
    {
        $settings = app(AccessSettings::class);
        $settings->fill([
            'registration_mode' => RegistrationMode::Disabled->value,
        ])->save();
        app(RuntimeSettings::class)->forgetPublicRuntimeCache();

        $this->postJson('/api/auth/register', [
            'name' => 'Blocked User',
            'email' => 'blocked@example.test',
            'password' => 'Secure-password-123',
            'password_confirmation' => 'Secure-password-123',
        ])->assertForbidden();
    }

    public function test_email_verification_requirement_can_be_disabled(): void
    {
        Notification::fake();
        $settings = app(AccessSettings::class);
        $settings->fill([
            'email_verification_required' => false,
        ])->save();
        app(RuntimeSettings::class)->forgetPublicRuntimeCache();

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Verified User',
            'email' => 'verified@example.test',
            'password' => 'Secure-password-123',
            'password_confirmation' => 'Secure-password-123',
        ]);

        $user = User::query()->where('email', 'verified@example.test')->firstOrFail();

        $response->assertCreated()
            ->assertJsonPath('data.email_verified', true)
            ->assertJsonPath('email_verification_required', false);
        Notification::assertNotSentTo($user, VerifyEmail::class);
    }

    public function test_password_policy_is_applied_to_registration(): void
    {
        $settings = app(AccessSettings::class);
        $settings->fill([
            'password_min_length' => 16,
            'password_require_mixed_case' => true,
            'password_require_numbers' => true,
            'password_require_symbols' => true,
        ])->save();
        app(RuntimeSettings::class)->forgetPublicRuntimeCache();

        $this->postJson('/api/auth/register', [
            'name' => 'Weak User',
            'email' => 'weak@example.test',
            'password' => 'short-password',
            'password_confirmation' => 'short-password',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('password');

        $this->postJson('/api/auth/register', [
            'name' => 'Strong User',
            'email' => 'strong@example.test',
            'password' => 'Strong-password-123!',
            'password_confirmation' => 'Strong-password-123!',
        ])->assertCreated();
    }

    public function test_authentication_lifetimes_are_applied_from_database_settings(): void
    {
        $settings = app(AccessSettings::class);
        $settings->fill([
            'password_confirmation_timeout_seconds' => 900,
            'email_verification_expire_minutes' => 30,
        ])->save();
        app(RuntimeSettings::class)->forgetPublicRuntimeCache();
        app(RuntimeSettings::class)->applyRequestSettings();

        $this->assertSame(900, config('auth.password_timeout'));

        $user = User::factory()->create();
        $verificationUrl = (new VerifyEmail)->toMail($user)->actionUrl;

        parse_str((string) parse_url($verificationUrl, PHP_URL_QUERY), $query);

        $this->assertNotEmpty($query['expires'] ?? null);
        $this->assertLessThanOrEqual(now()->addMinutes(30)->timestamp, (int) $query['expires']);
        $this->assertGreaterThan(now()->addMinutes(29)->timestamp, (int) $query['expires']);
    }

    public function test_settings_pages_are_permission_protected(): void
    {
        $admin = User::factory()->admin()->create();
        $editor = User::factory()->editor()->create();

        $this->actingAs($admin);
        $this->assertTrue(ManageGeneralSettings::canAccess());
        $this->assertTrue(ManageGitHubSettings::canAccess());

        $this->actingAs($editor);
        $this->assertFalse(ManageGeneralSettings::canAccess());
        $this->assertFalse(ManageGitHubSettings::canAccess());
    }

    public function test_admin_can_save_general_settings_and_settings_changes_are_audited(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(ManageGeneralSettings::class)
            ->set('data.application_name', 'Release Desk')
            ->call('save');

        $this->assertSame('Release Desk', app(GeneralSettings::class)->application_name);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'settings.updated',
            'model_type' => 'settings',
            'model_id' => 0,
        ]);
    }

    public function test_admin_can_mount_every_settings_page(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        foreach ([
            ManageGeneralSettings::class,
            ManageAccessSettings::class,
            ManageNotificationSettings::class,
            ManageGovernanceSettings::class,
            ManageGitHubSettings::class,
            ManageSecuritySettings::class,
            ManageOperationsSettings::class,
        ] as $settingsPage) {
            Livewire::test($settingsPage)->assertStatus(200);
        }
    }
}
