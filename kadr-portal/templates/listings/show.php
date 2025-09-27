<?php

declare(strict_types=1);

/** @var array<string, mixed> $listing */
/** @var string $csrfToken */

use function KadrPortal\Helpers\current_user;
use function KadrPortal\Helpers\is_authenticated;

$user = current_user();
$isOwner = is_authenticated() && $user !== null && (int) $user['id'] === (int) $listing['user_id'];
$images = isset($listing['images']) && is_array($listing['images']) ? $listing['images'] : [];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($listing['title'], ENT_QUOTES, 'UTF-8'); ?> — Kadr Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/comments.css">
</head>
<body>
<?php require __DIR__ . '/../../components/header.php'; ?>
<main class="container">
    <article class="listing-detail">
        <header>
            <h1><?= htmlspecialchars($listing['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
            <div class="listing-detail-meta">
                <span>Цена: <?= htmlspecialchars(number_format((float) $listing['price'], 2, '.', ' '), ENT_QUOTES, 'UTF-8'); ?> ₽</span>
                <span>Категория: <?= htmlspecialchars($listing['category_name'] ?? 'Без категории', ENT_QUOTES, 'UTF-8'); ?></span>
                <span>Автор: <?= htmlspecialchars($listing['author_name'] ?? 'Неизвестно', ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="listing-detail-meta">
                <span>Создано: <?= htmlspecialchars(date('d.m.Y H:i', strtotime($listing['created_at'])), ENT_QUOTES, 'UTF-8'); ?></span>
                <span>Обновлено: <?= htmlspecialchars(date('d.m.Y H:i', strtotime($listing['updated_at'] ?? $listing['created_at'])), ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
        </header>
        <?php if ($images !== []) : ?>
            <section class="listing-gallery" data-gallery>
                <button class="gallery-control gallery-control-prev" type="button" data-gallery-prev aria-label="Предыдущее фото">
                    ‹
                </button>
                <div class="listing-gallery-track" data-gallery-track>
                    <?php foreach ($images as $index => $image) : ?>
                        <?php
                        $path = is_array($image) && isset($image['path']) && is_string($image['path']) ? $image['path'] : null;
                        $thumb = is_array($image) && isset($image['thumb']) && is_string($image['thumb']) ? $image['thumb'] : $path;

                        if ($path === null) {
                            continue;
                        }
                        ?>
                        <figure class="listing-gallery-item" data-gallery-item data-index="<?= (int) $index; ?>">
                            <img src="<?= htmlspecialchars((string) $thumb, ENT_QUOTES, 'UTF-8'); ?>"
                                 alt="Изображение объявления"
                                 loading="lazy"
                                 data-full-image="<?= htmlspecialchars($path, ENT_QUOTES, 'UTF-8'); ?>"
                                 <?php if ($thumb !== null) : ?>data-thumb="<?= htmlspecialchars($thumb, ENT_QUOTES, 'UTF-8'); ?>"<?php endif; ?>>
                        </figure>
                    <?php endforeach; ?>
                </div>
                <button class="gallery-control gallery-control-next" type="button" data-gallery-next aria-label="Следующее фото">
                    ›
                </button>
            </section>
        <?php endif; ?>
        <p><?= nl2br(htmlspecialchars($listing['description'], ENT_QUOTES, 'UTF-8')); ?></p>
        <div class="listing-detail-actions">
            <a class="button button-secondary" href="/listings">Вернуться к списку</a>
            <?php if ($isOwner) : ?>
                <a class="button" href="/listings/<?= htmlspecialchars((string) $listing['id'], ENT_QUOTES, 'UTF-8'); ?>/edit">Редактировать</a>
                <form action="/listings/<?= htmlspecialchars((string) $listing['id'], ENT_QUOTES, 'UTF-8'); ?>/delete" method="post" onsubmit="return confirm('Удалить объявление?');">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                    <button type="submit" class="button button-secondary">Удалить</button>
                </form>
            <?php endif; ?>
        </div>
    </article>
    <?php
    $listingId = (int) $listing['id'];
    $isAuthenticated = is_authenticated();

    require __DIR__ . '/../components/comments.php';
    require __DIR__ . '/../components/comment-form.php';
    ?>
</main>
<script src="/assets/js/listings.js" defer></script>
<script src="/assets/js/comments.js" defer></script>
</body>
</html>
