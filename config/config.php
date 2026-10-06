<?php

declare(strict_types=1);

return [
    'app' => [
        'name' => 'InternMatch',
        'base_url' => '/InternMatchIntegrated/public',
        'timezone' => 'Asia/Yangon',
    ],

    'database' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: '3307',
        'name' => getenv('DB_NAME') ?: 'internmatch_db',
        'username' => getenv('DB_USER') ?: 'root',
        'password' => getenv('DB_PASSWORD') !== false
            ? getenv('DB_PASSWORD')
            : '',
    ],

    'session' => [
        'name' => 'internmatch_session',
    ],
];