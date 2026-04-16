<?php

declare(strict_types=1);

namespace LibraryTest\Controller;

use Library\Controller\HomeController;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

class HomeControllerTest extends AbstractHttpControllerTestCase
{
    protected function setUp(): void
    {
        $config = include __DIR__ . '/../../../../config/application.config.php';
        $config['module_listener_options']['config_cache_enabled'] = false;
        $config['module_listener_options']['module_map_cache_enabled'] = false;

        $this->setApplicationConfig($config);

        parent::setUp();
    }

    public function testRootRedirectsToLogin(): void
    {
        $this->dispatch('/', 'GET');

        $this->assertResponseStatusCode(302);
        $this->assertControllerName(HomeController::class);
        $this->assertMatchedRouteName('home');
        $this->assertRedirectTo('/admin/auth');
    }

    public function testAdminEntryRedirectsToLogin(): void
    {
        $this->dispatch('/admin', 'GET');

        $this->assertResponseStatusCode(302);
        $this->assertControllerName(HomeController::class);
        $this->assertMatchedRouteName('library');
        $this->assertRedirectTo('/admin/auth');
    }

    public function testInvalidRouteDoesNotCrash(): void
    {
        $this->dispatch('/khong-ton-tai', 'GET');

        $this->assertResponseStatusCode(404);
    }
}
