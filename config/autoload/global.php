<?php
/**
 * Global configuration: DB adapter (non-sensitive settings).
 * Sensitive credentials go into config/autoload/local.php (git-ignored).
 */
return [
    'db' => [
        'driver'  => 'Pdo_Mysql',
        'charset' => 'utf8mb4',
    ],

    'service_manager' => [
        'factories' => [
            // Laminas\Db adapter via official factory
            \Laminas\Db\Adapter\Adapter::class
                => \Laminas\Db\Adapter\AdapterServiceFactory::class,
        ],
        'aliases' => [
            'db' => \Laminas\Db\Adapter\Adapter::class,
        ],
    ],
];
