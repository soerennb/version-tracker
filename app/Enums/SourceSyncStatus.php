<?php

namespace App\Enums;

enum SourceSyncStatus: string
{
    case QUEUED = 'queued';
    case RUNNING = 'running';
    case SUCCEEDED = 'succeeded';
    case FAILED = 'failed';

    public function label(): string
    {
        return __('filament.source_sync.status.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::QUEUED => 'gray',
            self::RUNNING => 'warning',
            self::SUCCEEDED => 'success',
            self::FAILED => 'danger',
        };
    }
}
