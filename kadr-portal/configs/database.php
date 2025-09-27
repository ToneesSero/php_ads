<?php

declare(strict_types=1);

return [
    'host' => getenv('DB_HOST') ?: 'db',
    'port' => (int) (getenv('DB_PORT') ?: 5432),
    'dbname' => getenv('DB_NAME') ?: 'app',
    'user' => getenv('DB_USER') ?: 'app',
    'password' => getenv('DB_PASSWORD') ?: 'app_password',
];
