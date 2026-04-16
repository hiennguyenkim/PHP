<?php
declare(strict_types=1);

namespace Controllers;

use Core\BaseController;
use Core\Session;
use Models\BookModel;

class BookController extends BaseController
{
    private BookModel $bookModel;

    public function __construct()
    {
        $this->requireLogin();
        $this->requireAdmin();
        $this->bookModel = new BookModel();
    }

    public function index(): void
    {
        $filters = [
            'search' => $this->query('search'),
            'category' => $this->query('category'),
            'status' => $this->query('status'),
        ];

        $this->render('books/list', [
            'title' => 'Quan ly sach',
            'books' => $this->bookModel->getAll($filters),
            'categories' => $this->bookModel->getCategories(),
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
            $this->redirect('book/create');
        }

        $form = $this->collectFormData();
        $errors = $this->validateForm($form);

        if ($errors !== []) {
            $this->renderForm('create', $form, $errors);
            return;
        }

        $this->bookModel->create([
            'title' => $form['title'],
            'author' => $form['author'],
            'category' => $form['category'],
            'publisher' => $form['publisher'] !== '' ? $form['publisher'] : null,
            'published_year' => $form['published_year'] !== '' ? (int) $form['published_year'] : null,
            'isbn' => $form['isbn'] !== '' ? $form['isbn'] : null,
            'language' => $form['language'],
            'description' => $form['description'] !== '' ? $form['description'] : null,
            'cover_image' => $form['cover_image'] !== '' ? $form['cover_image'] : null,
            'quantity' => (int) $form['quantity'],
            'available_quantity' => (int) $form['quantity'],
        ]);

        Session::flash('success', 'Da them sach moi vao thu vien.');
        $this->redirect('book/index');
    }

    public function edit(string $id): void
    {
        $book = $this->bookModel->getById((int) $id);

        if ($book === null) {
            Session::flash('error', 'Khong tim thay sach can chinh sua.');
            $this->redirect('book/index');
        }

        $this->renderForm('edit', [
            'id' => (string) $book['id'],
            'title' => (string) $book['title'],
            'author' => (string) $book['author'],
            'category' => (string) $book['category'],
            'publisher' => (string) ($book['publisher'] ?? ''),
            'published_year' => (string) ($book['published_year'] ?? ''),
            'isbn' => (string) ($book['isbn'] ?? ''),
            'language' => (string) ($book['language'] ?? 'Tiếng Việt'),
            'description' => (string) ($book['description'] ?? ''),
            'cover_image' => (string) ($book['cover_image'] ?? ''),
            'quantity' => (string) $book['quantity'],
            'borrowed_quantity' => (string) ($book['borrowed_quantity'] ?? 0),
            'available_quantity' => (string) $book['available_quantity'],
        ]);
    }

    public function update(string $id): void
    {
        if (!$this->isPost()) {
            $this->redirect('book/edit/' . $id);
        }

        $bookId = (int) $id;
        $existingBook = $this->bookModel->getById($bookId);

        if ($existingBook === null) {
            Session::flash('error', 'Sach khong ton tai.');
            $this->redirect('book/index');
        }

        $form = $this->collectFormData();
        $form['id'] = (string) $bookId;
        $form['borrowed_quantity'] = (string) ($existingBook['borrowed_quantity'] ?? 0);

        $errors = $this->validateForm($form, $existingBook);

        if ($errors !== []) {
            $form['available_quantity'] = (string) ($existingBook['available_quantity'] ?? 0);
            $this->renderForm('edit', $form, $errors);
            return;
        }

        $borrowedQuantity = (int) ($existingBook['borrowed_quantity'] ?? 0);
        $quantity = (int) $form['quantity'];

        $this->bookModel->update($bookId, [
            'title' => $form['title'],
            'author' => $form['author'],
            'category' => $form['category'],
            'publisher' => $form['publisher'] !== '' ? $form['publisher'] : null,
            'published_year' => $form['published_year'] !== '' ? (int) $form['published_year'] : null,
            'isbn' => $form['isbn'] !== '' ? $form['isbn'] : null,
            'language' => $form['language'],
            'description' => $form['description'] !== '' ? $form['description'] : null,
            'cover_image' => $form['cover_image'] !== '' ? $form['cover_image'] : null,
            'quantity' => $quantity,
            'available_quantity' => $quantity - $borrowedQuantity,
        ]);

        Session::flash('success', 'Cap nhat sach thanh cong.');
        $this->redirect('book/index');
    }

    public function delete(string $id): void
    {
        if (!$this->isPost()) {
            $this->redirect('book/index');
        }

        $bookId = (int) $id;
        $book = $this->bookModel->getById($bookId);

        if ($book === null) {
            Session::flash('error', 'Khong tim thay sach can xoa.');
            $this->redirect('book/index');
        }

        if ($this->bookModel->hasLoanHistory($bookId)) {
            Session::flash('error', 'Sach nay da co lich su muon tra, khong the xoa.');
            $this->redirect('book/index');
        }

        $this->bookModel->delete($bookId);

        Session::flash('success', 'Da xoa sach khoi thu vien.');
        $this->redirect('book/index');
    }

    private function collectFormData(): array
    {
        return [
            'title' => $this->post('title'),
            'author' => $this->post('author'),
            'category' => $this->post('category'),
            'publisher' => $this->post('publisher'),
            'published_year' => $this->post('published_year'),
            'isbn' => $this->post('isbn'),
            'language' => $this->post('language', 'Tiếng Việt'),
            'description' => $this->post('description'),
            'cover_image' => $this->post('cover_image'),
            'quantity' => $this->post('quantity'),
        ];
    }

    private function validateForm(array $form, ?array $existingBook = null): array
    {
        $errors = [];
        $currentYear = (int) date('Y') + 1;
        $quantity = filter_var($form['quantity'], FILTER_VALIDATE_INT);
        $borrowedQuantity = (int) ($existingBook['borrowed_quantity'] ?? 0);

        if ($form['title'] === '') {
            $errors['title'] = 'Ten sach la bat buoc.';
        } elseif (mb_strlen($form['title']) > 255) {
            $errors['title'] = 'Ten sach khong duoc vuot qua 255 ky tu.';
        }

        if ($form['author'] === '') {
            $errors['author'] = 'Tac gia la bat buoc.';
        } elseif (mb_strlen($form['author']) > 150) {
            $errors['author'] = 'Tac gia khong duoc vuot qua 150 ky tu.';
        }

        if ($form['category'] === '') {
            $errors['category'] = 'The loai la bat buoc.';
        }

        if ($quantity === false || $quantity < 0) {
            $errors['quantity'] = 'So luong phai la so nguyen khong am.';
        } elseif ($existingBook !== null && $quantity < $borrowedQuantity) {
            $errors['quantity'] = 'So luong moi phai lon hon hoac bang so ban dang duoc muon.';
        }

        if ($form['published_year'] !== '') {
            $publishedYear = filter_var($form['published_year'], FILTER_VALIDATE_INT);

            if ($publishedYear === false || $publishedYear < 1900 || $publishedYear > $currentYear) {
                $errors['published_year'] = 'Nam xuat ban khong hop le.';
            }
        }

        if ($form['cover_image'] !== '' && !filter_var($form['cover_image'], FILTER_VALIDATE_URL)) {
            $errors['cover_image'] = 'Lien ket anh bia phai la URL hop le.';
        }

        if ($form['isbn'] !== '' && mb_strlen($form['isbn']) > 30) {
            $errors['isbn'] = 'ISBN khong duoc vuot qua 30 ky tu.';
        }

        return $errors;
    }

    private function renderForm(string $mode, array $form = [], array $errors = []): void
    {
        $defaults = [
            'id' => '',
            'title' => '',
            'author' => '',
            'category' => '',
            'publisher' => '',
            'published_year' => '',
            'isbn' => '',
            'language' => 'Tiếng Việt',
            'description' => '',
            'cover_image' => '',
            'quantity' => '1',
            'borrowed_quantity' => '0',
            'available_quantity' => '1',
        ];

        $this->render('books/form', [
            'title' => $mode === 'edit' ? 'Cap nhat sach' : 'Them sach moi',
            'mode' => $mode,
            'form' => array_merge($defaults, $form),
            'errors' => $errors,
            'categories' => $this->bookModel->getCategories(),
        ]);
    }
}
