<?php

declare(strict_types=1);

namespace KadrPortal\Controllers;

use PDO;
use RuntimeException;
use Throwable;

use function KadrPortal\Helpers\csrf_token;
use function KadrPortal\Helpers\current_user;
use function KadrPortal\Helpers\db;
use function KadrPortal\Helpers\flash_old_input;
use function KadrPortal\Helpers\get_flash;
use function KadrPortal\Helpers\get_listing_upload_limit;
use function KadrPortal\Helpers\get_listing_upload_max_size;
use function KadrPortal\Helpers\get_listing_uploads;
use function KadrPortal\Helpers\is_authenticated;
use function KadrPortal\Helpers\old_input;
use function KadrPortal\Helpers\reset_csrf_token;
use function KadrPortal\Helpers\set_flash;
use function KadrPortal\Helpers\validate_listing;
use function KadrPortal\Helpers\verify_csrf_token;

class ListingController
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = db();
    }

    public function index(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        $filters = [];
        $params = [];

        $search = trim($_GET['search'] ?? '');

        if ($search !== '') {
            $filters[] = '(l.title ILIKE :search OR l.description ILIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }

        $categoryId = null;
        $category = trim($_GET['category'] ?? '');

        if ($category !== '' && ctype_digit($category)) {
            $categoryId = (int) $category;
            $filters[] = 'l.category_id = :category_id';
            $params[':category_id'] = $categoryId;
        }

        $minPrice = null;
        $minPriceRaw = str_replace(',', '.', trim($_GET['min_price'] ?? ''));

        if ($minPriceRaw !== '' && is_numeric($minPriceRaw)) {
            $minPrice = max(0.0, (float) $minPriceRaw);
            $filters[] = 'l.price >= :min_price';
            $params[':min_price'] = $minPrice;
        }

        $maxPrice = null;
        $maxPriceRaw = str_replace(',', '.', trim($_GET['max_price'] ?? ''));

        if ($maxPriceRaw !== '' && is_numeric($maxPriceRaw)) {
            $maxPrice = max(0.0, (float) $maxPriceRaw);
            $filters[] = 'l.price <= :max_price';
            $params[':max_price'] = $maxPrice;
        }

        if ($minPrice !== null && $maxPrice !== null && $minPrice > $maxPrice) {
            $temp = $minPrice;
            $minPrice = $maxPrice;
            $maxPrice = $temp;
            $params[':min_price'] = $minPrice;
            $params[':max_price'] = $maxPrice;
        }

        $whereClause = $filters !== [] ? 'WHERE ' . implode(' AND ', $filters) : '';

        $sql = <<<SQL
SELECT
    l.id,
    l.title,
    l.description,
    l.price,
    l.created_at,
    c.name AS category_name,
    u.name AS author_name,
    img.image_path AS main_image_path
FROM listings AS l
LEFT JOIN categories AS c ON c.id = l.category_id
INNER JOIN users AS u ON u.id = l.user_id
LEFT JOIN LATERAL (
    SELECT image_path
    FROM listing_images
    WHERE listing_id = l.id
    ORDER BY is_main DESC, id ASC
    LIMIT 1
) AS img ON TRUE
$whereClause
ORDER BY l.created_at DESC
LIMIT :limit OFFSET :offset
SQL;

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $key => $value) {
            if (is_int($value)) {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
                continue;
            }

            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue(':limit', $perPage + 1, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $listings = $stmt->fetchAll();
        $hasMore = false;

        if (count($listings) > $perPage) {
            $hasMore = true;
            array_pop($listings);
        }

        foreach ($listings as &$listing) {
            $path = $listing['main_image_path'] ?? null;
            $thumb = $this->buildThumbPath(is_string($path) ? $path : null);

            $listing['main_image_thumb'] = $thumb ?? (is_string($path) ? $path : null);
        }

        unset($listing);
        $categories = $this->getCategories();

        $currentFilters = [
            'search' => $search,
            'category' => $categoryId,
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
        ];

        $nextPage = $hasMore ? $page + 1 : null;
        $csrfToken = csrf_token();

        header('Content-Type: text/html; charset=utf-8');
        require __DIR__ . '/../templates/listings/index.php';
    }

    public function show(string $id): void
    {
        $listingId = (int) $id;

        if ($listingId <= 0) {
            http_response_code(404);
            echo 'Объявление не найдено.';
            return;
        }

        $listing = $this->findListing($listingId);

        if ($listing === null) {
            http_response_code(404);
            echo 'Объявление не найдено.';
            return;
        }

        $csrfToken = csrf_token();

        header('Content-Type: text/html; charset=utf-8');
        require __DIR__ . '/../templates/listings/show.php';
    }

    public function create(): void
    {
        if (!$this->ensureAuthenticated()) {
            return;
        }

        $errors = $this->getListingErrors('listing_create_errors');
        $old = old_input();
        $csrfToken = csrf_token();
        $categories = $this->getCategories();
        $uploadedImages = get_listing_uploads();
        $uploadLimit = get_listing_upload_limit();
        $uploadMaxSize = get_listing_upload_max_size();

        header('Content-Type: text/html; charset=utf-8');
        require __DIR__ . '/../templates/listings/create.php';
    }

    public function store(): void
    {
        $user = $this->requireUser();

        if ($user === null) {
            return;
        }

        if (!$this->checkCsrf($_POST['csrf_token'] ?? null)) {
            http_response_code(400);
            echo 'CSRF token mismatch.';
            return;
        }

        $validation = validate_listing($_POST);
        $errors = $validation['errors'];
        $data = $validation['data'];
        $formState = $data;
        $formState['price_input'] = $_POST['price'] ?? '';
        $formState['category_input'] = $_POST['category_id'] ?? '';

        if ($data['price'] === null) {
            $data['price'] = 0.0;
        }

        if ($data['category_id'] !== null && !$this->categoryExists($data['category_id'])) {
            $errors['category_id'] = 'Выберите существующую категорию.';
        }

        if ($errors !== []) {
            $this->rememberFormState($formState, 'listing_create_errors', $errors);
            header('Location: /listings/create', true, 303);
            return;
        }

        try {
            $listingId = $this->createListing($user['id'], $data);
        } catch (Throwable $exception) {
            $this->rememberFormState($formState, 'listing_create_errors', [
                'general' => 'Не удалось создать объявление. Попробуйте позже.',
            ]);
            header('Location: /listings/create', true, 303);
            return;
        }

        reset_csrf_token();
        header('Location: /listings/' . $listingId, true, 303);
    }

    public function edit(string $id): void
    {
        $user = $this->requireUser();

        if ($user === null) {
            return;
        }

        $listingId = (int) $id;

        if ($listingId <= 0) {
            http_response_code(404);
            echo 'Объявление не найдено.';
            return;
        }

        $listing = $this->findListing($listingId);

        if ($listing === null) {
            http_response_code(404);
            echo 'Объявление не найдено.';
            return;
        }

        if ((int) $listing['user_id'] !== $user['id']) {
            http_response_code(403);
            echo 'Недостаточно прав для редактирования.';
            return;
        }

        $errors = $this->getListingErrors('listing_edit_errors');
        $old = old_input();
        $categories = $this->getCategories();
        $csrfToken = csrf_token();
        $uploadedImages = get_listing_uploads();
        $uploadLimit = get_listing_upload_limit();
        $uploadMaxSize = get_listing_upload_max_size();
        header('Content-Type: text/html; charset=utf-8');
        require __DIR__ . '/../templates/listings/edit.php';
    }

    public function update(string $id): void
    {
        $user = $this->requireUser();

        if ($user === null) {
            return;
        }

        if (!$this->checkCsrf($_POST['csrf_token'] ?? null)) {
            http_response_code(400);
            echo 'CSRF token mismatch.';
            return;
        }

        $listingId = (int) $id;

        if ($listingId <= 0) {
            http_response_code(404);
            echo 'Объявление не найдено.';
            return;
        }

        $listing = $this->findListing($listingId);

        if ($listing === null) {
            http_response_code(404);
            echo 'Объявление не найдено.';
            return;
        }

        if ((int) $listing['user_id'] !== $user['id']) {
            http_response_code(403);
            echo 'Недостаточно прав для редактирования.';
            return;
        }

        $validation = validate_listing($_POST);
        $errors = $validation['errors'];
        $data = $validation['data'];
        $formState = $data;
        $formState['price_input'] = $_POST['price'] ?? '';
        $formState['category_input'] = $_POST['category_id'] ?? '';

        if ($data['price'] === null) {
            $data['price'] = 0.0;
        }

        if ($data['category_id'] !== null && !$this->categoryExists($data['category_id'])) {
            $errors['category_id'] = 'Выберите существующую категорию.';
        }

        if ($errors !== []) {
            $this->rememberFormState($formState, 'listing_edit_errors', $errors);
            header('Location: /listings/' . $listingId . '/edit', true, 303);
            return;
        }

        try {
            $this->updateListing($listingId, $user['id'], $data);
        } catch (Throwable $exception) {
            $this->rememberFormState($formState, 'listing_edit_errors', [
                'general' => 'Не удалось обновить объявление. Попробуйте позже.',
            ]);
            header('Location: /listings/' . $listingId . '/edit', true, 303);
            return;
        }

        reset_csrf_token();
        header('Location: /listings/' . $listingId, true, 303);
    }

    public function destroy(string $id): void
    {
        $user = $this->requireUser();

        if ($user === null) {
            return;
        }

        if (!$this->checkCsrf($_POST['csrf_token'] ?? null)) {
            http_response_code(400);
            echo 'CSRF token mismatch.';
            return;
        }

        $listingId = (int) $id;

        if ($listingId <= 0) {
            http_response_code(404);
            echo 'Объявление не найдено.';
            return;
        }

        try {
            $stmt = $this->pdo->prepare('DELETE FROM listings WHERE id = :id AND user_id = :user_id');
            $stmt->bindValue(':id', $listingId, PDO::PARAM_INT);
            $stmt->bindValue(':user_id', $user['id'], PDO::PARAM_INT);
            $stmt->execute();
        } catch (Throwable $exception) {
            http_response_code(500);
            echo 'Не удалось удалить объявление.';
            return;
        }

        reset_csrf_token();
        header('Location: /listings', true, 303);
    }

    private function ensureAuthenticated(): bool
    {
        if (is_authenticated()) {
            return true;
        }

        header('Location: /login', true, 303);

        return false;
    }

    /**
     * @return array{id:int,email:string,name:string}|null
     */
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

    private function rememberFormState(array $data, string $errorKey, array $errors): void
    {
        $oldInput = [
            'title' => (string) ($data['title'] ?? ''),
            'description' => (string) ($data['description'] ?? ''),
            'price' => '',
            'category_id' => '',
        ];

        if (isset($data['price_input']) && $data['price_input'] !== '') {
            $oldInput['price'] = (string) $data['price_input'];
        } elseif (isset($data['price'])) {
            $oldInput['price'] = $this->formatPrice((float) $data['price']);
        }

        if (isset($data['category_input']) && $data['category_input'] !== '') {
            $oldInput['category_id'] = (string) $data['category_input'];
        } elseif (isset($data['category_id']) && $data['category_id'] !== null) {
            $oldInput['category_id'] = (string) $data['category_id'];
        }

        flash_old_input($oldInput);
        set_flash($errorKey, $errors);
    }

    /**
     * @return array<string, string>
     */
    private function getListingErrors(string $key): array
    {
        $errors = get_flash($key, []);

        if (!is_array($errors)) {
            return [];
        }

        /** @var array<string, string> $errors */
        return $errors;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getCategories(): array
    {
        $stmt = $this->pdo->query('SELECT id, name FROM categories ORDER BY name');

        return $stmt->fetchAll();
    }

    private function categoryExists(int $categoryId): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM categories WHERE id = :id');
        $stmt->bindValue(':id', $categoryId, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * @param array{title:string,description:string,price:float,category_id:int|null} $data
     */
    private function createListing(int $userId, array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO listings (user_id, category_id, title, description, price) VALUES (:user_id, :category_id, :title, :description, :price) RETURNING id'
        );

        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);

        if ($data['category_id'] === null) {
            $stmt->bindValue(':category_id', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':category_id', $data['category_id'], PDO::PARAM_INT);
        }

        $stmt->bindValue(':title', $data['title']);
        $stmt->bindValue(':description', $data['description']);
        $stmt->bindValue(':price', $this->formatPrice($data['price']));
        $stmt->execute();

        $id = $stmt->fetchColumn();

        if ($id === false) {
            throw new RuntimeException('Не удалось получить идентификатор объявления.');
        }

        return (int) $id;
    }

    /**
     * @param array{title:string,description:string,price:float,category_id:int|null} $data
     */
    private function updateListing(int $listingId, int $userId, array $data): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE listings SET category_id = :category_id, title = :title, description = :description, price = :price, updated_at = NOW() WHERE id = :id AND user_id = :user_id'
        );

        if ($data['category_id'] === null) {
            $stmt->bindValue(':category_id', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':category_id', $data['category_id'], PDO::PARAM_INT);
        }

        $stmt->bindValue(':title', $data['title']);
        $stmt->bindValue(':description', $data['description']);
        $stmt->bindValue(':price', $this->formatPrice($data['price']));
        $stmt->bindValue(':id', $listingId, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findListing(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT l.id, l.title, l.description, l.price, l.created_at, l.updated_at, l.user_id, c.name AS category_name, c.id AS category_id, u.name AS author_name FROM listings AS l LEFT JOIN categories AS c ON c.id = l.category_id INNER JOIN users AS u ON u.id = l.user_id WHERE l.id = :id LIMIT 1'
        );

        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $listing = $stmt->fetch();

        if ($listing === false) {
            return null;
        }

        $images = $this->getListingImages($id);
        $listing['images'] = $images;

        $listing['main_image_path'] = $images[0]['path'] ?? null;
        $listing['main_image_thumb'] = $images[0]['thumb'] ?? null;

        return $listing;
    }

    /**
     * @return array<int, array{path:string,thumb:string,is_main:bool}>
     */
    private function getListingImages(int $listingId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT image_path, COALESCE(is_main, FALSE) AS is_main FROM listing_images WHERE listing_id = :id ORDER BY is_main DESC, id ASC'
        );

        $stmt->bindValue(':id', $listingId, PDO::PARAM_INT);
        $stmt->execute();

        $images = [];

        while ($row = $stmt->fetch()) {
            if (!isset($row['image_path']) || !is_string($row['image_path'])) {
                continue;
            }

            $path = $row['image_path'];

            $images[] = [
                'path' => $path,
                'thumb' => $this->buildThumbPath($path) ?? $path,
                'is_main' => (bool) ($row['is_main'] ?? false),
            ];
        }

        return $images;
    }

    private function buildThumbPath(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        $lastSlash = strrpos($path, '/');

        if ($lastSlash === false) {
            return null;
        }

        $directory = substr($path, 0, $lastSlash + 1);
        $filename = substr($path, $lastSlash + 1);

        if ($filename === '') {
            return null;
        }

        return $directory . 'thumb_' . $filename;
    }

    private function formatPrice(float $price): string
    {
        return number_format($price, 2, '.', '');
    }

    private function checkCsrf(?string $token): bool
    {
        return verify_csrf_token($token);
    }
}
