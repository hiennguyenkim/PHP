<?php
declare(strict_types=1);

namespace Library\Controller\Api;

use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\View\Model\JsonModel;
use Library\Model\Table\BookTable;
use Library\Model\Entity\Book;

class BookApiController extends AbstractRestfulController
{
    private BookTable $table;

    public function __construct(BookTable $table)
    {
        $this->table = $table;
    }

    // GET /api/books
    public function getList()
    {
        $books = $this->table->fetchAll();
        $data = [];
        foreach ($books as $book) {
            $data[] = $book->getArrayCopy();
        }
        return $this->jsonResponse($data);
    }

    // GET /api/books/:id
    public function get($id)
    {
        try {
            $book = $this->table->getBook((int) $id);
            return $this->jsonResponse($book->getArrayCopy());
        } catch (\Exception $e) {
            return $this->jsonResponse(['error' => 'Book not found'], 404);
        }
    }

    // POST /api/books
    public function create($data)
    {
        $data = json_decode($this->getRequest()->getContent(), true) ?: [];
        $book = new Book();
        $book->exchangeArray($data);
        try {
            $this->table->saveBook($book);
            return $this->jsonResponse(['status' => 'success', 'message' => 'Book created'], 201);
        } catch (\Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    // PUT /api/books/:id
    public function update($id, $data)
    {
        $data = json_decode($this->getRequest()->getContent(), true) ?: [];
        try {
            $book = $this->table->getBook((int) $id);
            $book->exchangeArray($data);
            $book->id = (int) $id;
            $this->table->saveBook($book);
            return $this->jsonResponse(['status' => 'success', 'message' => 'Book updated']);
        } catch (\Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 404);
        }
    }

    // DELETE /api/books/:id
    public function delete($id)
    {
        try {
            $this->table->deleteBook((int) $id);
            return $this->jsonResponse(['status' => 'success', 'message' => 'Book deleted']);
        } catch (\Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Helper to return JSON response with correct Vietnamese encoding.
     */
    private function jsonResponse(array $data, int $statusCode = 200)
    {
        $response = $this->getResponse();
        $response->setStatusCode($statusCode);
        $response->setContent(json_encode($data, JSON_UNESCAPED_UNICODE));
        $response->getHeaders()->addHeaderLine('Content-Type', 'application/json');
        return $response;
    }
}
