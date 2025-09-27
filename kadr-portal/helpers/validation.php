<?php

declare(strict_types=1);

namespace KadrPortal\Helpers;

/**
 * Validate registration data.
 *
 * @param array<string, string> $input
 *
 * @return array{errors: array<string, string>, data: array<string, string>}
 */
function validate_registration(array $input): array
{
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';
    $name = trim($input['name'] ?? '');

    $errors = [];

    if ($email === '') {
        $errors['email'] = 'Укажите адрес электронной почты.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Введите корректный email.';
    }

    if ($name === '') {
        $errors['name'] = 'Имя обязательно.';
    } elseif (mb_strlen($name) > 255) {
        $errors['name'] = 'Имя не должно превышать 255 символов.';
    }

    if ($password === '') {
        $errors['password'] = 'Пароль обязателен.';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Пароль должен содержать минимум 8 символов.';
    }

    return [
        'errors' => $errors,
        'data' => [
            'email' => $email,
            'password' => $password,
            'name' => $name,
        ],
    ];
}

/**
 * Validate login form data.
 *
 * @param array<string, string> $input
 *
 * @return array{errors: array<string, string>, data: array<string, string>}
 */
function validate_login(array $input): array
{
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';

    $errors = [];

    if ($email === '') {
        $errors['email'] = 'Введите email.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Email указан неверно.';
    }

    if ($password === '') {
        $errors['password'] = 'Введите пароль.';
    }

    return [
        'errors' => $errors,
        'data' => [
            'email' => $email,
            'password' => $password,
        ],
    ];
}
