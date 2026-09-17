<?php

namespace App\Filament\Resources\Deployments\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DeploymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('software_id')
                    ->label(__('filament.deployments.fields.software'))
                    ->relationship('software', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('version_id')
                    ->label(__('filament.deployments.fields.version'))
                    ->relationship('version', 'version_number')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('environment_id')
                    ->label(__('filament.deployments.fields.environment'))
                    ->relationship('environment', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                DateTimePicker::make('scheduled_at')
                    ->label(__('filament.deployments.fields.scheduled_at'))
                    ->seconds(false),
                TextInput::make('change_reference')
                    ->label(__('filament.deployments.fields.change_reference'))
                    ->maxLength(150),
                DateTimePicker::make('maintenance_window_start')
                    ->label(__('filament.deployments.fields.maintenance_window_start'))
                    ->seconds(false),
                DateTimePicker::make('maintenance_window_end')
                    ->label(__('filament.deployments.fields.maintenance_window_end'))
                    ->seconds(false),
                Textarea::make('notes')
                    ->label(__('filament.deployments.fields.notes'))
                    ->rows(5)
                    ->columnSpanFull(),
            ]);
    }
}
