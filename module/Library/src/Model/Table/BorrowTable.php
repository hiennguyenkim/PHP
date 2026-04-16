<?php
declare(strict_types=1);

namespace Library\Model\Table;

use Library\Model\Entity\BorrowRecord;
use Laminas\Db\Sql\Expression;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\Db\Sql\Select;

class BorrowTable
{
    private TableGateway $tableGateway;

    public function __construct(TableGateway $tableGateway)
    {
        $this->tableGateway = $tableGateway;
    }

    /**
     * Fetch all borrow records joined with book title and user info.
     */
    public function fetchAllWithDetails(array $filters = [], ?int $userId = null, int $limit = 0): array
    {
        $sql      = $this->tableGateway->getSql();
        $select   = $sql->select()
            ->columns([
                'id',
                'book_id',
                'user_id',
                'borrow_date',
                'return_date',
                'created_at',
                'status' => new Expression(
                    "CASE
                        WHEN borrow_records.status = 'borrowed' AND borrow_records.return_date < CURDATE()
                            THEN 'overdue'
                        ELSE borrow_records.status
                     END"
                ),
            ])
            ->join('books', 'borrow_records.book_id = books.id',
                ['book_title' => 'title', 'book_isbn' => 'isbn'])
            ->join('users', 'borrow_records.user_id = users.id',
                ['full_name', 'username'])
            ->order('borrow_records.created_at DESC');

        if ($userId !== null) {
            $select->where(['borrow_records.user_id' => $userId]);
        }

        if (($filters['search'] ?? '') !== '') {
            $search = '%' . $filters['search'] . '%';
            $select->where->nest
                ->like('books.title', $search)
                ->or
                ->like('books.author', $search)
                ->or
                ->like('books.isbn', $search)
                ->or
                ->like('users.full_name', $search)
                ->or
                ->like('users.username', $search)
                ->unnest();
        }

        if (($filters['status'] ?? '') !== '') {
            if ($filters['status'] === 'overdue') {
                $select->where(new Expression(
                    "(borrow_records.status = 'overdue' OR (borrow_records.status = 'borrowed' AND borrow_records.return_date < CURDATE()))"
                ));
            } elseif ($filters['status'] === 'borrowed') {
                $select->where(new Expression(
                    "(borrow_records.status = 'borrowed' AND borrow_records.return_date >= CURDATE())"
                ));
            } else {
                $select->where(['borrow_records.status' => $filters['status']]);
            }
        }

        if ($userId === null && ($filters['user_id'] ?? '') !== '') {
            $select->where(['borrow_records.user_id' => (int) $filters['user_id']]);
        }

        if ($limit > 0) {
            $select->limit($limit);
        }

        $stmt   = $sql->prepareStatementForSqlObject($select);
        $result = $stmt->execute();

        $records = [];
        foreach ($result as $row) {
            $record = new BorrowRecord();
            $record->exchangeArray($row);
            $records[] = $record;
        }
        return $records;
    }

    public function getRecord(int $id): BorrowRecord
    {
        $rowset = $this->tableGateway->select(['id' => $id]);
        $row    = $rowset->current();
        if (! $row instanceof BorrowRecord) {
            throw new \RuntimeException(sprintf('Không tìm thấy phiếu mượn ID %d.', $id));
        }
        return $row;
    }

    public function borrow(int $bookId, int $userId, string $borrowDate, string $returnDate): void
    {
        $this->tableGateway->insert([
            'book_id'     => $bookId,
            'user_id'     => $userId,
            'borrow_date' => $borrowDate,
            'return_date' => $returnDate,
            'status'      => 'borrowed',
        ]);
    }

    public function returnBook(int $id): void
    {
        $this->tableGateway->update(['status' => 'returned'], ['id' => $id]);
    }

    public function countBorrowed(?int $userId = null): int
    {
        $sql    = $this->tableGateway->getSql();
        $select = $sql->select()->columns([
            'c' => new Expression(
                "SUM(CASE
                    WHEN borrow_records.status = 'borrowed'
                     AND borrow_records.return_date >= CURDATE()
                        THEN 1
                    ELSE 0
                 END)"
            ),
        ]);

        if ($userId !== null) {
            $select->where(['user_id' => $userId]);
        }

        $stmt   = $sql->prepareStatementForSqlObject($select);
        $result = $stmt->execute();
        return (int) $result->current()['c'];
    }

    public function countOverdue(?int $userId = null): int
    {
        $sql    = $this->tableGateway->getSql();
        $select = $sql->select()->columns([
            'c' => new Expression(
                "SUM(CASE
                    WHEN borrow_records.status = 'overdue'
                      OR (borrow_records.status = 'borrowed' AND borrow_records.return_date < CURDATE())
                        THEN 1
                    ELSE 0
                 END)"
            ),
        ]);

        if ($userId !== null) {
            $select->where(['user_id' => $userId]);
        }

        $stmt   = $sql->prepareStatementForSqlObject($select);
        $result = $stmt->execute();
        return (int) $result->current()['c'];
    }

    public function countReturned(?int $userId = null): int
    {
        $sql    = $this->tableGateway->getSql();
        $select = $sql->select()
            ->columns([
                'c' => new Expression("SUM(CASE WHEN status = 'returned' THEN 1 ELSE 0 END)"),
            ]);

        if ($userId !== null) {
            $select->where(['user_id' => $userId]);
        }

        $stmt   = $sql->prepareStatementForSqlObject($select);
        $result = $stmt->execute();

        return (int) $result->current()['c'];
    }

    public function countDueSoon(int $userId, int $days = 7): int
    {
        $sql    = $this->tableGateway->getSql();
        $select = $sql->select()
            ->columns([
                'c' => new Expression(
                    sprintf(
                        "SUM(CASE
                            WHEN status = 'borrowed'
                             AND return_date >= CURDATE()
                             AND return_date <= DATE_ADD(CURDATE(), INTERVAL %d DAY)
                                THEN 1
                            ELSE 0
                         END)",
                        $days
                    )
                ),
            ])
            ->where(['user_id' => $userId]);
        $stmt   = $sql->prepareStatementForSqlObject($select);
        $result = $stmt->execute();

        return (int) $result->current()['c'];
    }

    public function countActiveLoansForUser(int $userId): int
    {
        $sql    = $this->tableGateway->getSql();
        $select = $sql->select()
            ->columns([
                'c' => new Expression(
                    "SUM(CASE
                        WHEN status IN ('borrowed', 'overdue')
                            THEN 1
                        ELSE 0
                     END)"
                ),
            ])
            ->where(['user_id' => $userId]);
        $stmt   = $sql->prepareStatementForSqlObject($select);
        $result = $stmt->execute();

        return (int) $result->current()['c'];
    }

    public function hasOverdueLoans(int $userId): bool
    {
        $sql    = $this->tableGateway->getSql();
        $select = $sql->select()
            ->columns(['id'])
            ->where(['user_id' => $userId])
            ->where("(status = 'overdue' OR (status = 'borrowed' AND return_date < CURDATE()))")
            ->limit(1);
        $stmt   = $sql->prepareStatementForSqlObject($select);
        $result = $stmt->execute();

        return (bool) $result->current();
    }

    public function hasActiveLoan(int $userId, int $bookId): bool
    {
        $rowset = $this->tableGateway->select(function (Select $select) use ($userId, $bookId) {
            $select->columns(['id']);
            $select->where([
                'user_id' => $userId,
                'book_id' => $bookId,
            ]);
            $select->where->in('status', ['borrowed', 'overdue']);
            $select->limit(1);
        });
        return $rowset->count() > 0;
    }

    public function hasBorrowHistoryForBook(int $bookId): bool
    {
        $rowset = $this->tableGateway->select(function (Select $select) use ($bookId) {
            $select->columns(['id']);
            $select->where(['book_id' => $bookId]);
            $select->limit(1);
        });

        return $rowset->count() > 0;
    }

    public function getSummary(?int $userId = null): array
    {
        return [
            'borrowed'  => $this->countBorrowed($userId),
            'overdue'   => $this->countOverdue($userId),
            'returned'  => $this->countReturned($userId),
            'due_soon'  => $userId !== null ? $this->countDueSoon($userId) : 0,
        ];
    }
}
