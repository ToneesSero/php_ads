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
   curl http://localhost:8081
   ```
5. Убедитесь, что список объявлений открывается:
   ```bash
   curl http://localhost:8081/listings
   ```

## Laravel board окружение

1. Скопируйте настройки окружения будущего Laravel приложения:
   ```bash
   cp laravel-board/.env.example laravel-board/.env
   ```
2. Соберите и поднимите инфраструктуру Laravel доски объявлений:
   ```bash
   docker compose -f docker-compose.laravel.yml up -d --build
   ```
3. Проверьте, что Nginx отвечает на новом порту:
   ```bash
   curl http://localhost:8082
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

## Проверка функционала объявлений

```bash
# Список объявлений с фильтрами
curl "http://localhost:8081/listings?search=php"

# Карточка конкретного объявления
curl http://localhost:8081/listings/1

# Создание объявления (требуется авторизованный пользователь)
curl -X POST http://localhost:8081/listings \
  -d "title=Test" \
  -d "description=Test description" \
  -d "price=1000"
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
