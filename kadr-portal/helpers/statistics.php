<?php

declare(strict_types=1);

namespace KadrPortal\Helpers;

use PDO;

/**
 * @param array<int> $listingIds
 *
 * @return array<int, array{comments:int,favorites:int}>
 */
function get_related_counters(array $listingIds): array
{
    $listingIds = array_values(array_unique(array_map('intval', $listingIds)));

    if ($listingIds === []) {
        return [];
    }

    $pdo = db();
    $placeholders = [];
    $bindings = [];

    foreach ($listingIds as $index => $id) {
        $key = ':id' . $index;
        $placeholders[] = $key;
        $bindings[$key] = $id;
    }

    $inClause = implode(',', $placeholders);

    $comments = array_fill_keys($listingIds, 0);
    $favorites = array_fill_keys($listingIds, 0);

    $stmt = $pdo->prepare('SELECT listing_id, COUNT(*) AS total FROM listing_comments WHERE listing_id IN (' . $inClause . ') GROUP BY listing_id');

    foreach ($bindings as $key => $value) {
        $stmt->bindValue($key, $value, PDO::PARAM_INT);
    }

    $stmt->execute();

    while ($row = $stmt->fetch()) {
        $listingId = (int) ($row['listing_id'] ?? 0);
        $total = (int) ($row['total'] ?? 0);

        if (isset($comments[$listingId])) {
            $comments[$listingId] = $total;
        }
    }

    $stmt = $pdo->prepare('SELECT listing_id, COUNT(*) AS total FROM favorites WHERE listing_id IN (' . $inClause . ') GROUP BY listing_id');

    foreach ($bindings as $key => $value) {
        $stmt->bindValue($key, $value, PDO::PARAM_INT);
    }

    $stmt->execute();

    while ($row = $stmt->fetch()) {
        $listingId = (int) ($row['listing_id'] ?? 0);
        $total = (int) ($row['total'] ?? 0);

        if (isset($favorites[$listingId])) {
            $favorites[$listingId] = $total;
        }
    }

    $result = [];

    foreach ($listingIds as $id) {
        $result[$id] = [
            'comments' => $comments[$id] ?? 0,
            'favorites' => $favorites[$id] ?? 0,
        ];
    }

    return $result;
}

/**
 * Получить агрегированную статистику по объявлению.
 *
 * @return array<string, mixed>|null
 */
function get_listing_statistics(int $listingId): ?array
{
    $pdo = db();

    $stmt = $pdo->prepare(
        'SELECT id, title, status, price, views_count, last_viewed_at, created_at, updated_at '
        . 'FROM listings WHERE id = :id LIMIT 1'
    );

    $stmt->bindValue(':id', $listingId, PDO::PARAM_INT);
    $stmt->execute();

    $row = $stmt->fetch();

    if ($row === false) {
        return null;
    }

    $counters = get_related_counters([$listingId]);
    $counts = $counters[$listingId] ?? ['comments' => 0, 'favorites' => 0];

    return [
        'id' => (int) $row['id'],
        'title' => (string) ($row['title'] ?? ''),
        'status' => (string) ($row['status'] ?? 'active'),
        'price' => (float) ($row['price'] ?? 0),
        'views_count' => (int) ($row['views_count'] ?? 0),
        'last_viewed_at' => $row['last_viewed_at'] ?? null,
        'created_at' => (string) ($row['created_at'] ?? ''),
        'updated_at' => (string) ($row['updated_at'] ?? ''),
        'comments' => (int) ($counts['comments'] ?? 0),
        'favorites' => (int) ($counts['favorites'] ?? 0),
    ];
}
