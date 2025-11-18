<?php

namespace App\Observers;

use App\Helpers\AuditHelper;
use App\Models\Version;

class VersionObserver
{
    public function created(Version $version): void
    {
        AuditHelper::logAction(
            auth()->user(),
            'version.created',
            Version::class,
            (int) $version->getKey(),
            [],
            $version->toArray()
        );
    }

    public function updated(Version $version): void
    {
        AuditHelper::logAction(
            auth()->user(),
            'version.updated',
            Version::class,
            (int) $version->getKey(),
            $version->getOriginal(),
            $version->getChanges()
        );
    }

    public function deleted(Version $version): void
    {
        AuditHelper::logAction(
            auth()->user(),
            'version.deleted',
            Version::class,
            (int) $version->getKey(),
            $version->getOriginal(),
            []
        );
    }
}
