<?php

declare(strict_types=1);

/** @var array<int, array<string, mixed>> $favorites */
/** @var string $csrfToken */

use function KadrPortal\Helpers\is_authenticated;

$isAuthenticated = is_authenticated();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Избранное — Kadr Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body data-auth="<?= $isAuthenticated ? '1' : '0'; ?>">
<?php require __DIR__ . '/../../components/header.php'; ?>
<main class="container favorites-page">
    <section class="favorites-section">
        <div class="favorites-header">
            <h1>Избранные объявления</h1>
            <p class="favorites-subtitle">Все объявления, которые вы сохранили для просмотра позже.</p>
        </div>
        <?php if ($favorites !== []) : ?>
            <section class="listings-grid" data-favorites-list>
                <?php foreach ($favorites as $listing) : ?>
                    <?php require __DIR__ . '/../components/listing-card.php'; ?>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
        <p class="favorites-empty" data-favorites-empty <?php if ($favorites !== []) : ?>hidden<?php endif; ?>>
            В избранном пока пусто. Добавьте интересные объявления со страницы списка.
        </p>
    </section>
</main>
<script src="/assets/js/favorites.js" defer></script>
</body>
</html>
