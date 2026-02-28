<?php

$trustedHosts = array_values(array_filter(array_map(
    static fn (string $trustedHostPattern): string => trim($trustedHostPattern),
    explode(',', (string) env('TRUSTED_HOSTS', ''))
)));

$trustedProxiesValue = trim((string) env('TRUSTED_PROXIES', ''));
$trustedProxies = null;

if ($trustedProxiesValue === '*') {
    $trustedProxies = '*';
} elseif ($trustedProxiesValue !== '') {
    $trustedProxies = array_values(array_filter(array_map(
        static fn (string $proxy): string => trim($proxy),
        explode(',', $trustedProxiesValue)
    )));
}

return [
    'trusted_hosts' => $trustedHosts,
    'trusted_proxies' => $trustedProxies,
    'api_rate_limit_per_minute' => (int) env('API_RATE_LIMIT_PER_MINUTE', 60),
    'force_hsts' => (bool) env('SECURITY_FORCE_HSTS', false),
    'upload_max_kb' => (int) env('UPLOAD_MAX_KB', 10240),
    'upload_allowed_extensions' => array_values(array_filter(array_map(
        static fn (string $extension): string => strtolower(trim($extension)),
        explode(',', (string) env(
            'UPLOAD_ALLOWED_EXTENSIONS',
            'pdf,txt,csv,json,xml,md,doc,docx,xls,xlsx,png,jpg,jpeg,zip'
        ))
    ))),
];
