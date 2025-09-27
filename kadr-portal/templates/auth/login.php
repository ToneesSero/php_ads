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
    <title>Вход — Kadr Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<?php require __DIR__ . '/../../components/header.php'; ?>
<main class="container auth-container">
    <h1>Вход</h1>
    <?php if (!empty($generalError)) : ?>
        <div class="alert alert-danger"><?= htmlspecialchars($generalError, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <form action="/login" method="post" class="auth-form" id="login-form" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
        <div class="form-group">
            <label for="login-email">Email</label>
            <input type="email" name="email" id="login-email" value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
            <?php if (!empty($fieldErrors['email'])) : ?>
                <p class="form-error" data-error="email"><?= htmlspecialchars($fieldErrors['email'], ENT_QUOTES, 'UTF-8'); ?></p>
            <?php else : ?>
                <p class="form-error" data-error="email"></p>
            <?php endif; ?>
        </div>
        <div class="form-group">
            <label for="login-password">Пароль</label>
            <input type="password" name="password" id="login-password" required>
            <?php if (!empty($fieldErrors['password'])) : ?>
                <p class="form-error" data-error="password"><?= htmlspecialchars($fieldErrors['password'], ENT_QUOTES, 'UTF-8'); ?></p>
            <?php else : ?>
                <p class="form-error" data-error="password"></p>
            <?php endif; ?>
        </div>
        <button type="submit" class="button">Войти</button>
    </form>
    <p class="auth-switch">Нет аккаунта? <a href="/register">Создать</a></p>
</main>
<script src="/assets/js/auth.js" defer></script>
</body>
</html>
