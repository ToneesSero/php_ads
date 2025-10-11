<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', config('app.name', 'ПоРукам'))</title>

        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        @if (file_exists(public_path('build/manifest.json')))
            @vite(['resources/js/app.js'])
        @endif
    </head>
    <body class="bg-light" data-auth="{{ auth()->check() ? '1' : '0' }}">
        @php
            $unreadMessages = 0;

            try {
                $unreadMessages = \App\Facades\UnreadMessages::count();
            } catch (\Throwable $exception) {
                $unreadMessages = 0;
            }
        @endphp

        <div id="app" class="min-vh-100 d-flex flex-column">
            <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm">
                <div class="container">
                    <a class="navbar-brand fw-bold text-primary" href="{{ url('/listings') }}">ПоРукам</a>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>

                    <div class="collapse navbar-collapse" id="mainNavbar">
                        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('listings*') ? 'active' : '' }}" href="{{ url('/listings') }}">Объявления</a>
                            </li>

                            @auth
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ url('/listings/create') }}">Добавить объявление</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ url('/profile/listings') }}">Мои объявления</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ url('/favorites') }}">Избранное</a>
                                </li>
                                <li class="nav-item position-relative">
                                    <a class="nav-link" href="{{ url('/messages') }}">
                                        Сообщения
                                        @if ($unreadMessages > 0)
                                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                                {{ $unreadMessages }}
                                                <span class="visually-hidden">непрочитанные сообщения</span>
                                            </span>
                                        @endif
                                    </a>
                                </li>
                            @else
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ url('/favorites') }}">Избранное</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ url('/messages') }}">Сообщения</a>
                                </li>
                            @endauth
                        </ul>

                        <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center gap-lg-2">
                            @auth
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('dashboard') }}">Кабинет</a>
                                </li>
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        {{ auth()->user()->name }}
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('profile.edit') }}">Профиль</a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form method="POST" action="{{ route('logout') }}">
                                                @csrf
                                                <button type="submit" class="dropdown-item text-danger">Выйти</button>
                                            </form>
                                        </li>
                                    </ul>
                                </li>
                            @else
                                @if (Route::has('login'))
                                    <li class="nav-item">
                                        <a class="btn btn-outline-primary" href="{{ route('login') }}">Войти</a>
                                    </li>
                                @endif
                                @if (Route::has('register'))
                                    <li class="nav-item">
                                        <a class="btn btn-primary" href="{{ route('register') }}">Регистрация</a>
                                    </li>
                                @endif
                            @endauth
                        </ul>
                    </div>
                </div>
            </nav>

            <main class="flex-fill py-4">
                <div class="container">
                    @yield('content')
                </div>
            </main>

            <footer class="bg-white border-top py-3">
                <div class="container text-center text-muted small">
                    &copy; {{ date('Y') }} {{ config('app.name', 'ПоРукам') }}
                </div>
            </footer>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
        @stack('scripts')
        @yield('scripts')
    </body>
</html>
