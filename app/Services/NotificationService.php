<?php

namespace App\Services;

use App\Enums\SubscriptionEvent;
use App\Enums\UserRole;
use App\Enums\VulnerabilitySeverity;
use App\Models\EolAlertDelivery;
use App\Models\NotificationDelivery;
use App\Models\User;
use App\Models\Version;
use App\Models\Vulnerability;
use App\Notifications\FixAvailableNotification;
use App\Notifications\LifecycleAlertNotification;
use App\Notifications\SecurityAlertNotification;
use App\Notifications\VersionApprovedNotification;
use App\Notifications\VersionPublishedNotification;
use Illuminate\Notifications\Notification as NotificationInstance;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class NotificationService
{
    public function notifyVersionApproved(Version $version): void
    {
        $policy = $this->notificationPolicy('release_approved');

        if (! $policy['enabled']) {
            return;
        }

        $this->dispatchActionable(
            $this->releaseRecipients($version, SubscriptionEvent::RELEASE),
            new VersionApprovedNotification($version),
            'version-approved:'.$version->id,
            $policy['channels'],
        );
    }

    public function notifyVersionPublished(Version $version): void
    {
        $policy = $this->notificationPolicy('release_published');

        if (! $policy['enabled']) {
            return;
        }

        $this->dispatchActionable(
            $this->releaseRecipients($version, SubscriptionEvent::RELEASE),
            new VersionPublishedNotification($version),
            'version-published:'.$version->id,
            $policy['channels'],
        );
    }

    public function notifySecurityAlert(Vulnerability $vulnerability): void
    {
        $securityPolicy = $this->notificationPolicy('security_alert');
        $fixPolicy = $this->notificationPolicy('fix_available');

        if (! $securityPolicy['enabled'] && ! $fixPolicy['enabled']) {
            return;
        }

        if (! $vulnerability->severity instanceof VulnerabilitySeverity
            || ! in_array($vulnerability->severity->value, app(RuntimeSettings::class)->governance()->blocking_vulnerability_severities, true)) {
            return;
        }

        $recipients = $this->releaseRecipients($vulnerability->affectedVersion, SubscriptionEvent::SECURITY);

        if ($securityPolicy['enabled']) {
            $this->dispatchActionable(
                $recipients,
                new SecurityAlertNotification($vulnerability),
                'security-alert:vulnerability:'.$vulnerability->id,
                $securityPolicy['channels'],
            );
        }

        if ($vulnerability->fixed_version_id !== null && $fixPolicy['enabled']) {
            $this->dispatchActionable(
                $recipients,
                new FixAvailableNotification($vulnerability),
                'fix-available:vulnerability:'.$vulnerability->id,
                $fixPolicy['channels'],
            );
        }
    }

    public function notifyUpcomingEol(?int $days = null): int
    {
        $policy = $this->notificationPolicy('lifecycle_alert');

        if (! $policy['enabled']) {
            return 0;
        }

        $days ??= app(RuntimeSettings::class)->notifications()->eol_alert_horizon_days;

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

        $alertWindows = array_map('intval', app(RuntimeSettings::class)->notifications()->eol_alert_windows);
        sort($alertWindows, SORT_NUMERIC);

        foreach ($alertWindows as $windowDays) {
            $windowDays = (int) $windowDays;

            if ($windowDays <= 0) {
                continue;
            }

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

            $this->dispatchActionable(
                $this->releaseRecipients($version, SubscriptionEvent::EOL),
                new LifecycleAlertNotification($version),
                'eol:version:'.$version->id.':'.$windowDays.':'.$version->eol_date->toDateString(),
                $this->notificationPolicy('lifecycle_alert')['channels'],
            );

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

    private function dispatchActionable(
        Collection $recipients,
        NotificationInstance $notification,
        string $eventKey,
        array $channels,
    ): void {
        if ($channels === []) {
            return;
        }

        foreach ($recipients as $recipient) {
            if (! $recipient instanceof User) {
                continue;
            }

            $now = now();
            $inserted = NotificationDelivery::query()->insertOrIgnore([
                'user_id' => $recipient->id,
                'event_key' => $eventKey,
                'channel' => implode(',', $channels),
                'status' => 'queued',
                'attempts' => 1,
                'queued_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if ($inserted === 0) {
                continue;
            }

            $queuedNotification = clone $notification;

            if (method_exists($queuedNotification, 'setDeliveryEventKey')) {
                $queuedNotification->setDeliveryEventKey($eventKey);
            }

            if (method_exists($queuedNotification, 'setDeliveryChannels')) {
                $queuedNotification->setDeliveryChannels($channels);
            }

            try {
                $recipient->notify($queuedNotification);

                NotificationDelivery::query()
                    ->where('user_id', $recipient->id)
                    ->where('event_key', $eventKey)
                    ->update([
                        'status' => 'sent',
                        'sent_at' => now(),
                        'updated_at' => now(),
                    ]);
            } catch (Throwable $exception) {
                NotificationDelivery::query()
                    ->where('user_id', $recipient->id)
                    ->where('event_key', $eventKey)
                    ->update([
                        'status' => 'failed',
                        'failed_at' => now(),
                        'error_message' => $exception->getMessage(),
                        'updated_at' => now(),
                    ]);
            }
        }
    }

    /**
     * @return array{enabled: bool, channels: array<int, string>}
     */
    private function notificationPolicy(string $event): array
    {
        $settings = app(RuntimeSettings::class)->notifications();

        return [
            'enabled' => (bool) ($settings->{$event.'_enabled'} ?? false),
            'channels' => array_values(array_intersect(
                ['mail', 'database'],
                (array) ($settings->{$event.'_channels'} ?? []),
            )),
        ];
    }
}
