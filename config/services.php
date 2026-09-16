<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'github' => [
        'token' => env('GITHUB_TOKEN'),
        'sync_enabled' => (bool) env('GITHUB_SYNC_ENABLED', false),
        'timeout' => (int) env('GITHUB_HTTP_TIMEOUT', 10),
        'max_pages' => (int) env('GITHUB_SYNC_MAX_PAGES', 10),
    ],

    'osv' => [
        'url' => env('OSV_API_URL', 'https://api.osv.dev/v1/querybatch'),
        'timeout' => (int) env('OSV_HTTP_TIMEOUT', 10),
        'retry_times' => (int) env('OSV_HTTP_RETRY_TIMES', 2),
    ],

    'risk_intelligence' => [
        'enabled' => (bool) env('RISK_INTELLIGENCE_ENABLED', false),
        'epss_url' => env('EPSS_API_URL', 'https://api.first.org/data/v1/epss'),
        'kev_url' => env('CISA_KEV_URL', 'https://www.cisa.gov/sites/default/files/feeds/known_exploited_vulnerabilities.json'),
        'timeout' => (int) env('RISK_INTELLIGENCE_HTTP_TIMEOUT', 15),
        'retry_times' => (int) env('RISK_INTELLIGENCE_HTTP_RETRY_TIMES', 2),
    ],

];
