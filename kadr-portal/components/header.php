<?php

declare(strict_types=1);

use function KadrPortal\Helpers\current_user;
use function KadrPortal\Helpers\csrf_token;
use function KadrPortal\Helpers\is_authenticated;
use function KadrPortal\Helpers\unread_messages_count;

$user = current_user();
$csrfToken = csrf_token();
$unreadMessages = is_authenticated() ? unread_messages_count() : 0;
?>
<header class="site-header">
    <div class="container header-inner">
        <a class="logo" href="/">Kadr Portal</a>
        <nav class="main-nav">
            <ul>
                <li class="nav-item"><a href="/listings">Объявления</a></li>
                <?php if (is_authenticated() && $user !== null) : ?>
                    <li class="nav-item"><a href="/listings/create">Добавить объявление</a></li>
                    <li class="nav-item"><a href="/profile/listings">Мои объявления</a></li>
                    <li class="nav-item"><a href="/favorites">Избранное</a></li>
                    <li class="nav-item"><a href="/messages">Сообщения<?php if ($unreadMessages > 0) : ?><span class="nav-badge" aria-label="Непрочитанные сообщения"><?= (int) $unreadMessages; ?></span><?php endif; ?></a></li>
                    <li class="nav-item">Здравствуйте, <?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?></li>
                    <li class="nav-item">
                        <form action="/logout" method="post" class="logout-form">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                            <button type="submit" class="button button-link">Выйти</button>
                        </form>
                    </li>
                <?php else : ?>
                    <li class="nav-item"><a href="/favorites">Избранное</a></li>
                    <li class="nav-item"><a href="/messages">Сообщения</a></li>
                    <li class="nav-item"><a href="/login">Вход</a></li>
                    <li class="nav-item"><a href="/register">Регистрация</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>
