<?php

declare(strict_types=1);

/** @var array<string, string> $errors */
/** @var array<string, string> $old */
/** @var string $csrfToken */
/** @var array<int, array<string, mixed>> $categories */
/** @var array<int, array{id:string,path:string,thumb:string}> $uploadedImages */
/** @var int $uploadLimit */
/** @var int $uploadMaxSize */

$uploadedImages = $uploadedImages ?? [];
$uploadLimit = $uploadLimit ?? 5;
$uploadMaxSize = $uploadMaxSize ?? 5 * 1024 * 1024;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Новое объявление — Kadr Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/upload.css">
</head>
<body>
<?php require __DIR__ . '/../../components/header.php'; ?>
<main class="container listings-page">
    <h1>Создать объявление</h1>
    <?php if (!empty($errors['general'])) : ?>
        <div class="alert alert-danger"><?= htmlspecialchars($errors['general'], ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <form action="/listings" method="post" class="card" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
        <div class="form-group">
            <label for="title">Заголовок</label>
            <input type="text" id="title" name="title" required maxlength="255" value="<?= htmlspecialchars($old['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            <?php if (!empty($errors['title'])) : ?>
                <p class="form-error"><?= htmlspecialchars($errors['title'], ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>
        </div>
        <div class="form-group">
            <label for="description">Описание</label>
            <textarea id="description" name="description" rows="6" required><?= htmlspecialchars($old['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            <?php if (!empty($errors['description'])) : ?>
                <p class="form-error"><?= htmlspecialchars($errors['description'], ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>
        </div>
        <div class="form-group">
            <label for="price">Цена</label>
            <input type="number" id="price" name="price" min="0" step="0.01" required value="<?= htmlspecialchars($old['price'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            <?php if (!empty($errors['price'])) : ?>
                <p class="form-error"><?= htmlspecialchars($errors['price'], ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>
        </div>
        <div class="form-group">
            <label for="category-id">Категория</label>
            <select id="category-id" name="category_id">
                <option value="">Не выбрано</option>
                <?php foreach ($categories as $category) : ?>
                    <option value="<?= htmlspecialchars((string) $category['id'], ENT_QUOTES, 'UTF-8'); ?>" <?php if (($old['category_id'] ?? '') !== '' && (int) $old['category_id'] === (int) $category['id']) : ?>selected<?php endif; ?>>
                        <?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($errors['category_id'])) : ?>
                <p class="form-error"><?= htmlspecialchars($errors['category_id'], ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>
        </div>
        <section class="form-group">
            <h2>Фотографии</h2>
            <?php require __DIR__ . '/../components/image-upload.php'; ?>
        </section>
        <div class="form-actions">
            <button type="submit" class="button">Сохранить</button>
            <a href="/listings" class="button button-secondary">Отмена</a>
        </div>
    </form>
</main>
<script src="/assets/js/upload.js" defer></script>
<script src="/assets/js/listings.js" defer></script>
</body>
</html>
