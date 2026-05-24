<?php

return [
    'db' => [
        'host' => getenv('KRISHI_DB_HOST') ?: 'localhost',
        'port' => getenv('KRISHI_DB_PORT') ?: '3307',
        'name' => getenv('KRISHI_DB_NAME') ?: 'krishiconnect',
        'user' => getenv('KRISHI_DB_USER') ?: 'root',
        'pass' => getenv('KRISHI_DB_PASS') ?: '',
        'charset' => getenv('KRISHI_DB_CHARSET') ?: 'utf8mb4',
    ],
    'base_url' => getenv('KRISHI_BASE_URL') !== false ? getenv('KRISHI_BASE_URL') : null,
];
