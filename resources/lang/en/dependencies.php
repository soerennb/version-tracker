<?php

return [
    'fields' => [
        'applies_to_version' => 'Applies to release',
        'min_version' => 'Min version',
        'max_version' => 'Max version',
        'type' => 'Type',
    ],
    'labels' => [
        'all_releases' => 'All releases',
    ],
    'help' => [
        'applies_to_version' => 'Leave empty when the dependency applies to every release.',
    ],
    'health' => [
        'label' => 'Health',
        'healthy' => 'Healthy',
        'healthy_detail' => 'Dependency constraints and lifecycle look current.',
        'broken' => 'Broken',
        'broken_detail' => 'Dependency constraints are invalid.',
        'unsafe' => 'Unsafe',
        'unsafe_detail' => 'Dependency has open high or critical vulnerabilities.',
        'eol_risk' => 'EOL risk',
        'eol_risk_detail' => 'Dependency reaches end of life within 90 days.',
        'outdated' => 'Outdated',
        'outdated_detail' => 'A newer published dependency version exists outside the configured maximum.',
    ],
];
