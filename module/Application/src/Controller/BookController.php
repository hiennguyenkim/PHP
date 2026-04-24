<?php

declare(strict_types=1);

namespace Application\Controller;

use Laminas\View\Model\ViewModel;
use Application\Core\Session;
use Application\Model\BookModel;

class BookController extends BaseController
{
    private BookModel $bookModel;

    public function __construct(BookModel $bookModel)
    {
        $this->bookModel = $bookModel;
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
            'search'   => trim((string) $this->getRequest()->getQuery('search', '')),
            'category' => (string) $this->getRequest()->getQuery('category', ''),
            'status'   => (string) $this->getRequest()->getQuery('status', ''),
        ];

        return new ViewModel([
            'title'      => 'Quan ly sach',
            'books'      => $this->bookModel->getAll($filters),
            'categories' => $this->bookModel->getCategories(),
            'filters'    => $filters,
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
                $this->bookModel->create([
                    'title'          => $form['title'],
                    'author'         => $form['author'],
                    'category'       => $form['category'],
                    'publisher'      => $form['publisher'] !== '' ? $form['publisher'] : null,
                    'published_year' => $form['published_year'] !== '' ? (int) $form['published_year'] : null,
                    'isbn'           => $form['isbn'] !== '' ? $form['isbn'] : null,
                    'language'       => $form['language'],
                    'description'    => $form['description'] !== '' ? $form['description'] : null,
                    'cover_image'    => $form['cover_image'] !== '' ? $form['cover_image'] : null,
                    'quantity'       => (int) $form['quantity'],
                    'available_quantity' => (int) $form['quantity'],
                ]);
                Session::flash('success', 'Da them sach moi vao thu vien.');
                return $this->redirect()->toRoute('book');
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
        $book = $this->bookModel->getById($id);

        if ($book === null) {
            Session::flash('error', 'Khong tim thay sach can chinh sua.');
            return $this->redirect()->toRoute('book');
        }

        if ($this->getRequest()->isPost()) {
            $form                    = $this->collectFormData();
            $form['id']              = (string) $id;
            $form['borrowed_quantity'] = (string) ($book['borrowed_quantity'] ?? 0);
            $errors                  = $this->validateForm($form, $book);

            if ($errors === []) {
                $borrowed = (int) ($book['borrowed_quantity'] ?? 0);
                $qty      = (int) $form['quantity'];
                $this->bookModel->update($id, [
                    'title'          => $form['title'],
                    'author'         => $form['author'],
                    'category'       => $form['category'],
                    'publisher'      => $form['publisher'] !== '' ? $form['publisher'] : null,
                    'published_year' => $form['published_year'] !== '' ? (int) $form['published_year'] : null,
                    'isbn'           => $form['isbn'] !== '' ? $form['isbn'] : null,
                    'language'       => $form['language'],
                    'description'    => $form['description'] !== '' ? $form['description'] : null,
                    'cover_image'    => $form['cover_image'] !== '' ? $form['cover_image'] : null,
                    'quantity'       => $qty,
                    'available_quantity' => $qty - $borrowed,
                ]);
                Session::flash('success', 'Cap nhat sach thanh cong.');
                return $this->redirect()->toRoute('book');
            }

            $form['available_quantity'] = (string) ($book['available_quantity'] ?? 0);
            return $this->buildFormView('edit', $form, $errors);
        }

        return $this->buildFormView('edit', [
            'id'                 => (string) $book['id'],
            'title'              => (string) $book['title'],
            'author'             => (string) $book['author'],
            'category'           => (string) $book['category'],
            'publisher'          => (string) ($book['publisher'] ?? ''),
            'published_year'     => (string) ($book['published_year'] ?? ''),
            'isbn'               => (string) ($book['isbn'] ?? ''),
            'language'           => (string) ($book['language'] ?? 'Tiếng Việt'),
            'description'        => (string) ($book['description'] ?? ''),
            'cover_image'        => (string) ($book['cover_image'] ?? ''),
            'quantity'           => (string) $book['quantity'],
            'borrowed_quantity'  => (string) ($book['borrowed_quantity'] ?? 0),
            'available_quantity' => (string) $book['available_quantity'],
        ]);
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
            return $this->redirect()->toRoute('book');
        }

        $id   = (int) $this->params()->fromRoute('id', 0);
        $book = $this->bookModel->getById($id);

        if ($book === null) {
            Session::flash('error', 'Khong tim thay sach can xoa.');
            return $this->redirect()->toRoute('book');
        }

        if ($this->bookModel->hasLoanHistory($id)) {
            Session::flash('error', 'Sach nay da co lich su muon tra, khong the xoa.');
            return $this->redirect()->toRoute('book');
        }

        $this->bookModel->delete($id);
        Session::flash('success', 'Da xoa sach khoi thu vien.');
        return $this->redirect()->toRoute('book');
    }

    private function collectFormData(): array
    {
        return [
            'title'          => trim((string) $this->getRequest()->getPost('title', '')),
            'author'         => trim((string) $this->getRequest()->getPost('author', '')),
            'category'       => trim((string) $this->getRequest()->getPost('category', '')),
            'publisher'      => trim((string) $this->getRequest()->getPost('publisher', '')),
            'published_year' => trim((string) $this->getRequest()->getPost('published_year', '')),
            'isbn'           => trim((string) $this->getRequest()->getPost('isbn', '')),
            'language'       => (string) $this->getRequest()->getPost('language', 'Tiếng Việt'),
            'description'    => trim((string) $this->getRequest()->getPost('description', '')),
            'cover_image'    => trim((string) $this->getRequest()->getPost('cover_image', '')),
            'quantity'       => trim((string) $this->getRequest()->getPost('quantity', '')),
        ];
    }

    private function buildFormView(string $mode, array $form = [], array $errors = []): ViewModel
    {
        $defaults = [
            'id' => '', 'title' => '', 'author' => '', 'category' => '',
            'publisher' => '', 'published_year' => '', 'isbn' => '',
            'language' => 'Tiếng Việt', 'description' => '', 'cover_image' => '',
            'quantity' => '1', 'borrowed_quantity' => '0', 'available_quantity' => '1',
        ];

        $vm = new ViewModel([
            'title'      => $mode === 'edit' ? 'Cap nhat sach' : 'Them sach moi',
            'mode'       => $mode,
            'form'       => array_merge($defaults, $form),
            'errors'     => $errors,
            'categories' => $this->bookModel->getCategories(),
        ]);
        $vm->setTemplate('application/book/form');
        return $vm;
    }

    private function validateForm(array $form, ?array $existingBook = null): array
    {
        $errors          = [];
        $currentYear     = (int) date('Y') + 1;
        $quantity        = filter_var($form['quantity'], FILTER_VALIDATE_INT);
        $borrowedQty     = (int) ($existingBook['borrowed_quantity'] ?? 0);

        if ($form['title'] === '') {
            $errors['title'] = 'Ten sach la bat buoc.';
        } elseif (mb_strlen($form['title']) > 255) {
            $errors['title'] = 'Ten sach khong duoc vuot qua 255 ky tu.';
        }

        if ($form['author'] === '') {
            $errors['author'] = 'Tac gia la bat buoc.';
        }

        if ($form['category'] === '') {
            $errors['category'] = 'The loai la bat buoc.';
        }

        if ($quantity === false || $quantity < 0) {
            $errors['quantity'] = 'So luong phai la so nguyen khong am.';
        } elseif ($existingBook !== null && $quantity < $borrowedQty) {
            $errors['quantity'] = 'So luong moi phai lon hon hoac bang so ban dang duoc muon.';
        }

        if ($form['published_year'] !== '') {
            $y = filter_var($form['published_year'], FILTER_VALIDATE_INT);
            if ($y === false || $y < 1900 || $y > $currentYear) {
                $errors['published_year'] = 'Nam xuat ban khong hop le.';
            }
        }

        if ($form['cover_image'] !== '' && ! filter_var($form['cover_image'], FILTER_VALIDATE_URL)) {
            $errors['cover_image'] = 'Lien ket anh bia phai la URL hop le.';
        }

        return $errors;
    }
}
