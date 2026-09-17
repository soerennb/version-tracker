<?php

namespace App\Observers;

use App\Helpers\AuditHelper;
use App\Models\Environment;

class EnvironmentObserver
{
    /**
     * Handle the Environment "created" event.
     */
    public function created(Environment $environment): void
    {
        AuditHelper::logAction(
            auth()->user(),
            'environment.created',
            Environment::class,
            (int) $environment->getKey(),
            [],
            $environment->toArray(),
        );
    }

    /**
     * Handle the Environment "updated" event.
     */
    public function updated(Environment $environment): void
    {
        AuditHelper::logAction(
            auth()->user(),
            'environment.updated',
            Environment::class,
            (int) $environment->getKey(),
            $environment->getOriginal(),
            $environment->getChanges(),
        );
    }

    /**
     * Handle the Environment "deleted" event.
     */
    public function deleted(Environment $environment): void
    {
        AuditHelper::logAction(
            auth()->user(),
            'environment.deleted',
            Environment::class,
            (int) $environment->getKey(),
            $environment->getOriginal(),
            [],
        );
    }

    /**
     * Handle the Environment "restored" event.
     */
    public function restored(Environment $environment): void
    {
        //
    }

    /**
     * Handle the Environment "force deleted" event.
     */
    public function forceDeleted(Environment $environment): void
    {
        //
    }
}
