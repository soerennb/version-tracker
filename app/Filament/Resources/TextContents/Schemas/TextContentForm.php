<?php

namespace App\Filament\Resources\TextContents\Schemas;

use App\Enums\Language;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TextContentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('version_id')
                    ->relationship('version', 'id')
                    ->required(),
                TextInput::make('title')
                    ->required(),
                Textarea::make('content')
                    ->required()
                    ->columnSpanFull(),
                Select::make('language')
                    ->options(Language::class)
                    ->default('de')
                    ->required(),
            ]);
    }
}
