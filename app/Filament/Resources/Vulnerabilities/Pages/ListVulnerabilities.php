<?php

namespace App\Filament\Resources\Vulnerabilities\Pages;

use App\Filament\Resources\Vulnerabilities\VulnerabilityResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVulnerabilities extends ListRecords
{
    protected static string $resource = VulnerabilityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
