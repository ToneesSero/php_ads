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

/**
 * Validate listing form data for creation or update.
 *
 * @param array<string, string> $input
 *
 * @return array{errors: array<string, string>, data: array<string, mixed>}
 */
function validate_listing(array $input): array
{
    $title = trim($input['title'] ?? '');
    $description = trim($input['description'] ?? '');
    $priceRaw = str_replace(',', '.', trim($input['price'] ?? ''));
    $categoryRaw = trim($input['category_id'] ?? '');

    $errors = [];

    if ($title === '') {
        $errors['title'] = 'Введите заголовок объявления.';
    } elseif (mb_strlen($title) > 255) {
        $errors['title'] = 'Заголовок не должен превышать 255 символов.';
    }

    if ($description === '') {
        $errors['description'] = 'Добавьте описание объявления.';
    }

    $price = null;

    if ($priceRaw === '') {
        $errors['price'] = 'Укажите цену.';
    } elseif (!is_numeric($priceRaw)) {
        $errors['price'] = 'Цена должна быть числом.';
    } else {
        $price = round((float) $priceRaw, 2);

        if ($price < 0) {
            $errors['price'] = 'Цена не может быть отрицательной.';
        }
    }

    $categoryId = null;

    if ($categoryRaw !== '') {
        if (!ctype_digit($categoryRaw)) {
            $errors['category_id'] = 'Категория указана неверно.';
        } else {
            $categoryId = (int) $categoryRaw;
        }
    }

    return [
        'errors' => $errors,
        'data' => [
            'title' => $title,
            'description' => $description,
            'price' => $price,
            'category_id' => $categoryId,
        ],
    ];
}

