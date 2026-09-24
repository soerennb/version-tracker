<?php

namespace App\Filament\Resources\ComponentVersions\Schemas;

use App\Models\ComponentVersion;
use App\Models\TrackedComponent;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class ComponentVersionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tracked_component_id')->label(__('filament.composition.component'))->relationship('component', 'name')->searchable()->required()->live()->disabled(fn ($record): bool => $record !== null),
                TextInput::make('version_label')->label(__('filament.composition.version'))->required()->maxLength(100)->disabled(fn ($record): bool => $record !== null),
                Textarea::make('notes')->rows(3),
                Select::make('ted_acceptance_status')
                    ->label(__('filament.composition.ted_status'))
                    ->options([
                        'unknown' => __('filament.composition.ted_unknown'),
                        'accepted' => __('filament.composition.ted_accepted'),
                        'not_accepted' => __('filament.composition.ted_not_accepted'),
                    ])
                    ->default('unknown')
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Set $set, ?string $state): void {
                        if ($state === 'unknown') {
                            $set('ted_checked_at', null);
                        }
                    })
                    ->visible(fn (Get $get, ?ComponentVersion $record): bool => self::isEformsSdk($get, $record)),
                DatePicker::make('ted_checked_at')
                    ->label(__('filament.composition.ted_checked_at'))
                    ->required(fn (Get $get): bool => $get('ted_acceptance_status') !== 'unknown')
                    ->visible(fn (Get $get, ?ComponentVersion $record): bool => self::isEformsSdk($get, $record)),
            ]);
    }

    private static function isEformsSdk(Get $get, ?ComponentVersion $record): bool
    {
        if ($record !== null) {
            return $record->component?->kind === 'eforms_sdk';
        }

        return TrackedComponent::query()->whereKey($get('tracked_component_id'))->value('kind') === 'eforms_sdk';
    }
}
