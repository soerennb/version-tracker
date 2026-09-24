<?php

namespace App\Services;

use App\Enums\DeploymentStatus;
use App\Models\Deployment;
use App\Models\Environment;

class InstalledReleaseService
{
    public function current(Environment $environment, int $softwareId): ?Deployment
    {
        return Deployment::query()
            ->with(['version.composition.baselineVersion.component', 'version.composition.eformsComponentVersion.component', 'version.composition.activeEformsSdkVersion.component', 'version.composition.supportedInterfaces.componentVersion.component', 'customizationVersion.component', 'software', 'environment.customer'])
            ->where('environment_id', $environment->id)
            ->where('software_id', $softwareId)
            ->where('status', DeploymentStatus::SUCCEEDED)
            ->orderByDesc('completed_at')
            ->orderByDesc('id')
            ->first();
    }
}
