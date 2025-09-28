<?php

declare(strict_types=1);

namespace KadrPortal\Controllers;

use PDO;

use function KadrPortal\Helpers\csrf_token;
use function KadrPortal\Helpers\current_user;
use function KadrPortal\Helpers\db;
use function KadrPortal\Helpers\is_authenticated;
use function KadrPortal\Helpers\verify_csrf_token;

class FavoriteController
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = db();
    }

    public function index(): void
    {
        $user = $this->requireUser();

        if ($user === null) {
            return;
        }

        $favorites = $this->fetchFavorites((int) $user['id']);
        $csrfToken = csrf_token();

        header('Content-Type: text/html; charset=utf-8');
        require __DIR__ . '/../templates/favorites/index.php';
    }

    public function toggle(): void
    {
        if (!is_authenticated()) {
            $this->json(['error' => 'Требуется авторизация.'], 401);
            return;
        }

        $user = current_user();

        if ($user === null) {
            $this->json(['error' => 'Пользователь не найден.'], 401);
            return;
        }

        $listingId = isset($_POST['listing_id']) ? (int) $_POST['listing_id'] : 0;
        $token = $_POST['csrf_token'] ?? null;

        if (!verify_csrf_token(is_string($token) ? $token : null)) {
            $this->json(['error' => 'Неверный CSRF токен.'], 422);
            return;
        }

        if ($listingId <= 0) {
            $this->json(['error' => 'Некорректное объявление.'], 400);
            return;
        }

        $action = is_string($_POST['action'] ?? null) ? strtolower((string) $_POST['action']) : 'toggle';
        $existingId = $this->getFavoriteId((int) $user['id'], $listingId);

        if ($existingId !== null) {
            if ($action === 'remove' || $action === 'toggle') {
                $stmt = $this->pdo->prepare('DELETE FROM favorites WHERE id = :id');
                $stmt->bindValue(':id', $existingId, PDO::PARAM_INT);
                $stmt->execute();

                $this->json([
                    'status' => 'removed',
                    'favorite' => false,
                ]);

                return;
            }

            $this->json([
                'status' => 'exists',
                'favorite' => true,
            ]);

            return;
        }

        if ($action === 'remove') {
            $this->json([
                'status' => 'skipped',
                'favorite' => false,
            ]);

            return;
        }

        if (!$this->listingExists($listingId)) {
            $this->json(['error' => 'Объявление не найдено.'], 404);
            return;
        }
        if ($action === 'add' || $action === 'toggle') {
            $stmt = $this->pdo->prepare(
                'INSERT INTO favorites (user_id, listing_id) VALUES (:user_id, :listing_id)'
            );
            $stmt->bindValue(':user_id', (int) $user['id'], PDO::PARAM_INT);
            $stmt->bindValue(':listing_id', $listingId, PDO::PARAM_INT);
            $stmt->execute();

            $this->json([
                'status' => 'added',
                'favorite' => true,
            ], 201);

            return;
        }

        $this->json([
            'status' => 'skipped',
            'favorite' => false,
        ]);
    }

    private function fetchFavorites(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT f.created_at, l.id, l.title, l.description, l.price, l.created_at AS listing_created_at, '
            . 'c.name AS category_name, u.name AS author_name, img.image_path AS main_image_path '
            . 'FROM favorites AS f '
            . 'INNER JOIN listings AS l ON l.id = f.listing_id '
            . 'LEFT JOIN categories AS c ON c.id = l.category_id '
            . 'INNER JOIN users AS u ON u.id = l.user_id '
            . 'LEFT JOIN LATERAL ('
            . '    SELECT image_path'
            . '    FROM listing_images'
            . '    WHERE listing_id = l.id'
            . '    ORDER BY is_main DESC, id ASC'
            . '    LIMIT 1'
            . ') AS img ON TRUE '
            . "WHERE f.user_id = :user_id AND l.status = 'active' "
            . 'ORDER BY f.created_at DESC'
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll();
        $favorites = [];

        foreach ($rows as $row) {
            $path = isset($row['main_image_path']) && is_string($row['main_image_path']) ? $row['main_image_path'] : null;
            $favorites[] = [
                'id' => (int) ($row['id'] ?? 0),
                'title' => (string) ($row['title'] ?? ''),
                'description' => (string) ($row['description'] ?? ''),
                'price' => (float) ($row['price'] ?? 0),
                'created_at' => (string) ($row['listing_created_at'] ?? ''),
                'category_name' => $row['category_name'] ?? null,
                'author_name' => $row['author_name'] ?? null,
                'main_image_path' => $path,
                'main_image_thumb' => $this->buildThumbPath($path) ?? $path,
                'is_favorite' => true,
            ];
        }

        return $favorites;
    }

    private function getFavoriteId(int $userId, int $listingId): ?int
    {
        $stmt = $this->pdo->prepare('SELECT id FROM favorites WHERE user_id = :user_id AND listing_id = :listing_id');
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':listing_id', $listingId, PDO::PARAM_INT);
        $stmt->execute();

        $id = $stmt->fetchColumn();

        return $id !== false ? (int) $id : null;
    }

    private function listingExists(int $listingId): bool
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM listings WHERE id = :id AND status = 'active'");
        $stmt->bindValue(':id', $listingId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchColumn() !== false;
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

    private function requireUser(): ?array
    {
        if (!is_authenticated()) {
            header('Location: /login', true, 303);
            return null;
        }

        $user = current_user();

        if ($user === null) {
            header('Location: /login', true, 303);
            return null;
        }

        return $user;
    }

    private function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}
