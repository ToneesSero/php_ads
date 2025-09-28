<?php

declare(strict_types=1);

namespace KadrPortal\Controllers;

use PDO;
use function KadrPortal\Helpers\current_user;
use function KadrPortal\Helpers\db;
use function KadrPortal\Helpers\ensureSession;
use function KadrPortal\Helpers\is_authenticated;
use function KadrPortal\Helpers\verify_csrf_token;

class CommentController
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = db();
    }

    public function index(string $listingId): void
    {
        $id = (int) $listingId;

        if ($id <= 0) {
            $this->json(['error' => 'Некорректный идентификатор объявления.'], 400);
            return;
        }

        if (!$this->listingExists($id)) {
            $this->json(['error' => 'Объявление не найдено.'], 404);
            return;
        }

        $afterRaw = $_GET['after'] ?? null;
        $afterId = null;

        if (is_string($afterRaw) && ctype_digit($afterRaw)) {
            $afterId = (int) $afterRaw;
        }

        $user = current_user();

        if ($afterId !== null && $afterId > 0) {
            $stmt = $this->pdo->prepare(
                'SELECT lc.id, lc.comment_text, lc.created_at, lc.user_id, u.name AS author_name
                FROM listing_comments AS lc
                INNER JOIN users AS u ON u.id = lc.user_id
                WHERE lc.listing_id = :listing_id AND lc.id > :after_id
                ORDER BY lc.id ASC
                LIMIT 50'
            );
            $stmt->bindValue(':after_id', $afterId, PDO::PARAM_INT);
        } else {
            $stmt = $this->pdo->prepare(
                'SELECT lc.id, lc.comment_text, lc.created_at, lc.user_id, u.name AS author_name
                FROM listing_comments AS lc
                INNER JOIN users AS u ON u.id = lc.user_id
                WHERE lc.listing_id = :listing_id
                ORDER BY lc.id ASC
                LIMIT 50'
            );
        }

        $stmt->bindValue(':listing_id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll();
        $comments = [];
        $lastId = $afterId ?? 0;

        foreach ($rows as $row) {
            $commentId = (int) ($row['id'] ?? 0);
            $authorId = (int) ($row['user_id'] ?? 0);
            $createdAt = (string) ($row['created_at'] ?? '');

            if ($commentId > $lastId) {
                $lastId = $commentId;
            }

            $comments[] = [
                'id' => $commentId,
                'author' => [
                    'id' => $authorId,
                    'name' => (string) ($row['author_name'] ?? 'Неизвестно'),
                ],
                'text' => (string) ($row['comment_text'] ?? ''),
                'created_at' => $createdAt,
                'created_at_formatted' => $this->formatDate($createdAt),
                'own' => $user !== null && (int) $user['id'] === $authorId,
            ];
        }

        $this->json([
            'comments' => $comments,
            'last_comment_id' => $lastId,
            'count' => count($comments),
        ]);
    }

    public function store(): void
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

        $text = isset($_POST['comment_text']) ? trim((string) $_POST['comment_text']) : '';
        $cleanText = $this->sanitizeComment($text);

        if ($cleanText === '') {
            $this->json(['error' => 'Введите текст комментария.'], 422);
            return;
        }

        if (mb_strlen($cleanText) > 1000) {
            $this->json(['error' => 'Комментарий не должен превышать 1000 символов.'], 422);
            return;
        }

        if (!$this->listingExists($listingId)) {
            $this->json(['error' => 'Объявление не найдено.'], 404);
            return;
        }

        $rateLimitError = $this->checkRateLimit((int) $user['id']);

        if ($rateLimitError !== null) {
            $this->json(['error' => $rateLimitError], 429);
            return;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO listing_comments (listing_id, user_id, comment_text)
            VALUES (:listing_id, :user_id, :text)
            RETURNING id, created_at'
        );
        $stmt->execute([
            ':listing_id' => $listingId,
            ':user_id' => (int) $user['id'],
            ':text' => $cleanText,
        ]);

        $row = $stmt->fetch();

        if ($row === false) {
            $this->json(['error' => 'Не удалось сохранить комментарий.'], 500);
            return;
        }

        $commentId = (int) ($row['id'] ?? 0);
        $createdAt = (string) ($row['created_at'] ?? '');

        $this->json([
            'comment' => [
                'id' => $commentId,
                'author' => [
                    'id' => (int) $user['id'],
                    'name' => (string) $user['name'],
                ],
                'text' => $cleanText,
                'created_at' => $createdAt,
                'created_at_formatted' => $this->formatDate($createdAt),
                'own' => true,
            ],
        ], 201);
    }

    public function destroy(string $commentId): void
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

        $id = (int) $commentId;

        if ($id <= 0) {
            $this->json(['error' => 'Некорректный идентификатор комментария.'], 400);
            return;
        }

        $token = $_POST['csrf_token'] ?? null;

        if (!verify_csrf_token(is_string($token) ? $token : null)) {
            $this->json(['error' => 'Неверный CSRF токен.'], 422);
            return;
        }

        $stmt = $this->pdo->prepare(
            'SELECT user_id FROM listing_comments WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);
        $ownerId = $stmt->fetchColumn();

        if ($ownerId === false) {
            $this->json(['error' => 'Комментарий не найден.'], 404);
            return;
        }

        if ((int) $ownerId !== (int) $user['id']) {
            $this->json(['error' => 'Удалять комментарий может только автор.'], 403);
            return;
        }

        $deleteStmt = $this->pdo->prepare('DELETE FROM listing_comments WHERE id = :id');
        $deleteStmt->execute([':id' => $id]);

        $this->json(['status' => 'deleted']);
    }

    private function sanitizeComment(string $text): string
    {
        $stripped = strip_tags($text);
        $normalized = preg_replace('/\s+/u', ' ', $stripped) ?? '';

        return trim($normalized);
    }

    private function listingExists(int $listingId): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM listings WHERE id = :id');
        $stmt->execute([':id' => $listingId]);

        return $stmt->fetchColumn() !== false;
    }

    private function checkRateLimit(int $userId): ?string
    {
        ensureSession();

        if (!isset($_SESSION['_comment_rate'])) {
            $_SESSION['_comment_rate'] = [];
        }

        $now = time();
        $window = 60; // seconds
        $maxPerWindow = 5;

        $history = $_SESSION['_comment_rate'][$userId] ?? [];

        if (!is_array($history)) {
            $history = [];
        }

        $history = array_filter(
            $history,
            static fn ($timestamp): bool => is_int($timestamp) && ($now - $timestamp) < $window
        );

        if (count($history) >= $maxPerWindow) {
            $_SESSION['_comment_rate'][$userId] = array_values($history);

            return 'Слишком много комментариев. Попробуйте позже.';
        }

        $history[] = $now;
        $_SESSION['_comment_rate'][$userId] = array_values($history);

        return null;
    }

    private function formatDate(string $date): string
    {
        $timestamp = strtotime($date);

        if ($timestamp === false) {
            return $date;
        }

        return date('d.m.Y H:i', $timestamp);
    }

    private function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}
