<?php

declare(strict_types=1);

/** @var array<string, mixed> $stats */

$views = (int) ($stats['views_count'] ?? 0);
$comments = (int) ($stats['comments'] ?? 0);
$favorites = (int) ($stats['favorites'] ?? 0);
$lastViewed = $stats['last_viewed_at'] ?? null;
?>
<ul class="listing-stats" data-stats>
    <li>
        <span class="listing-stats__label">Просмотры</span>
        <span class="listing-stats__value" data-stat-views><?= $views; ?></span>
    </li>
    <li>
        <span class="listing-stats__label">Комментарии</span>
        <span class="listing-stats__value" data-stat-comments><?= $comments; ?></span>
    </li>
    <li>
        <span class="listing-stats__label">Избранное</span>
        <span class="listing-stats__value" data-stat-favorites><?= $favorites; ?></span>
    </li>
    <li>
        <span class="listing-stats__label">Последний просмотр</span>
        <span class="listing-stats__value" data-stat-last><?= $lastViewed !== null ? htmlspecialchars(date('d.m.Y H:i', strtotime((string) $lastViewed)), ENT_QUOTES, 'UTF-8') : '—'; ?></span>
    </li>
</ul>
