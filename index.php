<?php
declare(strict_types=1);

/**
 * Fallback entrypoint when the project root is used as the web root.
 * This keeps `/` working in local development without forcing a redirect to `/public/`.
 */
require __DIR__ . '/public/index.php';
