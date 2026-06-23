<?php

namespace App\Observers;

use App\Helpers\AuditHelper;
use App\Models\VersionReview;

class VersionReviewObserver
{
    public function created(VersionReview $versionReview): void
    {
        AuditHelper::logAction(
            auth()->user(),
            'version_review.created',
            VersionReview::class,
            (int) $versionReview->getKey(),
            [],
            $versionReview->toArray()
        );
    }

    public function updated(VersionReview $versionReview): void
    {
        AuditHelper::logAction(
            auth()->user(),
            'version_review.updated',
            VersionReview::class,
            (int) $versionReview->getKey(),
            $versionReview->getOriginal(),
            $versionReview->getChanges()
        );
    }

    public function deleted(VersionReview $versionReview): void
    {
        AuditHelper::logAction(
            auth()->user(),
            'version_review.deleted',
            VersionReview::class,
            (int) $versionReview->getKey(),
            $versionReview->getOriginal(),
            []
        );
    }
}
