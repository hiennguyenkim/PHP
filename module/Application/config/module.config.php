<?php

declare(strict_types=1);

namespace Application;

use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\Factory\InvokableFactory;

return [
    'router' => [
        'routes' => [

            'auth' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/auth[/:action]',
                    'defaults' => [
                        'controller' => Controller\AuthController::class,
                        'action' => 'login',
                    ],
                ]
            ],
            'book' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/book[/:action[/:id]]',
                    'defaults' => [
                        'controller' => Controller\BookController::class,
                        'action' => 'index',
                    ],
                ]
            ],
            'loan' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/loan[/:action[/:id]]',
                    'defaults' => [
                        'controller' => Controller\LoanController::class,
                        'action' => 'index',
                    ],
                ]
            ],
            'user' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/user[/:action[/:id]]',
                    'defaults' => [
                        'controller' => Controller\UserController::class,
                        'action' => 'index',
                    ],
                ]
            ],
            'dashboard' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/dashboard',
                    'defaults' => [
                        'controller' => Controller\DashboardController::class,
                        'action' => 'index',
                    ],
                ]
            ],

            'home' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'application' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/application[/:action]',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
        ],
    ],
        'controllers' => [
        'factories' => [
            Controller\IndexController::class => InvokableFactory::class,
            Controller\AuthController::class =>
                \Laminas\ServiceManager\AbstractFactory\ReflectionBasedAbstractFactory::class,
            Controller\BookController::class =>
                \Laminas\ServiceManager\AbstractFactory\ReflectionBasedAbstractFactory::class,
            Controller\DashboardController::class =>
                \Laminas\ServiceManager\AbstractFactory\ReflectionBasedAbstractFactory::class,
            Controller\LoanController::class =>
                \Laminas\ServiceManager\AbstractFactory\ReflectionBasedAbstractFactory::class,
            Controller\UserController::class =>
                \Laminas\ServiceManager\AbstractFactory\ReflectionBasedAbstractFactory::class,
        ],
    ],

        'service_manager' => [
        'factories' => [
            Model\BookModel::class => \Laminas\ServiceManager\AbstractFactory\ReflectionBasedAbstractFactory::class,
            Model\LoanModel::class => \Laminas\ServiceManager\AbstractFactory\ReflectionBasedAbstractFactory::class,
            Model\UserModel::class => \Laminas\ServiceManager\AbstractFactory\ReflectionBasedAbstractFactory::class,
        ],
    ],
    'view_manager' => [
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/404',
        'exception_template'       => 'error/index',
        'template_map' => [
            'layout/layout'           => __DIR__ . '/../view/layout/layout.phtml',
            'application/index/index' => __DIR__ . '/../view/application/index/index.phtml',
            'error/404'               => __DIR__ . '/../view/error/404.phtml',
            'error/index'             => __DIR__ . '/../view/error/index.phtml',
        ],
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
];
