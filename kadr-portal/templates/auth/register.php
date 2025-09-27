<?php

declare(strict_types=1);

/** @var array<string, string> $fieldErrors */
/** @var string|null $generalError */
/** @var string $csrfToken */
/** @var array<string, string> $old */
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Регистрация — Kadr Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<?php require __DIR__ . '/../../components/header.php'; ?>
<main class="container auth-container">
    <h1>Регистрация</h1>
    <?php if (!empty($generalError)) : ?>
        <div class="alert alert-danger"><?= htmlspecialchars($generalError, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <form action="/register" method="post" class="auth-form" id="register-form" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
        <div class="form-group">
            <label for="register-name">Имя</label>
            <input type="text" name="name" id="register-name" value="<?= htmlspecialchars($old['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required minlength="2" maxlength="255">
            <?php if (!empty($fieldErrors['name'])) : ?>
                <p class="form-error" data-error="name"><?= htmlspecialchars($fieldErrors['name'], ENT_QUOTES, 'UTF-8'); ?></p>
            <?php else : ?>
                <p class="form-error" data-error="name"></p>
            <?php endif; ?>
        </div>
        <div class="form-group">
            <label for="register-email">Email</label>
            <input type="email" name="email" id="register-email" value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
            <?php if (!empty($fieldErrors['email'])) : ?>
                <p class="form-error" data-error="email"><?= htmlspecialchars($fieldErrors['email'], ENT_QUOTES, 'UTF-8'); ?></p>
            <?php else : ?>
                <p class="form-error" data-error="email"></p>
            <?php endif; ?>
        </div>
        <div class="form-group">
            <label for="register-password">Пароль</label>
            <input type="password" name="password" id="register-password" required minlength="8">
            <?php if (!empty($fieldErrors['password'])) : ?>
                <p class="form-error" data-error="password"><?= htmlspecialchars($fieldErrors['password'], ENT_QUOTES, 'UTF-8'); ?></p>
            <?php else : ?>
                <p class="form-error" data-error="password"></p>
            <?php endif; ?>
        </div>
        <button type="submit" class="button">Зарегистрироваться</button>
    </form>
    <p class="auth-switch">Уже есть аккаунт? <a href="/login">Войти</a></p>
</main>
<script src="/assets/js/auth.js" defer></script>
</body>
</html>
