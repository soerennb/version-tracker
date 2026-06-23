<?php

namespace App\Enums;

enum RejectReason: string
{
    case MISSING_CONTENT = 'missing_content';
    case SECURITY_RISK = 'security_risk';
    case DEPENDENCY_RISK = 'dependency_risk';
    case MISSING_ARTIFACTS = 'missing_artifacts';
    case LIFECYCLE_INCOMPLETE = 'lifecycle_incomplete';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::MISSING_CONTENT => __('versions.review.reject_reasons.missing_content'),
            self::SECURITY_RISK => __('versions.review.reject_reasons.security_risk'),
            self::DEPENDENCY_RISK => __('versions.review.reject_reasons.dependency_risk'),
            self::MISSING_ARTIFACTS => __('versions.review.reject_reasons.missing_artifacts'),
            self::LIFECYCLE_INCOMPLETE => __('versions.review.reject_reasons.lifecycle_incomplete'),
            self::OTHER => __('versions.review.reject_reasons.other'),
        };
    }
}
