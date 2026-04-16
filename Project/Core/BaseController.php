<?php
declare(strict_types=1);

namespace Core;

abstract class BaseController
{
    protected function render(string $view, array $data = []): void
    {
        $viewFile = BASE_PATH . '/Views/' . $view . '.php';
        $layoutFile = BASE_PATH . '/Views/layout.php';

        if (!is_file($viewFile)) {
            throw new \RuntimeException("View khong ton tai: {$view}");
        }

        $data['currentUser'] = Session::get('user');
        $data['flashSuccess'] = Session::flash('success');
        $data['flashError'] = Session::flash('error');

        extract($data, EXTR_SKIP);

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        if (is_file($layoutFile)) {
            require $layoutFile;
            return;
        }

        echo $content;
    }

    protected function redirect(string $route): void
    {
        $target = 'index.php';

        if ($route !== '') {
            $target .= '?url=' . ltrim($route, '/');
        }

        header('Location: ' . $target);
        exit;
    }

    protected function requireLogin(): void
    {
        if (!Session::has('user')) {
            Session::flash('error', 'Vui long dang nhap de truy cap trang quan ly.');
            $this->redirect('auth/login');
        }
    }

    protected function requireGuest(): void
    {
        if (Session::has('user')) {
            $this->redirect('dashboard/index');
        }
    }

    protected function isPost(): bool
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    }

    protected function post(string $key, string $default = ''): string
    {
        return trim((string) ($_POST[$key] ?? $default));
    }

    protected function rawPost(string $key, string $default = ''): string
    {
        return (string) ($_POST[$key] ?? $default);
    }

    protected function query(string $key, string $default = ''): string
    {
        return trim((string) ($_GET[$key] ?? $default));
    }

    protected function currentUser(): array
    {
        return (array) Session::get('user', []);
    }

    protected function isAdmin(): bool
    {
        return (string) ($this->currentUser()['role'] ?? '') === 'admin';
    }

    protected function requireAdmin(): void
    {
        if (!$this->isAdmin()) {
            Session::flash('error', 'Chi quan tri vien thu vien moi co quyen truy cap chuc nang nay.');
            $this->redirect('dashboard/index');
        }
    }

    protected function isValidDate(string $value): bool
    {
        if ($value === '') {
            return false;
        }

        $date = \DateTime::createFromFormat('Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }
}
