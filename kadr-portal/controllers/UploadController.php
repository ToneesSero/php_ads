<?php

declare(strict_types=1);

namespace KadrPortal\Controllers;

use RuntimeException;

use function KadrPortal\Helpers\get_listing_upload_limit;
use function KadrPortal\Helpers\get_listing_uploads;
use function KadrPortal\Helpers\is_authenticated;
use function KadrPortal\Helpers\remember_listing_upload;
use function KadrPortal\Helpers\remove_listing_upload;
use function KadrPortal\Helpers\store_listing_upload;
use function KadrPortal\Helpers\verify_csrf_token;

class UploadController
{
    public function store(): void
    {
        $this->setJsonHeader();

        if (!$this->requireAuth()) {
            return;
        }

        $token = $this->resolveToken();

        if (!$this->checkCsrf($token)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'CSRF token mismatch.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $uploads = get_listing_uploads();

        if (count($uploads) >= get_listing_upload_limit()) {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'error' => 'Достигнут лимит из 5 изображений.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        if (!isset($_FILES['image'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Файл не найден в запросе.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $stored = store_listing_upload($_FILES['image']);
        } catch (RuntimeException $exception) {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'error' => $exception->getMessage(),
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        remember_listing_upload($stored);

        $remaining = get_listing_upload_limit() - count(get_listing_uploads());

        echo json_encode([
            'success' => true,
            'image' => $stored,
            'remaining' => $remaining,
        ], JSON_UNESCAPED_UNICODE);
    }

    public function destroy(): void
    {
        $this->setJsonHeader();

        if (!$this->requireAuth()) {
            return;
        }

        $token = $this->resolveToken();

        if (!$this->checkCsrf($token)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'CSRF token mismatch.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $id = (string) ($_POST['id'] ?? '');

        if ($id === '') {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Не указан файл для удаления.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        if (!remove_listing_upload($id)) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Файл не найден или уже удален.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        echo json_encode([
            'success' => true,
            'id' => $id,
        ], JSON_UNESCAPED_UNICODE);
    }

    private function requireAuth(): bool
    {
        if (is_authenticated()) {
            return true;
        }

        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Требуется авторизация.',
        ], JSON_UNESCAPED_UNICODE);

        return false;
    }

    private function setJsonHeader(): void
    {
        header('Content-Type: application/json; charset=utf-8');
    }

    private function resolveToken(): ?string
    {
        $headerToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

        if (is_string($headerToken) && $headerToken !== '') {
            return $headerToken;
        }

        $postToken = $_POST['csrf_token'] ?? null;

        return is_string($postToken) ? $postToken : null;
    }

    private function checkCsrf(?string $token): bool
    {
        return verify_csrf_token($token);
    }
}
