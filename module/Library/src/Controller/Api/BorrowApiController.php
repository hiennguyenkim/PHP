<?php
declare(strict_types=1);

namespace Library\Controller\Api;

use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\View\Model\JsonModel;
use Library\Model\Table\BorrowTable;
use Library\Model\Entity\BorrowRecord;

class BorrowApiController extends AbstractRestfulController
{
    private BorrowTable $table;

    public function __construct(BorrowTable $table)
    {
        $this->table = $table;
    }

    // GET /api/borrows
    public function getList()
    {
        $records = $this->table->fetchAllWithDetails();
        $data = [];
        foreach ($records as $record) {
            $data[] = $record->getArrayCopy();
        }
        return $this->jsonResponse($data);
    }

    // GET /api/borrows/:id
    public function get($id)
    {
        try {
            $record = $this->table->getRecord((int) $id);
            return $this->jsonResponse($record->getArrayCopy());
        } catch (\Exception $e) {
            return $this->jsonResponse(['error' => 'Borrow record not found'], 404);
        }
    }

    // POST /api/borrows
    public function create($data)
    {
        $data = json_decode($this->getRequest()->getContent(), true) ?: [];
        if (!isset($data['book_id']) || !isset($data['user_id']) || !isset($data['borrow_date']) || !isset($data['return_date'])) {
            return $this->jsonResponse(['error' => 'Missing book_id, user_id, borrow_date, or return_date'], 400);
        }

        try {
            $this->table->borrow(
                (int) $data['book_id'],
                (int) $data['user_id'],
                $data['borrow_date'],
                $data['return_date']
            );
            return $this->jsonResponse(['status' => 'success', 'message' => 'Book borrowed'], 201);
        } catch (\Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    // PUT /api/borrows/:id (Usually for returning a book)
    public function update($id, $data)
    {
        $data = json_decode($this->getRequest()->getContent(), true) ?: [];
        if (isset($data['action']) && $data['action'] === 'return') {
            try {
                $this->table->returnBook((int) $id);
                return $this->jsonResponse(['status' => 'success', 'message' => 'Book returned']);
            } catch (\Exception $e) {
                return $this->jsonResponse(['error' => $e->getMessage()], 500);
            }
        }

        return $this->jsonResponse(['error' => 'Unsupported action. Use action=return to return a book.'], 400);
    }

    // DELETE /api/borrows/:id
    public function delete($id)
    {
        return $this->jsonResponse(['error' => 'Delete operation not implemented for borrow records'], 501);
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
