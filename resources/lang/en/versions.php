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
    ],
];
