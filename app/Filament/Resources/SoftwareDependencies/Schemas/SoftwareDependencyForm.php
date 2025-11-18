<?php

namespace App\Filament\Resources\SoftwareDependencies\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SoftwareDependencyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('software_id')
                    ->relationship('software', 'name')
                    ->required(),
                Select::make('depends_on_software_id')
                    ->relationship('dependsOnSoftware', 'name')
                    ->required(),
                Select::make('min_version_id')
                    ->relationship('minVersion', 'id'),
                Select::make('max_version_id')
                    ->relationship('maxVersion', 'id'),
                TextInput::make('dependency_type')
                    ->required()
                    ->default('runtime'),
            ]);
    }
}
