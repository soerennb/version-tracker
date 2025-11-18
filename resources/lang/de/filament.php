<?php

return [
    'fields' => [
        'created_by' => 'Erstellt von',
        'created_at' => 'Erstellt am',
        'updated_at' => 'Aktualisiert am',
    ],
    'actions' => [
        'approve' => 'Freigeben',
        'reject' => 'Ablehnen',
        'reason' => 'Begründung',
        'duplicate' => 'Duplizieren',
    ],
    'messages' => [
        'version_approved' => 'Version wurde freigegeben',
        'version_rejected' => 'Version wurde abgelehnt',
        'approval_empty' => 'Keine Versionen warten auf Freigabe',
        'approval_pending' => 'Offene Freigaben',
        'duplicate_missing' => 'Die ausgewählte Version wurde nicht gefunden.',
    ],
    'navigation' => [
        'version_tracking' => 'Versionverwaltung',
        'content_group' => 'Inhalte & Assets',
        'software' => 'Software',
        'versions' => 'Versionen',
        'content' => 'Inhalte',
        'text_content' => 'Texte',
        'files' => 'Dateianhänge',
        'dependencies' => 'Abhängigkeiten',
        'security' => 'Sicherheit',
        'audit' => 'Audit & Exporte',
        'approval' => 'Versionsfreigabe',
        'analytics' => 'Auswertungen',
    ],
    'software' => [
        'fields' => [
            'name' => 'Name',
            'description' => 'Beschreibung',
            'status' => 'Status',
            'current_version' => 'Aktuelle Version',
            'last_release_date' => 'Letzte Veröffentlichung',
            'license_type' => 'Lizenztyp',
            'compliance_status' => 'Compliance-Status',
            'github_repo_url' => 'GitHub-Repository-URL',
        ],
        'compliance' => [
            'compliant' => 'Konform',
            'non_compliant' => 'Nicht konform',
            'unknown' => 'Unbekannt',
        ],
    ],
    'versions' => [
        'fields' => [
            'software' => 'Software',
            'version_number' => 'Versionsnummer',
            'release_date' => 'Veröffentlichungsdatum',
            'status' => 'Status',
            'approval_status' => 'Freigabestatus',
            'eol_date' => 'Ende des Supports',
            'lts_date' => 'LTS-Datum',
            'support_status' => 'Supportstatus',
        ],
    ],
];
