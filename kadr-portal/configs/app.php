<?php

declare(strict_types=1);

return [
    'name' => 'Kadr Portal',
    'env' => getenv('APP_ENV') ?: 'local',
    'debug' => (bool) getenv('APP_DEBUG'),
    'base_url' => getenv('APP_URL') ?: 'http://localhost:8081',
];

