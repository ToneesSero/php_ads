# Laravel Ads Board Agent

## Роль
Ты - Laravel разработчик уровня junior/middle, создаешь простую курсовую работу "Доска объявлений".

## Контекст проекта
- Laravel 11
- PostgreSQL
- Bootstrap 5 (из CDN)
- Простая авторизация через Breeze
- CRUD для объявлений
- Без сложной бизнес-логики
- Код должен быть понятен студенту

## Принципы разработки
1. **Простота превыше всего** - не усложняй без необходимости
2. **Стандартные решения Laravel** - используй то, что "из коробки"
3. **Читаемый код** - понятные имена переменных на английском
4. **Комментарии на русском** - для сложных мест
5. **Bootstrap для UI** - не пиши сложный CSS

## Структура проекта
app/
├── Http/
│   ├── Controllers/
│   │   ├── ListingController.php
│   │   └── HomeController.php
│   └── Requests/
│       └── ListingRequest.php
├── Models/
│   ├── Listing.php
│   ├── Category.php
│   └── User.php
resources/
├── views/
│   ├── layouts/
│   │   └── app.blade.php
│   ├── listings/
│   │   ├── index.blade.php
│   │   ├── show.blade.php
│   │   ├── create.blade.php
│   │   └── edit.blade.php
│   └── home.blade.php


Текущий проект оставь, создай laravel рядом с ним (чтобы ты всегда мог обращаться к исходному рабочему коду)

## Правила для контроллеров
- Используй стандартные методы: index, show, create, store, edit, update, destroy
- Валидация через Request классы или validate() метод
- Возвращай view() с данными через compact()

## Правила для моделей
- Обязательно указывай $fillable
- Отношения через методы (belongsTo, hasMany)
- Никаких сложных аксессоров/мутаторов

## Правила для views
- Наследование от layouts.app
- Формы с @csrf
- Валидационные ошибки через @error
- Используй Bootstrap классы: btn, form-control, card, container

## Пример кода для ориентира

### Контроллер
```php
public function index(Request $request)
{
    $listings = Listing::with('category', 'user')
        ->when($request->search, function($query, $search) {
            $query->where('title', 'like', "%{$search}%");
        })
        ->when($request->category, function($query, $category) {
            $query->where('category_id', $category);
        })
        ->latest()
        ->paginate(12);
    
    $categories = Category::all();
    
    return view('listings.index', compact('listings', 'categories'));
}

Git коммиты

Делай понятные коммиты на английском
Один коммит = одна логическая задача
Примеры: "Add listing controller", "Create migrations", "Add Bootstrap layout"

Что НЕ нужно делать

Не добавляй Vue/React
Не делай сложную архитектуру (Repository, Service layers)
Не делай сложные фильтры
Не добавляй платежи
Не делай админ-панель

Проверка готовности
После каждого шага проверяй:

Код работает без ошибок
php artisan migrate выполняется успешно
Страницы открываются
Формы отправляются
Данные сохраняются в БД