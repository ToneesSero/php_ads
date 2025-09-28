<?php

declare(strict_types=1);

namespace KadrPortal\Controllers;

use PDO;
use Throwable;

use function KadrPortal\Helpers\current_user;
use function KadrPortal\Helpers\csrf_token;
use function KadrPortal\Helpers\db;
use function KadrPortal\Helpers\get_related_counters;
use function KadrPortal\Helpers\get_listing_statistics;
use function KadrPortal\Helpers\is_authenticated;
use function KadrPortal\Helpers\validate_listing;
use function KadrPortal\Helpers\verify_csrf_token;

class ProfileController
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = db();
    }

    public function listings(): void
    {
        $user = $this->requireUser();

        if ($user === null) {
            return;
        }

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        $statusFilter = isset($_GET['status']) ? strtolower(trim((string) $_GET['status'])) : 'all';
        $allowedStatus = ['all', 'active', 'inactive'];

        if (!in_array($statusFilter, $allowedStatus, true)) {
            $statusFilter = 'all';
        }

        $categoryRaw = trim((string) ($_GET['category'] ?? ''));
        $categoryId = null;

        if ($categoryRaw !== '' && ctype_digit($categoryRaw)) {
            $categoryId = (int) $categoryRaw;
        }

        $fromRaw = trim((string) ($_GET['date_from'] ?? ''));
        $toRaw = trim((string) ($_GET['date_to'] ?? ''));
        $dateFrom = $this->normalizeDate($fromRaw);
        $dateTo = $this->normalizeDate($toRaw);

        $filters = ['l.user_id = :user_id'];
        $bindings = [':user_id' => (int) $user['id']];

        if ($statusFilter !== 'all') {
            $filters[] = 'l.status = :status';
            $bindings[':status'] = $statusFilter;
        }

        if ($categoryId !== null) {
            $filters[] = 'l.category_id = :category_id';
            $bindings[':category_id'] = $categoryId;
        }

        if ($dateFrom !== null) {
            $filters[] = 'l.created_at >= :date_from';
            $bindings[':date_from'] = $dateFrom . ' 00:00:00';
        }

        if ($dateTo !== null) {
            $filters[] = 'l.created_at <= :date_to';
            $bindings[':date_to'] = $dateTo . ' 23:59:59';
        }

        $where = 'WHERE ' . implode(' AND ', $filters);

        $totalsSql = 'SELECT COUNT(*) AS total_listings, COALESCE(SUM(views_count), 0) AS total_views FROM listings AS l ' . $where;
        $totalsStmt = $this->pdo->prepare($totalsSql);

        foreach ($bindings as $key => $value) {
            if (is_int($value)) {
                $totalsStmt->bindValue($key, $value, PDO::PARAM_INT);
                continue;
            }

            $totalsStmt->bindValue($key, $value);
        }

        $totalsStmt->execute();
        $totalsRow = $totalsStmt->fetch();
        $totalListings = (int) ($totalsRow['total_listings'] ?? 0);
        $totalViews = (int) ($totalsRow['total_views'] ?? 0);

        $commentsStmt = $this->pdo->prepare('SELECT COUNT(*) FROM listing_comments WHERE listing_id IN (SELECT id FROM listings AS l ' . $where . ')');

        foreach ($bindings as $key => $value) {
            if (is_int($value)) {
                $commentsStmt->bindValue($key, $value, PDO::PARAM_INT);
                continue;
            }

            $commentsStmt->bindValue($key, $value);
        }

        $commentsStmt->execute();
        $totalComments = (int) $commentsStmt->fetchColumn();

        $favoritesStmt = $this->pdo->prepare('SELECT COUNT(*) FROM favorites WHERE listing_id IN (SELECT id FROM listings AS l ' . $where . ')');

        foreach ($bindings as $key => $value) {
            if (is_int($value)) {
                $favoritesStmt->bindValue($key, $value, PDO::PARAM_INT);
                continue;
            }

            $favoritesStmt->bindValue($key, $value);
        }

        $favoritesStmt->execute();
        $totalFavorites = (int) $favoritesStmt->fetchColumn();

        $sql = <<<SQL
SELECT
    l.id,
    l.title,
    l.description,
    l.price,
    l.status,
    l.created_at,
    l.updated_at,
    l.views_count,
    l.last_viewed_at,
    l.category_id,
    c.name AS category_name
FROM listings AS l
LEFT JOIN categories AS c ON c.id = l.category_id
$where
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

        $stmt->bindValue(':limit', $perPage + 1, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll();
        $hasMore = false;

        if (count($rows) > $perPage) {
            $hasMore = true;
            array_pop($rows);
        }

        $listingIds = [];
        $listings = [];

        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);

            if ($id <= 0) {
                continue;
            }

            $listingIds[] = $id;
            $listings[] = [
                'id' => $id,
                'title' => (string) ($row['title'] ?? ''),
                'description' => (string) ($row['description'] ?? ''),
                'price' => (float) ($row['price'] ?? 0),
                'status' => (string) ($row['status'] ?? 'active'),
                'created_at' => (string) ($row['created_at'] ?? ''),
                'updated_at' => (string) ($row['updated_at'] ?? ''),
                'views_count' => (int) ($row['views_count'] ?? 0),
                'last_viewed_at' => $row['last_viewed_at'] ?? null,
                'category_id' => isset($row['category_id']) ? (int) $row['category_id'] : null,
                'category_name' => $row['category_name'] ?? null,
            ];
        }

        $counters = $listingIds !== [] ? get_related_counters($listingIds) : [];

        foreach ($listings as &$listing) {
            $id = $listing['id'];
            $listing['comments'] = $counters[$id]['comments'] ?? 0;
            $listing['favorites'] = $counters[$id]['favorites'] ?? 0;
        }

        unset($listing);

        $categories = $this->fetchCategories();

        $summary = [
            'total_listings' => $totalListings,
            'total_views' => $totalViews,
            'total_comments' => $totalComments,
            'total_favorites' => $totalFavorites,
        ];

        $pagination = [
            'page' => $page,
            'per_page' => $perPage,
            'has_more' => $hasMore,
            'next_page' => $hasMore ? $page + 1 : null,
        ];

        $filtersState = [
            'status' => $statusFilter,
            'category' => $categoryId,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];

        $csrfToken = csrf_token();

        header('Content-Type: text/html; charset=utf-8');
        require __DIR__ . '/../templates/profile/my-listings.php';
    }

    public function updateStatus(string $id): void
    {
        $user = $this->requireUser(true);

        if ($user === null) {
            return;
        }

        $listingId = (int) $id;

        if ($listingId <= 0) {
            $this->json(['error' => 'Некорректное объявление.'], 404);
            return;
        }

        $payload = $this->getInputData();
        $status = isset($payload['status']) ? strtolower(trim((string) $payload['status'])) : '';

        if (!in_array($status, ['active', 'inactive'], true)) {
            $this->json(['error' => 'Недопустимый статус.'], 422);
            return;
        }

        $token = $this->extractCsrfToken($payload);

        if (!$this->verifyCsrf($token)) {
            $this->json(['error' => 'Неверный CSRF токен.'], 422);
            return;
        }

        $stmt = $this->pdo->prepare('UPDATE listings SET status = :status, updated_at = NOW() WHERE id = :id AND user_id = :user_id');
        $stmt->bindValue(':status', $status);
        $stmt->bindValue(':id', $listingId, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', (int) $user['id'], PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            $this->json(['error' => 'Объявление не найдено.'], 404);
            return;
        }

        $this->json(['status' => 'updated', 'listing_status' => $status]);
    }

    public function quickUpdate(string $id): void
    {
        $user = $this->requireUser(true);

        if ($user === null) {
            return;
        }

        $listingId = (int) $id;

        if ($listingId <= 0) {
            $this->json(['error' => 'Некорректное объявление.'], 404);
            return;
        }

        $listing = $this->findUserListing($listingId, (int) $user['id']);

        if ($listing === null) {
            $this->json(['error' => 'Объявление не найдено.'], 404);
            return;
        }

        $payload = $this->getInputData();
        $token = $this->extractCsrfToken($payload);

        if (!$this->verifyCsrf($token)) {
            $this->json(['error' => 'Неверный CSRF токен.'], 422);
            return;
        }

        $validation = validate_listing([
            'title' => (string) ($payload['title'] ?? ''),
            'description' => (string) ($payload['description'] ?? ''),
            'price' => (string) ($payload['price'] ?? ''),
            'category_id' => $listing['category_id'] !== null ? (string) $listing['category_id'] : '',
        ]);

        $errors = $validation['errors'];
        $data = $validation['data'];

        if ($errors !== []) {
            $this->json(['error' => 'Проверьте введённые данные.', 'details' => $errors], 422);
            return;
        }

        if ($data['price'] === null) {
            $this->json(['error' => 'Цена указана неверно.'], 422);
            return;
        }

        $stmt = $this->pdo->prepare(
            'UPDATE listings SET title = :title, description = :description, price = :price, updated_at = NOW() '
            . 'WHERE id = :id AND user_id = :user_id'
        );

        $stmt->bindValue(':title', $data['title']);
        $stmt->bindValue(':description', $data['description']);
        $stmt->bindValue(':price', $this->formatPrice($data['price']));
        $stmt->bindValue(':id', $listingId, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', (int) $user['id'], PDO::PARAM_INT);
        $stmt->execute();

        $this->json([
            'status' => 'updated',
            'listing' => [
                'id' => $listingId,
                'title' => $data['title'],
                'description' => $data['description'],
                'price' => $this->formatPrice($data['price']),
            ],
        ]);
    }

    public function delete(string $id): void
    {
        $user = $this->requireUser(true);

        if ($user === null) {
            return;
        }

        $listingId = (int) $id;

        if ($listingId <= 0) {
            $this->json(['error' => 'Некорректное объявление.'], 404);
            return;
        }

        $payload = $this->getInputData();
        $token = $this->extractCsrfToken($payload);

        if (!$this->verifyCsrf($token)) {
            $this->json(['error' => 'Неверный CSRF токен.'], 422);
            return;
        }

        $stmt = $this->pdo->prepare('DELETE FROM listings WHERE id = :id AND user_id = :user_id');
        $stmt->bindValue(':id', $listingId, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', (int) $user['id'], PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            $this->json(['error' => 'Объявление не найдено.'], 404);
            return;
        }

        $this->json(['status' => 'deleted']);
    }

    public function duplicate(string $id): void
    {
        $user = $this->requireUser(true);

        if ($user === null) {
            return;
        }

        $listingId = (int) $id;

        if ($listingId <= 0) {
            $this->json(['error' => 'Некорректное объявление.'], 404);
            return;
        }

        $payload = $this->getInputData($_POST);
        $token = $this->extractCsrfToken($payload);

        if (!$this->verifyCsrf($token)) {
            $this->json(['error' => 'Неверный CSRF токен.'], 422);
            return;
        }

        $listing = $this->findUserListing($listingId, (int) $user['id']);

        if ($listing === null) {
            $this->json(['error' => 'Объявление не найдено.'], 404);
            return;
        }

        $newId = null;

        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare(
                'INSERT INTO listings (user_id, category_id, title, description, price, status, views_count, last_viewed_at) '
                . 'VALUES (:user_id, :category_id, :title, :description, :price, :status, 0, NULL) RETURNING id'
            );

            $stmt->bindValue(':user_id', (int) $user['id'], PDO::PARAM_INT);
            $stmt->bindValue(':category_id', $listing['category_id'], $listing['category_id'] !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':title', $listing['title'] . ' (копия)');
            $stmt->bindValue(':description', $listing['description']);
            $stmt->bindValue(':price', $this->formatPrice((float) $listing['price']));
            $stmt->bindValue(':status', 'inactive');
            $stmt->execute();

            $newId = $stmt->fetchColumn();

            if ($newId === false) {
                throw new \RuntimeException('Не удалось создать копию.');
            }

            $newId = (int) $newId;

            $imageStmt = $this->pdo->prepare(
                'INSERT INTO listing_images (listing_id, image_path, is_main) '
                . 'SELECT :new_id, image_path, is_main FROM listing_images WHERE listing_id = :old_id'
            );

            $imageStmt->bindValue(':new_id', $newId, PDO::PARAM_INT);
            $imageStmt->bindValue(':old_id', $listingId, PDO::PARAM_INT);
            $imageStmt->execute();

            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            $this->json(['error' => 'Не удалось дублировать объявление.'], 500);
            return;
        }

        $this->json(['status' => 'duplicated', 'listing_id' => $newId]);
    }

    public function bulkAction(): void
    {
        $user = $this->requireUser(true);

        if ($user === null) {
            return;
        }

        $payload = $this->getInputData($_POST);
        $token = $this->extractCsrfToken($payload);

        if (!$this->verifyCsrf($token)) {
            $this->json(['error' => 'Неверный CSRF токен.'], 422);
            return;
        }

        $action = isset($payload['action']) ? strtolower(trim((string) $payload['action'])) : '';
        $idsRaw = $payload['ids'] ?? [];
        $ids = [];

        if (is_string($idsRaw)) {
            $idsRaw = explode(',', $idsRaw);
        }

        if (is_array($idsRaw)) {
            foreach ($idsRaw as $value) {
                if (ctype_digit((string) $value)) {
                    $ids[] = (int) $value;
                }
            }
        }

        $ids = array_values(array_unique(array_filter($ids)));

        if ($ids === []) {
            $this->json(['error' => 'Выберите объявления для действия.'], 422);
            return;
        }

        if (!in_array($action, ['delete', 'deactivate', 'activate'], true)) {
            $this->json(['error' => 'Неизвестное действие.'], 422);
            return;
        }

        $placeholders = [];
        $bindings = [':user_id' => (int) $user['id']];

        foreach ($ids as $index => $listingId) {
            $key = ':id' . $index;
            $placeholders[] = $key;
            $bindings[$key] = $listingId;
        }

        $inClause = implode(',', $placeholders);

        if ($action === 'delete') {
            $sql = 'DELETE FROM listings WHERE user_id = :user_id AND id IN (' . $inClause . ')';
        } elseif ($action === 'deactivate') {
            $sql = "UPDATE listings SET status = 'inactive', updated_at = NOW() WHERE user_id = :user_id AND id IN ($inClause)";
        } else {
            $sql = "UPDATE listings SET status = 'active', updated_at = NOW() WHERE user_id = :user_id AND id IN ($inClause)";
        }

        $stmt = $this->pdo->prepare($sql);

        foreach ($bindings as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        }

        $stmt->execute();

        $this->json(['status' => 'ok']);
    }

    public function stats(string $id): void
    {
        $user = $this->requireUser(true);

        if ($user === null) {
            return;
        }

        $listingId = (int) $id;

        if ($listingId <= 0) {
            $this->json(['error' => 'Некорректное объявление.'], 404);
            return;
        }

        $listing = $this->findUserListing($listingId, (int) $user['id']);

        if ($listing === null) {
            $this->json(['error' => 'Объявление не найдено.'], 404);
            return;
        }

        $stats = get_listing_statistics($listingId);

        if ($stats === null) {
            $this->json(['error' => 'Статистика недоступна.'], 404);
            return;
        }

        $this->json(['data' => $stats]);
    }

    private function requireUser(bool $json = false): ?array
    {
        if (!is_authenticated()) {
            if ($json) {
                $this->json(['error' => 'Требуется авторизация.'], 401);
                return null;
            }

            header('Location: /login', true, 303);
            return null;
        }

        $user = current_user();

        if ($user === null) {
            if ($json) {
                $this->json(['error' => 'Пользователь не найден.'], 401);
                return null;
            }

            header('Location: /login', true, 303);
            return null;
        }

        return $user;
    }

    private function normalizeDate(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        $date = date_create($value);

        if ($date === false) {
            return null;
        }

        return $date->format('Y-m-d');
    }

    /**
     * @return array<string, mixed>
     */
    private function getInputData(?array $fallback = null): array
    {
        if ($fallback !== null && $fallback !== []) {
            return $fallback;
        }

        $raw = file_get_contents('php://input') ?: '';
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if ($raw === '') {
            return [];
        }

        if (is_string($contentType) && str_contains(strtolower($contentType), 'application/json')) {
            $data = json_decode($raw, true);

            return is_array($data) ? $data : [];
        }

        $data = [];
        parse_str($raw, $data);

        return is_array($data) ? $data : [];
    }

    private function extractCsrfToken(array $data): ?string
    {
        if (isset($_SERVER['HTTP_X_CSRF_TOKEN']) && is_string($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            return $_SERVER['HTTP_X_CSRF_TOKEN'];
        }

        $token = $data['csrf_token'] ?? $data['_token'] ?? null;

        return is_string($token) ? $token : null;
    }

    private function verifyCsrf(?string $token): bool
    {
        return verify_csrf_token($token);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findUserListing(int $listingId, int $userId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, category_id, title, description, price FROM listings WHERE id = :id AND user_id = :user_id LIMIT 1');
        $stmt->bindValue(':id', $listingId, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $listing = $stmt->fetch();

        if ($listing === false) {
            return null;
        }

        return [
            'id' => (int) ($listing['id'] ?? 0),
            'category_id' => isset($listing['category_id']) ? (int) $listing['category_id'] : null,
            'title' => (string) ($listing['title'] ?? ''),
            'description' => (string) ($listing['description'] ?? ''),
            'price' => (float) ($listing['price'] ?? 0),
        ];
    }

    private function fetchCategories(): array
    {
        $stmt = $this->pdo->query('SELECT id, name FROM categories ORDER BY name ASC');

        return $stmt->fetchAll() ?: [];
    }

    private function formatPrice(float $price): string
    {
        return number_format($price, 2, '.', '');
    }

    private function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }
}
