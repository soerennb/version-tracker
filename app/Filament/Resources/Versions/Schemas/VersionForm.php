<?php

namespace App\Filament\Resources\Versions\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class VersionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('software_id')
                    ->label(__('filament.versions.fields.software'))
                    ->relationship('software', 'name')
                    ->searchable()
                    ->required(),
                TextInput::make('version_number')
                    ->label(__('filament.versions.fields.version_number'))
                    ->required()
                    ->regex('/^\d+(\.\d+){0,2}$/')
                    ->helperText('e.g. 1.2.3'),
                DatePicker::make('release_date')
                    ->label(__('filament.versions.fields.release_date'))
                    ->required(),
                DatePicker::make('eol_date')
                    ->label(__('filament.versions.fields.eol_date')),
                DatePicker::make('lts_date')
                    ->label(__('filament.versions.fields.lts_date')),
                TextInput::make('support_status')
                    ->label(__('filament.versions.fields.support_status'))
                    ->maxLength(255),
            ]);
    }
}
