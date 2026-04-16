<?php
declare(strict_types=1);

namespace Controllers;

use Core\BaseController;
use Core\Session;
use Models\BookModel;
use Models\LoanModel;
use Models\UserModel;

class LoanController extends BaseController
{
    private BookModel $bookModel;
    private LoanModel $loanModel;
    private UserModel $userModel;

    public function __construct()
    {
        $this->requireLogin();
        $this->bookModel = new BookModel();
        $this->loanModel = new LoanModel();
        $this->userModel = new UserModel();
    }

    public function index(): void
    {
        $isAdmin = $this->isAdmin();
        $currentUser = $this->currentUser();
        $userId = (int) ($currentUser['id'] ?? 0);

        $filters = [
            'search' => $this->query('search'),
            'status' => $this->query('status'),
            'book_id' => $this->query('book_id'),
        ];

        if ($isAdmin) {
            $filters['user_id'] = $this->query('user_id');
        }

        $this->render('loans/list', [
            'title' => $isAdmin ? 'Quan ly muon tra' : 'Phieu muon cua toi',
            'isAdmin' => $isAdmin,
            'filters' => $filters,
            'summary' => $this->loanModel->getSummary($isAdmin ? null : $userId),
            'loans' => $isAdmin
                ? $this->loanModel->getAll($filters)
                : $this->loanModel->getAllForUser($userId, $filters),
            'users' => $isAdmin ? $this->userModel->getActiveOptions() : [],
            'books' => $this->bookModel->getOptions(),
        ]);
    }

    public function create(): void
    {
        if ($this->isAdmin()) {
            $this->renderAdminForm('create', [
                'borrow_date' => date('Y-m-d'),
                'due_date' => date('Y-m-d', strtotime('+14 days')),
                'status' => 'borrowed',
            ]);
            return;
        }

        $this->renderMemberRequestForm([
            'book_id' => '',
            'notes' => '',
        ]);
    }

    public function store(): void
    {
        if (!$this->isPost()) {
            $this->redirect('loan/create');
        }

        if ($this->isAdmin()) {
            $this->storeAdminLoan();
            return;
        }

        $this->storeMemberRequest();
    }

    public function edit(string $id): void
    {
        $this->requireAdmin();

        $loan = $this->loanModel->getById((int) $id);

        if ($loan === null) {
            Session::flash('error', 'Khong tim thay phieu muon can chinh sua.');
            $this->redirect('loan/index');
        }

        $this->renderAdminForm('edit', [
            'id' => (string) $loan['id'],
            'user_id' => (string) $loan['user_id'],
            'book_id' => (string) $loan['book_id'],
            'borrow_date' => (string) $loan['borrow_date'],
            'due_date' => (string) $loan['due_date'],
            'returned_date' => (string) ($loan['returned_date'] ?? ''),
            'status' => (string) ($loan['display_status'] ?? $loan['status']),
            'notes' => (string) ($loan['notes'] ?? ''),
        ], [], $loan);
    }

    public function update(string $id): void
    {
        $this->requireAdmin();

        if (!$this->isPost()) {
            $this->redirect('loan/edit/' . $id);
        }

        $loanId = (int) $id;
        $existingLoan = $this->loanModel->getById($loanId);

        if ($existingLoan === null) {
            Session::flash('error', 'Phieu muon khong ton tai.');
            $this->redirect('loan/index');
        }

        $form = $this->collectAdminFormData();
        $form['id'] = (string) $loanId;
        $errors = $this->validateAdminForm($form, $loanId);

        if ($errors !== []) {
            $this->renderAdminForm('edit', $form, $errors, $existingLoan);
            return;
        }

        try {
            $this->loanModel->update($loanId, $form);
            Session::flash('success', 'Cap nhat phieu muon thanh cong.');
            $this->redirect('loan/index');
        } catch (\Throwable $throwable) {
            $errors['general'] = $throwable->getMessage();
            $this->renderAdminForm('edit', $form, $errors, $existingLoan);
        }
    }

    public function approve(string $id): void
    {
        $this->requireAdmin();

        if (!$this->isPost()) {
            $this->redirect('loan/index');
        }

        try {
            $this->loanModel->approve((int) $id);
            Session::flash('success', 'Da duyet yeu cau muon sach va cap nhat ton kho.');
        } catch (\Throwable $throwable) {
            Session::flash('error', $throwable->getMessage());
        }

        $this->redirect('loan/index');
    }

    public function markReturn(string $id): void
    {
        $this->requireAdmin();

        if (!$this->isPost()) {
            $this->redirect('loan/index');
        }

        try {
            $this->loanModel->markReturned((int) $id);
            Session::flash('success', 'Da ghi nhan sach da duoc tra.');
        } catch (\Throwable $throwable) {
            Session::flash('error', $throwable->getMessage());
        }

        $this->redirect('loan/index');
    }

    public function cancel(string $id): void
    {
        if (!$this->isPost()) {
            $this->redirect('loan/index');
        }

        $loanId = (int) $id;
        $loan = $this->isAdmin()
            ? $this->loanModel->getById($loanId)
            : $this->loanModel->getByIdForUser($loanId, (int) ($this->currentUser()['id'] ?? 0));

        if ($loan === null) {
            Session::flash('error', 'Khong tim thay phieu muon phu hop de huy.');
            $this->redirect('loan/index');
        }

        try {
            $this->loanModel->cancel($loanId);
            Session::flash(
                'success',
                $this->isAdmin()
                    ? 'Da huy yeu cau muon sach.'
                    : 'Da huy yeu cau muon sach cua ban.'
            );
        } catch (\Throwable $throwable) {
            Session::flash('error', $throwable->getMessage());
        }

        $this->redirect('loan/index');
    }

    private function storeAdminLoan(): void
    {
        $form = $this->collectAdminFormData();
        $errors = $this->validateAdminForm($form);

        if ($errors !== []) {
            $this->renderAdminForm('create', $form, $errors);
            return;
        }

        try {
            $this->loanModel->create($form);
            Session::flash('success', 'Da lap phieu muon thanh cong.');
            $this->redirect('loan/index');
        } catch (\Throwable $throwable) {
            $errors['general'] = $throwable->getMessage();
            $this->renderAdminForm('create', $form, $errors);
        }
    }

    private function storeMemberRequest(): void
    {
        $currentUser = $this->currentUser();
        $userId = (int) ($currentUser['id'] ?? 0);
        $form = [
            'book_id' => $this->post('book_id'),
            'notes' => $this->post('notes'),
        ];
        $errors = $this->validateMemberRequest($form, $userId);

        if ($errors !== []) {
            $this->renderMemberRequestForm($form, $errors);
            return;
        }

        try {
            $this->loanModel->create([
                'user_id' => $userId,
                'book_id' => $form['book_id'],
                'borrow_date' => date('Y-m-d'),
                'due_date' => date('Y-m-d', strtotime('+14 days')),
                'returned_date' => '',
                'status' => 'pending',
                'notes' => $form['notes'],
            ]);
            Session::flash('success', 'Da gui yeu cau muon sach. Thu thu se duyet trong danh sach phieu muon.');
            $this->redirect('loan/index');
        } catch (\Throwable $throwable) {
            $errors['general'] = $throwable->getMessage();
            $this->renderMemberRequestForm($form, $errors);
        }
    }

    private function collectAdminFormData(): array
    {
        return [
            'user_id' => $this->post('user_id'),
            'book_id' => $this->post('book_id'),
            'borrow_date' => $this->post('borrow_date'),
            'due_date' => $this->post('due_date'),
            'returned_date' => $this->post('returned_date'),
            'status' => $this->post('status', 'borrowed'),
            'notes' => $this->post('notes'),
        ];
    }

    private function validateAdminForm(array $form, ?int $loanId = null): array
    {
        $errors = [];
        $allowedStatuses = ['pending', 'borrowed', 'overdue', 'returned', 'cancelled'];

        if (!filter_var($form['user_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
            $errors['user_id'] = 'Vui long chon thanh vien hop le.';
        } else {
            $user = $this->userModel->getById((int) $form['user_id']);

            if ($user === null || (string) ($user['role'] ?? '') !== 'member') {
                $errors['user_id'] = 'Chi co the lap phieu cho thanh vien thu vien.';
            } elseif ((string) ($user['status'] ?? 'inactive') !== 'active') {
                $errors['user_id'] = 'Thanh vien duoc chon dang tam khoa.';
            }
        }

        if (!filter_var($form['book_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
            $errors['book_id'] = 'Vui long chon sach hop le.';
        } else {
            $book = $this->bookModel->getById((int) $form['book_id']);

            if ($book === null) {
                $errors['book_id'] = 'Sach duoc chon khong ton tai.';
            }
        }

        if (
            !isset($errors['user_id'])
            && !isset($errors['book_id'])
            && $this->loanModel->userHasOpenLoanForBook(
                (int) $form['user_id'],
                (int) $form['book_id'],
                $loanId
            )
        ) {
            $errors['book_id'] = 'Thanh vien nay dang co yeu cau hoac phieu muon mo doi voi dau sach nay.';
        }

        if (!$this->isValidDate($form['borrow_date'])) {
            $errors['borrow_date'] = 'Ngay muon khong hop le.';
        }

        if (!$this->isValidDate($form['due_date'])) {
            $errors['due_date'] = 'Han tra khong hop le.';
        } elseif ($this->isValidDate($form['borrow_date']) && $form['due_date'] < $form['borrow_date']) {
            $errors['due_date'] = 'Han tra phai sau hoac bang ngay muon.';
        }

        if (!in_array($form['status'], $allowedStatuses, true)) {
            $errors['status'] = 'Trang thai phieu muon khong hop le.';
        }

        if ($form['returned_date'] !== '' && !$this->isValidDate($form['returned_date'])) {
            $errors['returned_date'] = 'Ngay tra thuc te khong hop le.';
        }

        if (
            $form['returned_date'] !== ''
            && $this->isValidDate($form['borrow_date'])
            && $form['returned_date'] < $form['borrow_date']
        ) {
            $errors['returned_date'] = 'Ngay tra thuc te khong the som hon ngay muon.';
        }

        if ($form['status'] !== 'returned' && $form['returned_date'] !== '') {
            $errors['returned_date'] = 'Chi phieu da tra moi duoc nhap ngay tra thuc te.';
        }

        if (mb_strlen($form['notes']) > 255) {
            $errors['notes'] = 'Ghi chu khong duoc vuot qua 255 ky tu.';
        }

        return $errors;
    }

    private function validateMemberRequest(array $form, int $userId): array
    {
        $errors = [];

        if (!filter_var($form['book_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
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

        if (mb_strlen($form['notes']) > 255) {
            $errors['notes'] = 'Ghi chu khong duoc vuot qua 255 ky tu.';
        }

        return $errors;
    }

    private function renderAdminForm(
        string $mode,
        array $form = [],
        array $errors = [],
        ?array $loan = null
    ): void {
        $defaults = [
            'id' => '',
            'user_id' => '',
            'book_id' => '',
            'borrow_date' => date('Y-m-d'),
            'due_date' => date('Y-m-d', strtotime('+14 days')),
            'returned_date' => '',
            'status' => 'borrowed',
            'notes' => '',
        ];

        $this->render('loans/form', [
            'title' => $mode === 'edit' ? 'Cap nhat phieu muon' : 'Lap phieu muon',
            'mode' => $mode,
            'isAdmin' => true,
            'form' => array_merge($defaults, $form),
            'errors' => $errors,
            'loan' => $loan,
            'users' => $this->userModel->getActiveOptions(),
            'books' => $this->bookModel->getOptions(),
            'statusOptions' => [
                'pending' => 'Cho duyet',
                'borrowed' => 'Dang muon',
                'overdue' => 'Qua han',
                'returned' => 'Da tra',
                'cancelled' => 'Da huy',
            ],
        ]);
    }

    private function renderMemberRequestForm(array $form = [], array $errors = []): void
    {
        $this->render('loans/form', [
            'title' => 'Gui yeu cau muon sach',
            'mode' => 'create',
            'isAdmin' => false,
            'form' => array_merge([
                'book_id' => '',
                'notes' => '',
            ], $form),
            'errors' => $errors,
            'users' => [],
            'books' => $this->bookModel->getBorrowableOptions(),
            'statusOptions' => [],
            'requester' => $this->currentUser(),
        ]);
    }
}
