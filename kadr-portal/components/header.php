<?php

declare(strict_types=1);

use function KadrPortal\Helpers\current_user;
use function KadrPortal\Helpers\csrf_token;
use function KadrPortal\Helpers\is_authenticated;

$user = current_user();
$csrfToken = csrf_token();
?>
<header class="site-header">
    <div class="container header-inner">
        <a class="logo" href="/">Kadr Portal</a>
        <nav class="main-nav">
            <ul>
                <li class="nav-item"><a href="/listings">Объявления</a></li>
                <?php if (is_authenticated() && $user !== null) : ?>
                    <li class="nav-item"><a href="/listings/create">Добавить объявление</a></li>
                    <li class="nav-item">Здравствуйте, <?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?></li>
                    <li class="nav-item">
                        <form action="/logout" method="post" class="logout-form">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                            <button type="submit" class="button button-link">Выйти</button>
                        </form>
                    </li>
                <?php else : ?>
                    <li class="nav-item"><a href="/login">Вход</a></li>
                    <li class="nav-item"><a href="/register">Регистрация</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>
