<?php

return [
    'setup_token' => env('INSTALLER_SETUP_TOKEN'),

    'web_setup_enabled' => (bool) env('INSTALLER_WEB_SETUP_ENABLED', true),

    'demo_reference_date' => env('DEMO_REFERENCE_DATE', '2026-01-01'),
];
