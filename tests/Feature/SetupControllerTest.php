<?php

namespace Tests\Feature;

use App\Settings\GeneralSettings;
use App\Settings\InstallationState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetupControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'installation.setup_token' => 'setup-token-for-tests',
            'installation.web_setup_enabled' => true,
        ]);
    }

    public function test_setup_page_is_available_only_with_a_configured_token(): void
    {
        $this->get('/install')->assertOk();

        config(['installation.setup_token' => null]);

        $this->get('/install')->assertNotFound();
    }

    public function test_invalid_setup_tokens_are_rejected(): void
    {
        $this->post('/install', $this->validPayload(['token' => 'wrong-token']))
            ->assertForbidden();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_setup_creates_the_first_administrator_and_is_then_disabled(): void
    {
        $this->post('/install', $this->validPayload())
            ->assertRedirect('/admin/login');

        $this->assertDatabaseHas('users', [
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);
        $this->assertSame('Release Desk', app(GeneralSettings::class)->application_name);
        $this->assertSame('standard', app(InstallationState::class)->profile);
        $this->assertTrue(app(InstallationState::class)->isCompleted());

        $this->get('/install')->assertNotFound();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return [
            'token' => 'setup-token-for-tests',
            'admin_name' => 'Administrator',
            'admin_email' => 'admin@example.com',
            'password' => 'Secure-password-123',
            'password_confirmation' => 'Secure-password-123',
            'application_name' => 'Release Desk',
            'support_url' => 'https://support.example.test',
            'default_locale' => 'de',
            'fallback_locale' => 'en',
            'mail_from_address' => 'hello@example.test',
            'mail_from_name' => 'Release Desk',
            ...$overrides,
        ];
    }
}
