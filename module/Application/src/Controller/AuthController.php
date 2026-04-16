<?php
declare(strict_types=1);

namespace Application\Controller;

use Laminas\View\Model\ViewModel;
use Application\Core\Session;
use Application\Model\UserModel;

class AuthController extends BaseController
{
    private UserModel $userModel;

    public function __construct(UserModel $userModel)
    {
        $this->userModel = $userModel;
    }

    public function loginAction(): ViewModel
    {
        $this->requireGuest();

        $errors = [];
        $old = ['username' => ''];

        if ($this->getRequest()->isPost()) {
            $username = trim((string) $this->getRequest()->getPost('username', ''));
            $password = $this->rawPost('password');
            $old['username'] = $username;

            if ($username === '') {
                $errors['username'] = 'Vui long nhap ten dang nhap.';
            }
            if ($password === '') {
                $errors['password'] = 'Vui long nhap mat khau.';
            }

            if ($errors === []) {
                $user = $this->userModel->getByUsername($username);

                if ($user === null || !password_verify($password, (string) $user['password'])) {
                    $errors['general'] = 'Ten dang nhap hoac mat khau khong dung.';
                } elseif (($user['status'] ?? 'active') !== 'active') {
                    $errors['general'] = 'Tai khoan nay dang tam khoa.';
                } else {
                    Session::set('user', [
                        'id'        => (int) $user['id'],
                        'username'  => (string) $user['username'],
                        'full_name' => (string) $user['full_name'],
                        'email'     => (string) $user['email'],
                        'role'      => (string) $user['role'],
                        'status'    => (string) $user['status'],
                    ]);
                    Session::flash('success', 'Dang nhap thanh cong vao he thong thu vien.');
                    return $this->redirect()->toRoute('dashboard');
                }
            }
        }

        return new ViewModel([
            'title'  => 'Dang nhap thu vien',
            'errors' => $errors,
            'old'    => $old,
        ]);
    }

    public function registerAction(): ViewModel
    {
        $this->requireGuest();

        $errors = [];
        $old = ['full_name' => '', 'username' => '', 'email' => ''];

        if ($this->getRequest()->isPost()) {
            $form = [
                'full_name'        => trim((string) $this->getRequest()->getPost('full_name', '')),
                'username'         => trim((string) $this->getRequest()->getPost('username', '')),
                'email'            => trim((string) $this->getRequest()->getPost('email', '')),
                'password'         => $this->rawPost('password'),
                'confirm_password' => $this->rawPost('confirm_password'),
            ];
            $old = ['full_name' => $form['full_name'], 'username' => $form['username'], 'email' => $form['email']];
            $errors = $this->validateRegistration($form);

            if ($errors === []) {
                $this->userModel->create([
                    'username'  => $form['username'],
                    'password'  => password_hash($form['password'], PASSWORD_DEFAULT),
                    'full_name' => $form['full_name'],
                    'email'     => $form['email'],
                    'role'      => 'member',
                    'status'    => 'active',
                ]);
                Session::flash('success', 'Dang ky thanh cong. Ban co the dang nhap ngay bay gio.');
                return $this->redirect()->toRoute('auth', ['action' => 'login']);
            }
        }

        return new ViewModel([
            'title'  => 'Dang ky thanh vien thu vien',
            'errors' => $errors,
            'old'    => $old,
        ]);
    }

    public function logoutAction()
    {
        if (Session::has('user')) {
            Session::destroy();
            Session::start();
            Session::flash('success', 'Ban da dang xuat khoi he thong.');
        }
        return $this->redirect()->toRoute('auth', ['action' => 'login']);
    }

    private function validateRegistration(array $form): array
    {
        $errors = [];

        if ($form['full_name'] === '') {
            $errors['full_name'] = 'Ho ten la bat buoc.';
        } elseif (mb_strlen($form['full_name']) < 3 || mb_strlen($form['full_name']) > 100) {
            $errors['full_name'] = 'Ho ten phai tu 3 den 100 ky tu.';
        }

        if ($form['username'] === '') {
            $errors['username'] = 'Ten dang nhap la bat buoc.';
        } elseif (mb_strlen($form['username']) < 3 || mb_strlen($form['username']) > 50) {
            $errors['username'] = 'Ten dang nhap phai tu 3 den 50 ky tu.';
        } elseif ($this->userModel->usernameExists($form['username'])) {
            $errors['username'] = 'Ten dang nhap da ton tai.';
        }

        if ($form['email'] === '') {
            $errors['email'] = 'Email la bat buoc.';
        } elseif (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email khong dung dinh dang.';
        }

        if ($form['password'] === '') {
            $errors['password'] = 'Mat khau la bat buoc.';
        } elseif (mb_strlen($form['password']) < 6) {
            $errors['password'] = 'Mat khau phai co it nhat 6 ky tu.';
        }

        if ($form['confirm_password'] !== $form['password']) {
            $errors['confirm_password'] = 'Mat khau xac nhan khong khop.';
        }

        return $errors;
    }
}
