<?php

declare(strict_types=1);

namespace Application\Controller;

use Laminas\View\Model\ViewModel;
use Application\Core\Session;
use Application\Model\LoanModel;
use Application\Model\UserModel;

class UserController extends BaseController
{
    private LoanModel $loanModel;
    private UserModel $userModel;

    public function __construct(LoanModel $loanModel, UserModel $userModel)
    {
        $this->loanModel = $loanModel;
        $this->userModel = $userModel;
    }

    public function indexAction(): ViewModel
    {
        if ($r = $this->requireLogin()) {
            return $r;
        }
        if ($r = $this->requireAdmin()) {
            return $r;
        }

        $filters = [
            'search' => trim((string) $this->getRequest()->getQuery('search', '')),
            'role'   => (string) $this->getRequest()->getQuery('role', ''),
            'status' => (string) $this->getRequest()->getQuery('status', ''),
        ];

        return new ViewModel([
            'title'   => 'Quan ly thanh vien',
            'users'   => $this->userModel->getAll($filters),
            'filters' => $filters,
        ]);
    }

    public function createAction(): ViewModel
    {
        if ($r = $this->requireLogin()) {
            return $r;
        }
        if ($r = $this->requireAdmin()) {
            return $r;
        }

        if ($this->getRequest()->isPost()) {
            $form   = $this->collectFormData();
            $errors = $this->validateForm($form);

            if ($errors === []) {
                $this->userModel->create([
                    'username'  => $form['username'],
                    'password'  => password_hash($form['password'], PASSWORD_DEFAULT),
                    'full_name' => $form['full_name'],
                    'email'     => $form['email'],
                    'role'      => $form['role'],
                    'status'    => $form['status'],
                ]);
                Session::flash('success', 'Da tao thanh vien moi thanh cong.');
                return $this->redirect()->toRoute('user');
            }
            return $this->buildFormView('create', $form, $errors);
        }

        return $this->buildFormView('create');
    }

    public function editAction(): ViewModel
    {
        if ($r = $this->requireLogin()) {
            return $r;
        }
        if ($r = $this->requireAdmin()) {
            return $r;
        }

        $id   = (int) $this->params()->fromRoute('id', 0);
        $user = $this->userModel->getById($id);

        if ($user === null) {
            Session::flash('error', 'Khong tim thay thanh vien can chinh sua.');
            return $this->redirect()->toRoute('user');
        }

        if ($this->getRequest()->isPost()) {
            $form         = $this->collectFormData();
            $form['id']   = (string) $id;
            $errors       = $this->validateForm($form, true, $id);

            if ($errors === []) {
                $passwordHash = $form['password'] !== ''
                    ? password_hash($form['password'], PASSWORD_DEFAULT)
                    : null;

                $this->userModel->update($id, [
                    'username'  => $form['username'],
                    'full_name' => $form['full_name'],
                    'email'     => $form['email'],
                    'role'      => $form['role'],
                    'status'    => $form['status'],
                ], $passwordHash);

                // Sync session if editing self
                $currentUser = $this->currentUser();
                if ((int) ($currentUser['id'] ?? 0) === $id) {
                    Session::set('user', [
                        'id' => $id, 'username' => $form['username'], 'full_name' => $form['full_name'],
                        'email' => $form['email'], 'role' => $form['role'], 'status' => $form['status'],
                    ]);
                }

                Session::flash('success', 'Cap nhat thong tin thanh vien thanh cong.');
                return $this->redirect()->toRoute('user');
            }

            return $this->buildFormView('edit', $form, $errors, $this->loanModel->getHistoryByUserId($id, 5));
        }

        return $this->buildFormView('edit', [
            'id'               => (string) $user['id'],
            'full_name'        => (string) $user['full_name'],
            'username'         => (string) $user['username'],
            'email'            => (string) $user['email'],
            'role'             => (string) $user['role'],
            'status'           => (string) $user['status'],
            'password'         => '',
            'confirm_password' => '',
        ], [], $this->loanModel->getHistoryByUserId($id, 5));
    }

    public function deleteAction()
    {
        if ($r = $this->requireLogin()) {
            return $r;
        }
        if ($r = $this->requireAdmin()) {
            return $r;
        }

        if (! $this->getRequest()->isPost()) {
            return $this->redirect()->toRoute('user');
        }

        $userId = (int) $this->params()->fromRoute('id', 0);
        $user   = $this->userModel->getById($userId);

        if ($user === null) {
            Session::flash('error', 'Khong tim thay thanh vien can xoa.');
            return $this->redirect()->toRoute('user');
        }

        if ((int) ($this->currentUser()['id'] ?? 0) === $userId) {
            Session::flash('error', 'Khong the xoa tai khoan dang dang nhap.');
            return $this->redirect()->toRoute('user');
        }

        if ($this->userModel->hasLoanHistory($userId)) {
            Session::flash('error', 'Thanh vien nay da co lich su muon sach, khong the xoa.');
            return $this->redirect()->toRoute('user');
        }

        $this->userModel->delete($userId);
        Session::flash('success', 'Da xoa thanh vien thanh cong.');
        return $this->redirect()->toRoute('user');
    }

    // ── Private helpers ──────────────────────────────────────────────────
    private function collectFormData(): array
    {
        return [
            'full_name'        => trim((string) $this->getRequest()->getPost('full_name', '')),
            'username'         => trim((string) $this->getRequest()->getPost('username', '')),
            'email'            => trim((string) $this->getRequest()->getPost('email', '')),
            'role'             => (string) $this->getRequest()->getPost('role', 'member'),
            'status'           => (string) $this->getRequest()->getPost('status', 'active'),
            'password'         => $this->rawPost('password'),
            'confirm_password' => $this->rawPost('confirm_password'),
        ];
    }

    private function buildFormView(
        string $mode,
        array $form = [],
        array $errors = [],
        array $loanHistory = []
    ): ViewModel {
        $defaults = [
            'id' => '', 'full_name' => '', 'username' => '', 'email' => '',
            'role' => 'member', 'status' => 'active', 'password' => '', 'confirm_password' => '',
        ];
        $vm = new ViewModel([
            'title'       => $mode === 'edit' ? 'Cap nhat thanh vien' : 'Them thanh vien',
            'mode'        => $mode,
            'form'        => array_merge($defaults, $form),
            'errors'      => $errors,
            'loanHistory' => $loanHistory,
        ]);
        $vm->setTemplate('application/user/form');
        return $vm;
    }

    private function validateForm(array $form, bool $isEdit = false, ?int $userId = null): array
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
        } elseif ($this->userModel->usernameExists($form['username'], $userId)) {
            $errors['username'] = 'Ten dang nhap da ton tai.';
        }

        if ($form['email'] === '') {
            $errors['email'] = 'Email la bat buoc.';
        } elseif (! filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email khong dung dinh dang.';
        }

        if (! in_array($form['role'], ['admin', 'member'], true)) {
            $errors['role'] = 'Vai tro khong hop le.';
        }
        if (! in_array($form['status'], ['active', 'inactive'], true)) {
            $errors['status'] = 'Trang thai khong hop le.';
        }

        if (! $isEdit && $form['password'] === '') {
            $errors['password'] = 'Mat khau la bat buoc.';
        } elseif ($form['password'] !== '' && mb_strlen($form['password']) < 6) {
            $errors['password'] = 'Mat khau phai co it nhat 6 ky tu.';
        }

        if (! $isEdit || $form['password'] !== '') {
            if ($form['confirm_password'] !== $form['password']) {
                $errors['confirm_password'] = 'Mat khau xac nhan khong khop.';
            }
        }

        return $errors;
    }
}
