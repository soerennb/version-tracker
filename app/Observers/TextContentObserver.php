<?php

namespace App\Observers;

use App\Helpers\AuditHelper;
use App\Models\TextContent;

class TextContentObserver
{
    public function created(TextContent $textContent): void
    {
        AuditHelper::logAction(auth()->user(), 'text_content.created', TextContent::class, (int) $textContent->getKey(), [], $textContent->toArray());
    }

    public function updated(TextContent $textContent): void
    {
        $changes = $textContent->getChanges();
        $oldValues = array_intersect_key($textContent->getOriginal(), $changes);

        AuditHelper::logAction(auth()->user(), 'text_content.updated', TextContent::class, (int) $textContent->getKey(), $oldValues, $changes);
    }

    public function deleted(TextContent $textContent): void
    {
        AuditHelper::logAction(auth()->user(), 'text_content.deleted', TextContent::class, (int) $textContent->getKey(), $textContent->getOriginal(), []);
    }
}
