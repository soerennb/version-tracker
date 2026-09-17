<?php

namespace App\Enums;

enum DeploymentStatus: string
{
    case PLANNED = 'planned';
    case APPROVED = 'approved';
    case IN_PROGRESS = 'in_progress';
    case SUCCEEDED = 'succeeded';
    case FAILED = 'failed';
    case CANCELED = 'canceled';
    case ROLLED_BACK = 'rolled_back';

    public function label(): string
    {
        return __('deployments.status.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::PLANNED => 'gray',
            self::APPROVED => 'info',
            self::IN_PROGRESS => 'warning',
            self::SUCCEEDED => 'success',
            self::FAILED, self::ROLLED_BACK => 'danger',
            self::CANCELED => 'gray',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::SUCCEEDED, self::FAILED, self::CANCELED, self::ROLLED_BACK], true);
    }

    public function isActive(): bool
    {
        return ! $this->isTerminal();
    }
}
