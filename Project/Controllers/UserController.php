<?php
declare(strict_types=1);

namespace Controllers;

use Core\BaseController;
use Core\Session;
use Models\LoanModel;
use Models\UserModel;

class UserController extends BaseController
{
    private LoanModel $loanModel;
    private UserModel $userModel;

    public function __construct()
    {
        $this->requireLogin();
        $this->requireAdmin();
        $this->loanModel = new LoanModel();
        $this->userModel = new UserModel();
    }

    public function index(): void
    {
        $filters = [
            'search' => $this->query('search'),
            'role' => $this->query('role'),
            'status' => $this->query('status'),
        ];

        $this->render('users/index', [
            'title' => 'Quan ly thanh vien',
            'users' => $this->userModel->getAll($filters),
            'filters' => $filters,
        ]);
    }

    public function create(): void
    {
        $this->renderForm('create');
    }

    public function store(): void
    {
        if (!$this->isPost()) {
            $this->redirect('user/create');
        }

        $form = $this->collectFormData();
        $errors = $this->validateForm($form);

        if ($errors !== []) {
            $this->renderForm('create', $form, $errors);
            return;
        }

        $this->userModel->create([
            'username' => $form['username'],
            'password' => password_hash($form['password'], PASSWORD_DEFAULT),
            'full_name' => $form['full_name'],
            'email' => $form['email'],
            'role' => $form['role'],
            'status' => $form['status'],
        ]);

        Session::flash('success', 'Da tao thanh vien moi thanh cong.');
        $this->redirect('user/index');
    }

    public function edit(string $id): void
    {
        $user = $this->userModel->getById((int) $id);

        if ($user === null) {
            Session::flash('error', 'Khong tim thay thanh vien can chinh sua.');
            $this->redirect('user/index');
        }

        $this->renderForm('edit', [
            'id' => (string) $user['id'],
            'full_name' => (string) $user['full_name'],
            'username' => (string) $user['username'],
            'email' => (string) $user['email'],
            'role' => (string) $user['role'],
            'status' => (string) $user['status'],
            'password' => '',
            'confirm_password' => '',
        ], [], $this->loanModel->getHistoryByUserId((int) $id, 5));
    }

    public function update(string $id): void
    {
        if (!$this->isPost()) {
            $this->redirect('user/edit/' . $id);
        }

        $userId = (int) $id;
        $existingUser = $this->userModel->getById($userId);

        if ($existingUser === null) {
            Session::flash('error', 'Thanh vien khong ton tai.');
            $this->redirect('user/index');
        }

        $form = $this->collectFormData();
        $form['id'] = (string) $userId;
        $errors = $this->validateForm($form, true, $userId);

        if ($errors !== []) {
            $this->renderForm('edit', $form, $errors, $this->loanModel->getHistoryByUserId($userId, 5));
            return;
        }

        $passwordHash = $form['password'] !== ''
            ? password_hash($form['password'], PASSWORD_DEFAULT)
            : null;

        $this->userModel->update($userId, [
            'username' => $form['username'],
            'full_name' => $form['full_name'],
            'email' => $form['email'],
            'role' => $form['role'],
            'status' => $form['status'],
        ], $passwordHash);

        $currentUser = $this->currentUser();
        if ((int) ($currentUser['id'] ?? 0) === $userId) {
            Session::set('user', [
                'id' => $userId,
                'username' => $form['username'],
                'full_name' => $form['full_name'],
                'email' => $form['email'],
                'role' => $form['role'],
                'status' => $form['status'],
            ]);
        }

        Session::flash('success', 'Cap nhat thong tin thanh vien thanh cong.');
        $this->redirect('user/index');
    }

    public function delete(string $id): void
    {
        if (!$this->isPost()) {
            $this->redirect('user/index');
        }

        $userId = (int) $id;
        $user = $this->userModel->getById($userId);

        if ($user === null) {
            Session::flash('error', 'Khong tim thay thanh vien can xoa.');
            $this->redirect('user/index');
        }

        if ((int) ($this->currentUser()['id'] ?? 0) === $userId) {
            Session::flash('error', 'Khong the xoa tai khoan dang dang nhap.');
            $this->redirect('user/index');
        }

        if ($this->userModel->hasLoanHistory($userId)) {
            Session::flash('error', 'Thanh vien nay da co lich su muon sach, khong the xoa.');
            $this->redirect('user/index');
        }

        $this->userModel->delete($userId);

        Session::flash('success', 'Da xoa thanh vien thanh cong.');
        $this->redirect('user/index');
    }

    private function collectFormData(): array
    {
        return [
            'full_name' => $this->post('full_name'),
            'username' => $this->post('username'),
            'email' => $this->post('email'),
            'role' => $this->post('role', 'member'),
            'status' => $this->post('status', 'active'),
            'password' => $this->rawPost('password'),
            'confirm_password' => $this->rawPost('confirm_password'),
        ];
    }

    private function validateForm(array $form, bool $isEdit = false, ?int $userId = null): array
    {
        $errors = [];
        $roles = ['admin', 'member'];
        $statuses = ['active', 'inactive'];

        if ($form['full_name'] === '') {
            $errors['full_name'] = 'Ho ten la bat buoc.';
        } elseif (mb_strlen($form['full_name']) < 3 || mb_strlen($form['full_name']) > 100) {
            $errors['full_name'] = 'Ho ten phai tu 3 den 100 ky tu.';
        }

        if ($form['username'] === '') {
            $errors['username'] = 'Ten dang nhap la bat buoc.';
        } elseif (mb_strlen($form['username']) < 3 || mb_strlen($form['username']) > 50) {
            $errors['username'] = 'Ten dang nhap phai tu 3 den 50 ky tu.';
        } elseif ($this->userModel->usernameExists($form['username'], $userId)) {
            $errors['username'] = 'Ten dang nhap da ton tai.';
        }

        if ($form['email'] === '') {
            $errors['email'] = 'Email la bat buoc.';
        } elseif (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email khong dung dinh dang.';
        }

        if (!in_array($form['role'], $roles, true)) {
            $errors['role'] = 'Vai tro duoc chon khong hop le.';
        }

        if (!in_array($form['status'], $statuses, true)) {
            $errors['status'] = 'Trang thai duoc chon khong hop le.';
        }

        if (!$isEdit && $form['password'] === '') {
            $errors['password'] = 'Mat khau la bat buoc.';
        } elseif ($form['password'] !== '' && mb_strlen($form['password']) < 6) {
            $errors['password'] = 'Mat khau phai co it nhat 6 ky tu.';
        }

        if (!$isEdit || $form['password'] !== '' || $form['confirm_password'] !== '') {
            if ($form['confirm_password'] === '') {
                $errors['confirm_password'] = 'Vui long nhap lai mat khau.';
            } elseif ($form['confirm_password'] !== $form['password']) {
                $errors['confirm_password'] = 'Mat khau xac nhan khong khop.';
            }
        }

        return $errors;
    }

    private function renderForm(
        string $mode,
        array $form = [],
        array $errors = [],
        array $loanHistory = []
    ): void {
        $defaults = [
            'id' => '',
            'full_name' => '',
            'username' => '',
            'email' => '',
            'role' => 'member',
            'status' => 'active',
            'password' => '',
            'confirm_password' => '',
        ];

        $this->render('users/form', [
            'title' => $mode === 'edit' ? 'Cap nhat thanh vien' : 'Them thanh vien',
            'mode' => $mode,
            'form' => array_merge($defaults, $form),
            'errors' => $errors,
            'loanHistory' => $loanHistory,
        ]);
    }
}
