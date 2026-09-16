<?php

namespace App\Services;

use App\Enums\SupportStatus;
use App\Enums\VersionStatus;
use App\Models\Version;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class LifecycleService
{
    /**
     * @return Builder<Version>
     */
    public function upcomingEolQuery(?int $days = null): Builder
    {
        $days ??= app(RuntimeSettings::class)->notifications()->eol_alert_horizon_days;

        return Version::query()
            ->with('software')
            ->where('status', VersionStatus::PUBLISHED->value)
            ->whereNotNull('eol_date')
            ->whereDate('eol_date', '>=', now()->toDateString())
            ->whereDate('eol_date', '<=', now()->addDays($days)->toDateString());
    }

    /**
     * @return Collection<int, Version>
     */
    public function upcomingEol(?int $days = null): Collection
    {
        return $this->upcomingEolQuery($days)
            ->orderBy('eol_date')
            ->get();
    }

    /**
     * @return array<string, int>
     */
    public function dashboardStats(?int $days = null): array
    {
        return [
            'upcoming_eol' => $this->upcomingEolQuery($days)->count(),
            'maintenance' => Version::query()->where('support_status', SupportStatus::MAINTENANCE->value)->count(),
            'deprecated' => Version::query()->where('support_status', SupportStatus::DEPRECATED->value)->count(),
            'eol' => Version::query()->where('support_status', SupportStatus::EOL->value)->count(),
        ];
    }
}
