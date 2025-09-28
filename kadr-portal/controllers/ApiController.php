<?php

declare(strict_types=1);

namespace KadrPortal\Controllers;

use PDO;
use RuntimeException;
use Throwable;

use function KadrPortal\Helpers\current_user;
use function KadrPortal\Helpers\db;

class ApiController
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = db();
    }

    public function listings(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $page = max(1, (int) ($_GET['page'] ?? 1));
            $limit = (int) ($_GET['limit'] ?? 10);
            $limit = max(1, min(50, $limit));
            $offset = ($page - 1) * $limit;

            [$whereClause, $bindings, $appliedFilters] = $this->buildFilters($_GET ?? []);
            $user = current_user();
            $userId = $user !== null ? (int) $user['id'] : null;
            $favoriteSelect = $userId !== null ? ', (f.id IS NOT NULL) AS is_favorite' : ', FALSE AS is_favorite';
            $favoriteJoin = $userId !== null
                ? 'LEFT JOIN favorites AS f ON f.listing_id = l.id AND f.user_id = :favorite_user_id'
                : '';
            $sql = <<<SQL
SELECT
    l.id,
    l.title,
    l.description,
    l.price,
    l.created_at,
    c.name AS category_name,
    u.name AS author_name,
    img.image_path AS main_image_path
    {$favoriteSelect}
FROM listings AS l
LEFT JOIN categories AS c ON c.id = l.category_id
INNER JOIN users AS u ON u.id = l.user_id
LEFT JOIN LATERAL (
    SELECT image_path
    FROM listing_images
    WHERE listing_id = l.id
    ORDER BY is_main DESC, id ASC
    LIMIT 1
) AS img ON TRUE
{$favoriteJoin}
$whereClause
ORDER BY l.created_at DESC
LIMIT :limit OFFSET :offset
SQL;

            $stmt = $this->pdo->prepare($sql);

            foreach ($bindings as $key => $value) {
                if (is_int($value)) {
                    $stmt->bindValue($key, $value, PDO::PARAM_INT);
                    continue;
                }

                $stmt->bindValue($key, $value);
            }

            if ($userId !== null) {
                $stmt->bindValue(':favorite_user_id', $userId, PDO::PARAM_INT);
            }

            $stmt->bindValue(':limit', $limit + 1, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            $rows = $stmt->fetchAll();
            $hasMore = false;

            if (count($rows) > $limit) {
                $hasMore = true;
                array_pop($rows);
            }

            $data = [];

            foreach ($rows as $row) {
                $path = isset($row['main_image_path']) && is_string($row['main_image_path']) ? $row['main_image_path'] : null;

                $thumb = $this->buildThumbPath($path);

                $data[] = [
                    'id' => (int) $row['id'],
                    'title' => (string) $row['title'],
                    'description' => (string) $row['description'],
                    'price' => (float) $row['price'],
                    'created_at' => (string) $row['created_at'],
                    'category_name' => $row['category_name'] ?? null,
                    'author_name' => $row['author_name'] ?? null,
                    'main_image_path' => $path,
                    'main_image_thumb' => $thumb ?? $path,
                    'url' => '/listings/' . (int) $row['id'],
                    'is_favorite' => isset($row['is_favorite']) ? (bool) $row['is_favorite'] : false,
                ];
            }

            $response = [
                'data' => $data,
                'pagination' => [
                    'currentPage' => $page,
                    'hasMore' => $hasMore,
                    'nextPage' => $hasMore ? $page + 1 : null,
                ],
                'filters' => $appliedFilters,
            ];

            echo json_encode($response, JSON_UNESCAPED_UNICODE);
        } catch (RuntimeException $exception) {
            http_response_code(400);
            echo json_encode([
                'error' => $exception->getMessage(),
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $exception) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Не удалось загрузить объявления.',
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return array{0:string,1:array<string, int|float|string|null>,2:array<string, int|float|string|null>}
     */
    private function buildFilters(array $query): array
    {
        $filters = ["l.status = 'active'"];
        $bindings = [];

        $search = isset($query['search']) ? trim((string) $query['search']) : '';

        if ($search !== '') {
            $filters[] = '(l.title ILIKE :search OR l.description ILIKE :search)';
            $bindings[':search'] = '%' . $search . '%';
        }

        $category = isset($query['category']) ? trim((string) $query['category']) : '';
        $categoryId = null;

        if ($category !== '' && ctype_digit($category)) {
            $categoryId = (int) $category;

            if ($categoryId > 0) {
                $filters[] = 'l.category_id = :category_id';
                $bindings[':category_id'] = $categoryId;
            }
        }

        $minPriceRaw = isset($query['min_price']) ? str_replace(',', '.', trim((string) $query['min_price'])) : '';
        $maxPriceRaw = isset($query['max_price']) ? str_replace(',', '.', trim((string) $query['max_price'])) : '';

        $minPrice = null;
        $maxPrice = null;

        if ($minPriceRaw !== '' && is_numeric($minPriceRaw)) {
            $minPrice = max(0.0, (float) $minPriceRaw);
            $filters[] = 'l.price >= :min_price';
            $bindings[':min_price'] = $minPrice;
        }

        if ($maxPriceRaw !== '' && is_numeric($maxPriceRaw)) {
            $maxPrice = max(0.0, (float) $maxPriceRaw);
            $filters[] = 'l.price <= :max_price';
            $bindings[':max_price'] = $maxPrice;
        }

        if ($minPrice !== null && $maxPrice !== null && $minPrice > $maxPrice) {
            $temp = $minPrice;
            $minPrice = $maxPrice;
            $maxPrice = $temp;
            $bindings[':min_price'] = $minPrice;
            $bindings[':max_price'] = $maxPrice;
        }

        $whereClause = $filters !== [] ? 'WHERE ' . implode(' AND ', $filters) : '';

        return [
            $whereClause,
            $bindings,
            [
                'search' => $search,
                'category' => $categoryId,
                'min_price' => $minPrice,
                'max_price' => $maxPrice,
            ],
        ];
    }

    private function buildThumbPath(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        $lastSlash = strrpos($path, '/');

        if ($lastSlash === false) {
            return null;
        }

        $directory = substr($path, 0, $lastSlash + 1);
        $filename = substr($path, $lastSlash + 1);

        if ($filename === '') {
            return null;
        }

        return $directory . 'thumb_' . $filename;
    }
}
