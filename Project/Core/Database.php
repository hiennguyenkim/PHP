<?php
declare(strict_types=1);

namespace Core;

use PDO;
use PDOException;

class Database
{
    private static ?self $instance = null;
    private PDO $connection;

    private function __construct()
    {
        $config = $this->loadConfig();

        $host = (string) ($config['host'] ?? getenv('DB_HOST') ?: '127.0.0.1');
        $database = (string) ($config['name'] ?? getenv('DB_NAME') ?: 'mvc_app');
        $username = (string) ($config['user'] ?? getenv('DB_USER') ?: 'root');
        $password = (string) ($config['pass'] ?? getenv('DB_PASS') ?: '');

        try {
            $this->connection = new PDO(
                "mysql:host={$host};dbname={$database};charset=utf8mb4",
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $exception) {
            throw new PDOException(
                'Khong the ket noi co so du lieu. Hay kiem tra thong tin trong Core/Database.php hoac bien moi truong.',
                (int) $exception->getCode(),
                $exception
            );
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }

    private function loadConfig(): array
    {
        $configFiles = [
            BASE_PATH . '/config.local.php',
            BASE_PATH . '/config.php',
        ];

        foreach ($configFiles as $configFile) {
            if (!is_file($configFile)) {
                continue;
            }

            $config = require $configFile;

            if (!is_array($config)) {
                continue;
            }

            if (isset($config['database']) && is_array($config['database'])) {
                return $config['database'];
            }

            return $config;
        }

        return [];
    }
}
