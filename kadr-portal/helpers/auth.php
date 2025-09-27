<?php

declare(strict_types=1);

namespace KadrPortal\Helpers;

use RuntimeException;

function ensureSession(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        throw new RuntimeException('Session has not been started.');
    }
}

/**
 * Store user information in the session and regenerate the session ID to prevent fixation.
 *
 * @param array{id:int,email:string,name:string} $user
 */
function login_user(array $user): void
{
    ensureSession();

    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => $user['id'],
        'email' => $user['email'],
        'name' => $user['name'],
    ];
}

/**
 * Clear authentication data and rotate the session identifier.
 */
function logout_user(): void
{
    ensureSession();

    $_SESSION = [];
    session_regenerate_id(true);
}

function is_authenticated(): bool
{
    ensureSession();

    return isset($_SESSION['user']);
}

/**
 * @return array{id:int,email:string,name:string}|null
 */
function current_user(): ?array
{
    ensureSession();

    if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
        return null;
    }

    /** @var array{id:int,email:string,name:string}|null $user */
    $user = $_SESSION['user'];

    return $user;
}

/**
 * Store one-time flash data in the session.
 */
function set_flash(string $key, mixed $value): void
{
    ensureSession();

    if (!isset($_SESSION['_flash'])) {
        $_SESSION['_flash'] = [];
    }

    $_SESSION['_flash'][$key] = $value;
}

/**
 * Retrieve flash data and remove it from the session.
 */
function get_flash(string $key, mixed $default = null): mixed
{
    ensureSession();

    if (!isset($_SESSION['_flash'][$key])) {
        return $default;
    }

    $value = $_SESSION['_flash'][$key];
    unset($_SESSION['_flash'][$key]);

    if ($key === '_old_input') {
        return is_array($value) ? $value : $default;
    }

    return $value;
}

/**
 * Store previous form values to repopulate fields on validation failure.
 *
 * @param array<string, string> $input
 */
function flash_old_input(array $input): void
{
    ensureSession();
    set_flash('_old_input', $input);
}

/**
 * Retrieve flashed old input data.
 *
 * @return array<string, string>
 */
function old_input(): array
{
    static $cache = null;

    if ($cache !== null) {
        return $cache;
    }

    $old = get_flash('_old_input', []);

    if (!is_array($old)) {
        $cache = [];
        return $cache;
    }

    /** @var array<string, string> $old */
    $cache = $old;

    return $cache;
}
