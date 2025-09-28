<?php

declare(strict_types=1);

/** @var int $listingId */
/** @var string $csrfToken */
/** @var bool $isAuthenticated */

use function KadrPortal\Helpers\current_user;

$user = current_user();
?>
<section class="comment-form" data-comment-form>
    <h3 class="comment-form__title">Оставить комментарий</h3>
    <?php if ($isAuthenticated && $user !== null) : ?>
        <form class="comment-form__form" data-comment-form-target>
            <input type="hidden" name="listing_id" value="<?= htmlspecialchars((string) $listingId, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <label class="comment-form__label" for="comment-text">
                Сообщение
                <textarea id="comment-text" name="comment_text" rows="4" maxlength="1000" required placeholder="Поделитесь своим мнением"></textarea>
            </label>
            <div class="comment-form__actions">
                <button type="submit" class="button">Отправить</button>
                <p class="comment-form__hint">Публикуя комментарий, вы соглашаетесь с правилами площадки.</p>
            </div>
        </form>
        <p class="comment-form__status" data-comment-status aria-live="polite"></p>
    <?php else : ?>
        <p class="comment-form__auth-note">
            Только авторизованные пользователи могут оставлять комментарии.
            <a href="/login">Войдите</a> или <a href="/register">зарегистрируйтесь</a>.
        </p>
    <?php endif; ?>
</section>
