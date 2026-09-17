<?php

namespace App\Filament\Resources\Deployments\Pages;

use App\Filament\Resources\Deployments\DeploymentResource;
use App\Models\User;
use App\Services\DeploymentService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateDeployment extends CreateRecord
{
    protected static string $resource = DeploymentResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $actor = auth()->user();

        $result = app(DeploymentService::class)->create(
            $data,
            $actor instanceof User ? $actor : null,
        );

        return $result['deployment'];
    }
}
