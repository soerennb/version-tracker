<?php

return [
    'severity' => [
        'critical' => 'Kritisch',
        'high' => 'Hoch',
        'medium' => 'Mittel',
        'low' => 'Niedrig',
        'unknown' => 'Unbekannt',
    ],
    'status' => [
        'open' => 'Offen',
        'fixed' => 'Behoben',
        'accepted' => 'Akzeptiertes Risiko',
        'false_positive' => 'False Positive',
    ],
    'exploitability' => [
        'unknown' => 'Unbekannt',
        'no_known_exploit' => 'Kein bekannter Exploit',
        'proof_of_concept' => 'Proof of Concept',
        'active' => 'Aktive Ausnutzung',
    ],
    'fields' => [
        'cve' => 'CVE',
        'software' => 'Software',
        'severity' => 'Schweregrad',
        'cvss' => 'CVSS',
        'fix' => 'Fix',
        'exploitability' => 'Ausnutzbarkeit',
    ],
    'dashboard' => [
        'open_critical_high' => 'Offen kritisch/hoch',
        'fix_available' => 'Fix verfügbar',
        'eol_risk' => 'EOL in 90 Tagen',
        'affected_software' => 'Betroffene Software',
        'open_by_severity' => 'Offen nach Schweregrad',
        'priority_findings' => 'Priorisierte Findings',
        'no_fix' => 'Kein Fix',
        'empty' => 'Keine offenen kritischen oder hohen Findings.',
    ],
];
