<?php

namespace App\Filament\Resources\SoftwareDependencies\Schemas;

use App\Models\Version;
use App\Rules\AcyclicSoftwareDependency;
use App\Rules\VersionBelongsToSoftware;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class SoftwareDependencyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('software_id')
                    ->relationship('software', 'name')
                    ->live()
                    ->afterStateUpdated(fn (Set $set) => $set('applies_to_version_id', null))
                    ->required(),
                Select::make('depends_on_software_id')
                    ->relationship('dependsOnSoftware', 'name')
                    ->live()
                    ->afterStateUpdated(function (Set $set): void {
                        $set('min_version_id', null);
                        $set('max_version_id', null);
                    })
                    ->rules(fn (Get $get): array => [new AcyclicSoftwareDependency(self::softwareId($get('software_id')))])
                    ->required(),
                Select::make('applies_to_version_id')
                    ->label(__('dependencies.fields.applies_to_version'))
                    ->options(fn (Get $get): array => self::versionOptions($get('software_id')))
                    ->searchable()
                    ->helperText(__('dependencies.help.applies_to_version'))
                    ->rules(fn (Get $get): array => [new VersionBelongsToSoftware(self::softwareId($get('software_id')))]),
                Select::make('min_version_id')
                    ->options(fn (Get $get): array => self::versionOptions($get('depends_on_software_id')))
                    ->searchable()
                    ->rules(fn (Get $get): array => [new VersionBelongsToSoftware(self::softwareId($get('depends_on_software_id')))]),
                Select::make('max_version_id')
                    ->options(fn (Get $get): array => self::versionOptions($get('depends_on_software_id')))
                    ->searchable()
                    ->rules(fn (Get $get): array => [new VersionBelongsToSoftware(self::softwareId($get('depends_on_software_id')))]),
                TextInput::make('dependency_type')
                    ->required()
                    ->default('runtime'),
            ]);
    }

    /**
     * @return array<int, string>
     */
    protected static function versionOptions(mixed $softwareId): array
    {
        $softwareId = self::softwareId($softwareId);

        if ($softwareId === null) {
            return [];
        }

        return Version::query()
            ->where('software_id', $softwareId)
            ->orderByDesc('release_date')
            ->pluck('version_number', 'id')
            ->all();
    }

    protected static function softwareId(mixed $softwareId): ?int
    {
        return filled($softwareId) ? (int) $softwareId : null;
    }
}
