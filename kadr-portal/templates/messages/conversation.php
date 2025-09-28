<?php

declare(strict_types=1);

/** @var array{id:int,name:string} $otherUser */
/** @var array<int, array<string, mixed>> $messages */
/** @var string $csrfToken */

use function KadrPortal\Helpers\is_authenticated;

$isAuthenticated = is_authenticated();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Диалог с <?= htmlspecialchars($otherUser['name'], ENT_QUOTES, 'UTF-8'); ?> — Kadr Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body data-auth="<?= $isAuthenticated ? '1' : '0'; ?>">
<?php require __DIR__ . '/../../components/header.php'; ?>
<main class="container messages-page">
    <section class="messages-conversation-view">
        <header class="messages-header conversation-header">
            <div>
                <h1>Диалог с <?= htmlspecialchars($otherUser['name'], ENT_QUOTES, 'UTF-8'); ?></h1>
                <p class="messages-subtitle">Здесь отображается переписка с пользователем.</p>
            </div>
            <a class="button button-secondary" href="/messages">К списку сообщений</a>
        </header>
        <div class="messages-thread" data-messages-thread>
            <?php foreach ($messages as $message) : ?>
                <?php
                $isOwn = (bool) ($message['own'] ?? false);
                $text = (string) ($message['text'] ?? '');
                $createdAt = (string) ($message['created_at_formatted'] ?? '');
                $listingTitle = isset($message['listing_title']) && is_string($message['listing_title'])
                    ? $message['listing_title']
                    : null;
                ?>
                <div class="message-item<?= $isOwn ? ' message-item--own' : ' message-item--incoming'; ?>">
                    <p class="message-item__text"><?= nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8')); ?></p>
                    <?php if ($listingTitle !== null && $listingTitle !== '') : ?>
                        <p class="message-item__listing">Объявление: <?= htmlspecialchars($listingTitle, ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endif; ?>
                    <?php if ($createdAt !== '') : ?>
                        <time class="message-item__time"><?= htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8'); ?></time>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <p class="messages-empty" data-messages-empty <?php if ($messages !== []) : ?>hidden<?php endif; ?>>
            Пока нет сообщений. Начните диалог, чтобы обсудить объявление.
        </p>
        <form class="message-form" data-message-form data-messages-target="[data-messages-thread]">
            <input type="hidden" name="to_user_id" value="<?= htmlspecialchars((string) $otherUser['id'], ENT_QUOTES, 'UTF-8'); ?>">
            <div class="form-group">
                <label for="conversation-text">Сообщение</label>
                <textarea id="conversation-text" name="text" rows="4" required placeholder="Напишите сообщение"></textarea>
            </div>
            <p class="form-error" data-message-error></p>
            <p class="form-success" data-message-success hidden>Сообщение отправлено.</p>
            <div class="form-actions">
                <button type="submit" class="button">Отправить</button>
            </div>
        </form>
    </section>
</main>
<script src="/assets/js/messages.js" defer></script>
</body>
</html>
