<?php

return [
    'fields' => [
        'applies_to_version' => 'Gilt für Release',
        'min_version' => 'Min-Version',
        'max_version' => 'Max-Version',
        'type' => 'Typ',
    ],
    'labels' => [
        'all_releases' => 'Alle Releases',
    ],
    'help' => [
        'applies_to_version' => 'Leer lassen, wenn die Abhängigkeit für alle Releases gilt.',
    ],
    'health' => [
        'label' => 'Health',
        'healthy' => 'Gesund',
        'healthy_detail' => 'Dependency-Constraints und Lifecycle sind aktuell.',
        'broken' => 'Defekt',
        'broken_detail' => 'Dependency-Constraints sind ungültig.',
        'unsafe' => 'Unsicher',
        'unsafe_detail' => 'Dependency hat offene hohe oder kritische Sicherheitslücken.',
        'eol_risk' => 'EOL-Risiko',
        'eol_risk_detail' => 'Dependency erreicht innerhalb von 90 Tagen EOL.',
        'outdated' => 'Veraltet',
        'outdated_detail' => 'Eine neuere veröffentlichte Dependency-Version liegt außerhalb des konfigurierten Maximums.',
    ],
];
