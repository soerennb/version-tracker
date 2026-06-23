<?php

namespace Tests\Feature;

use App\Enums\SubscriptionEvent;
use App\Enums\UserRole;
use App\Enums\VersionStatus;
use App\Models\Software;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Version;
use App\Models\Vulnerability;
use App\Notifications\FixAvailableNotification;
use App\Notifications\LifecycleAlertNotification;
use App\Notifications\SecurityAlertNotification;
use App\Notifications\VersionApprovedNotification;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationRecipientsTest extends TestCase
{
    use RefreshDatabase;

    public function test_version_approved_notifications_are_sent_only_to_admins(): void
    {
        Notification::fake();

        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);
        $editor = User::factory()->create([
            'role' => UserRole::EDITOR,
        ]);
        $viewer = User::factory()->create([
            'role' => UserRole::VIEWER,
        ]);

        $version = Version::factory()->create();

        app(NotificationService::class)->notifyVersionApproved($version);

        Notification::assertSentTo($admin, VersionApprovedNotification::class);
        Notification::assertNotSentTo($editor, VersionApprovedNotification::class);
        Notification::assertNotSentTo($viewer, VersionApprovedNotification::class);
    }

    public function test_security_alert_notifications_are_sent_only_to_admins(): void
    {
        Notification::fake();

        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);
        $editor = User::factory()->create([
            'role' => UserRole::EDITOR,
        ]);

        $vulnerability = Vulnerability::factory()->create([
            'severity' => 'high',
        ]);

        app(NotificationService::class)->notifySecurityAlert($vulnerability);

        Notification::assertSentTo($admin, SecurityAlertNotification::class);
        Notification::assertNotSentTo($editor, SecurityAlertNotification::class);
    }

    public function test_security_alert_and_fix_notifications_include_release_owners(): void
    {
        Notification::fake();

        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);
        $owner = User::factory()->create([
            'role' => UserRole::VIEWER,
        ]);
        $bystander = User::factory()->create([
            'role' => UserRole::VIEWER,
        ]);
        $subscriber = User::factory()->create([
            'role' => UserRole::VIEWER,
        ]);
        $software = Software::factory()->create([
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);
        Subscription::factory()->for($subscriber)->for($software)->create([
            'event' => SubscriptionEvent::SECURITY,
        ]);
        $affectedVersion = Version::factory()->for($software)->create();
        $fixedVersion = Version::factory()->for($software)->create();
        $vulnerability = Vulnerability::factory()->for($affectedVersion, 'affectedVersion')->create([
            'severity' => 'critical',
            'fixed_version_id' => $fixedVersion->id,
        ]);

        app(NotificationService::class)->notifySecurityAlert($vulnerability);

        Notification::assertSentTo($admin, SecurityAlertNotification::class);
        Notification::assertSentTo($owner, SecurityAlertNotification::class);
        Notification::assertSentTo($subscriber, SecurityAlertNotification::class);
        Notification::assertSentTo($owner, FixAvailableNotification::class);
        Notification::assertSentTo($subscriber, FixAvailableNotification::class);
        Notification::assertNotSentTo($bystander, SecurityAlertNotification::class);
        Notification::assertNotSentTo($bystander, FixAvailableNotification::class);
    }

    public function test_lifecycle_alert_notifications_are_sent_only_to_admins(): void
    {
        Notification::fake();

        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);
        $editor = User::factory()->create([
            'role' => UserRole::EDITOR,
        ]);
        $owner = User::factory()->create([
            'role' => UserRole::VIEWER,
        ]);
        $software = Software::factory()->create([
            'created_by' => $owner->id,
        ]);

        Version::factory()->for($software)->create([
            'status' => VersionStatus::PUBLISHED,
            'eol_date' => now()->addDays(20),
        ]);
        Version::factory()->create([
            'status' => VersionStatus::PUBLISHED,
            'eol_date' => now()->addDays(120),
        ]);

        app(NotificationService::class)->notifyUpcomingEol();

        Notification::assertSentTo($admin, LifecycleAlertNotification::class);
        Notification::assertSentTo($owner, LifecycleAlertNotification::class);
        Notification::assertNotSentTo($editor, LifecycleAlertNotification::class);
    }
}
