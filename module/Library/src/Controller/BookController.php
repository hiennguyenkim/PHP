<?php
declare(strict_types=1);

namespace Library\Controller;

use Library\Form\BookForm;
use Library\Model\Entity\Book;
use Library\Model\Table\BookTable;
use Library\Model\Table\BorrowTable;
use Library\Session\AuthSessionContainer;
use Laminas\Form\FormElementManager;
use Laminas\Http\Response;
use Laminas\View\Model\ViewModel;
use RuntimeException;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 * @psalm-suppress PossiblyUnusedMethod
 */
class BookController extends BaseController
{
    public function __construct(
        AuthSessionContainer $authSessionContainer,
        private BookTable $bookTable,
        private BorrowTable $borrowTable,
        private FormElementManager $formElementManager
    ) {
        parent::__construct($authSessionContainer);
    }

    public function indexAction()
    {
        if ($response = $this->requireLogin()) {
            return $response;
        }

        $filters = [
            'search'   => trim($this->queryString('search')),
            'category' => $this->queryString('category'),
            'status'   => $this->queryString('status'),
        ];

        return new ViewModel([
            'books'      => $this->bookTable->fetchAll($filters),
            'filters'    => $filters,
            'categories' => $this->bookTable->fetchCategories(),
            'summary'    => $this->bookTable->getSummary(),
            'isAdmin'    => $this->isAdmin(),
        ]);
    }

    public function addAction()
    {
        if ($response = $this->requireAdmin()) {
            return $response;
        }

        $form = $this->formElementManager->get(BookForm::class);
        if (! $form instanceof BookForm) {
            throw new RuntimeException('Không thể khởi tạo biểu mẫu sách.');
        }

        if ($this->httpRequest()->isPost()) {
            $form->setData($this->postData());
            if ($form->isValid()) {
                /** @var array<string, mixed> $data */
                $data = $form->getData();
                $book = new Book();
                $book->exchangeArray($data);
                $this->bookTable->saveBook($book);
                $this->flash()->addSuccessMessage('Đã thêm sách "' . $book->title . '" vào thư viện.');
                return $this->redirect()->toRoute('library/book');
            }
        }

        $view = new ViewModel(['form' => $form, 'mode' => 'add']);
        $view->setTemplate('library/book/form');

        return $view;
    }

    public function editAction()
    {
        if ($response = $this->requireAdmin()) {
            return $response;
        }

        $id   = $this->routeInt('id');
        try {
            $book = $this->bookTable->getBook($id);
        } catch (\RuntimeException $exception) {
            $this->flash()->addErrorMessage($exception->getMessage());

            return $this->redirect()->toRoute('library/book');
        }

        $form = $this->formElementManager->get(BookForm::class);
        if (! $form instanceof BookForm) {
            throw new RuntimeException('Không thể khởi tạo biểu mẫu sách.');
        }
        $form->bind($book);

        if ($this->httpRequest()->isPost()) {
            $form->setData($this->postData());
            if ($form->isValid()) {
                $this->bookTable->saveBook($book);
                $this->flash()->addSuccessMessage('Đã cập nhật thông tin sách.');
                return $this->redirect()->toRoute('library/book');
            }
        }

        $view = new ViewModel(['form' => $form, 'mode' => 'edit', 'book' => $book]);
        $view->setTemplate('library/book/form');

        return $view;
    }

    public function deleteAction()
    {
        if ($response = $this->requireAdmin()) {
            return $response;
        }

        if (! $this->httpRequest()->isPost()) {
            return $this->redirect()->toRoute('library/book');
        }

        $id = $this->routeInt('id');

        if ($this->borrowTable->hasBorrowHistoryForBook($id)) {
            $this->flash()->addErrorMessage('Không thể xóa sách đã từng có lịch sử mượn trả.');

            return $this->redirect()->toRoute('library/book');
        }

        $this->bookTable->deleteBook($id);
        $this->flash()->addSuccessMessage('Đã xóa sách khỏi thư viện.');
        return $this->redirect()->toRoute('library/book');
    }
}
