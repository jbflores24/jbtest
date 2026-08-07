<?php

declare(strict_types=1);

return [
    'name' => $_ENV['APP_NAME'] ?? 'JB API',
    'env' => $_ENV['APP_ENV'] ?? 'production',
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'url' => $_ENV['APP_URL'] ?? 'http://localhost:8000',
    'base_route' => $_ENV['APP_BASE_ROUTE'] ?? '/api',
    'route_cache_enabled' => filter_var($_ENV['ROUTE_CACHE_ENABLED'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'route_cache_path' => $_ENV['ROUTE_CACHE_PATH'] ?? 'storage/cache/routes.json',
    'cors_allowed_origins' => $_ENV['CORS_ALLOWED_ORIGINS'] ?? '*',
];