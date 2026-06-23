<?php

namespace App\Services;

use App\Enums\SubscriptionEvent;
use App\Enums\UserRole;
use App\Enums\VulnerabilitySeverity;
use App\Models\User;
use App\Models\Version;
use App\Models\Vulnerability;
use App\Notifications\FixAvailableNotification;
use App\Notifications\LifecycleAlertNotification;
use App\Notifications\SecurityAlertNotification;
use App\Notifications\VersionApprovedNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    public function notifyVersionApproved(Version $version): void
    {
        Notification::send($this->releaseRecipients($version, SubscriptionEvent::RELEASE), new VersionApprovedNotification($version));
    }

    public function notifySecurityAlert(Vulnerability $vulnerability): void
    {
        if (! $vulnerability->severity instanceof VulnerabilitySeverity || ! $vulnerability->severity->shouldNotify()) {
            return;
        }

        $recipients = $this->releaseRecipients($vulnerability->affectedVersion, SubscriptionEvent::SECURITY);

        Notification::send($recipients, new SecurityAlertNotification($vulnerability));

        if ($vulnerability->fixed_version_id !== null) {
            Notification::send($recipients, new FixAvailableNotification($vulnerability));
        }
    }

    public function notifyUpcomingEol(int $days = 90): void
    {
        app(LifecycleService::class)->upcomingEol($days)
            ->each(fn (Version $version): mixed => Notification::send($this->releaseRecipients($version, SubscriptionEvent::EOL), new LifecycleAlertNotification($version)));
    }

    protected function adminRecipients(): Collection
    {
        return User::query()
            ->where('role', UserRole::ADMIN->value)
            ->whereNotNull('email')
            ->get();
    }

    protected function releaseRecipients(?Version $version, ?SubscriptionEvent $event = null): Collection
    {
        $adminRecipients = $this->adminRecipients();

        if (! $version) {
            return $adminRecipients;
        }

        $version->loadMissing('software');

        $ownerIds = collect([
            $version->software?->created_by,
            $version->software?->updated_by,
            $version->created_by,
        ])->filter()->unique()->values();

        if ($ownerIds->isEmpty()) {
            return $adminRecipients;
        }

        return $adminRecipients
            ->merge(User::query()->whereIn('id', $ownerIds)->whereNotNull('email')->get())
            ->merge($this->subscriptionRecipients($version, $event))
            ->unique('id')
            ->values();
    }

    protected function subscriptionRecipients(Version $version, ?SubscriptionEvent $event): Collection
    {
        if (! $event) {
            return collect();
        }

        return User::query()
            ->whereNotNull('email')
            ->whereHas('subscriptions', fn ($query) => $query
                ->where('software_id', $version->software_id)
                ->whereIn('event', [SubscriptionEvent::ALL->value, $event->value]))
            ->get();
    }
}
