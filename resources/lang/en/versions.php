<?php

return [
    'status' => [
        'draft' => 'Draft',
        'published' => 'Published',
    ],
    'support' => [
        'supported' => 'Supported',
        'maintenance' => 'Maintenance',
        'deprecated' => 'Deprecated',
        'eol' => 'End of life',
    ],
    'readiness' => [
        'label' => 'Readiness',
        'missing_required_content' => 'Missing German or English release notes',
        'blocking_vulnerabilities' => 'High or critical vulnerabilities attached',
        'invalid_dependencies' => 'Dependency constraints are not satisfied',
        'missing_attachments' => 'No release attachment uploaded',
        'missing_lifecycle' => 'No support, LTS, or EOL lifecycle data set',
    ],
    'review' => [
        'reject_reason' => 'Reject reason',
        'actions' => [
            'comment' => 'Comment',
            'approved' => 'Approved',
            'published' => 'Published',
            'rejected' => 'Rejected',
        ],
        'reject_reasons' => [
            'missing_content' => 'Missing or incomplete release notes',
            'security_risk' => 'Unresolved security risk',
            'dependency_risk' => 'Dependency or compatibility risk',
            'missing_artifacts' => 'Missing release artifacts',
            'lifecycle_incomplete' => 'Lifecycle data incomplete',
            'other' => 'Other',
        ],
    ],
    'governance' => [
        'four_eyes_required' => 'Critical releases require approval by another user.',
        'approve_requires_pending' => 'Only pending drafts can be approved.',
        'reject_requires_pending' => 'Only pending drafts can be rejected.',
        'not_ready' => 'This release is not ready for approval yet.',
        'override_not_allowed' => 'You are not allowed to override release readiness.',
        'override_reason_required' => 'A reason is required when overriding release readiness.',
        'publish_requires_approval' => 'A release must be approved before it can be published.',
        'approval_invalidated' => 'This change resets the approval and requires another review.',
    ],
];
