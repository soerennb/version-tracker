<?php

namespace App\Filament\Resources\TextContents;

use App\Filament\Resources\TextContents\Pages\CreateTextContent;
use App\Filament\Resources\TextContents\Pages\EditTextContent;
use App\Filament\Resources\TextContents\Pages\ListTextContents;
use App\Filament\Resources\TextContents\Schemas\TextContentForm;
use App\Filament\Resources\TextContents\Tables\TextContentsTable;
use App\Models\TextContent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TextContentResource extends Resource
{
    protected static ?string $model = TextContent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?int $navigationSort = 21;

    public static function getNavigationGroup(): ?string
    {
        return __('filament.navigation.content_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.navigation.text_content');
    }

    public static function form(Schema $schema): Schema
    {
        return TextContentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TextContentsTable::configure($table);
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
            'index' => ListTextContents::route('/'),
            'create' => CreateTextContent::route('/create'),
            'edit' => EditTextContent::route('/{record}/edit'),
        ];
    }
}
