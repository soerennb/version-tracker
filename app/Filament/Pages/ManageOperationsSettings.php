<?php

namespace App\Filament\Pages;

use App\Settings\OperationsSettings;
use BackedEnum;
use DateTimeZone;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageOperationsSettings extends SettingsPage
{
    protected static string $requiredAbility = 'manage_advanced_settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static ?int $navigationSort = 82;

    protected static string $settings = OperationsSettings::class;

    public static function getNavigationGroup(): ?string
    {
        return __('filament.navigation.settings_advanced');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.navigation.settings_operations');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('filament.settings.operations.scheduler'))
                    ->description(__('filament.settings.operations.scheduler_description'))
                    ->columns(2)
                    ->components([
                        Toggle::make('scheduler_enabled')
                            ->label(__('filament.settings.fields.scheduler_enabled')),
                        Select::make('system_timezone')
                            ->label(__('filament.settings.fields.system_timezone'))
                            ->options(array_combine(DateTimeZone::listIdentifiers(), DateTimeZone::listIdentifiers()))
                            ->searchable()
                            ->required()
                            ->native(false),
                    ]),
                Section::make(__('filament.settings.operations.public_runtime'))
                    ->columns(2)
                    ->components([
                        TextInput::make('public_runtime_cache_ttl_seconds')
                            ->label(__('filament.settings.fields.public_runtime_cache_ttl_seconds'))
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(86400),
                    ]),
            ]);
    }
}
