<?php

namespace App\Filament\Resources\TrackedComponents;

use App\Filament\Resources\TrackedComponents\Pages\CreateTrackedComponent;
use App\Filament\Resources\TrackedComponents\Pages\EditTrackedComponent;
use App\Filament\Resources\TrackedComponents\Pages\ListTrackedComponents;
use App\Filament\Resources\TrackedComponents\Schemas\TrackedComponentForm;
use App\Filament\Resources\TrackedComponents\Tables\TrackedComponentsTable;
use App\Models\TrackedComponent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TrackedComponentResource extends Resource
{
    protected static ?string $model = TrackedComponent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getNavigationGroup(): ?string
    {
        return __('filament.navigation.version_tracking');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.composition.tracked_components');
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
        return TrackedComponentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TrackedComponentsTable::configure($table);
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
            'index' => ListTrackedComponents::route('/'),
            'create' => CreateTrackedComponent::route('/create'),
            'edit' => EditTrackedComponent::route('/{record}/edit'),
        ];
    }
}
