<?php
declare(strict_types=1);

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Application\Core\Session;

abstract class BaseController extends AbstractActionController
{
    protected function currentUser(): array
    {
        return (array) Session::get('user', []);
    }

    protected function isAdmin(): bool
    {
        return (string) ($this->currentUser()['role'] ?? '') === 'admin';
    }

    protected function requireLogin(): ?ViewModel
    {
        if (!Session::has('user')) {
            Session::flash('error', 'Vui long dang nhap de truy cap trang nay.');
            $this->redirect()->toRoute('auth', ['action' => 'login']);
            return new ViewModel();
        }
        return null;
    }

    protected function requireAdmin(): ?ViewModel
    {
        $u = Session::get('user', []);
        if (($u['role'] ?? '') !== 'admin') {
            Session::flash('error', 'Chi quan tri vien moi co quyen truy cap.');
            $this->redirect()->toRoute('dashboard', ['action' => 'index']);
            return new ViewModel();
        }
        return null;
    }

    protected function requireGuest(): void
    {
        if (Session::has('user')) {
            $this->redirect()->toRoute('dashboard', ['action' => 'index']);
        }
    }

    protected function rawPost(string $key, string $default = ''): string
    {
        return (string) ($this->getRequest()->getPost($key, $default));
    }
}
