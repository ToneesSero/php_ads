<?php

declare(strict_types=1);

/** @var array<int, array<string, mixed>> $listings */
/** @var array<int, array<string, mixed>> $categories */
/** @var array<string, mixed> $summary */
/** @var array<string, mixed> $pagination */
/** @var array<string, mixed> $filtersState */
/** @var string $csrfToken */

use function KadrPortal\Helpers\is_authenticated;

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Мои объявления — Kadr Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/profile.css">
</head>
<body data-auth="<?= is_authenticated() ? '1' : '0'; ?>">
<?php require __DIR__ . '/../../components/header.php'; ?>
<main class="container profile-listings">
    <header class="profile-listings__header">
        <div>
            <h1>Мои объявления</h1>
            <p class="profile-listings__subtitle">Управляйте активностью объявлений, редактируйте и следите за статистикой.</p>
        </div>
        <section class="profile-overview" aria-label="Краткая статистика">
            <div class="profile-overview__item">
                <span class="profile-overview__label">Всего объявлений</span>
                <strong class="profile-overview__value"><?= (int) ($summary['total_listings'] ?? 0); ?></strong>
            </div>
            <div class="profile-overview__item">
                <span class="profile-overview__label">Просмотры</span>
                <strong class="profile-overview__value"><?= (int) ($summary['total_views'] ?? 0); ?></strong>
            </div>
            <div class="profile-overview__item">
                <span class="profile-overview__label">Комментарии</span>
                <strong class="profile-overview__value"><?= (int) ($summary['total_comments'] ?? 0); ?></strong>
            </div>
            <div class="profile-overview__item">
                <span class="profile-overview__label">В избранном</span>
                <strong class="profile-overview__value"><?= (int) ($summary['total_favorites'] ?? 0); ?></strong>
            </div>
        </section>
    </header>

    <section class="profile-filters" aria-label="Фильтры объявлений">
        <form method="get" class="profile-filters__form">
            <div class="form-group">
                <label for="status">Статус</label>
                <select id="status" name="status">
                    <?php $statusValue = $filtersState['status'] ?? 'all'; ?>
                    <option value="all" <?php if ($statusValue === 'all') : ?>selected<?php endif; ?>>Все</option>
                    <option value="active" <?php if ($statusValue === 'active') : ?>selected<?php endif; ?>>Активные</option>
                    <option value="inactive" <?php if ($statusValue === 'inactive') : ?>selected<?php endif; ?>>Неактивные</option>
                </select>
            </div>
            <div class="form-group">
                <label for="category">Категория</label>
                <select id="category" name="category">
                    <option value="">Все категории</option>
                    <?php $categoryValue = (int) ($filtersState['category'] ?? 0); ?>
                    <?php foreach ($categories as $category) : ?>
                        <?php $categoryId = (int) ($category['id'] ?? 0); ?>
                        <option value="<?= htmlspecialchars((string) $categoryId, ENT_QUOTES, 'UTF-8'); ?>" <?php if ($categoryId === $categoryValue) : ?>selected<?php endif; ?>>
                            <?= htmlspecialchars((string) ($category['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="date-from">Создано с</label>
                <input type="date" id="date-from" name="date_from" value="<?= htmlspecialchars((string) ($filtersState['date_from'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="form-group">
                <label for="date-to">Создано по</label>
                <input type="date" id="date-to" name="date_to" value="<?= htmlspecialchars((string) ($filtersState['date_to'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="form-actions profile-filters__actions">
                <button type="submit" class="button">Применить</button>
                <a class="button button-secondary" href="/profile/listings">Сбросить</a>
            </div>
        </form>
    </section>

    <section class="profile-bulk" aria-label="Массовые действия">
        <form class="profile-bulk__form" data-bulk-form>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="form-group">
                <label for="bulk-action">Действие</label>
                <select id="bulk-action" name="action" required>
                    <option value="" selected disabled>Выберите действие</option>
                    <option value="deactivate">Снять с публикации</option>
                    <option value="activate">Опубликовать</option>
                    <option value="delete">Удалить</option>
                </select>
            </div>
            <div class="form-actions">
                <button type="submit" class="button" data-bulk-apply>Применить</button>
            </div>
        </form>
    </section>

    <?php if ($listings !== []) : ?>
        <section class="profile-table" aria-label="Список объявлений">
            <table>
                <thead>
                <tr>
                    <th scope="col"><input type="checkbox" data-profile-select-all aria-label="Выбрать все объявления"></th>
                    <th scope="col">Заголовок</th>
                    <th scope="col">Цена</th>
                    <th scope="col">Статус</th>
                    <th scope="col">Создано</th>
                    <th scope="col">Статистика</th>
                    <th scope="col" class="profile-table__actions">Действия</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($listings as $listing) : ?>
                    <tr data-listing-row data-listing-id="<?= (int) $listing['id']; ?>">
                        <td><input type="checkbox" data-listing-checkbox value="<?= (int) $listing['id']; ?>"></td>
                        <td class="profile-table__title">
                            <strong><?= htmlspecialchars((string) $listing['title'], ENT_QUOTES, 'UTF-8'); ?></strong>
                            <p class="profile-table__description"><?= htmlspecialchars(mb_strimwidth((string) $listing['description'], 0, 120, '…'), ENT_QUOTES, 'UTF-8'); ?></p>
                        </td>
                        <td><?= htmlspecialchars(number_format((float) $listing['price'], 2, '.', ' '), ENT_QUOTES, 'UTF-8'); ?> ₽</td>
                        <td>
                            <span class="status-badge status-badge--<?= $listing['status'] === 'active' ? 'active' : 'inactive'; ?>" data-status-badge><?= $listing['status'] === 'active' ? 'Активно' : 'Неактивно'; ?></span>
                        </td>
                        <?php
                        $createdAtRaw = $listing['created_at'] ?? '';
                        $createdAtFormatted = '—';

                        if (is_string($createdAtRaw) && $createdAtRaw !== '') {
                            $timestamp = strtotime($createdAtRaw);

                            if ($timestamp !== false) {
                                $createdAtFormatted = date('d.m.Y', $timestamp);
                            }
                        }
                        ?>
                        <td><?= htmlspecialchars($createdAtFormatted, ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <?php $stats = [
                                'views_count' => (int) ($listing['views_count'] ?? 0),
                                'comments' => (int) ($listing['comments'] ?? 0),
                                'favorites' => (int) ($listing['favorites'] ?? 0),
                                'last_viewed_at' => $listing['last_viewed_at'] ?? null,
                            ]; ?>
                            <?php require __DIR__ . '/listing-stats.php'; ?>
                        </td>
                        <td class="profile-table__actions">
                            <?php require __DIR__ . '/../components/listing-actions.php'; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <div class="profile-pagination">
                <?php $page = (int) ($pagination['page'] ?? 1); ?>
                <?php if ($page > 1) : ?>
                    <?php $prevQuery = array_merge($_GET, ['page' => $page - 1]); ?>
                    <a class="button button-secondary" href="?<?= htmlspecialchars(http_build_query($prevQuery), ENT_QUOTES, 'UTF-8'); ?>">Предыдущая</a>
                <?php endif; ?>
                <?php if (!empty($pagination['has_more']) && !empty($pagination['next_page'])) : ?>
                    <?php $nextQuery = array_merge($_GET, ['page' => (int) $pagination['next_page']]); ?>
                    <a class="button" href="?<?= htmlspecialchars(http_build_query($nextQuery), ENT_QUOTES, 'UTF-8'); ?>">Следующая</a>
                <?php endif; ?>
            </div>
        </section>
    <?php else : ?>
        <p class="profile-empty">У вас пока нет объявлений. Создайте первое прямо сейчас!</p>
    <?php endif; ?>
</main>

<dialog class="profile-modal" data-edit-dialog>
    <form method="dialog" class="profile-modal__content" data-edit-form>
        <h2>Быстрое редактирование</h2>
        <p class="profile-modal__hint">Изменения сохраняются сразу после отправки формы.</p>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
        <div class="form-group">
            <label for="edit-title">Заголовок</label>
            <input type="text" id="edit-title" name="title" required maxlength="255">
        </div>
        <div class="form-group">
            <label for="edit-price">Цена</label>
            <input type="number" id="edit-price" name="price" min="0" step="0.01" required>
        </div>
        <div class="form-group">
            <label for="edit-description">Описание</label>
            <textarea id="edit-description" name="description" rows="4" required></textarea>
        </div>
        <div class="form-actions profile-modal__actions">
            <button type="submit" class="button">Сохранить</button>
            <button type="button" class="button button-secondary" data-edit-cancel>Отмена</button>
        </div>
    </form>
</dialog>

<script src="/assets/js/profile.js" defer></script>
</body>
</html>
