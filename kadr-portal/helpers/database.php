<?php

declare(strict_types=1);

namespace KadrPortal\Helpers;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Obtain a shared PDO connection using application configuration.
 */
function db(): PDO
{
    static $connection = null;

    if ($connection instanceof PDO) {
        return $connection;
    }

    $config = require __DIR__ . '/../configs/database.php';

    $dsn = sprintf(
        'pgsql:host=%s;port=%d;dbname=%s',
        $config['host'],
        $config['port'],
        $config['dbname']
    );

    try {
        $connection = new PDO(
            $dsn,
            $config['user'],
            $config['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    } catch (PDOException $exception) {
        throw new RuntimeException('Не удалось подключиться к базе данных.', 0, $exception);
    }

    return $connection;
}
