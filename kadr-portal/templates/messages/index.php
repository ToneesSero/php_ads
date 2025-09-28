<?php

declare(strict_types=1);

/** @var array<int, array<string, mixed>> $conversations */
/** @var string $csrfToken */

use function KadrPortal\Helpers\is_authenticated;

$isAuthenticated = is_authenticated();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Сообщения — Kadr Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body data-auth="<?= $isAuthenticated ? '1' : '0'; ?>">
<?php require __DIR__ . '/../../components/header.php'; ?>
<main class="container messages-page">
    <section class="messages-list">
        <header class="messages-header">
            <h1>Личные сообщения</h1>
            <p class="messages-subtitle">Общайтесь с авторами объявлений и отслеживайте ответы.</p>
        </header>
        <?php if ($conversations !== []) : ?>
            <ul class="messages-conversations" data-messages-conversations>
                <?php foreach ($conversations as $conversation) : ?>
                    <?php
                    $preview = mb_strimwidth((string) ($conversation['last_message'] ?? ''), 0, 120, '…', 'UTF-8');
                    $formatted = (string) ($conversation['last_message_formatted'] ?? '');
                    $count = (int) ($conversation['unread_count'] ?? 0);
                    $listingTitle = isset($conversation['listing_title']) && is_string($conversation['listing_title'])
                        ? $conversation['listing_title']
                        : null;
                    ?>
                    <li class="messages-conversation">
                        <a href="/messages/<?= htmlspecialchars((string) $conversation['user_id'], ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="messages-conversation-header">
                                <span class="messages-conversation-name"><?= htmlspecialchars((string) $conversation['user_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php if ($count > 0) : ?>
                                    <span class="messages-conversation-badge" aria-label="Непрочитанные сообщения"><?= $count; ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if ($listingTitle !== null && $listingTitle !== '') : ?>
                                <p class="messages-conversation-listing">Объявление: <?= htmlspecialchars($listingTitle, ENT_QUOTES, 'UTF-8'); ?></p>
                            <?php endif; ?>
                            <p class="messages-conversation-preview"><?= htmlspecialchars($preview, ENT_QUOTES, 'UTF-8'); ?></p>
                            <?php if ($formatted !== '') : ?>
                                <time class="messages-conversation-time"><?= htmlspecialchars($formatted, ENT_QUOTES, 'UTF-8'); ?></time>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else : ?>
            <p class="messages-empty">У вас пока нет сообщений. Напишите автору объявления со страницы его описания.</p>
        <?php endif; ?>
    </section>
</main>
<script src="/assets/js/messages.js" defer></script>
</body>
</html>
