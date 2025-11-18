<?php

namespace App\Observers;

use App\Helpers\AuditHelper;
use App\Models\Software;

class SoftwareObserver
{
    public function created(Software $software): void
    {
        AuditHelper::logAction(
            auth()->user(),
            'software.created',
            Software::class,
            (int) $software->getKey(),
            [],
            $software->toArray()
        );
    }

    public function updated(Software $software): void
    {
        AuditHelper::logAction(
            auth()->user(),
            'software.updated',
            Software::class,
            (int) $software->getKey(),
            $software->getOriginal(),
            $software->getChanges()
        );
    }

    public function deleted(Software $software): void
    {
        AuditHelper::logAction(
            auth()->user(),
            'software.deleted',
            Software::class,
            (int) $software->getKey(),
            $software->getOriginal(),
            []
        );
    }
}
