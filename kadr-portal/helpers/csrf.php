<?php

declare(strict_types=1);

namespace KadrPortal\Helpers;

use RuntimeException;

/**
 * Ensure that the PHP session has been started before interacting with CSRF helpers.
 */
function ensureSessionStarted(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        throw new RuntimeException('Session must be started before using CSRF helpers.');
    }
}

/**
 * Retrieve (and lazily create) the CSRF token for the current session.
 */
function csrf_token(): string
{
    ensureSessionStarted();

    if (!isset($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf_token'];
}

/**
 * Validate the provided CSRF token against the session token.
 */
function verify_csrf_token(?string $token): bool
{
    ensureSessionStarted();

    if (!is_string($token) || $token === '') {
        return false;
    }

    $sessionToken = $_SESSION['_csrf_token'] ?? '';

    if ($sessionToken === '') {
        return false;
    }

    return hash_equals($sessionToken, $token);
}

/**
 * Invalidate the current CSRF token, forcing regeneration on the next request.
 */
function reset_csrf_token(): void
{
    ensureSessionStarted();
    unset($_SESSION['_csrf_token']);
}
