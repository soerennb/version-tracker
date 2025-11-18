<?php

namespace App\Filament\Resources\Software\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DependenciesIncomingRelationManager extends RelationManager
{
    protected static string $relationship = 'dependenciesIncoming';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('software_id')
                    ->relationship('software', 'name')
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

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('software.name')
                    ->searchable(),
                TextColumn::make('minVersion.id')
                    ->searchable(),
                TextColumn::make('maxVersion.id')
                    ->searchable(),
                TextColumn::make('dependency_type')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
