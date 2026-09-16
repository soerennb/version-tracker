<?php

namespace App\Filament\Pages;

use App\Enums\Language;
use App\Enums\VulnerabilitySeverity;
use App\Settings\GovernanceSettings;
use BackedEnum;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageGovernanceSettings extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckBadge;

    protected static ?int $navigationSort = 73;

    protected static string $settings = GovernanceSettings::class;

    public static function getNavigationGroup(): ?string
    {
        return __('filament.navigation.settings_basis');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.navigation.settings_governance');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('filament.settings.governance.readiness'))
                    ->description(__('filament.settings.governance.readiness_description'))
                    ->columns(2)
                    ->components([
                        CheckboxList::make('required_content_languages')
                            ->label(__('filament.settings.fields.required_content_languages'))
                            ->options([
                                Language::DE->value => Language::DE->nativeLabel(),
                                Language::EN->value => Language::EN->nativeLabel(),
                            ])
                            ->columns(2)
                            ->required(),
                        CheckboxList::make('blocking_vulnerability_severities')
                            ->label(__('filament.settings.fields.blocking_vulnerability_severities'))
                            ->options(collect(VulnerabilitySeverity::cases())
                                ->mapWithKeys(fn (VulnerabilitySeverity $severity): array => [$severity->value => $severity->label()])
                                ->all())
                            ->columns(2)
                            ->required(),
                        Toggle::make('require_security_clearance')
                            ->label(__('filament.settings.fields.require_security_clearance')),
                        Toggle::make('require_attachments')
                            ->label(__('filament.settings.fields.require_attachments')),
                        Toggle::make('require_lifecycle')
                            ->label(__('filament.settings.fields.require_lifecycle')),
                        Toggle::make('require_dependency_validation')
                            ->label(__('filament.settings.fields.require_dependency_validation')),
                        Toggle::make('require_sbom')
                            ->label(__('filament.settings.fields.require_sbom')),
                        TextInput::make('sbom_max_age_days')
                            ->label(__('filament.settings.fields.sbom_max_age_days'))
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(3650),
                        Toggle::make('block_active_exploits')
                            ->label(__('filament.settings.fields.block_active_exploits')),
                    ]),
                Section::make(__('filament.settings.governance.approval'))
                    ->description(__('filament.settings.governance.approval_description'))
                    ->columns(2)
                    ->components([
                        Toggle::make('require_four_eyes_for_critical_releases')
                            ->label(__('filament.settings.fields.require_four_eyes_for_critical_releases')),
                        Toggle::make('allow_readiness_override')
                            ->label(__('filament.settings.fields.allow_readiness_override')),
                    ]),
            ]);
    }
}
