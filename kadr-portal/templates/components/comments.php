<?php

declare(strict_types=1);

/** @var int $listingId */
/** @var string $csrfToken */
?>
<section class="comments" data-comments data-listing-id="<?= htmlspecialchars((string) $listingId, ENT_QUOTES, 'UTF-8'); ?>" data-csrf-token="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
    <div class="comments__header">
        <h2 class="comments__title">Комментарии</h2>
        <button type="button" class="comments__notify" data-comments-show-new hidden style="display: none;">
            Показать новые (<span data-comments-new-count>0</span>)
        </button>
    </div>
    <div class="comments__body">
        <div class="comments__loader" data-comments-loader style="display: flex;">
            <span class="comments__spinner" aria-hidden="true"></span>
            <span>Загрузка комментариев…</span>
        </div>
        <ul class="comments__list" data-comments-list aria-live="polite"></ul>
        <p class="comments__empty" data-comments-empty style="display: none;">Пока нет комментариев — станьте первым!</p>
        <p class="comments__error" data-comments-error hidden style="display: none;"></p>
    </div>
</section>