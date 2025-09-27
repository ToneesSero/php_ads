# Kadr Portal

Базовая инфраструктура для учебного проекта «Доска объявлений».

## Стек
- PHP 8.2 (php-fpm)
- PostgreSQL 15
- Nginx
- Docker Compose

## Быстрый старт
1. Скопируйте файл окружения и отредактируйте при необходимости:
   ```bash
   cp .env.example .env
   ```
2. Запустите инфраструктуру:
   ```bash
   docker compose up -d
   ```
3. Выполните миграции:
   ```bash
   make migrate
   ```
4. Проверьте работу главной страницы:
   ```bash
   curl http://localhost:8080
   ```

## Полезные команды
```bash
make up       # Поднять контейнеры
make down     # Остановить контейнеры
make migrate  # Применить миграцию 001_init.sql
make logs     # Логи всех сервисов
make shell    # Shell внутри PHP контейнера
make db-shell # Консоль psql
```

## Структура
```
kadr-portal/
├── components/
├── configs/
├── controllers/
├── helpers/
├── public/
│   ├── assets/
│   ├── layouts/
│   └── uploads/
├── templates/
├── migrations/
└── docker/
```
