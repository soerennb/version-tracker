<?php

namespace App\Filament\Pages;

use App\Enums\RegistrationMode;
use App\Settings\AccessSettings;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageAccessSettings extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?int $navigationSort = 71;

    protected static string $settings = AccessSettings::class;

    public static function getNavigationGroup(): ?string
    {
        return __('filament.navigation.settings_basis');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.navigation.settings_access');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('filament.settings.access.registration'))
                    ->description(__('filament.settings.access.registration_description'))
                    ->columns(2)
                    ->components([
                        Select::make('registration_mode')
                            ->label(__('filament.settings.fields.registration_mode'))
                            ->options([
                                RegistrationMode::Open->value => __('filament.settings.registration_modes.open'),
                                RegistrationMode::InvitationOnly->value => __('filament.settings.registration_modes.invitation_only'),
                                RegistrationMode::Disabled->value => __('filament.settings.registration_modes.disabled'),
                            ])
                            ->required(),
                        Toggle::make('email_verification_required')
                            ->label(__('filament.settings.fields.email_verification_required')),
                        TextInput::make('invitation_expiry_days')
                            ->label(__('filament.settings.fields.invitation_expiry_days'))
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(90),
                    ]),
                Section::make(__('filament.settings.access.passwords'))
                    ->columns(2)
                    ->components([
                        TextInput::make('password_min_length')
                            ->label(__('filament.settings.fields.password_min_length'))
                            ->numeric()
                            ->required()
                            ->minValue(8)
                            ->maxValue(128),
                        Toggle::make('password_require_mixed_case')
                            ->label(__('filament.settings.fields.password_require_mixed_case')),
                        Toggle::make('password_require_numbers')
                            ->label(__('filament.settings.fields.password_require_numbers')),
                        Toggle::make('password_require_symbols')
                            ->label(__('filament.settings.fields.password_require_symbols')),
                        TextInput::make('password_reset_expire_minutes')
                            ->label(__('filament.settings.fields.password_reset_expire_minutes'))
                            ->numeric()
                            ->required()
                            ->minValue(5)
                            ->maxValue(1440),
                        TextInput::make('password_reset_throttle_seconds')
                            ->label(__('filament.settings.fields.password_reset_throttle_seconds'))
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(3600),
                        TextInput::make('password_confirmation_timeout_seconds')
                            ->label(__('filament.settings.fields.password_confirmation_timeout_seconds'))
                            ->numeric()
                            ->required()
                            ->minValue(60)
                            ->maxValue(86400),
                        TextInput::make('email_verification_expire_minutes')
                            ->label(__('filament.settings.fields.email_verification_expire_minutes'))
                            ->numeric()
                            ->required()
                            ->minValue(5)
                            ->maxValue(10080),
                    ]),
                Section::make(__('filament.settings.access.rate_limits'))
                    ->columns(2)
                    ->components([
                        TextInput::make('auth_rate_limit_per_minute')
                            ->label(__('filament.settings.fields.auth_rate_limit_per_minute'))
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(1000),
                        TextInput::make('verification_rate_limit_per_minute')
                            ->label(__('filament.settings.fields.verification_rate_limit_per_minute'))
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(1000),
                    ]),
                Section::make(__('filament.settings.access.sessions'))
                    ->columns(2)
                    ->components([
                        TextInput::make('session_lifetime_minutes')
                            ->label(__('filament.settings.fields.session_lifetime_minutes'))
                            ->numeric()
                            ->required()
                            ->minValue(5)
                            ->maxValue(43200),
                        Toggle::make('session_expire_on_close')
                            ->label(__('filament.settings.fields.session_expire_on_close')),
                    ]),
            ]);
    }
}
