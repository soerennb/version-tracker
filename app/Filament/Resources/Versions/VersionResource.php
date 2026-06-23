<?php

namespace App\Filament\Resources\Versions;

use App\Filament\Resources\Versions\Pages\CreateVersion;
use App\Filament\Resources\Versions\Pages\EditVersion;
use App\Filament\Resources\Versions\Pages\ListVersions;
use App\Filament\Resources\Versions\Pages\ReleaseNotesEditor;
use App\Filament\Resources\Versions\RelationManagers\FileAttachmentsRelationManager;
use App\Filament\Resources\Versions\RelationManagers\TextContentsRelationManager;
use App\Filament\Resources\Versions\RelationManagers\VulnerabilitiesRelationManager;
use App\Filament\Resources\Versions\Schemas\VersionForm;
use App\Filament\Resources\Versions\Tables\VersionsTable;
use App\Models\Version;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VersionResource extends Resource
{
    protected static ?string $model = Version::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocument;

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('filament.navigation.versions');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament.navigation.version_tracking');
    }

    public static function form(Schema $schema): Schema
    {
        return VersionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VersionsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'fileAttachments',
                'software.dependenciesOutgoing.dependsOnSoftware',
                'software.dependenciesOutgoing.minVersion',
                'software.dependenciesOutgoing.maxVersion',
                'textContents',
                'vulnerabilities',
            ]);
    }

    public static function getRelations(): array
    {
        return [
            TextContentsRelationManager::class,
            FileAttachmentsRelationManager::class,
            VulnerabilitiesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVersions::route('/'),
            'create' => CreateVersion::route('/create'),
            'edit' => EditVersion::route('/{record}/edit'),
            'release-notes' => ReleaseNotesEditor::route('/{record}/release-notes'),
        ];
    }
}
