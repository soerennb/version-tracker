<?php

namespace App\Filament\Resources\Deployments\Schemas;

use App\Enums\DeploymentStatus;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DeploymentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('filament.deployments.sections.summary'))
                    ->columns(3)
                    ->schema([
                        TextEntry::make('software.name')
                            ->label(__('filament.deployments.fields.software')),
                        TextEntry::make('version.version_number')
                            ->label(__('filament.deployments.fields.version'))
                            ->badge(),
                        TextEntry::make('environment.customer.name')->label('Customer')->placeholder('Unassigned'),
                        TextEntry::make('version.composition.baselineVersion.version_label')->label('Baseline')->placeholder('Not tracked'),
                        TextEntry::make('version.composition.eformsComponentVersion.version_label')->label('eForms component')->placeholder('Not tracked'),
                        TextEntry::make('version.composition.activeEformsSdkVersion.version_label')->label('eForms SDK')->placeholder('Not tracked'),
                        TextEntry::make('customizationVersion.version_label')->label('Customization')->placeholder('Not tracked'),
                        TextEntry::make('environment.name')
                            ->label(__('filament.deployments.fields.environment'))
                            ->badge(),
                        TextEntry::make('status')
                            ->label(__('filament.deployments.fields.status'))
                            ->badge()
                            ->formatStateUsing(fn (?DeploymentStatus $state): ?string => $state?->label()),
                        TextEntry::make('scheduled_at')
                            ->label(__('filament.deployments.fields.scheduled_at'))
                            ->dateTime('d.m.Y H:i'),
                        TextEntry::make('completed_at')
                            ->label(__('filament.deployments.fields.completed_at'))
                            ->dateTime('d.m.Y H:i')
                            ->placeholder(__('filament.common.not_available')),
                        TextEntry::make('change_reference')
                            ->label(__('filament.deployments.fields.change_reference'))
                            ->placeholder(__('filament.common.not_available')),
                        TextEntry::make('creator.name')
                            ->label(__('filament.deployments.fields.created_by'))
                            ->placeholder(__('filament.common.system')),
                        TextEntry::make('executor.name')
                            ->label(__('filament.deployments.fields.executed_by'))
                            ->placeholder(__('filament.common.system')),
                        TextEntry::make('notes')
                            ->label(__('filament.deployments.fields.notes'))
                            ->columnSpanFull()
                            ->placeholder(__('filament.common.not_available')),
                        TextEntry::make('result')
                            ->label(__('filament.deployments.fields.result'))
                            ->columnSpanFull()
                            ->placeholder(__('filament.common.not_available')),
                    ]),
                Section::make(__('filament.deployments.sections.timeline'))
                    ->schema([
                        RepeatableEntry::make('events')
                            ->label('')
                            ->schema([
                                TextEntry::make('type')
                                    ->label(__('filament.deployments.fields.event'))
                                    ->formatStateUsing(fn ($state): ?string => $state?->label()),
                                TextEntry::make('from_status')
                                    ->label(__('filament.deployments.fields.from_status'))
                                    ->formatStateUsing(fn (?string $state): string => DeploymentStatus::tryFrom($state ?? '')?->label() ?? __('filament.common.not_available')),
                                TextEntry::make('to_status')
                                    ->label(__('filament.deployments.fields.to_status'))
                                    ->formatStateUsing(fn (?string $state): string => DeploymentStatus::tryFrom($state ?? '')?->label() ?? __('filament.common.not_available')),
                                TextEntry::make('actor.name')
                                    ->label(__('filament.deployments.fields.actor'))
                                    ->placeholder(__('filament.common.system')),
                                TextEntry::make('created_at')
                                    ->label(__('filament.deployments.fields.timestamp'))
                                    ->dateTime('d.m.Y H:i:s'),
                                TextEntry::make('comment')
                                    ->label(__('filament.deployments.fields.comment'))
                                    ->columnSpanFull()
                                    ->placeholder(__('filament.common.not_available')),
                                TextEntry::make('metadata')
                                    ->label(__('filament.deployments.fields.metadata'))
                                    ->formatStateUsing(fn (mixed $state): string => is_array($state)
                                        ? (json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '')
                                        : (string) ($state ?? ''))
                                    ->columnSpanFull()
                                    ->placeholder(__('filament.common.not_available')),
                            ])
                            ->columns(3),
                    ]),
            ]);
    }
}
