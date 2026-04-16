<?php
declare(strict_types=1);

namespace Library\Model\Table;

use Library\Model\Entity\Book;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\Db\Sql\Select;
use RuntimeException;

class BookTable
{
    private TableGateway $tableGateway;

    public function __construct(TableGateway $tableGateway)
    {
        $this->tableGateway = $tableGateway;
    }

    public function fetchAll(array $filters = []): \Laminas\Db\ResultSet\ResultSetInterface
    {
        return $this->tableGateway->select(function (Select $select) use ($filters): void {
            if (($filters['search'] ?? '') !== '') {
                $search = '%' . $filters['search'] . '%';
                $select->where->nest
                    ->like('title', $search)
                    ->or
                    ->like('author', $search)
                    ->or
                    ->like('isbn', $search)
                    ->unnest();
            }

            if (($filters['category'] ?? '') !== '') {
                $select->where(['category' => $filters['category']]);
            }

            if (($filters['status'] ?? '') !== '') {
                $select->where(['status' => $filters['status']]);
            }

            $select->order([
                new \Laminas\Db\Sql\Expression("CASE WHEN status = 'available' THEN 0 WHEN status = 'borrowed' THEN 1 ELSE 2 END"),
                'title ASC',
            ]);
        });
    }

    public function getBook(int $id): Book
    {
        $rowset = $this->tableGateway->select(['id' => $id]);
        $row    = $rowset->current();
        if (! $row instanceof Book) {
            throw new RuntimeException(sprintf('Không tìm thấy sách có ID %d.', $id));
        }
        return $row;
    }

    public function saveBook(Book $book): void
    {
        $status = $book->status === 'unavailable'
            ? 'unavailable'
            : $this->resolveAvailabilityStatus($book->quantity);

        $data = [
            'title'    => $book->title,
            'author'   => $book->author,
            'isbn'     => $book->isbn !== '' ? $book->isbn : null,
            'category' => $book->category,
            'quantity' => $book->quantity,
            'status'   => $status,
        ];

        $book->status = $status;

        if ($book->id === 0) {
            $this->tableGateway->insert($data);
        } else {
            $this->tableGateway->update($data, ['id' => $book->id]);
        }
    }

    public function deleteBook(int $id): void
    {
        $this->tableGateway->delete(['id' => $id]);
    }

    public function countAll(): int
    {
        $sql     = $this->tableGateway->getSql();
        $select  = $sql->select()->columns(['c' => new \Laminas\Db\Sql\Expression('COUNT(*)')]);
        $stmt    = $sql->prepareStatementForSqlObject($select);
        $result  = $stmt->execute();
        return (int) $result->current()['c'];
    }

    public function fetchCategories(): array
    {
        $sql    = $this->tableGateway->getSql();
        $select = $sql->select();
        $select->quantifier(Select::QUANTIFIER_DISTINCT);
        $select->columns(['category']);
        $select->where->isNotNull('category');
        $select->order('category ASC');
        $stmt   = $sql->prepareStatementForSqlObject($select);
        $result = $stmt->execute();
        $categories = [];

        foreach ($result as $row) {
            $category = trim((string) ($row['category'] ?? ''));
            if ($category !== '') {
                $categories[] = $category;
            }
        }

        return $categories;
    }

    public function getSummary(): array
    {
        $sql    = $this->tableGateway->getSql();
        $select = $sql->select()->columns([
            'total_titles'      => new \Laminas\Db\Sql\Expression('COUNT(*)'),
            'available_titles'  => new \Laminas\Db\Sql\Expression("SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END)"),
            'borrowed_titles'   => new \Laminas\Db\Sql\Expression("SUM(CASE WHEN status = 'borrowed' THEN 1 ELSE 0 END)"),
            'unavailable_titles'=> new \Laminas\Db\Sql\Expression("SUM(CASE WHEN status = 'unavailable' THEN 1 ELSE 0 END)"),
            'total_copies'      => new \Laminas\Db\Sql\Expression('SUM(quantity)'),
        ]);
        $stmt   = $sql->prepareStatementForSqlObject($select);
        $result = $stmt->execute()->current();

        return [
            'total_titles'       => (int) ($result['total_titles'] ?? 0),
            'available_titles'   => (int) ($result['available_titles'] ?? 0),
            'borrowed_titles'    => (int) ($result['borrowed_titles'] ?? 0),
            'unavailable_titles' => (int) ($result['unavailable_titles'] ?? 0),
            'total_copies'       => (int) ($result['total_copies'] ?? 0),
        ];
    }

    public function decrementAvailability(int $bookId): void
    {
        $book = $this->getBook($bookId);
        if ($book->status === 'unavailable') {
            throw new \DomainException('Sách này hiện đang bị đánh dấu không khả dụng.');
        }
        if ($book->quantity <= 0) {
            throw new \DomainException('Sách này hiện không còn bản sao để mượn.');
        }
        $newQty = $book->quantity - 1;
        $this->tableGateway->update(
            ['quantity' => $newQty, 'status' => $this->resolveAvailabilityStatus($newQty)],
            ['id' => $bookId]
        );
    }

    public function incrementAvailability(int $bookId): void
    {
        $book   = $this->getBook($bookId);
        $newQty = $book->quantity + 1;
        $status = $book->status === 'unavailable'
            ? 'unavailable'
            : $this->resolveAvailabilityStatus($newQty);

        $this->tableGateway->update(
            ['quantity' => $newQty, 'status' => $status],
            ['id' => $bookId]
        );
    }

    private function resolveAvailabilityStatus(int $quantity): string
    {
        return $quantity > 0 ? 'available' : 'borrowed';
    }
}
