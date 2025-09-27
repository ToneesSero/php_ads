<?php

declare(strict_types=1);

/** @var int $listingId */
/** @var string $csrfToken */
?>
<section class="comments" data-comments data-listing-id="<?= htmlspecialchars((string) $listingId, ENT_QUOTES, 'UTF-8'); ?>" data-csrf-token="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
    <div class="comments__header">
        <h2 class="comments__title">Комментарии</h2>
        <button type="button" class="comments__notify" data-comments-show-new hidden>
            Показать новые (<span data-comments-new-count>0</span>)
        </button>
    </div>
    <div class="comments__body">
        <ul class="comments__list" data-comments-list aria-live="polite"></ul>
        <p class="comments__empty" data-comments-empty>Пока нет комментариев — станьте первым!</p>
        <p class="comments__error" data-comments-error hidden></p>
        <div class="comments__loader" data-comments-loader hidden>
            <span class="comments__spinner" aria-hidden="true"></span>
            <span>Загрузка комментариев…</span>
        </div>
    </div>
</section>
