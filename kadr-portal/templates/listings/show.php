<?php

declare(strict_types=1);

/** @var array<string, mixed> $listing */
/** @var string $csrfToken */

use function KadrPortal\Helpers\current_user;
use function KadrPortal\Helpers\is_authenticated;

$user = current_user();
$isAuthenticated = is_authenticated();
$isOwner = $isAuthenticated && $user !== null && (int) $user['id'] === (int) $listing['user_id'];
$isFavorite = isset($listing['is_favorite']) ? (bool) $listing['is_favorite'] : false;
$images = isset($listing['images']) && is_array($listing['images']) ? $listing['images'] : [];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($listing['title'], ENT_QUOTES, 'UTF-8'); ?> — Kadr Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/comments.css">
</head>
<body data-auth="<?= $isAuthenticated ? '1' : '0'; ?>">
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
            <?php if ($isAuthenticated) : ?>
                <button type="button"
                        class="button button-secondary listing-card-favorite<?php if ($isFavorite) : ?> listing-card-favorite--active<?php endif; ?>"
                        data-favorite-button
                        data-listing-id="<?= htmlspecialchars((string) $listing['id'], ENT_QUOTES, 'UTF-8'); ?>"
                        data-favorited="<?= $isFavorite ? '1' : '0'; ?>"
                        data-csrf="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>"
                        aria-pressed="<?= $isFavorite ? 'true' : 'false'; ?>">
                    <?= $isFavorite ? 'В избранном' : 'В избранное'; ?>
                </button>
            <?php else : ?>
                <a class="button button-secondary" href="/login">Войти, чтобы добавить в избранное</a>
            <?php endif; ?>
            <?php if ($isOwner) : ?>
                <a class="button" href="/listings/<?= htmlspecialchars((string) $listing['id'], ENT_QUOTES, 'UTF-8'); ?>/edit">Редактировать</a>
                <form action="/listings/<?= htmlspecialchars((string) $listing['id'], ENT_QUOTES, 'UTF-8'); ?>/delete" method="post" onsubmit="return confirm('Удалить объявление?');">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                    <button type="submit" class="button button-secondary">Удалить</button>
                </form>
            <?php endif; ?>
        </div>
    </article>
    <?php if ($isAuthenticated && !$isOwner) : ?>
        <section class="message-contact">
            <h2>Связаться с автором</h2>
            <form class="message-form" data-message-form>
                <input type="hidden" name="to_user_id" value="<?= htmlspecialchars((string) $listing['user_id'], ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="listing_id" value="<?= htmlspecialchars((string) $listing['id'], ENT_QUOTES, 'UTF-8'); ?>">
                <div class="form-group">
                    <label for="message-text-<?= htmlspecialchars((string) $listing['id'], ENT_QUOTES, 'UTF-8'); ?>">Сообщение</label>
                    <textarea id="message-text-<?= htmlspecialchars((string) $listing['id'], ENT_QUOTES, 'UTF-8'); ?>" name="text" rows="4" required placeholder="Напишите автору объявления"></textarea>
                </div>
                <p class="form-error" data-message-error></p>
                <p class="form-success" data-message-success hidden>Сообщение отправлено. Автор увидит его в разделе сообщений.</p>
                <div class="form-actions">
                    <button type="submit" class="button">Отправить</button>
                </div>
            </form>
        </section>
    <?php elseif (!$isOwner) : ?>
        <p class="message-login-hint">Войдите, чтобы написать автору объявления.</p>
    <?php endif; ?>
    <?php
    $listingId = (int) $listing['id'];
    require __DIR__ . '/../components/comments.php';
    require __DIR__ . '/../components/comment-form.php';
    ?>
</main>
<script src="/assets/js/listings.js" defer></script>
<script src="/assets/js/comments.js" defer></script>
<script src="/assets/js/favorites.js" defer></script>
<script src="/assets/js/messages.js" defer></script>
</body>
</html>
