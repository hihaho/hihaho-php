<?php declare(strict_types=1);

return [
    // A personal access token, created in hihaho under Profile, API tokens.
    'token' => env('HIHAHO_API_TOKEN', ''),

    'base_url' => env('HIHAHO_API_URL', 'https://api.hihaho.com'),

    'timeout' => (int) env('HIHAHO_API_TIMEOUT', 30),

    'retries' => [
        // Retries on a 429 (every method) and on a 5xx or a timeout (GET only).
        'max' => (int) env('HIHAHO_API_MAX_RETRIES', 3),
        'rate_limited' => true,
        'server_errors_on_get' => true,
    ],
];
