<?php

declare(strict_types=1);

require_once __DIR__ . '/../helpers/router.php';

use KadrPortal\Helpers\Router;

$router = new Router();

$router->get('/', static function (): void {
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Welcome to Kadr Portal';
});

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
