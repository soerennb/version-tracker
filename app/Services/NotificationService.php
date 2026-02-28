<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Version;
use App\Models\Vulnerability;
use App\Notifications\SecurityAlertNotification;
use App\Notifications\VersionApprovedNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    public function notifyVersionApproved(Version $version): void
    {
        Notification::send($this->adminRecipients(), new VersionApprovedNotification($version));
    }

    public function notifySecurityAlert(Vulnerability $vulnerability): void
    {
        if ($vulnerability->severity !== 'critical' && $vulnerability->severity !== 'high') {
            return;
        }

        Notification::send($this->adminRecipients(), new SecurityAlertNotification($vulnerability));
    }

    protected function adminRecipients(): Collection
    {
        return User::query()
            ->where('role', UserRole::ADMIN->value)
            ->whereNotNull('email')
            ->get();
    }
}
