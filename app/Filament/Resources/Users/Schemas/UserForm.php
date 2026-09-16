<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\RuntimeSettings;
use App\Services\UserManagementService;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('filament.users.profile'))
                    ->columns(2)
                    ->components([
                        TextInput::make('name')
                            ->label(__('filament.users.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label(__('filament.users.email'))
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(User::class, 'email', ignoreRecord: true),
                        Select::make('role')
                            ->label(__('filament.users.role'))
                            ->options(fn (?User $record): array => self::roleOptions($record))
                            ->disableOptionWhen(fn (string $value): bool => $value === UserRole::ADMIN->value && ! (auth()->user()?->isAdmin() ?? false))
                            ->default(UserRole::VIEWER->value)
                            ->required(),
                        Toggle::make('is_active')
                            ->label(__('filament.users.active'))
                            ->default(true),
                    ]),
                Section::make(__('filament.users.permissions'))
                    ->description(__('filament.users.permissions_description'))
                    ->components([
                        CheckboxList::make('abilities')
                            ->label(__('filament.users.individual_permissions'))
                            ->options(fn (): array => self::abilityOptions())
                            ->columns(2)
                            ->searchable(),
                    ]),
                Section::make(__('filament.users.password'))
                    ->description(__('filament.users.password_description'))
                    ->columns(2)
                    ->components([
                        TextInput::make('password')
                            ->label(__('filament.users.new_password'))
                            ->password()
                            ->revealable()
                            ->autocomplete('new-password')
                            ->rule(app(RuntimeSettings::class)->passwordRule())
                            ->confirmed()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state)),
                        TextInput::make('password_confirmation')
                            ->label(__('filament.users.password_confirmation'))
                            ->password()
                            ->revealable()
                            ->autocomplete('new-password')
                            ->required(fn (Get $get): bool => filled($get('password')))
                            ->dehydrated(false),
                    ]),
            ]);
    }

    /**
     * @return array<string, string>
     */
    private static function roleOptions(?User $record): array
    {
        $options = [
            UserRole::EDITOR->value => __('filament.users.roles.editor'),
            UserRole::VIEWER->value => __('filament.users.roles.viewer'),
        ];

        if (auth()->user()?->isAdmin() || $record?->role === UserRole::ADMIN) {
            $options = [UserRole::ADMIN->value => __('filament.users.roles.admin'), ...$options];
        }

        return $options;
    }

    /**
     * @return array<string, string>
     */
    private static function abilityOptions(): array
    {
        return collect(app(UserManagementService::class)->assignableAbilities(auth()->user()))
            ->mapWithKeys(fn (string $ability): array => [$ability => self::abilityLabel($ability)])
            ->all();
    }

    private static function abilityLabel(string $ability): string
    {
        $label = __('api_tokens.permissions.'.$ability);

        return $label === 'api_tokens.permissions.'.$ability ? Str::headline($ability) : $label;
    }
}
