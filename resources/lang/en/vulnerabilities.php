<?php

return [
    'severity' => [
        'critical' => 'Critical',
        'high' => 'High',
        'medium' => 'Medium',
        'low' => 'Low',
        'unknown' => 'Unknown',
    ],
    'status' => [
        'open' => 'Open',
        'fixed' => 'Fixed',
        'accepted' => 'Accepted risk',
        'false_positive' => 'False positive',
    ],
    'exploitability' => [
        'unknown' => 'Unknown',
        'no_known_exploit' => 'No known exploit',
        'proof_of_concept' => 'Proof of concept',
        'active' => 'Active exploitation',
    ],
    'fields' => [
        'cve' => 'CVE',
        'software' => 'Software',
        'severity' => 'Severity',
        'cvss' => 'CVSS',
        'fix' => 'Fix',
        'exploitability' => 'Exploitability',
    ],
    'dashboard' => [
        'open_critical_high' => 'Open critical/high',
        'fix_available' => 'Fix available',
        'eol_risk' => 'EOL in 90 days',
        'affected_software' => 'Affected software',
        'open_by_severity' => 'Open by severity',
        'priority_findings' => 'Priority findings',
        'no_fix' => 'No fix',
        'empty' => 'No open critical or high findings.',
    ],
];
