<?php

namespace App\Filament\Resources\AuditLogs\Pages;

use App\Filament\Resources\AuditLogs\AuditLogResource;
use Filament\Resources\Pages\ViewRecord;

class ViewAuditLog extends ViewRecord
{
    protected static string $resource = AuditLogResource::class;

    protected string $view = 'filament.resources.audit-logs.pages.view-audit-log';

    public function getTitle(): string
    {
        return $this->record->action_label;
    }
}
