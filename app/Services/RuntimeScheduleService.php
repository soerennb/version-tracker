<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use DateTimeZone;
use Illuminate\Support\Facades\Cache;
use Throwable;

class RuntimeScheduleService
{
    public function __construct(private readonly RuntimeSettings $runtimeSettings) {}

    public function shouldRunGithubSync(): bool
    {
        if (! $this->runtimeSettings->operations()->scheduler_enabled || ! $this->runtimeSettings->github()->sync_enabled) {
            return false;
        }

        return $this->claimDailySlot('github-sync', $this->runtimeSettings->github()->sync_time, $this->runtimeSettings->github()->sync_timezone);
    }

    public function shouldRunLifecycleAlerts(): bool
    {
        if (! $this->runtimeSettings->operations()->scheduler_enabled || ! $this->runtimeSettings->notifications()->lifecycle_alert_enabled) {
            return false;
        }

        return $this->claimDailySlot(
            'lifecycle-alerts',
            $this->runtimeSettings->notifications()->lifecycle_alert_time,
            $this->runtimeSettings->notifications()->lifecycle_alert_timezone,
        );
    }

    private function claimDailySlot(string $name, string $time, string $timezone): bool
    {
        $time = substr(trim($time), 0, 5);

        if (! preg_match('/^\d{2}:\d{2}$/', $time)) {
            return false;
        }

        try {
            $dateTimeZone = new DateTimeZone($timezone);
        } catch (Throwable) {
            $dateTimeZone = new DateTimeZone((string) config('app.timezone', 'UTC'));
        }

        try {
            $now = CarbonImmutable::now($dateTimeZone);
            $scheduledAt = CarbonImmutable::createFromFormat(
                '!Y-m-d H:i',
                $now->format('Y-m-d').' '.$time,
                $dateTimeZone,
            );
        } catch (Throwable) {
            return false;
        }

        if (! $scheduledAt || $scheduledAt->format('H:i') !== $time || $now->lt($scheduledAt)) {
            return false;
        }

        $cacheKey = 'runtime-schedule:'.$name.':'.$now->format('Y-m-d').':'.$time.':'.$timezone;

        return Cache::add($cacheKey, $now->toIso8601String(), now()->addDays(2));
    }
}
