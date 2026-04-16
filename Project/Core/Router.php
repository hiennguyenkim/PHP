<?php
declare(strict_types=1);

namespace Core;

class Router
{
    public function dispatch(): void
    {
        $defaultRoute = Session::has('user') ? 'dashboard/index' : 'auth/login';
        $route = trim((string) ($_GET['url'] ?? $defaultRoute), '/');
        $segments = $route === '' ? explode('/', $defaultRoute) : array_values(array_filter(explode('/', $route)));

        $controllerSegment = $segments[0] ?? 'home';
        $actionSegment = $segments[1] ?? 'index';

        $controllerClass = 'Controllers\\' . $this->toStudlyCase($controllerSegment) . 'Controller';
        $action = $this->toCamelCase($actionSegment);
        $parameters = array_map('urldecode', array_slice($segments, 2));

        try {
            if (!class_exists($controllerClass)) {
                $this->renderError(404, 'Khong tim thay controller duoc yeu cau.');
                return;
            }

            $controller = new $controllerClass();

            if (!method_exists($controller, $action)) {
                $this->renderError(404, 'Khong tim thay chuc nang duoc yeu cau.');
                return;
            }

            call_user_func_array([$controller, $action], $parameters);
        } catch (\Throwable $throwable) {
            $this->renderError(500, $throwable->getMessage());
        }
    }

    private function toStudlyCase(string $segment): string
    {
        $segment = str_replace(['-', '_'], ' ', strtolower($segment));
        return str_replace(' ', '', ucwords($segment));
    }

    private function toCamelCase(string $segment): string
    {
        $studly = $this->toStudlyCase($segment);
        return lcfirst($studly);
    }

    private function renderError(int $statusCode, string $message): void
    {
        http_response_code($statusCode);
        $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

        echo <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loi {$statusCode}</title>
    <link rel="stylesheet" href="Public/css/app.css">
</head>
<body>
    <main class="error-page">
        <section class="panel error-panel">
            <p class="eyebrow">HTTP {$statusCode}</p>
            <h1>Khong the xu ly yeu cau</h1>
            <p>{$safeMessage}</p>
            <a class="button button-primary" href="index.php">Quay ve trang chinh</a>
        </section>
    </main>
</body>
</html>
HTML;
    }
}
