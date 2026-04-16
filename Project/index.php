<?php
declare(strict_types=1);

define('BASE_PATH', __DIR__);

require_once BASE_PATH . '/Core/Autoloader.php';

use Core\Router;
use Core\Session;

Session::start();

$router = new Router();
$router->dispatch();
