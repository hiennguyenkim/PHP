<?php

declare(strict_types=1);

use Laminas\Mvc\Application;

chdir(dirname(__DIR__));

/**
 * Normalize requests when the app is reached via /public or /public/index.php.
 * This keeps Laminas route matching stable across Apache rewrites and direct access.
 */
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$parsedPath = parse_url($requestUri, PHP_URL_PATH);
$uriPath    = is_string($parsedPath) && $parsedPath !== '' ? $parsedPath : '/';
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$scriptDir  = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
$normalizedPath = $uriPath;

if ($normalizedPath === '/public' || $normalizedPath === '/public/' || $normalizedPath === '/public/index.php') {
    $normalizedPath = '/';
} elseif (str_starts_with($normalizedPath, '/public/index.php/')) {
    $normalizedPath = substr($normalizedPath, strlen('/public/index.php')) ?: '/';
} elseif (str_starts_with($normalizedPath, '/public/')) {
    $normalizedPath = substr($normalizedPath, strlen('/public')) ?: '/';
}

if ($normalizedPath !== $uriPath) {
    $query = parse_url($requestUri, PHP_URL_QUERY);
    $normalizedQuery = is_string($query) && $query !== '' ? '?' . $query : '';
    $_SERVER['REQUEST_URI'] = $normalizedPath . $normalizedQuery;
    $uriPath = $normalizedPath;
}

// PHP built-in server: serve static files, fix SCRIPT_NAME for clean URLs
if (php_sapi_name() === 'cli-server') {
    $uri  = $uriPath;
    $file = __DIR__ . $uri;
    if ($uri !== '/' && is_file($file)) {
        return false; // serve static file as-is
    }
    // Without this fix, Laminas generates /index.php/admin/... instead of /admin/...
    $_SERVER['SCRIPT_NAME']     = '/';
    $_SERVER['PHP_SELF']        = $_SERVER['REQUEST_URI'] ?? '/';
    $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/index.php';
} else {
    if ($scriptName !== '' && $normalizedPath === $scriptName) {
        $normalizedPath = '/';
    } elseif ($scriptDir !== '' && $scriptDir !== '/' && str_starts_with($normalizedPath, $scriptDir . '/')) {
        $normalizedPath = substr($normalizedPath, strlen($scriptDir)) ?: '/';
    } elseif ($scriptDir !== '' && $scriptDir !== '/' && $normalizedPath === $scriptDir) {
        $normalizedPath = '/';
    }

    if ($normalizedPath !== $uriPath) {
        $query = parse_url($requestUri, PHP_URL_QUERY);
        $normalizedQuery = is_string($query) && $query !== '' ? '?' . $query : '';
        $_SERVER['REQUEST_URI'] = $normalizedPath . $normalizedQuery;
    }
}

// Composer autoloading
include __DIR__ . '/../vendor/autoload.php';

if (! class_exists(Application::class)) {
    throw new RuntimeException("Unable to load application. Run `composer install` first.");
}

$container = require __DIR__ . '/../config/container.php';
/** @var Application $app */
$app = $container->get('Application');
$app->run();
