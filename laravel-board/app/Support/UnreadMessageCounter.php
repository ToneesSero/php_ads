<?php

namespace App\Support;

/**
 * Простейший счётчик непрочитанных сообщений.
 * Пока возвращает 0, но позже сюда добавим работу с базой данных.
 */
class UnreadMessageCounter
{
    public function count(): int
    {
        return 0;
    }
}
