<?php

declare(strict_types=1);

namespace Application\Controller;

use Laminas\View\Model\ViewModel;
use Application\Core\Session;
use Application\Model\BookModel;
use Application\Model\LoanModel;
use Application\Model\UserModel;

class LoanController extends BaseController
{
    private BookModel $bookModel;
    private LoanModel $loanModel;
    private UserModel $userModel;

    public function __construct(BookModel $bookModel, LoanModel $loanModel, UserModel $userModel)
    {
        $this->bookModel = $bookModel;
        $this->loanModel = $loanModel;
        $this->userModel = $userModel;
    }

    public function indexAction(): ViewModel
    {
        if ($r = $this->requireLogin()) {
            return $r;
        }

        $isAdmin     = $this->isAdmin();
        $currentUser = $this->currentUser();
        $userId      = (int) ($currentUser['id'] ?? 0);

        $filters = [
            'search'  => trim((string) $this->getRequest()->getQuery('search', '')),
            'status'  => (string) $this->getRequest()->getQuery('status', ''),
            'book_id' => (string) $this->getRequest()->getQuery('book_id', ''),
        ];
        if ($isAdmin) {
            $filters['user_id'] = (string) $this->getRequest()->getQuery('user_id', '');
        }

        return new ViewModel([
            'title'   => $isAdmin ? 'Quan ly muon tra' : 'Phieu muon cua toi',
            'isAdmin' => $isAdmin,
            'filters' => $filters,
            'summary' => $this->loanModel->getSummary($isAdmin ? null : $userId),
            'loans'   => $isAdmin
                ? $this->loanModel->getAll($filters)
                : $this->loanModel->getAllForUser($userId, $filters),
            'users'   => $isAdmin ? $this->userModel->getActiveOptions() : [],
            'books'   => $this->bookModel->getOptions(),
        ]);
    }

    public function createAction(): ViewModel
    {
        if ($r = $this->requireLogin()) {
            return $r;
        }

        if ($this->isAdmin()) {
            // Admin POST
            if ($this->getRequest()->isPost()) {
                $form   = $this->collectAdminFormData();
                $errors = $this->validateAdminForm($form);
                if ($errors === []) {
                    try {
                        $this->loanModel->create($form);
                        Session::flash('success', 'Da lap phieu muon thanh cong.');
                        return $this->redirect()->toRoute('loan');
                    } catch (\Throwable $e) {
                        $errors['general'] = $e->getMessage();
                    }
                }
                return $this->buildAdminFormView('create', $form, $errors);
            }
            return $this->buildAdminFormView('create', [
                'borrow_date' => date('Y-m-d'),
                'due_date'    => date('Y-m-d', strtotime('+14 days')),
                'status'      => 'borrowed',
            ]);
        }

        // Member POST
        if ($this->getRequest()->isPost()) {
            $currentUser = $this->currentUser();
            $userId      = (int) ($currentUser['id'] ?? 0);
            $form        = [
                'book_id' => (string) $this->getRequest()->getPost('book_id', ''),
                'notes'   => trim((string) $this->getRequest()->getPost('notes', '')),
            ];
            $errors = $this->validateMemberRequest($form, $userId);
            if ($errors === []) {
                try {
                    $this->loanModel->create([
                        'user_id'       => $userId,
                        'book_id'       => $form['book_id'],
                        'borrow_date'   => date('Y-m-d'),
                        'due_date'      => date('Y-m-d', strtotime('+14 days')),
                        'returned_date' => '',
                        'status'        => 'pending',
                        'notes'         => $form['notes'],
                    ]);
                    Session::flash('success', 'Da gui yeu cau muon sach. Thu thu se duyet trong thoi gian som nhat.');
                    return $this->redirect()->toRoute('loan');
                } catch (\Throwable $e) {
                    $errors['general'] = $e->getMessage();
                }
            }
            return $this->buildMemberFormView($form, $errors);
        }

        return $this->buildMemberFormView(['book_id' => '', 'notes' => '']);
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
        $loan = $this->loanModel->getById($id);

        if ($loan === null) {
            Session::flash('error', 'Khong tim thay phieu muon can chinh sua.');
            return $this->redirect()->toRoute('loan');
        }

        if ($this->getRequest()->isPost()) {
            $form         = $this->collectAdminFormData();
            $form['id']   = (string) $id;
            $errors       = $this->validateAdminForm($form, $id);

            if ($errors === []) {
                try {
                    $this->loanModel->update($id, $form);
                    Session::flash('success', 'Cap nhat phieu muon thanh cong.');
                    return $this->redirect()->toRoute('loan');
                } catch (\Throwable $e) {
                    $errors['general'] = $e->getMessage();
                }
            }
            return $this->buildAdminFormView('edit', $form, $errors, $loan);
        }

        return $this->buildAdminFormView('edit', [
            'id'            => (string) $loan['id'],
            'user_id'       => (string) $loan['user_id'],
            'book_id'       => (string) $loan['book_id'],
            'borrow_date'   => (string) $loan['borrow_date'],
            'due_date'      => (string) $loan['due_date'],
            'returned_date' => (string) ($loan['returned_date'] ?? ''),
            'status'        => (string) ($loan['display_status'] ?? $loan['status']),
            'notes'         => (string) ($loan['notes'] ?? ''),
        ], [], $loan);
    }

    public function approveAction()
    {
        if ($r = $this->requireLogin()) {
            return $r;
        }
        if ($r = $this->requireAdmin()) {
            return $r;
        }

        if (! $this->getRequest()->isPost()) {
            return $this->redirect()->toRoute('loan');
        }
        try {
            $this->loanModel->approve((int) $this->params()->fromRoute('id', 0));
            Session::flash('success', 'Da duyet yeu cau muon sach va cap nhat ton kho.');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        return $this->redirect()->toRoute('loan');
    }

    public function markReturnAction()
    {
        if ($r = $this->requireLogin()) {
            return $r;
        }
        if ($r = $this->requireAdmin()) {
            return $r;
        }

        if (! $this->getRequest()->isPost()) {
            return $this->redirect()->toRoute('loan');
        }
        try {
            $this->loanModel->markReturned((int) $this->params()->fromRoute('id', 0));
            Session::flash('success', 'Da ghi nhan sach da duoc tra.');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        return $this->redirect()->toRoute('loan');
    }

    public function cancelAction()
    {
        if ($r = $this->requireLogin()) {
            return $r;
        }

        if (! $this->getRequest()->isPost()) {
            return $this->redirect()->toRoute('loan');
        }

        $loanId = (int) $this->params()->fromRoute('id', 0);
        $uid    = (int) ($this->currentUser()['id'] ?? 0);
        $loan   = $this->isAdmin()
            ? $this->loanModel->getById($loanId)
            : $this->loanModel->getByIdForUser($loanId, $uid);

        if ($loan === null) {
            Session::flash('error', 'Khong tim thay phieu muon phu hop de huy.');
            return $this->redirect()->toRoute('loan');
        }

        try {
            $this->loanModel->cancel($loanId);
            Session::flash(
                'success',
                $this->isAdmin()
                    ? 'Da huy yeu cau muon sach.'
                    : 'Da huy yeu cau muon sach cua ban.'
            );
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        return $this->redirect()->toRoute('loan');
    }

    // ── Private helpers ──────────────────────────────────────────────────
    private function isValidDate(string $value): bool
    {
        if ($value === '') {
            return false;
        }
        $d = \DateTime::createFromFormat('Y-m-d', $value);
        return $d !== false && $d->format('Y-m-d') === $value;
    }

    private function collectAdminFormData(): array
    {
        return [
            'user_id'       => (string) $this->getRequest()->getPost('user_id', ''),
            'book_id'       => (string) $this->getRequest()->getPost('book_id', ''),
            'borrow_date'   => (string) $this->getRequest()->getPost('borrow_date', ''),
            'due_date'      => (string) $this->getRequest()->getPost('due_date', ''),
            'returned_date' => (string) $this->getRequest()->getPost('returned_date', ''),
            'status'        => (string) $this->getRequest()->getPost('status', 'borrowed'),
            'notes'         => trim((string) $this->getRequest()->getPost('notes', '')),
        ];
    }

    private function buildAdminFormView(
        string $mode,
        array $form = [],
        array $errors = [],
        ?array $loan = null
    ): ViewModel {
        $defaults = [
            'id' => '', 'user_id' => '', 'book_id' => '',
            'borrow_date' => date('Y-m-d'), 'due_date' => date('Y-m-d', strtotime('+14 days')),
            'returned_date' => '', 'status' => 'borrowed', 'notes' => '',
        ];
        $vm = new ViewModel([
            'title'         => $mode === 'edit' ? 'Cap nhat phieu muon' : 'Lap phieu muon',
            'mode'          => $mode,
            'isAdmin'       => true,
            'form'          => array_merge($defaults, $form),
            'errors'        => $errors,
            'loan'          => $loan,
            'users'         => $this->userModel->getActiveOptions(),
            'books'         => $this->bookModel->getOptions(),
            'statusOptions' => [
                'pending' => 'Cho duyet',
                'borrowed' => 'Dang muon',
                'overdue' => 'Qua han',
                'returned' => 'Da tra',
                'cancelled' => 'Da huy',
            ],
        ]);
        $vm->setTemplate('application/loan/form');
        return $vm;
    }

    private function buildMemberFormView(array $form = [], array $errors = []): ViewModel
    {
        $vm = new ViewModel([
            'title'     => 'Gui yeu cau muon sach',
            'mode'      => 'create',
            'isAdmin'   => false,
            'form'      => array_merge(['book_id' => '', 'notes' => ''], $form),
            'errors'    => $errors,
            'books'     => $this->bookModel->getBorrowableOptions(),
            'requester' => $this->currentUser(),
        ]);
        $vm->setTemplate('application/loan/form');
        return $vm;
    }

    private function validateAdminForm(array $form, ?int $loanId = null): array
    {
        $errors = [];
        if (! filter_var($form['user_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
            $errors['user_id'] = 'Vui long chon thanh vien hop le.';
        } else {
            $user = $this->userModel->getById((int) $form['user_id']);
            if ($user === null || ($user['role'] ?? '') !== 'member') {
                $errors['user_id'] = 'Chi co the lap phieu cho thanh vien thu vien.';
            } elseif (($user['status'] ?? '') !== 'active') {
                $errors['user_id'] = 'Thanh vien duoc chon dang tam khoa.';
            }
        }

        if (! filter_var($form['book_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
            $errors['book_id'] = 'Vui long chon sach hop le.';
        } else {
            $book = $this->bookModel->getById((int) $form['book_id']);
            if ($book === null) {
                $errors['book_id'] = 'Sach duoc chon khong ton tai.';
            }
        }

        if (! isset($errors['user_id']) && ! isset($errors['book_id'])) {
            if ($this->loanModel->userHasOpenLoanForBook((int) $form['user_id'], (int) $form['book_id'], $loanId)) {
                $errors['book_id'] = 'Thanh vien nay dang co yeu cau hoac phieu muon mo doi voi dau sach nay.';
            }
        }

        if (! $this->isValidDate($form['borrow_date'])) {
            $errors['borrow_date'] = 'Ngay muon khong hop le.';
        }
        if (! $this->isValidDate($form['due_date'])) {
            $errors['due_date'] = 'Han tra khong hop le.';
        } elseif ($this->isValidDate($form['borrow_date']) && $form['due_date'] < $form['borrow_date']) {
            $errors['due_date'] = 'Han tra phai sau hoac bang ngay muon.';
        }

        $allowed = ['pending', 'borrowed', 'overdue', 'returned', 'cancelled'];
        if (! in_array($form['status'], $allowed, true)) {
            $errors['status'] = 'Trang thai khong hop le.';
        }

        if ($form['returned_date'] !== '' && ! $this->isValidDate($form['returned_date'])) {
            $errors['returned_date'] = 'Ngay tra thuc te khong hop le.';
        }
        if ($form['status'] !== 'returned' && $form['returned_date'] !== '') {
            $errors['returned_date'] = 'Chi phieu da tra moi duoc nhap ngay tra thuc te.';
        }

        return $errors;
    }

    private function validateMemberRequest(array $form, int $userId): array
    {
        $errors = [];
        if (! filter_var($form['book_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
            $errors['book_id'] = 'Vui long chon sach can muon.';
        } else {
            $book = $this->bookModel->getById((int) $form['book_id']);
            if ($book === null) {
                $errors['book_id'] = 'Sach duoc chon khong ton tai.';
            } elseif ((int) ($book['available_quantity'] ?? 0) < 1) {
                $errors['book_id'] = 'Sach nay tam thoi khong con ban sao kha dung.';
            } elseif ($this->loanModel->userHasOpenLoanForBook($userId, (int) $form['book_id'])) {
                $errors['book_id'] = 'Ban da co yeu cau hoac dang muon dau sach nay.';
            }
        }
        return $errors;
    }
}
