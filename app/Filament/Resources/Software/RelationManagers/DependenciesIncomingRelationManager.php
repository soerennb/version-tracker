<?php

namespace App\Filament\Resources\Software\RelationManagers;

use App\Models\Version;
use App\Rules\AcyclicSoftwareDependency;
use App\Rules\VersionBelongsToSoftware;
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
                    ->rules(fn (): array => [new AcyclicSoftwareDependency((int) $this->getOwnerRecord()->getKey(), valueIsSource: true)])
                    ->required(),
                Select::make('min_version_id')
                    ->options(fn (): array => $this->ownerVersionOptions())
                    ->searchable()
                    ->rules(fn (): array => [new VersionBelongsToSoftware((int) $this->getOwnerRecord()->getKey())]),
                Select::make('max_version_id')
                    ->options(fn (): array => $this->ownerVersionOptions())
                    ->searchable()
                    ->rules(fn (): array => [new VersionBelongsToSoftware((int) $this->getOwnerRecord()->getKey())]),
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

    /**
     * @return array<int, string>
     */
    protected function ownerVersionOptions(): array
    {
        return Version::query()
            ->where('software_id', $this->getOwnerRecord()->getKey())
            ->orderByDesc('release_date')
            ->pluck('version_number', 'id')
            ->all();
    }
}
