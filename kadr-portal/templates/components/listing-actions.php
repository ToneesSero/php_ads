<?php

declare(strict_types=1);

/** @var array<string, mixed> $listing */
/** @var string $csrfToken */

$listingId = (int) ($listing['id'] ?? 0);
$title = (string) ($listing['title'] ?? '');
$description = (string) ($listing['description'] ?? '');
$price = number_format((float) ($listing['price'] ?? 0), 2, '.', '');
$status = (string) ($listing['status'] ?? 'active');
?>
<div class="listing-actions" data-listing-actions>
    <button type="button"
            class="button button-link"
            data-profile-edit
            data-listing-id="<?= $listingId; ?>"
            data-listing-title="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>"
            data-listing-description="<?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8'); ?>"
            data-listing-price="<?= htmlspecialchars($price, ENT_QUOTES, 'UTF-8'); ?>">
        Редактировать
    </button>
    <button type="button"
            class="button button-link"
            data-profile-toggle
            data-listing-id="<?= $listingId; ?>"
            data-current-status="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>">
        <?= $status === 'active' ? 'Снять с публикации' : 'Опубликовать'; ?>
    </button>
    <button type="button"
            class="button button-link"
            data-profile-refresh
            data-listing-id="<?= $listingId; ?>">
        Обновить статистику
    </button>
    <button type="button"
            class="button button-link"
            data-profile-duplicate
            data-listing-id="<?= $listingId; ?>">
        Дублировать
    </button>
    <button type="button"
            class="button button-link listing-actions__danger"
            data-profile-delete
            data-listing-id="<?= $listingId; ?>">
        Удалить
    </button>
    <input type="hidden" data-listing-csrf value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
</div>
