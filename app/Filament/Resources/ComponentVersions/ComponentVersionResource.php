<?php

namespace App\Filament\Resources\ComponentVersions;

use App\Filament\Resources\ComponentVersions\Pages\CreateComponentVersion;
use App\Filament\Resources\ComponentVersions\Pages\EditComponentVersion;
use App\Filament\Resources\ComponentVersions\Pages\ListComponentVersions;
use App\Filament\Resources\ComponentVersions\Schemas\ComponentVersionForm;
use App\Filament\Resources\ComponentVersions\Tables\ComponentVersionsTable;
use App\Models\ComponentVersion;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ComponentVersionResource extends Resource
{
    protected static ?string $model = ComponentVersion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getNavigationGroup(): ?string
    {
        return __('filament.navigation.version_tracking');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.composition.component_versions');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view_versions') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('edit_versions') ?? false;
    }

    public static function canEdit($record): bool
    {
        return static::canCreate();
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return ComponentVersionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ComponentVersionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListComponentVersions::route('/'),
            'create' => CreateComponentVersion::route('/create'),
            'edit' => EditComponentVersion::route('/{record}/edit'),
        ];
    }
}
