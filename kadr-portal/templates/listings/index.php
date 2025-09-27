<?php

declare(strict_types=1);

/** @var array<int, array<string, mixed>> $listings */
/** @var array<int, array<string, mixed>> $categories */
/** @var array{search:string, category:int|null, min_price:float|null, max_price:float|null} $currentFilters */
/** @var bool $hasMore */
/** @var int|null $nextPage */
/** @var int $page */
/** @var string $csrfToken */

use function KadrPortal\Helpers\is_authenticated;

$searchValue = $currentFilters['search'] ?? '';
$categoryValue = $currentFilters['category'] ?? null;
$minPriceValue = $currentFilters['min_price'] !== null ? number_format((float) $currentFilters['min_price'], 2, '.', '') : '';
$maxPriceValue = $currentFilters['max_price'] !== null ? number_format((float) $currentFilters['max_price'], 2, '.', '') : '';

$filterQuery = [];

if ($searchValue !== '') {
    $filterQuery['search'] = $searchValue;
}

if ($categoryValue !== null) {
    $filterQuery['category'] = (string) $categoryValue;
}

if ($minPriceValue !== '') {
    $filterQuery['min_price'] = $minPriceValue;
}

if ($maxPriceValue !== '') {
    $filterQuery['max_price'] = $maxPriceValue;
}

$baseQuery = http_build_query($filterQuery);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Объявления — Kadr Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<?php require __DIR__ . '/../../components/header.php'; ?>
<main class="container listings-page">
    <section class="listings-header">
        <div class="listings-header-actions">
            <h1>Объявления</h1>
            <?php if (is_authenticated()) : ?>
                <a class="button" href="/listings/create">Создать объявление</a>
            <?php endif; ?>
        </div>
        <form class="listings-filter" method="get" data-listings-filter>
            <div class="form-group">
                <label for="search">Поиск</label>
                <input type="search" id="search" name="search" value="<?= htmlspecialchars($searchValue, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Название или описание">
            </div>
            <div class="form-group">
                <label for="category">Категория</label>
                <select id="category" name="category">
                    <option value="">Все категории</option>
                    <?php foreach ($categories as $category) : ?>
                        <option value="<?= htmlspecialchars((string) $category['id'], ENT_QUOTES, 'UTF-8'); ?>" <?php if ((int) ($category['id']) === (int) ($categoryValue ?? 0)) : ?>selected<?php endif; ?>>
                            <?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="min-price">Цена от</label>
                <input type="number" id="min-price" name="min_price" min="0" step="0.01" value="<?= htmlspecialchars($minPriceValue, ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="form-group">
                <label for="max-price">Цена до</label>
                <input type="number" id="max-price" name="max_price" min="0" step="0.01" value="<?= htmlspecialchars($maxPriceValue, ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="form-actions">
                <button type="submit" class="button">Применить</button>
                <button type="button" class="button button-secondary" data-reset-filters>Сбросить</button>
            </div>
        </form>
    </section>

    <?php if ($listings !== []) : ?>
        <section class="listings-grid" data-listings-container data-next-page="<?= $nextPage !== null ? htmlspecialchars((string) $nextPage, ENT_QUOTES, 'UTF-8') : ''; ?>" data-base-query="<?= htmlspecialchars($baseQuery, ENT_QUOTES, 'UTF-8'); ?>">
            <?php foreach ($listings as $listing) : ?>
                <?php require __DIR__ . '/../components/listing-card.php'; ?>
            <?php endforeach; ?>
        </section>
        <div class="form-actions" data-pagination>
            <?php if ($page > 1) : ?>
                <?php $prevQuery = http_build_query(array_merge($filterQuery, ['page' => $page - 1])); ?>
                <a class="button button-secondary" href="/listings?<?= htmlspecialchars($prevQuery, ENT_QUOTES, 'UTF-8'); ?>">Предыдущая</a>
            <?php endif; ?>
            <?php if ($hasMore && $nextPage !== null) : ?>
                <?php $nextQuery = http_build_query(array_merge($filterQuery, ['page' => $nextPage])); ?>
                <a class="button" href="/listings?<?= htmlspecialchars($nextQuery, ENT_QUOTES, 'UTF-8'); ?>">Следующая</a>
            <?php endif; ?>
        </div>
    <?php else : ?>
        <p class="listing-empty">Пока нет объявлений. Станьте первым, кто добавит предложение!</p>
    <?php endif; ?>
</main>
<script src="/assets/js/listings.js" defer></script>
</body>
</html>
