<?php

declare(strict_types=1);


$secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Lax',
]);

session_start();

require_once __DIR__ . '/../helpers/router.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/validation.php';
require_once __DIR__ . '/../controllers/AuthController.php';

use KadrPortal\Controllers\AuthController;
use KadrPortal\Helpers\Router;

$router = new Router();
$authController = new AuthController();

require_once __DIR__ . '/../helpers/router.php';

use KadrPortal\Helpers\Router;

$router = new Router();

$router->get('/', static function (): void {
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Welcome to Kadr Portal';
});


$router->get('/login', [$authController, 'showLogin']);
$router->post('/login', [$authController, 'login']);
$router->get('/register', [$authController, 'showRegister']);
$router->post('/register', [$authController, 'register']);
$router->post('/logout', [$authController, 'logout']);


$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
