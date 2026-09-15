<?php

namespace App\Services;

use App\Enums\SubscriptionEvent;
use App\Enums\UserRole;
use App\Enums\VulnerabilitySeverity;
use App\Models\EolAlertDelivery;
use App\Models\User;
use App\Models\Version;
use App\Models\Vulnerability;
use App\Notifications\FixAvailableNotification;
use App\Notifications\LifecycleAlertNotification;
use App\Notifications\SecurityAlertNotification;
use App\Notifications\VersionApprovedNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    /**
     * @var array<int, int>
     */
    private const EOL_ALERT_WINDOWS = [7, 30, 90];

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

    public function notifyUpcomingEol(int $days = 90): int
    {
        return app(LifecycleService::class)->upcomingEol($days)
            ->reduce(function (int $dispatchedAlerts, Version $version): int {
                $windowDays = $this->eolAlertWindow($version);

                if ($windowDays === null || ! $this->dispatchEolAlert($version, $windowDays)) {
                    return $dispatchedAlerts;
                }

                return $dispatchedAlerts + 1;
            }, 0);
    }

    private function eolAlertWindow(Version $version): ?int
    {
        if ($version->eol_date === null) {
            return null;
        }

        $daysUntilEol = (int) today()->diffInDays($version->eol_date, false);

        foreach (self::EOL_ALERT_WINDOWS as $windowDays) {
            if ($daysUntilEol <= $windowDays) {
                return $windowDays;
            }
        }

        return null;
    }

    private function dispatchEolAlert(Version $version, int $windowDays): bool
    {
        return DB::transaction(function () use ($version, $windowDays): bool {
            $now = now();
            $deliveryKey = [
                'version_id' => $version->id,
                'eol_date' => $version->eol_date->toDateString(),
                'window_days' => $windowDays,
            ];

            if (EolAlertDelivery::query()->insertOrIgnore([
                ...$deliveryKey,
                'dispatched_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]) === 0) {
                return false;
            }

            Notification::send($this->releaseRecipients($version, SubscriptionEvent::EOL), new LifecycleAlertNotification($version));

            EolAlertDelivery::query()
                ->where($deliveryKey)
                ->update([
                    'dispatched_at' => now(),
                    'updated_at' => now(),
                ]);

            return true;
        });
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
