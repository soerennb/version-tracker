<?php

namespace App\Filament\Resources\Deployments;

use App\Filament\Resources\Deployments\Pages\CreateDeployment;
use App\Filament\Resources\Deployments\Pages\EditDeployment;
use App\Filament\Resources\Deployments\Pages\ListDeployments;
use App\Filament\Resources\Deployments\Pages\ViewDeployment;
use App\Filament\Resources\Deployments\Schemas\DeploymentForm;
use App\Filament\Resources\Deployments\Schemas\DeploymentInfolist;
use App\Filament\Resources\Deployments\Tables\DeploymentsTable;
use App\Models\Deployment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class DeploymentResource extends Resource
{
    protected static ?string $model = Deployment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?int $navigationSort = 21;

    public static function getNavigationGroup(): ?string
    {
        return __('filament.navigation.operations');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.navigation.deployments');
    }

    public static function getModelLabel(): string
    {
        return __('filament.deployments.deployment');
    }

    public static function getPluralLabel(): ?string
    {
        return __('filament.deployments.deployments');
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return DeploymentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DeploymentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DeploymentsTable::configure($table);
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
            'index' => ListDeployments::route('/'),
            'create' => CreateDeployment::route('/create'),
            'view' => ViewDeployment::route('/{record}'),
            'edit' => EditDeployment::route('/{record}/edit'),
        ];
    }
}
