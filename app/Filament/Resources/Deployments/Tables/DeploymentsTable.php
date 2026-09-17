<?php

namespace App\Filament\Resources\Deployments\Tables;

use App\Enums\DeploymentStatus;
use App\Models\Deployment;
use App\Services\DeploymentService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

class DeploymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with([
                'software',
                'version',
                'environment',
                'creator',
                'approver',
                'executor',
            ])->withCount('events'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('software.name')
                    ->label(__('filament.deployments.fields.software'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('version.version_number')
                    ->label(__('filament.deployments.fields.version'))
                    ->badge()
                    ->color('info'),
                TextColumn::make('environment.name')
                    ->label(__('filament.deployments.fields.environment'))
                    ->badge()
                    ->searchable(),
                TextColumn::make('status')
                    ->label(__('filament.deployments.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (?DeploymentStatus $state): ?string => $state?->label())
                    ->color(fn (?DeploymentStatus $state): string => $state?->color() ?? 'gray'),
                TextColumn::make('scheduled_at')
                    ->label(__('filament.deployments.fields.scheduled_at'))
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                TextColumn::make('completed_at')
                    ->label(__('filament.deployments.fields.completed_at'))
                    ->dateTime('d.m.Y H:i')
                    ->placeholder(__('filament.common.not_available'))
                    ->sortable(),
                TextColumn::make('executor.name')
                    ->label(__('filament.deployments.fields.executed_by'))
                    ->placeholder(__('filament.common.system'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('events_count')
                    ->label(__('filament.deployments.fields.events'))
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('environment_id')
                    ->label(__('filament.deployments.fields.environment'))
                    ->relationship('environment', 'name'),
                SelectFilter::make('status')
                    ->label(__('filament.deployments.fields.status'))
                    ->options(collect(DeploymentStatus::cases())->mapWithKeys(fn (DeploymentStatus $status): array => [$status->value => $status->label()])->all()),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('approve')
                    ->label(__('filament.deployments.actions.approve'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->authorize('approve')
                    ->visible(fn (Deployment $record): bool => $record->status === DeploymentStatus::PLANNED)
                    ->form([
                        Forms\Components\Textarea::make('comment')
                            ->label(__('filament.deployments.fields.comment'))
                            ->rows(3),
                    ])
                    ->action(fn (Deployment $record, array $data) => self::runAction(fn () => app(DeploymentService::class)->approve($record, $data['comment'] ?? null))),
                Action::make('start')
                    ->label(__('filament.deployments.actions.start'))
                    ->icon('heroicon-o-play')
                    ->color('warning')
                    ->authorize('start')
                    ->visible(fn (Deployment $record): bool => $record->status === DeploymentStatus::APPROVED)
                    ->form([
                        Forms\Components\Textarea::make('comment')
                            ->label(__('filament.deployments.fields.comment'))
                            ->rows(3),
                    ])
                    ->action(fn (Deployment $record, array $data) => self::runAction(fn () => app(DeploymentService::class)->start($record, $data['comment'] ?? null))),
                Action::make('succeed')
                    ->label(__('filament.deployments.actions.succeed'))
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->authorize('complete')
                    ->visible(fn (Deployment $record): bool => $record->status === DeploymentStatus::IN_PROGRESS)
                    ->form([
                        Forms\Components\Textarea::make('result')
                            ->label(__('filament.deployments.fields.result'))
                            ->required()
                            ->minLength(2)
                            ->rows(4),
                    ])
                    ->action(fn (Deployment $record, array $data) => self::runAction(fn () => app(DeploymentService::class)->succeed($record, $data['result']))),
                Action::make('fail')
                    ->label(__('filament.deployments.actions.fail'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->authorize('complete')
                    ->visible(fn (Deployment $record): bool => $record->status === DeploymentStatus::IN_PROGRESS)
                    ->form([
                        Forms\Components\Textarea::make('result')
                            ->label(__('filament.deployments.fields.result'))
                            ->required()
                            ->minLength(2)
                            ->rows(4),
                    ])
                    ->action(fn (Deployment $record, array $data) => self::runAction(fn () => app(DeploymentService::class)->fail($record, $data['result']))),
                Action::make('cancel')
                    ->label(__('filament.deployments.actions.cancel'))
                    ->icon('heroicon-o-minus-circle')
                    ->color('gray')
                    ->authorize('cancel')
                    ->visible(fn (Deployment $record): bool => $record->status?->isActive() ?? false)
                    ->form([
                        Forms\Components\Textarea::make('comment')
                            ->label(__('filament.deployments.fields.reason'))
                            ->required()
                            ->minLength(2)
                            ->rows(4),
                    ])
                    ->action(fn (Deployment $record, array $data) => self::runAction(fn () => app(DeploymentService::class)->cancel($record, $data['comment']))),
                Action::make('rollback')
                    ->label(__('filament.deployments.actions.rollback'))
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->authorize('rollback')
                    ->visible(fn (Deployment $record): bool => $record->status === DeploymentStatus::SUCCEEDED)
                    ->form([
                        Forms\Components\Select::make('rollback_version_id')
                            ->label(__('filament.deployments.fields.rollback_version'))
                            ->options(fn (Deployment $record): array => $record->software?->versions()
                                ->whereKeyNot($record->version_id)
                                ->where('approval_status', 'approved')
                                ->when($record->environment?->is_production, fn ($query) => $query->where('status', 'published'))
                                ->orderByDesc('release_date')
                                ->pluck('version_number', 'id')
                                ->all() ?? [])
                            ->required()
                            ->searchable(),
                        Forms\Components\TextInput::make('change_reference')
                            ->label(__('filament.deployments.fields.change_reference')),
                        Forms\Components\Textarea::make('notes')
                            ->label(__('filament.deployments.fields.notes'))
                            ->rows(3),
                    ])
                    ->action(fn (Deployment $record, array $data) => self::runAction(fn () => app(DeploymentService::class)->rollback($record, $data))),
                Action::make('correct')
                    ->label(__('filament.deployments.actions.correct'))
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->authorize('correct')
                    ->visible(fn (Deployment $record): bool => $record->status?->isTerminal() ?? false)
                    ->form([
                        Forms\Components\KeyValue::make('changes')
                            ->label(__('filament.deployments.fields.correction_values'))
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('reason')
                            ->label(__('filament.deployments.fields.reason'))
                            ->required()
                            ->minLength(10)
                            ->rows(4),
                    ])
                    ->action(fn (Deployment $record, array $data) => self::runAction(fn () => app(DeploymentService::class)->correct($record, $data['changes'] ?? [], $data['reason']))),
            ])
            ->toolbarActions([]);
    }

    protected static function runAction(\Closure $action): void
    {
        try {
            $action();

            Notification::make()
                ->title(__('filament.deployments.action_completed'))
                ->success()
                ->send();
        } catch (AuthorizationException|ValidationException $exception) {
            $body = $exception instanceof ValidationException
                ? collect($exception->errors())->flatten()->implode(' ')
                : $exception->getMessage();

            Notification::make()
                ->title(__('filament.messages.action_failed'))
                ->body($body)
                ->danger()
                ->send();
        }
    }
}
