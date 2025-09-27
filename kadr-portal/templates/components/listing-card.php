<?php

declare(strict_types=1);

/** @var array<string, mixed> $listing */
$thumb = isset($listing['main_image_thumb']) && is_string($listing['main_image_thumb'])
    ? $listing['main_image_thumb']
    : null;
$fullImage = isset($listing['main_image_path']) && is_string($listing['main_image_path'])
    ? $listing['main_image_path']
    : null;
?>
<article class="listing-card">
    <div class="listing-card-media">
        <?php if ($thumb !== null) : ?>
            <a class="listing-card-image" href="/listings/<?= htmlspecialchars((string) $listing['id'], ENT_QUOTES, 'UTF-8'); ?>">
                <img src="<?= htmlspecialchars($thumb, ENT_QUOTES, 'UTF-8'); ?>"
                     <?php if ($fullImage !== null) : ?>data-full-image="<?= htmlspecialchars($fullImage, ENT_QUOTES, 'UTF-8'); ?>"<?php endif; ?>
                     alt="Превью объявления"
                     loading="lazy"
                >
            </a>
        <?php else : ?>
            <a class="listing-card-placeholder" href="/listings/<?= htmlspecialchars((string) $listing['id'], ENT_QUOTES, 'UTF-8'); ?>">
                <span>Нет фото</span>
            </a>
        <?php endif; ?>
    </div>
    <h2>
        <a href="/listings/<?= htmlspecialchars((string) $listing['id'], ENT_QUOTES, 'UTF-8'); ?>">
            <?= htmlspecialchars($listing['title'], ENT_QUOTES, 'UTF-8'); ?>
        </a>
    </h2>
    <p class="text-muted">
        <?= htmlspecialchars(number_format((float) $listing['price'], 2, '.', ' '), ENT_QUOTES, 'UTF-8'); ?> ₽
    </p>
    <p>
        <?= htmlspecialchars(mb_strimwidth($listing['description'], 0, 220, '…', 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?>
    </p>
    <div class="listing-card-meta">
        <span>Категория: <?= htmlspecialchars($listing['category_name'] ?? 'Без категории', ENT_QUOTES, 'UTF-8'); ?></span>
        <span>Автор: <?= htmlspecialchars($listing['author_name'] ?? 'Неизвестно', ENT_QUOTES, 'UTF-8'); ?></span>
        <span>Опубликовано: <?= htmlspecialchars(date('d.m.Y', strtotime($listing['created_at'])), ENT_QUOTES, 'UTF-8'); ?></span>
    </div>
    <div class="listing-card-actions">
        <a class="button button-link" href="/listings/<?= htmlspecialchars((string) $listing['id'], ENT_QUOTES, 'UTF-8'); ?>">Подробнее</a>
    </div>
</article>
