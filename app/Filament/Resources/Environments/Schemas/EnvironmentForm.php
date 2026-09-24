<?php

namespace App\Filament\Resources\Environments\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class EnvironmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('customer_id')
                    ->label('Customer')
                    ->relationship('customer', 'name')
                    ->searchable(),
                TextInput::make('name')
                    ->label(__('filament.environments.fields.name'))
                    ->required()
                    ->maxLength(100),
                TextInput::make('code')
                    ->label(__('filament.environments.fields.code'))
                    ->required()
                    ->alphaDash()
                    ->maxLength(50),
                Textarea::make('description')
                    ->label(__('filament.environments.fields.description'))
                    ->rows(3)
                    ->columnSpanFull(),
                Toggle::make('is_production')
                    ->label(__('filament.environments.fields.is_production')),
                Toggle::make('is_active')
                    ->label(__('filament.environments.fields.is_active'))
                    ->default(true),
                TextInput::make('sort_order')
                    ->label(__('filament.environments.fields.sort_order'))
                    ->numeric()
                    ->minValue(0)
                    ->default(0),
            ]);
    }
}
