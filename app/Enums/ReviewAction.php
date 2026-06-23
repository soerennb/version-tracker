<?php

namespace App\Enums;

enum ReviewAction: string
{
    case COMMENT = 'comment';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::COMMENT => __('versions.review.actions.comment'),
            self::APPROVED => __('versions.review.actions.approved'),
            self::REJECTED => __('versions.review.actions.rejected'),
        };
    }
}
