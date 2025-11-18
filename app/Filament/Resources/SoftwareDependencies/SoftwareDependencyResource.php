<?php

namespace App\Filament\Resources\SoftwareDependencies;

use App\Filament\Resources\SoftwareDependencies\Pages\CreateSoftwareDependency;
use App\Filament\Resources\SoftwareDependencies\Pages\EditSoftwareDependency;
use App\Filament\Resources\SoftwareDependencies\Pages\ListSoftwareDependencies;
use App\Filament\Resources\SoftwareDependencies\Schemas\SoftwareDependencyForm;
use App\Filament\Resources\SoftwareDependencies\Tables\SoftwareDependenciesTable;
use App\Models\SoftwareDependency;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SoftwareDependencyResource extends Resource
{
    protected static ?string $model = SoftwareDependency::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return __('filament.navigation.version_tracking');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.navigation.dependencies');
    }

    public static function form(Schema $schema): Schema
    {
        return SoftwareDependencyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SoftwareDependenciesTable::configure($table);
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
            'index' => ListSoftwareDependencies::route('/'),
            'create' => CreateSoftwareDependency::route('/create'),
            'edit' => EditSoftwareDependency::route('/{record}/edit'),
        ];
    }
}
