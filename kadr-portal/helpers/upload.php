<?php

declare(strict_types=1);

namespace KadrPortal\Helpers;

use RuntimeException;

const LISTING_IMAGE_LIMIT = 5;
const LISTING_IMAGE_MAX_SIZE = 5 * 1024 * 1024; // 5 MB

/**
 * @return array<int, array{id:string,path:string,thumb:string}>
 */
function get_listing_uploads(): array
{
    ensureSession();

    $uploads = $_SESSION['listing_uploads'] ?? [];

    if (!is_array($uploads)) {
        return [];
    }

    $result = [];

    foreach ($uploads as $upload) {
        if (!is_array($upload)) {
            continue;
        }

        $id = $upload['id'] ?? null;
        $path = $upload['path'] ?? null;
        $thumb = $upload['thumb'] ?? null;

        if (is_string($id) && is_string($path) && is_string($thumb)) {
            $result[] = [
                'id' => $id,
                'path' => $path,
                'thumb' => $thumb,
            ];
        }
    }

    return $result;
}

function get_listing_upload_limit(): int
{
    return LISTING_IMAGE_LIMIT;
}

function get_listing_upload_max_size(): int
{
    return LISTING_IMAGE_MAX_SIZE;
}

/**
 * @param array{id:string,path:string,thumb:string} $upload
 */
function remember_listing_upload(array $upload): void
{
    ensureSession();

    if (!isset($_SESSION['listing_uploads']) || !is_array($_SESSION['listing_uploads'])) {
        $_SESSION['listing_uploads'] = [];
    }

    $_SESSION['listing_uploads'][] = $upload;
}

function remove_listing_upload(string $id): bool
{
    ensureSession();

    if (!preg_match('/^[a-f0-9]{32}\.(?:jpg|png)$/', $id)) {
        return false;
    }

    $uploads = get_listing_uploads();
    $updated = [];
    $removed = false;

    foreach ($uploads as $upload) {
        if ($upload['id'] === $id) {
            $removed = true;
            continue;
        }

        $updated[] = $upload;
    }

    if (!$removed) {
        return false;
    }

    $_SESSION['listing_uploads'] = $updated;

    $directory = listing_upload_directory();
    $filePath = $directory . '/' . $id;
    $thumbPath = $directory . '/thumb_' . $id;

    if (is_file($filePath)) {
        unlink($filePath);
    }

    if (is_file($thumbPath)) {
        unlink($thumbPath);
    }

    return true;
}

/**
 * @param array<string, mixed> $file
 *
 * @return array{id:string,path:string,thumb:string}
 */
function store_listing_upload(array $file): array
{
    if (!isset($file['error']) || !is_int($file['error'])) {
        throw new RuntimeException('Ошибка загрузки файла.');
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException(upload_error_message($file['error']));
    }

    $size = $file['size'] ?? 0;

    if (!is_int($size) || $size <= 0) {
        throw new RuntimeException('Файл поврежден или пуст.');
    }

    if ($size > LISTING_IMAGE_MAX_SIZE) {
        throw new RuntimeException('Файл превышает допустимый размер 5 МБ.');
    }

    $tmpPath = $file['tmp_name'] ?? '';

    if (!is_string($tmpPath) || $tmpPath === '' || !is_uploaded_file($tmpPath)) {
        throw new RuntimeException('Не удалось получить загруженный файл.');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo !== false ? finfo_file($finfo, $tmpPath) : false;

    if ($finfo !== false) {
        finfo_close($finfo);
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];

    if (!is_string($mime) || !isset($allowed[$mime])) {
        throw new RuntimeException('Поддерживаются только изображения JPG или PNG.');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    $directory = listing_upload_directory();

    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
        throw new RuntimeException('Не удалось подготовить каталог для загрузки.');
    }

    $destination = $directory . '/' . $filename;

    if (!move_uploaded_file($tmpPath, $destination)) {
        throw new RuntimeException('Не удалось сохранить файл.');
    }

    $thumbDestination = $directory . '/thumb_' . $filename;

    try {
        create_thumbnail($destination, $thumbDestination, 300, 200);
    } catch (RuntimeException $exception) {
        if (is_file($destination)) {
            unlink($destination);
        }

        throw $exception;
    }

    $relativePath = '/uploads/listings/' . $filename;
    $relativeThumb = '/uploads/listings/thumb_' . $filename;

    return [
        'id' => $filename,
        'path' => $relativePath,
        'thumb' => $relativeThumb,
    ];
}

function listing_upload_directory(): string
{
    return dirname(__DIR__) . '/public/uploads/listings';
}

function upload_error_message(int $errorCode): string
{
    return match ($errorCode) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Файл превышает допустимый размер.',
        UPLOAD_ERR_PARTIAL => 'Файл был загружен лишь частично.',
        UPLOAD_ERR_NO_FILE => 'Файл не был загружен.',
        UPLOAD_ERR_NO_TMP_DIR => 'Отсутствует временная папка для загрузки.',
        UPLOAD_ERR_CANT_WRITE => 'Не удалось записать файл на диск.',
        UPLOAD_ERR_EXTENSION => 'Загрузка файла остановлена расширением PHP.',
        default => 'Произошла ошибка при загрузке файла.',
    };
}
