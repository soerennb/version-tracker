<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label(__('filament.composition.customer'))->required()->maxLength(150),
                TextInput::make('code')->label(__('filament.composition.code'))->alphaDash()->maxLength(50),
            ]);
    }
}
