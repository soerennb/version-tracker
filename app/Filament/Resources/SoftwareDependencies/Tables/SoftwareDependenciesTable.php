<?php

namespace App\Filament\Resources\SoftwareDependencies\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SoftwareDependenciesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('software.name')
                    ->label(__('filament.versions.fields.software'))
                    ->searchable(),
                TextColumn::make('dependsOnSoftware.name')
                    ->label(__('filament.navigation.dependencies'))
                    ->searchable(),
                TextColumn::make('appliesToVersion.version_number')
                    ->label(__('dependencies.fields.applies_to_version'))
                    ->placeholder(__('dependencies.labels.all_releases')),
                TextColumn::make('minVersion.version_number')
                    ->label(__('dependencies.fields.min_version'))
                    ->searchable(),
                TextColumn::make('maxVersion.version_number')
                    ->label(__('dependencies.fields.max_version'))
                    ->searchable(),
                TextColumn::make('dependency_type')
                    ->label(__('dependencies.fields.type'))
                    ->badge()
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
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
