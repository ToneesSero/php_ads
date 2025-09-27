<?php

declare(strict_types=1);

// Настройка сессии
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


// Подключение всех необходимых файлов
require_once __DIR__ . '/../helpers/router.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/validation.php';
require_once __DIR__ . '/../helpers/database.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/ListingController.php';

use KadrPortal\Controllers\AuthController;
use KadrPortal\Controllers\ListingController;
use KadrPortal\Helpers\Router;

$router = new Router();
$authController = new AuthController();
$listingController = new ListingController();

$router->get('/', static function (): void {
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Welcome to Kadr Portal';
});


// Маршруты авторизации
$router->get('/login', [$authController, 'showLogin']);
$router->post('/login', [$authController, 'login']);
$router->get('/register', [$authController, 'showRegister']);
$router->post('/register', [$authController, 'register']);
$router->post('/logout', [$authController, 'logout']);


$router->get('/listings', [$listingController, 'index']);
$router->get('/listings/create', [$listingController, 'create']);
$router->post('/listings', [$listingController, 'store']);
$router->get('/listings/{id}', [$listingController, 'show']);
$router->get('/listings/{id}/edit', [$listingController, 'edit']);
$router->post('/listings/{id}/update', [$listingController, 'update']);
$router->post('/listings/{id}/delete', [$listingController, 'destroy']);

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');

