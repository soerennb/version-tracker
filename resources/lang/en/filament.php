<?php

return [
    'fields' => [
        'created_by' => 'Created by',
        'created_at' => 'Created at',
        'updated_at' => 'Updated at',
    ],
    'actions' => [
        'approve' => 'Approve',
        'reject' => 'Reject',
        'reason' => 'Reason',
        'duplicate' => 'Duplicate',
    ],
    'messages' => [
        'version_approved' => 'Version approved',
        'version_rejected' => 'Version rejected',
        'approval_empty' => 'No versions awaiting approval',
        'approval_pending' => 'Pending approvals',
        'duplicate_missing' => 'The selected version could not be found for duplication.',
    ],
    'navigation' => [
        'version_tracking' => 'Version Tracking',
        'content_group' => 'Content & Assets',
        'software' => 'Software',
        'versions' => 'Versions',
        'content' => 'Content',
        'text_content' => 'Text Content',
        'files' => 'File Attachments',
        'dependencies' => 'Dependencies',
        'security' => 'Security',
        'audit' => 'Audit & Exports',
        'approval' => 'Version Approval',
        'analytics' => 'Analytics',
    ],
    'software' => [
        'fields' => [
            'name' => 'Name',
            'description' => 'Description',
            'status' => 'Status',
            'current_version' => 'Current Version',
            'last_release_date' => 'Last Release Date',
            'license_type' => 'License Type',
            'compliance_status' => 'Compliance Status',
            'github_repo_url' => 'GitHub Repository URL',
        ],
        'compliance' => [
            'compliant' => 'Compliant',
            'non_compliant' => 'Non-compliant',
            'unknown' => 'Unknown',
        ],
    ],
    'versions' => [
        'fields' => [
            'software' => 'Software',
            'version_number' => 'Version Number',
            'release_date' => 'Release Date',
            'status' => 'Status',
            'approval_status' => 'Approval Status',
            'eol_date' => 'End of Life',
            'lts_date' => 'LTS Date',
            'support_status' => 'Support Status',
        ],
    ],
];
