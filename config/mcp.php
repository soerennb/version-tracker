<?php

use App\Models\FileAttachment;
use App\Models\Software;
use App\Models\SoftwareDependency;
use App\Models\TextContent;
use App\Models\Version;
use App\Models\Vulnerability;

return [
    'entities' => [
        'software' => ['model' => Software::class, 'parameter' => 'software', 'ability' => 'software', 'search' => 'name', 'relations' => ['creator'], 'fields' => ['name' => 'string', 'description' => 'string', 'status' => 'string', 'license_type' => 'string', 'compliance_status' => 'string', 'github_repo_url' => 'string']],
        'versions' => ['model' => Version::class, 'parameter' => 'version', 'ability' => 'versions', 'search' => 'version_number', 'relations' => ['software'], 'fields' => ['software_id' => 'integer', 'version_number' => 'string', 'release_date' => 'string', 'eol_date' => 'string', 'lts_date' => 'string', 'support_status' => 'string']],
        'text_contents' => ['model' => TextContent::class, 'parameter' => 'text_content', 'ability' => 'content', 'search' => 'title', 'relations' => ['version'], 'fields' => ['title' => 'string', 'content' => 'string', 'language' => 'string']],
        'software_dependencies' => ['model' => SoftwareDependency::class, 'parameter' => 'software_dependency', 'ability' => 'dependencies', 'search' => 'dependency_type', 'relations' => ['software', 'dependsOnSoftware', 'appliesToVersion', 'minVersion', 'maxVersion'], 'fields' => ['software_id' => 'integer', 'depends_on_software_id' => 'integer', 'applies_to_version_id' => 'integer', 'min_version_id' => 'integer', 'max_version_id' => 'integer', 'dependency_type' => 'string']],
        'vulnerabilities' => ['model' => Vulnerability::class, 'parameter' => 'vulnerability', 'ability' => 'vulnerabilities', 'search' => 'cve_id', 'relations' => ['affectedVersion', 'fixedVersion'], 'fields' => ['cve_id' => 'string', 'external_id' => 'string', 'affected_version_id' => 'integer', 'severity' => 'string', 'cvss_score' => 'number', 'description' => 'string', 'source' => 'string', 'source_url' => 'string', 'affected_range' => 'string', 'fixed_version_id' => 'integer', 'status' => 'string', 'exploitability' => 'string', 'published_date' => 'string', 'source_updated_at' => 'string']],
        'attachments' => ['model' => FileAttachment::class, 'parameter' => 'file_attachment', 'ability' => 'files', 'search' => 'filename', 'relations' => ['version.software'], 'fields' => ['artifact_type' => 'string', 'platform' => 'string', 'architecture' => 'string', 'checksum' => 'string', 'checksum_algorithm' => 'string', 'signature' => 'string', 'verification_status' => 'string', 'is_public' => 'boolean']],
    ],
];
