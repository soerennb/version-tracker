<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GovernanceSettings extends Settings
{
    /**
     * @var array<int, string>
     */
    public array $required_content_languages = ['de', 'en'];

    public bool $require_security_clearance = true;

    public bool $require_attachments = true;

    public bool $require_lifecycle = true;

    public bool $require_dependency_validation = true;

    /**
     * @var array<int, string>
     */
    public array $blocking_vulnerability_severities = ['critical', 'high'];

    public bool $require_four_eyes_for_critical_releases = false;

    public bool $allow_readiness_override = true;

    /** Require a parsed and recent SBOM before a release can be approved. */
    public bool $require_sbom = false;

    /** Maximum age in days for an SBOM when SBOM approval is required. */
    public int $sbom_max_age_days = 30;

    /** Treat findings marked as actively exploitable as release blockers. */
    public bool $block_active_exploits = true;

    public static function group(): string
    {
        return 'governance';
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'required_content_languages' => ['de', 'en'],
            'require_security_clearance' => true,
            'require_attachments' => true,
            'require_lifecycle' => true,
            'require_dependency_validation' => true,
            'blocking_vulnerability_severities' => ['critical', 'high'],
            'require_four_eyes_for_critical_releases' => (bool) config('release_governance.require_four_eyes_for_critical_releases', false),
            'allow_readiness_override' => true,
            'require_sbom' => false,
            'sbom_max_age_days' => 30,
            'block_active_exploits' => true,
        ];
    }
}
