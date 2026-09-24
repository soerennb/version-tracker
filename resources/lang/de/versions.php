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
        'missing_sbom' => 'Keine verarbeitete SBOM für dieses Release hochgeladen',
        'missing_release_composition' => 'Baseline, eForms-Komponente und genutztes SDK fehlen oder sind nicht vollständig zugeordnet',
        'stale_sbom' => 'Die letzte SBOM ist älter als das konfigurierte Aktualitätsfenster',
        'blocking_component_findings' => 'Blockierende Schwachstellen in SBOM-Komponenten gefunden',
    ],
    'review' => [
        'reject_reason' => 'Ablehnungsgrund',
        'actions' => [
            'comment' => 'Kommentar',
            'approved' => 'Freigegeben',
            'published' => 'Veröffentlicht',
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
        'approve_requires_pending' => 'Nur ausstehende Entwürfe können freigegeben werden.',
        'reject_requires_pending' => 'Nur ausstehende Entwürfe können abgelehnt werden.',
        'not_ready' => 'Dieses Release ist noch nicht freigabebereit.',
        'override_not_allowed' => 'Für diesen Readiness-Override fehlt die Berechtigung.',
        'override_reason_required' => 'Für einen Readiness-Override ist eine Begründung erforderlich.',
        'publish_requires_approval' => 'Ein Release muss vor der Veröffentlichung freigegeben werden.',
        'approval_invalidated' => 'Die Änderung setzt die Freigabe zurück und erfordert eine erneute Prüfung.',
    ],
];
