<?php
declare(strict_types=1);

spl_autoload_register(function (string $class): void {
    $basePath = dirname(__DIR__);
    $prefixes = [
        'Core\\' => $basePath . '/Core/',
        'Controllers\\' => $basePath . '/Controllers/',
        'Models\\' => $basePath . '/Models/',
    ];

    foreach ($prefixes as $prefix => $directory) {
        if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
            continue;
        }

        $relativeClass = substr($class, strlen($prefix));
        $file = $directory . str_replace('\\', '/', $relativeClass) . '.php';

        if (is_file($file)) {
            require_once $file;
        }

        return;
    }
});
