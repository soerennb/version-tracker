<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:lifecycle-alerts')]
#[Description('Send due end-of-life lifecycle alerts')]
class NotifyUpcomingEol extends Command
{
    public function handle(NotificationService $notificationService): int
    {
        $dispatchedAlerts = $notificationService->notifyUpcomingEol();

        $this->components->info("Dispatched {$dispatchedAlerts} lifecycle alert(s).");

        return self::SUCCESS;
    }
}
