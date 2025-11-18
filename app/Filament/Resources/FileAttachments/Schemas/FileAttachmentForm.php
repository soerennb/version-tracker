<?php

namespace App\Filament\Resources\FileAttachments\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class FileAttachmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('version_id')
                    ->relationship('version', 'id')
                    ->required(),
                TextInput::make('filename')
                    ->required(),
                TextInput::make('file_path')
                    ->required(),
                TextInput::make('mime_type')
                    ->required(),
                TextInput::make('size')
                    ->required()
                    ->numeric(),
            ]);
    }
}
