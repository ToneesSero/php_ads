<?php

declare(strict_types=1);

/** @var array<string, mixed> $listing */
?>
<article class="listing-card">
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
