<?php

namespace App\Filament\Resources\Software\Tables;

use App\Enums\ComplianceStatus;
use App\Enums\SoftwareStatus;
use App\Enums\SourceSyncStatus;
use App\Models\Software;
use App\Services\GitHubReleaseSyncDispatcher;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class SoftwareTable
{
    public static function configure(Table $table): Table
    {
        $statusOptions = collect(SoftwareStatus::cases())
            ->mapWithKeys(fn (SoftwareStatus $status) => [$status->value => $status->label()])
            ->all();
        $complianceOptions = collect(ComplianceStatus::cases())
            ->mapWithKeys(fn (ComplianceStatus $status) => [$status->value => $status->label()])
            ->all();

        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label(__('filament.software.fields.name'))
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => str($record->description)->limit(60)->toString()),
                TextColumn::make('status')
                    ->label(__('filament.software.fields.status'))
                    ->badge()
                    ->sortable()
                    ->formatStateUsing(fn (?SoftwareStatus $state) => $state?->label())
                    ->color(fn (?SoftwareStatus $state) => match ($state) {
                        SoftwareStatus::ACTIVE => 'success',
                        SoftwareStatus::INACTIVE => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('versions_count')
                    ->label(__('filament.navigation.versions'))
                    ->counts('versions')
                    ->badge()
                    ->color('info'),
                TextColumn::make('current_version')
                    ->label(__('filament.software.fields.current_version'))
                    ->sortable(),
                TextColumn::make('compliance_status')
                    ->label(__('filament.software.fields.compliance_status'))
                    ->badge()
                    ->formatStateUsing(fn (?ComplianceStatus $state) => $state?->label())
                    ->color(fn (?ComplianceStatus $state) => $state?->color() ?? 'gray'),
                TextColumn::make('last_release_date')
                    ->label(__('filament.software.fields.last_release_date'))
                    ->date('d.m.Y')
                    ->sortable(),
                TextColumn::make('latestSourceSyncRun.status')
                    ->label(__('filament.software.fields.source_sync_status'))
                    ->badge()
                    ->formatStateUsing(fn (?SourceSyncStatus $state): ?string => $state?->label())
                    ->color(fn (?SourceSyncStatus $state): string => $state?->color() ?? 'gray'),
                TextColumn::make('latestSourceSyncRun.created_at')
                    ->label(__('filament.software.fields.source_sync_last_run'))
                    ->dateTime('d.m.Y H:i')
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('creator.name')
                    ->label(__('filament.fields.created_by'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime('d.m.Y H:i')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('filament.software.fields.status'))
                    ->options($statusOptions),
                SelectFilter::make('compliance_status')
                    ->label(__('filament.software.fields.compliance_status'))
                    ->options($complianceOptions),
                TrashedFilter::make(),
            ])
            ->recordUrl(null)
            ->recordActions([
                Action::make('importGitHubReleases')
                    ->label(__('filament.actions.import_github_releases'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->requiresConfirmation()
                    ->visible(fn (Software $record): bool => filled($record->github_repo_url))
                    ->action(function (Software $record): void {
                        $syncRun = app(GitHubReleaseSyncDispatcher::class)->dispatch($record);

                        if (! $syncRun) {
                            Notification::make()
                                ->title(__('filament.messages.github_sync_already_active'))
                                ->warning()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title(__('filament.messages.github_sync_queued'))
                            ->body(__('filament.messages.github_sync_queued_description', [
                                'id' => $syncRun->id,
                            ]))
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
