<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Version;
use App\Models\Vulnerability;
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
}
