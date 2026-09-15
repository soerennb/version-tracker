<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\VersionStatus;
use App\Models\EolAlertDelivery;
use App\Models\User;
use App\Models\Version;
use App\Notifications\LifecycleAlertNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LifecycleAlertCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_lifecycle_alert_command_dispatches_each_due_window_only_once(): void
    {
        Notification::fake();
        User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);
        Version::factory()->create([
            'status' => VersionStatus::PUBLISHED,
            'eol_date' => today()->addDays(30),
        ]);

        $this->artisan('app:lifecycle-alerts')->assertSuccessful();
        $this->artisan('app:lifecycle-alerts')->assertSuccessful();

        $this->assertDatabaseCount('eol_alert_deliveries', 1);
        $delivery = EolAlertDelivery::query()->sole();
        $this->assertSame(30, $delivery->window_days);
        $this->assertNotNull($delivery->dispatched_at);
        Notification::assertSentTimes(LifecycleAlertNotification::class, 1);
    }

    public function test_lifecycle_alert_command_uses_the_nearest_due_window(): void
    {
        Notification::fake();
        User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        foreach ([89, 29, 6] as $daysUntilEol) {
            Version::factory()->create([
                'status' => VersionStatus::PUBLISHED,
                'eol_date' => today()->addDays($daysUntilEol),
            ]);
        }

        Version::factory()->create([
            'status' => VersionStatus::PUBLISHED,
            'eol_date' => today()->addDays(120),
        ]);
        Version::factory()->create([
            'status' => VersionStatus::DRAFT,
            'eol_date' => today()->addDays(6),
        ]);

        $this->artisan('app:lifecycle-alerts')->assertSuccessful();

        $this->assertDatabaseCount('eol_alert_deliveries', 3);
        $this->assertDatabaseHas('eol_alert_deliveries', ['window_days' => 90]);
        $this->assertDatabaseHas('eol_alert_deliveries', ['window_days' => 30]);
        $this->assertDatabaseHas('eol_alert_deliveries', ['window_days' => 7]);
        Notification::assertSentTimes(LifecycleAlertNotification::class, 3);
    }

    public function test_a_changed_eol_date_creates_a_new_delivery_window(): void
    {
        Notification::fake();
        User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);
        $version = Version::factory()->create([
            'status' => VersionStatus::PUBLISHED,
            'eol_date' => today()->addDays(29),
        ]);

        $this->artisan('app:lifecycle-alerts')->assertSuccessful();

        $version->update([
            'eol_date' => today()->addDays(6),
        ]);

        $this->artisan('app:lifecycle-alerts')->assertSuccessful();

        $this->assertDatabaseCount('eol_alert_deliveries', 2);
        $this->assertDatabaseHas('eol_alert_deliveries', [
            'eol_date' => today()->addDays(29)->toDateString(),
            'window_days' => 30,
        ]);
        $this->assertDatabaseHas('eol_alert_deliveries', [
            'eol_date' => today()->addDays(6)->toDateString(),
            'window_days' => 7,
        ]);
        Notification::assertSentTimes(LifecycleAlertNotification::class, 2);
    }

    public function test_alert_windows_progress_without_duplicate_dispatches(): void
    {
        Notification::fake();
        User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);
        $initialDate = today();
        Version::factory()->create([
            'status' => VersionStatus::PUBLISHED,
            'eol_date' => $initialDate->copy()->addDays(90),
        ]);

        $this->travelTo($initialDate->copy()->addDays(1));
        $this->artisan('app:lifecycle-alerts')->assertSuccessful();

        $this->travelTo($initialDate->copy()->addDays(61));
        $this->artisan('app:lifecycle-alerts')->assertSuccessful();

        $this->travelTo($initialDate->copy()->addDays(84));
        $this->artisan('app:lifecycle-alerts')->assertSuccessful();
        $this->travelBack();

        $this->assertDatabaseCount('eol_alert_deliveries', 3);
        $this->assertEquals([7, 30, 90], EolAlertDelivery::query()->orderBy('window_days')->pluck('window_days')->all());
        Notification::assertSentTimes(LifecycleAlertNotification::class, 3);
    }
}
