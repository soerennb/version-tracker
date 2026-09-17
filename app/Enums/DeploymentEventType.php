<?php

namespace App\Enums;

enum DeploymentEventType: string
{
    case CREATED = 'created';
    case UPDATED = 'updated';
    case APPROVED = 'approved';
    case STARTED = 'started';
    case SUCCEEDED = 'succeeded';
    case FAILED = 'failed';
    case CANCELED = 'canceled';
    case ROLLED_BACK = 'rolled_back';
    case CORRECTED = 'corrected';

    public function label(): string
    {
        return __('deployments.events.'.$this->value);
    }
}
