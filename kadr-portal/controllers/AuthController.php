<?php

declare(strict_types=1);

namespace KadrPortal\Controllers;

use PDO;
use PDOException;
use Throwable;

use function KadrPortal\Helpers\csrf_token;
use function KadrPortal\Helpers\flash_old_input;
use function KadrPortal\Helpers\get_flash;
use function KadrPortal\Helpers\is_authenticated;
use function KadrPortal\Helpers\login_user;
use function KadrPortal\Helpers\logout_user;
use function KadrPortal\Helpers\old_input;
use function KadrPortal\Helpers\reset_csrf_token;
use function KadrPortal\Helpers\set_flash;
use function KadrPortal\Helpers\validate_login;
use function KadrPortal\Helpers\validate_registration;
use function KadrPortal\Helpers\verify_csrf_token;

class AuthController
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = $this->createConnection();
    }

    public function showLogin(): void
    {
        if (is_authenticated()) {
            header('Location: /', true, 303);
            return;
        }

        $errors = get_flash('login_errors', []);
        $generalError = is_array($errors) ? ($errors['general'] ?? null) : null;
        $fieldErrors = is_array($errors) ? array_filter($errors, static fn ($key): bool => $key !== 'general', ARRAY_FILTER_USE_KEY) : [];
        $old = old_input();
        $csrfToken = csrf_token();

        header('Content-Type: text/html; charset=utf-8');
        require __DIR__ . '/../templates/auth/login.php';
    }

    public function showRegister(): void
    {
        if (is_authenticated()) {
            header('Location: /', true, 303);
            return;
        }

        $errors = get_flash('register_errors', []);
        $generalError = is_array($errors) ? ($errors['general'] ?? null) : null;
        $fieldErrors = is_array($errors) ? array_filter($errors, static fn ($key): bool => $key !== 'general', ARRAY_FILTER_USE_KEY) : [];
        $old = old_input();
        $csrfToken = csrf_token();

        header('Content-Type: text/html; charset=utf-8');
        require __DIR__ . '/../templates/auth/register.php';
    }

    public function register(): void
    {
        if (!$this->checkCsrf($_POST['csrf_token'] ?? null)) {
            http_response_code(400);
            echo 'CSRF token mismatch.';
            return;
        }

        [$errors, $data] = $this->validateRegistration($_POST);

        if ($errors !== []) {
            $this->rememberFormState($data, 'register_errors', $errors);
            header('Location: /register', true, 303);
            return;
        }

        try {
            if ($this->emailExists($data['email'])) {
                $errors['email'] = 'Такой email уже зарегистрирован.';
                $this->rememberFormState($data, 'register_errors', $errors);
                header('Location: /register', true, 303);
                return;
            }

            $user = $this->createUser($data['email'], $data['password'], $data['name']);
        } catch (Throwable $exception) {
            $this->rememberFormState($data, 'register_errors', [
                'general' => 'Не удалось завершить регистрацию. Попробуйте позже.',
            ]);
            header('Location: /register', true, 303);
            return;
        }

        login_user([
            'id' => (int) $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
        ]);

        reset_csrf_token();
        header('Location: /', true, 303);
    }

    public function login(): void
    {
        if (!$this->checkCsrf($_POST['csrf_token'] ?? null)) {
            http_response_code(400);
            echo 'CSRF token mismatch.';
            return;
        }

        [$errors, $data] = $this->validateLogin($_POST);

        if ($errors !== []) {
            $this->rememberFormState($data, 'login_errors', $errors);
            header('Location: /login', true, 303);
            return;
        }

        try {
            $user = $this->findUserByEmail($data['email']);
        } catch (Throwable $exception) {
            $this->rememberFormState($data, 'login_errors', [
                'general' => 'Не удалось выполнить вход. Попробуйте позже.',
            ]);
            header('Location: /login', true, 303);
            return;
        }

        if ($user === null || !password_verify($data['password'], $user['password_hash'])) {
            $this->rememberFormState($data, 'login_errors', [
                'general' => 'Неверные email или пароль.',
            ]);
            header('Location: /login', true, 303);
            return;
        }

        login_user([
            'id' => (int) $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
        ]);

        reset_csrf_token();
        header('Location: /', true, 303);
    }

    public function logout(): void
    {
        if (!$this->checkCsrf($_POST['csrf_token'] ?? null)) {
            http_response_code(400);
            echo 'CSRF token mismatch.';
            return;
        }

        logout_user();
        reset_csrf_token();
        header('Location: /', true, 303);
    }

    /**
     * @param array<string, string> $input
     *
     * @return array{0: array<string, string>, 1: array<string, string>}
     */
    private function validateRegistration(array $input): array
    {
        $result = validate_registration($input);

        return [$result['errors'], $result['data']];
    }

    /**
     * @param array<string, string> $input
     *
     * @return array{0: array<string, string>, 1: array<string, string>}
     */
    private function validateLogin(array $input): array
    {
        $result = validate_login($input);

        return [$result['errors'], $result['data']];
    }

    private function rememberFormState(array $data, string $errorKey, array $errors): void
    {
        $oldInput = ['email' => $data['email'] ?? ''];

        if (isset($data['name'])) {
            $oldInput['name'] = $data['name'];
        }

        flash_old_input($oldInput);

        set_flash($errorKey, $errors);
    }

    private function emailExists(string $email): bool
    {
        $statement = $this->pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $statement->bindValue(':email', $email);
        $statement->execute();

        return $statement->fetchColumn() !== false;
    }

    /**
     * @return array{id:int,email:string,name:string}
     */
    private function createUser(string $email, string $password, string $name): array
    {
        $statement = $this->pdo->prepare('INSERT INTO users (email, password_hash, name) VALUES (:email, :password_hash, :name) RETURNING id, email, name');
        $statement->bindValue(':email', $email);
        $statement->bindValue(':password_hash', password_hash($password, PASSWORD_ARGON2ID));
        $statement->bindValue(':name', $name);
        $statement->execute();

        /** @var array{id:int,email:string,name:string}|false $user */
        $user = $statement->fetch(PDO::FETCH_ASSOC);

        if ($user === false) {
            throw new PDOException('Unable to create user.');
        }

        return $user;
    }

    /**
     * @return array{id:int,email:string,name:string,password_hash:string}|null
     */
    private function findUserByEmail(string $email): ?array
    {
        $statement = $this->pdo->prepare('SELECT id, email, password_hash, name FROM users WHERE email = :email LIMIT 1');
        $statement->bindValue(':email', $email);
        $statement->execute();

        /** @var array{id:int,email:string,name:string,password_hash:string}|false $user */
        $user = $statement->fetch(PDO::FETCH_ASSOC);

        if ($user === false) {
            return null;
        }

        return $user;
    }

    private function checkCsrf(?string $token): bool
    {
        return verify_csrf_token($token);
    }

    private function createConnection(): PDO
    {
        $config = require __DIR__ . '/../configs/database.php';

        $dsn = sprintf('pgsql:host=%s;port=%d;dbname=%s', $config['host'], $config['port'], $config['dbname']);

        return new PDO(
            $dsn,
            $config['user'],
            $config['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        );
    }
}
