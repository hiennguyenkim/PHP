<?php
declare(strict_types=1);

namespace Controllers;

use Core\BaseController;
use Core\Session;

class HomeController extends BaseController
{
    public function index(): void
    {
        if (Session::has('user')) {
            $this->redirect('dashboard/index');
        }

        $this->redirect('auth/login');
    }
}
