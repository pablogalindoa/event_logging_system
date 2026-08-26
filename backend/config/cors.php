<?php

$allowedOrigins = array_values(array_filter(array_map(
    'trim',
    explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:5173'))
)));

return [
    'paths' => ['events'],
    'allowed_methods' => ['GET', 'POST', 'OPTIONS'],
    'allowed_origins' => $allowedOrigins,
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['Accept', 'Content-Type'],
    'exposed_headers' => ['X-Request-ID'],
    'max_age' => 3600,
    'supports_credentials' => false,
];
