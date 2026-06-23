<?php

return [
    'status' => [
        'draft' => 'Entwurf',
        'published' => 'Veröffentlicht',
    ],
    'support' => [
        'supported' => 'Unterstützt',
        'maintenance' => 'Wartung',
        'deprecated' => 'Veraltet',
        'eol' => 'Ende des Lebenszyklus',
    ],
    'readiness' => [
        'label' => 'Readiness',
        'missing_required_content' => 'Deutsche oder englische Release Notes fehlen',
        'blocking_vulnerabilities' => 'Hohe oder kritische Sicherheitslücken sind verknüpft',
        'invalid_dependencies' => 'Dependency-Constraints sind nicht erfüllt',
        'missing_attachments' => 'Kein Release-Anhang hochgeladen',
        'missing_lifecycle' => 'Kein Support-, LTS- oder EOL-Lifecycle gesetzt',
    ],
    'review' => [
        'reject_reason' => 'Ablehnungsgrund',
        'actions' => [
            'comment' => 'Kommentar',
            'approved' => 'Freigegeben',
            'rejected' => 'Abgelehnt',
        ],
        'reject_reasons' => [
            'missing_content' => 'Fehlende oder unvollständige Release Notes',
            'security_risk' => 'Offenes Sicherheitsrisiko',
            'dependency_risk' => 'Dependency- oder Kompatibilitätsrisiko',
            'missing_artifacts' => 'Fehlende Release-Artefakte',
            'lifecycle_incomplete' => 'Lifecycle-Daten unvollständig',
            'other' => 'Sonstiges',
        ],
    ],
    'governance' => [
        'four_eyes_required' => 'Kritische Releases müssen von einer anderen Person freigegeben werden.',
    ],
];
