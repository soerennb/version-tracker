<?php

namespace App\Filament\Resources\AuditLogs\Tables;

use App\Models\AuditLog;
use App\Models\Software;
use App\Models\TextContent;
use App\Models\Version;
use App\Models\VersionReview;
use App\Models\Vulnerability;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

class AuditLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('action_label')
                    ->label(__('filament.audit.event'))
                    ->badge()
                    ->color(fn (AuditLog $record): string => str_contains($record->action, 'deleted') || str_contains($record->action, 'rejected') ? 'danger' : (str_contains($record->action, 'created') || str_contains($record->action, 'approved') ? 'success' : 'info'))
                    ->searchable(query: fn ($query, string $search) => $query->where('action', 'like', "%{$search}%")),
                TextColumn::make('model_label')
                    ->label(__('filament.audit.object'))
                    ->description(fn (AuditLog $record): string => '#'.$record->model_id),
                TextColumn::make('changes_count')
                    ->label(__('filament.audit.changed_fields'))
                    ->state(fn (AuditLog $record): int => count($record->getChangedFields()))
                    ->badge()
                    ->color('gray'),
                TextColumn::make('user.name')
                    ->label(__('filament.audit.actor'))
                    ->placeholder(__('filament.audit.system'))
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label(__('filament.audit.timestamp'))
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable(),
                TextColumn::make('ip_address')
                    ->label(__('filament.audit.ip_address'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('model_type')
                    ->label(__('filament.audit.model'))
                    ->options([
                        Software::class => __('filament.audit.models.Software'),
                        Version::class => __('filament.audit.models.Version'),
                        TextContent::class => __('filament.audit.models.TextContent'),
                        Vulnerability::class => __('filament.audit.models.Vulnerability'),
                        VersionReview::class => __('filament.audit.models.VersionReview'),
                    ]),
                SelectFilter::make('action')
                    ->label(__('filament.audit.event'))
                    ->options(fn (): array => AuditLog::query()->distinct()->orderBy('action')->pluck('action', 'action')->map(fn (string $action): string => str($action)->replace('.', ' ')->headline()->toString())->all()),
            ])
            ->groups([
                Group::make('created_at')
                    ->label(__('filament.audit.day'))
                    ->date(),
                Group::make('model_type')
                    ->label(__('filament.audit.model'))
                    ->getTitleFromRecordUsing(fn (AuditLog $record): string => $record->model_label),
            ])
            ->defaultGroup('created_at')
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
