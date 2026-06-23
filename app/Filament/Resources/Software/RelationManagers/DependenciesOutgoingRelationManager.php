<?php

namespace App\Filament\Resources\Software\RelationManagers;

use App\Models\Version;
use App\Rules\AcyclicSoftwareDependency;
use App\Rules\VersionBelongsToSoftware;
use App\Services\DependencyHealthService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DependenciesOutgoingRelationManager extends RelationManager
{
    protected static string $relationship = 'dependenciesOutgoing';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('depends_on_software_id')
                    ->relationship('dependsOnSoftware', 'name')
                    ->live()
                    ->afterStateUpdated(function (Set $set): void {
                        $set('min_version_id', null);
                        $set('max_version_id', null);
                    })
                    ->rules(fn (): array => [new AcyclicSoftwareDependency((int) $this->getOwnerRecord()->getKey())])
                    ->required(),
                Select::make('min_version_id')
                    ->options(fn (Get $get): array => $this->versionOptions($get('depends_on_software_id')))
                    ->searchable()
                    ->rules(fn (Get $get): array => [new VersionBelongsToSoftware($this->softwareId($get('depends_on_software_id')))]),
                Select::make('max_version_id')
                    ->options(fn (Get $get): array => $this->versionOptions($get('depends_on_software_id')))
                    ->searchable()
                    ->rules(fn (Get $get): array => [new VersionBelongsToSoftware($this->softwareId($get('depends_on_software_id')))]),
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
                TextColumn::make('dependsOnSoftware.name')
                    ->searchable(),
                TextColumn::make('health')
                    ->label(__('dependencies.health.label'))
                    ->state(fn ($record): string => app(DependencyHealthService::class)->evaluate($record)['label'])
                    ->description(fn ($record): string => app(DependencyHealthService::class)->evaluate($record)['detail'])
                    ->badge()
                    ->color(fn ($record): string => app(DependencyHealthService::class)->evaluate($record)['color']),
                TextColumn::make('minVersion.version_number')
                    ->label(__('dependencies.fields.min_version'))
                    ->searchable(),
                TextColumn::make('maxVersion.version_number')
                    ->label(__('dependencies.fields.max_version'))
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
    protected function versionOptions(mixed $softwareId): array
    {
        $softwareId = $this->softwareId($softwareId);

        if ($softwareId === null) {
            return [];
        }

        return Version::query()
            ->where('software_id', $softwareId)
            ->orderByDesc('release_date')
            ->pluck('version_number', 'id')
            ->all();
    }

    protected function softwareId(mixed $softwareId): ?int
    {
        return filled($softwareId) ? (int) $softwareId : null;
    }
}
