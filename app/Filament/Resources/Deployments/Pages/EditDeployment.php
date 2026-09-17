<?php

namespace App\Filament\Resources\Deployments\Pages;

use App\Filament\Resources\Deployments\DeploymentResource;
use App\Models\Deployment;
use App\Models\User;
use App\Services\DeploymentService;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditDeployment extends EditRecord
{
    protected static string $resource = DeploymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $actor = auth()->user();

        return app(DeploymentService::class)->update(
            $record instanceof Deployment ? $record : Deployment::query()->findOrFail($record->getKey()),
            $data,
            $actor instanceof User ? $actor : null,
        );
    }
}
