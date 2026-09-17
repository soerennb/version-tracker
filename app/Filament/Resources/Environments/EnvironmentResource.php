<?php

namespace App\Filament\Resources\Environments;

use App\Filament\Resources\Environments\Pages\CreateEnvironment;
use App\Filament\Resources\Environments\Pages\EditEnvironment;
use App\Filament\Resources\Environments\Pages\ListEnvironments;
use App\Filament\Resources\Environments\Schemas\EnvironmentForm;
use App\Filament\Resources\Environments\Tables\EnvironmentsTable;
use App\Models\Environment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EnvironmentResource extends Resource
{
    protected static ?string $model = Environment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?int $navigationSort = 20;

    public static function getNavigationGroup(): ?string
    {
        return __('filament.navigation.operations');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.navigation.environments');
    }

    public static function getModelLabel(): string
    {
        return __('filament.environments.environment');
    }

    public static function getPluralLabel(): ?string
    {
        return __('filament.environments.environments');
    }

    public static function form(Schema $schema): Schema
    {
        return EnvironmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EnvironmentsTable::configure($table);
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
            'index' => ListEnvironments::route('/'),
            'create' => CreateEnvironment::route('/create'),
            'edit' => EditEnvironment::route('/{record}/edit'),
        ];
    }
}
