<?php

namespace App\Filament\Resources\TrackedComponents\Schemas;

use App\Models\TrackedComponent;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class TrackedComponentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label(__('filament.composition.name'))->required()->maxLength(150),
                Select::make('kind')->label(__('filament.composition.kind'))->options(fn (): array => collect(TrackedComponent::KINDS)->mapWithKeys(fn (string $kind): array => [$kind => __('filament.composition.kinds.'.$kind)])->all())->required()->live()->afterStateUpdated(fn (Set $set) => $set('customer_id', null))->disabled(fn ($record): bool => $record !== null),
                Select::make('customer_id')->label(__('filament.composition.customer'))->relationship('customer', 'name')->searchable()->visible(fn (Get $get): bool => $get('kind') === 'customization')->required(fn (Get $get): bool => $get('kind') === 'customization')->disabled(fn ($record): bool => $record !== null),
            ]);
    }
}
