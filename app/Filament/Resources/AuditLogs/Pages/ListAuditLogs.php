<?php

namespace App\Filament\Resources\AuditLogs\Pages;

use App\Filament\Resources\AuditLogs\AuditLogResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListAuditLogs extends ListRecords
{
    protected static string $resource = AuditLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportCsv')
                ->label(__('filament.actions.export_audit_csv'))
                ->icon('heroicon-o-arrow-down-tray')
                ->url(route('admin.exports.audit-logs.csv'))
                ->visible(fn (): bool => auth()->user() instanceof User && auth()->user()->can('view_audit_logs')),
        ];
    }
}
