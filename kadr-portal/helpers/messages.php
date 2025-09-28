<?php

declare(strict_types=1);

namespace KadrPortal\Helpers;

use PDO;

/**
 * Получить количество непрочитанных входящих сообщений для текущего пользователя.
 */
function unread_messages_count(): int
{
    if (!is_authenticated()) {
        return 0;
    }

    static $cache = null;

    if ($cache !== null) {
        return $cache;
    }

    $user = current_user();

    if ($user === null) {
        $cache = 0;

        return $cache;
    }

    $pdo = db();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM user_messages WHERE recipient_id = :user_id AND is_read = FALSE');
    $stmt->bindValue(':user_id', (int) $user['id'], PDO::PARAM_INT);
    $stmt->execute();

    $count = (int) $stmt->fetchColumn();
    $cache = $count;

    return $cache;
}
