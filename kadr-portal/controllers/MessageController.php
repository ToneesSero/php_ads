<?php

declare(strict_types=1);

namespace KadrPortal\Controllers;

use PDO;

use function KadrPortal\Helpers\csrf_token;
use function KadrPortal\Helpers\current_user;
use function KadrPortal\Helpers\db;
use function KadrPortal\Helpers\is_authenticated;
use function KadrPortal\Helpers\verify_csrf_token;

class MessageController
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

        $conversations = $this->loadConversations((int) $user['id']);
        $csrfToken = csrf_token();

        header('Content-Type: text/html; charset=utf-8');
        require __DIR__ . '/../templates/messages/index.php';
    }

    public function conversation(string $id): void
    {
        $user = $this->requireUser();

        if ($user === null) {
            return;
        }

        $otherId = (int) $id;

        if ($otherId <= 0) {
            http_response_code(404);
            echo 'Пользователь не найден.';
            return;
        }

        $otherUser = $this->findUser($otherId);

        if ($otherUser === null) {
            http_response_code(404);
            echo 'Пользователь не найден.';
            return;
        }

        $messages = $this->loadConversation((int) $user['id'], $otherId);
        $this->markAsRead($otherId, (int) $user['id']);
        $csrfToken = csrf_token();

        header('Content-Type: text/html; charset=utf-8');
        require __DIR__ . '/../templates/messages/conversation.php';
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

        $recipientId = isset($_POST['to_user_id']) ? (int) $_POST['to_user_id'] : 0;
        $listingId = isset($_POST['listing_id']) ? (int) $_POST['listing_id'] : null;
        $token = $_POST['csrf_token'] ?? null;

        if (!verify_csrf_token(is_string($token) ? $token : null)) {
            $this->json(['error' => 'Неверный CSRF токен.'], 422);
            return;
        }

        if ($recipientId <= 0) {
            $this->json(['error' => 'Некорректный получатель.'], 400);
            return;
        }

        if ($recipientId === (int) $user['id']) {
            $this->json(['error' => 'Нельзя отправить сообщение самому себе.'], 422);
            return;
        }

        if ($this->findUser($recipientId) === null) {
            $this->json(['error' => 'Пользователь не найден.'], 404);
            return;
        }

        if ($listingId !== null && $listingId <= 0) {
            $listingId = null;
        }

        $listingTitle = null;

        if ($listingId !== null) {
            $listingTitle = $this->getListingTitle($listingId);

            if ($listingTitle === null) {
                $this->json(['error' => 'Объявление не найдено.'], 404);
                return;
            }
        }

        $text = isset($_POST['text']) ? trim((string) $_POST['text']) : '';
        $cleanText = $this->sanitizeMessage($text);

        if ($cleanText === '') {
            $this->json(['error' => 'Введите текст сообщения.'], 422);
            return;
        }

        if (mb_strlen($cleanText) > 1500) {
            $this->json(['error' => 'Сообщение не должно превышать 1500 символов.'], 422);
            return;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO user_messages (sender_id, recipient_id, listing_id, message_text) '
            . 'VALUES (:sender_id, :recipient_id, :listing_id, :message_text) '
            . 'RETURNING id, created_at'
        );
        $stmt->bindValue(':sender_id', (int) $user['id'], PDO::PARAM_INT);
        $stmt->bindValue(':recipient_id', $recipientId, PDO::PARAM_INT);

        if ($listingId === null) {
            $stmt->bindValue(':listing_id', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':listing_id', $listingId, PDO::PARAM_INT);
        }

        $stmt->bindValue(':message_text', $cleanText);
        $stmt->execute();

        $row = $stmt->fetch();

        if ($row === false) {
            $this->json(['error' => 'Не удалось сохранить сообщение.'], 500);
            return;
        }

        $messageId = (int) ($row['id'] ?? 0);
        $createdAt = (string) ($row['created_at'] ?? '');

        $this->json([
            'message' => [
                'id' => $messageId,
                'text' => $cleanText,
                'created_at' => $createdAt,
                'created_at_formatted' => $this->formatDate($createdAt),
                'own' => true,
                'listing_id' => $listingId,
                'listing_title' => $listingTitle,
            ],
        ], 201);
    }

    private function loadConversations(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT m.id, m.sender_id, m.recipient_id, m.message_text, m.created_at, m.is_read, '
            . 'm.listing_id, s.name AS sender_name, r.name AS recipient_name, l.title AS listing_title '
            . 'FROM user_messages AS m '
            . 'LEFT JOIN users AS s ON s.id = m.sender_id '
            . 'LEFT JOIN users AS r ON r.id = m.recipient_id '
            . 'LEFT JOIN listings AS l ON l.id = m.listing_id '
            . 'WHERE m.sender_id = :user_id OR m.recipient_id = :user_id '
            . 'ORDER BY m.created_at DESC'
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll();
        $conversations = [];

        foreach ($rows as $row) {
            $senderId = (int) ($row['sender_id'] ?? 0);
            $recipientId = (int) ($row['recipient_id'] ?? 0);

            $otherId = $senderId === $userId ? $recipientId : $senderId;

            if ($otherId <= 0) {
                continue;
            }

            $otherName = $senderId === $userId
                ? (string) ($row['recipient_name'] ?? 'Пользователь')
                : (string) ($row['sender_name'] ?? 'Пользователь');

            if (!isset($conversations[$otherId])) {
                $conversations[$otherId] = [
                    'user_id' => $otherId,
                    'user_name' => $otherName,
                    'last_message' => '',
                    'last_message_at' => '',
                    'last_message_formatted' => '',
                    'unread_count' => 0,
                    'listing_title' => null,
                ];
            }

            if ($recipientId === $userId && !(bool) ($row['is_read'] ?? false)) {
                $conversations[$otherId]['unread_count']++;
            }

            $createdAt = (string) ($row['created_at'] ?? '');

            if (
                $conversations[$otherId]['last_message_at'] === ''
                || strtotime($conversations[$otherId]['last_message_at']) < strtotime($createdAt)
            ) {
                $conversations[$otherId]['last_message'] = (string) ($row['message_text'] ?? '');
                $conversations[$otherId]['last_message_at'] = $createdAt;
                $conversations[$otherId]['last_message_formatted'] = $this->formatDate($createdAt);
                $conversations[$otherId]['listing_title'] = $row['listing_title'] ?? null;
            }
        }

        usort(
            $conversations,
            static fn (array $a, array $b): int => strcmp($b['last_message_at'], $a['last_message_at'])
        );

        return $conversations;
    }

    private function loadConversation(int $userId, int $otherId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT m.id, m.sender_id, m.recipient_id, m.message_text, m.created_at, m.is_read, m.listing_id, '
            . 'l.title AS listing_title '
            . 'FROM user_messages AS m '
            . 'LEFT JOIN listings AS l ON l.id = m.listing_id '
            . 'WHERE (m.sender_id = :user_id AND m.recipient_id = :other_id) '
            . '   OR (m.sender_id = :other_id AND m.recipient_id = :user_id) '
            . 'ORDER BY m.created_at ASC'
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':other_id', $otherId, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll();
        $messages = [];

        foreach ($rows as $row) {
            $createdAt = (string) ($row['created_at'] ?? '');

            $messages[] = [
                'id' => (int) ($row['id'] ?? 0),
                'text' => (string) ($row['message_text'] ?? ''),
                'own' => (int) ($row['sender_id'] ?? 0) === $userId,
                'created_at' => $createdAt,
                'created_at_formatted' => $this->formatDate($createdAt),
                'is_read' => (bool) ($row['is_read'] ?? false),
                'listing_title' => $row['listing_title'] ?? null,
            ];
        }

        return $messages;
    }

    private function markAsRead(int $senderId, int $recipientId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE user_messages SET is_read = TRUE '
            . 'WHERE sender_id = :sender_id AND recipient_id = :recipient_id AND is_read = FALSE'
        );
        $stmt->bindValue(':sender_id', $senderId, PDO::PARAM_INT);
        $stmt->bindValue(':recipient_id', $recipientId, PDO::PARAM_INT);
        $stmt->execute();
    }

    private function findUser(int $userId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, name FROM users WHERE id = :id');
        $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        return [
            'id' => (int) ($row['id'] ?? 0),
            'name' => (string) ($row['name'] ?? ''),
        ];
    }

    private function getListingTitle(int $listingId): ?string
    {
        $stmt = $this->pdo->prepare('SELECT title FROM listings WHERE id = :id');
        $stmt->bindValue(':id', $listingId, PDO::PARAM_INT);
        $stmt->execute();

        $title = $stmt->fetchColumn();

        if ($title === false) {
            return null;
        }

        return (string) $title;
    }

    private function sanitizeMessage(string $text): string
    {
        $stripped = strip_tags($text);
        $normalized = preg_replace('/\s+/u', ' ', $stripped) ?? '';

        return trim($normalized);
    }

    private function formatDate(string $date): string
    {
        $timestamp = strtotime($date);

        if ($timestamp === false) {
            return $date;
        }

        return date('d.m.Y H:i', $timestamp);
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
