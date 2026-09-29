<?php

return [
    'enabled' => env('MONITORX_ENABLED', true),
    'path' => env('MONITORX_PATH', 'monitorx'),
    'username' => env('MONITORX_USERNAME'),
    'password' => env('MONITORX_PASSWORD'),
    'retention_days' => (int) env('MONITORX_RETENTION_DAYS', 30),
    'slow_request_ms' => (int) env('MONITORX_SLOW_REQUEST_MS', 1000),
    'slow_query_ms' => (int) env('MONITORX_SLOW_QUERY_MS', 100),
    'dumps_enabled' => env('MONITORX_DUMPS_ENABLED', env('APP_ENV') !== 'production'),
    'redact' => ['password', 'token', 'secret', 'authorization', 'cookie', 'api_key'],
    'modules' => [
        'requests' => 'Requests', 'commands' => 'Commands', 'schedule' => 'Schedule', 'jobs' => 'Jobs',
        'batches' => 'Batches', 'cache' => 'Cache', 'dumps' => 'Dumps', 'events' => 'Events',
        'exceptions' => 'Exceptions', 'gates' => 'Gates', 'http-client' => 'HTTP Client', 'logs' => 'Logs',
        'mail' => 'Mail', 'models' => 'Models', 'notifications' => 'Notifications', 'queries' => 'Queries',
        'redis' => 'Redis', 'views' => 'Views',
    ],
];
