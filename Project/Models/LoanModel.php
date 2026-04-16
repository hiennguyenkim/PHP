<?php
declare(strict_types=1);

namespace Models;

use Core\Database;
use PDO;

class LoanModel
{
    private PDO $connection;

    public function __construct()
    {
        $this->connection = Database::getInstance()->getConnection();
    }

    public function getAll(array $filters = []): array
    {
        return $this->fetchLoans($filters);
    }

    public function getAllForUser(int $userId, array $filters = []): array
    {
        return $this->fetchLoans($filters, $userId);
    }

    public function getById(int $id): ?array
    {
        return $this->fetchLoanById($id);
    }

    public function getByIdForUser(int $id, int $userId): ?array
    {
        return $this->fetchLoanById($id, $userId);
    }

    public function getRecent(int $limit = 6): array
    {
        return $this->fetchRecentLoans(null, $limit);
    }

    public function getRecentForUser(int $userId, int $limit = 6): array
    {
        return $this->fetchRecentLoans($userId, $limit);
    }

    public function getSummary(?int $userId = null): array
    {
        $this->syncOverdueStatuses();

        $sql = "
            SELECT
                COUNT(*) AS total_loans,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending,
                SUM(CASE WHEN status = 'borrowed' THEN 1 ELSE 0 END) AS borrowed,
                SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) AS overdue,
                SUM(CASE WHEN status = 'returned' THEN 1 ELSE 0 END) AS returned_total,
                SUM(CASE WHEN status = 'returned' AND DATE(returned_date) = CURDATE() THEN 1 ELSE 0 END) AS returned_today,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled
            FROM phieu_muon
        ";
        $parameters = [];

        if ($userId !== null) {
            $sql .= " WHERE user_id = :user_id";
            $parameters['user_id'] = $userId;
        }

        $statement = $this->connection->prepare($sql);
        $statement->execute($parameters);
        $summary = $statement->fetch() ?: [];

        return [
            'total_loans' => (int) ($summary['total_loans'] ?? 0),
            'pending' => (int) ($summary['pending'] ?? 0),
            'borrowed' => (int) ($summary['borrowed'] ?? 0),
            'overdue' => (int) ($summary['overdue'] ?? 0),
            'returned_total' => (int) ($summary['returned_total'] ?? 0),
            'returned_today' => (int) ($summary['returned_today'] ?? 0),
            'cancelled' => (int) ($summary['cancelled'] ?? 0),
        ];
    }

    public function getHistoryByUserId(int $userId, int $limit = 5): array
    {
        return $this->fetchRecentLoans($userId, $limit);
    }

    public function userHasOpenLoanForBook(int $userId, int $bookId, ?int $excludeId = null): bool
    {
        $sql = "
            SELECT id
            FROM phieu_muon
            WHERE user_id = :user_id
              AND book_id = :book_id
              AND status IN ('pending', 'borrowed', 'overdue')
        ";
        $parameters = [
            'user_id' => $userId,
            'book_id' => $bookId,
        ];

        if ($excludeId !== null) {
            $sql .= " AND id <> :exclude_id";
            $parameters['exclude_id'] = $excludeId;
        }

        $sql .= " LIMIT 1";

        $statement = $this->connection->prepare($sql);
        $statement->execute($parameters);

        return $statement->fetch() !== false;
    }

    public function create(array $data): int
    {
        $status = (string) $data['status'];

        try {
            $this->connection->beginTransaction();

            if ($this->affectsInventory($status)) {
                $this->reserveBook((int) $data['book_id']);
            }

            $returnedDate = $status === 'returned'
                ? ($data['returned_date'] !== '' ? $data['returned_date'] : date('Y-m-d'))
                : null;

            $statement = $this->connection->prepare(
                "INSERT INTO phieu_muon (
                    user_id,
                    book_id,
                    borrow_date,
                    due_date,
                    returned_date,
                    status,
                    notes
                 ) VALUES (
                    :user_id,
                    :book_id,
                    :borrow_date,
                    :due_date,
                    :returned_date,
                    :status,
                    :notes
                 )"
            );
            $statement->execute([
                'user_id' => (int) $data['user_id'],
                'book_id' => (int) $data['book_id'],
                'borrow_date' => $data['borrow_date'],
                'due_date' => $data['due_date'],
                'returned_date' => $returnedDate,
                'status' => $status,
                'notes' => $data['notes'],
            ]);

            $id = (int) $this->connection->lastInsertId();
            $this->connection->commit();

            return $id;
        } catch (\Throwable $throwable) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }

            throw $throwable;
        }
    }

    public function update(int $id, array $data): bool
    {
        try {
            $this->connection->beginTransaction();

            $existing = $this->getForUpdate($id);
            if ($existing === null) {
                throw new \RuntimeException('Phieu muon khong ton tai.');
            }

            $oldBookId = (int) $existing['book_id'];
            $newBookId = (int) $data['book_id'];
            $oldStatus = (string) $existing['status'];
            $newStatus = (string) $data['status'];

            $oldAffectsInventory = $this->affectsInventory($oldStatus);
            $newAffectsInventory = $this->affectsInventory($newStatus);

            if ($oldAffectsInventory && ($oldBookId !== $newBookId || !$newAffectsInventory)) {
                $this->releaseBook($oldBookId);
            }

            if ($newAffectsInventory && ($oldBookId !== $newBookId || !$oldAffectsInventory)) {
                $this->reserveBook($newBookId);
            }

            $returnedDate = $newStatus === 'returned'
                ? ($data['returned_date'] !== '' ? $data['returned_date'] : date('Y-m-d'))
                : null;

            $statement = $this->connection->prepare(
                "UPDATE phieu_muon
                 SET user_id = :user_id,
                     book_id = :book_id,
                     borrow_date = :borrow_date,
                     due_date = :due_date,
                     returned_date = :returned_date,
                     status = :status,
                     notes = :notes
                 WHERE id = :id"
            );
            $result = $statement->execute([
                'id' => $id,
                'user_id' => (int) $data['user_id'],
                'book_id' => $newBookId,
                'borrow_date' => $data['borrow_date'],
                'due_date' => $data['due_date'],
                'returned_date' => $returnedDate,
                'status' => $newStatus,
                'notes' => $data['notes'],
            ]);

            $this->connection->commit();

            return $result;
        } catch (\Throwable $throwable) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }

            throw $throwable;
        }
    }

    public function approve(int $id, ?string $borrowDate = null, ?string $dueDate = null): bool
    {
        try {
            $this->connection->beginTransaction();

            $existing = $this->getForUpdate($id);
            if ($existing === null) {
                throw new \RuntimeException('Khong tim thay yeu cau muon sach.');
            }

            if ((string) $existing['status'] !== 'pending') {
                throw new \RuntimeException('Chi co the duyet nhung phieu dang cho duyet.');
            }

            $borrowDate = $borrowDate ?: date('Y-m-d');
            $dueDate = $dueDate ?: date('Y-m-d', strtotime($borrowDate . ' +14 days'));

            $this->reserveBook((int) $existing['book_id']);

            $statement = $this->connection->prepare(
                "UPDATE phieu_muon
                 SET status = 'borrowed',
                     borrow_date = :borrow_date,
                     due_date = :due_date,
                     returned_date = NULL
                 WHERE id = :id"
            );
            $result = $statement->execute([
                'id' => $id,
                'borrow_date' => $borrowDate,
                'due_date' => $dueDate,
            ]);

            $this->connection->commit();

            return $result;
        } catch (\Throwable $throwable) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }

            throw $throwable;
        }
    }

    public function markReturned(int $id, ?string $returnedDate = null): bool
    {
        try {
            $this->connection->beginTransaction();

            $existing = $this->getForUpdate($id);
            if ($existing === null) {
                throw new \RuntimeException('Khong tim thay phieu muon can cap nhat.');
            }

            if (!in_array((string) $existing['status'], ['borrowed', 'overdue'], true)) {
                throw new \RuntimeException('Chi phieu dang muon moi co the ghi nhan tra sach.');
            }

            $this->releaseBook((int) $existing['book_id']);

            $statement = $this->connection->prepare(
                "UPDATE phieu_muon
                 SET status = 'returned',
                     returned_date = :returned_date
                 WHERE id = :id"
            );
            $result = $statement->execute([
                'id' => $id,
                'returned_date' => $returnedDate ?: date('Y-m-d'),
            ]);

            $this->connection->commit();

            return $result;
        } catch (\Throwable $throwable) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }

            throw $throwable;
        }
    }

    public function cancel(int $id): bool
    {
        try {
            $this->connection->beginTransaction();

            $existing = $this->getForUpdate($id);
            if ($existing === null) {
                throw new \RuntimeException('Khong tim thay phieu muon can huy.');
            }

            if ((string) $existing['status'] !== 'pending') {
                throw new \RuntimeException('Chi phieu dang cho duyet moi co the huy.');
            }

            $statement = $this->connection->prepare(
                "UPDATE phieu_muon
                 SET status = 'cancelled',
                     returned_date = NULL
                 WHERE id = :id"
            );
            $result = $statement->execute(['id' => $id]);

            $this->connection->commit();

            return $result;
        } catch (\Throwable $throwable) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }

            throw $throwable;
        }
    }

    private function fetchLoans(array $filters = [], ?int $userId = null): array
    {
        $this->syncOverdueStatuses();

        $sql = "
            SELECT
                pm.id,
                pm.user_id,
                pm.book_id,
                pm.borrow_date,
                pm.due_date,
                pm.returned_date,
                pm.status,
                pm.notes,
                pm.created_at,
                u.full_name,
                u.username,
                b.title AS book_title,
                b.author AS book_author,
                b.category AS book_category,
                CASE
                    WHEN pm.status IN ('borrowed', 'overdue') AND pm.due_date < CURDATE() THEN 'overdue'
                    ELSE pm.status
                END AS display_status
            FROM phieu_muon pm
            INNER JOIN users u ON u.id = pm.user_id
            INNER JOIN books b ON b.id = pm.book_id
            WHERE 1 = 1
        ";
        $parameters = [];

        if ($userId !== null) {
            $sql .= " AND pm.user_id = :scope_user_id";
            $parameters['scope_user_id'] = $userId;
        }

        if (($filters['search'] ?? '') !== '') {
            $sql .= " AND (
                u.full_name LIKE :search
                OR u.username LIKE :search
                OR b.title LIKE :search
            )";
            $parameters['search'] = '%' . $filters['search'] . '%';
        }

        if (($filters['status'] ?? '') !== '') {
            if ($filters['status'] === 'overdue') {
                $sql .= " AND pm.status IN ('borrowed', 'overdue') AND pm.due_date < CURDATE()";
            } elseif ($filters['status'] === 'borrowed') {
                $sql .= " AND pm.status = 'borrowed' AND pm.due_date >= CURDATE()";
            } else {
                $sql .= " AND pm.status = :status";
                $parameters['status'] = $filters['status'];
            }
        }

        if ($userId === null && ($filters['user_id'] ?? '') !== '') {
            $sql .= " AND pm.user_id = :user_id";
            $parameters['user_id'] = (int) $filters['user_id'];
        }

        if (($filters['book_id'] ?? '') !== '') {
            $sql .= " AND pm.book_id = :book_id";
            $parameters['book_id'] = (int) $filters['book_id'];
        }

        $sql .= " ORDER BY pm.created_at DESC, pm.id DESC";

        $statement = $this->connection->prepare($sql);
        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    private function fetchLoanById(int $id, ?int $userId = null): ?array
    {
        $this->syncOverdueStatuses();

        $sql = "
            SELECT
                pm.id,
                pm.user_id,
                pm.book_id,
                pm.borrow_date,
                pm.due_date,
                pm.returned_date,
                pm.status,
                pm.notes,
                pm.created_at,
                u.full_name,
                u.username,
                b.title AS book_title,
                CASE
                    WHEN pm.status IN ('borrowed', 'overdue') AND pm.due_date < CURDATE() THEN 'overdue'
                    ELSE pm.status
                END AS display_status
             FROM phieu_muon pm
             INNER JOIN users u ON u.id = pm.user_id
             INNER JOIN books b ON b.id = pm.book_id
             WHERE pm.id = :id
        ";
        $parameters = ['id' => $id];

        if ($userId !== null) {
            $sql .= " AND pm.user_id = :user_id";
            $parameters['user_id'] = $userId;
        }

        $sql .= " LIMIT 1";

        $statement = $this->connection->prepare($sql);
        $statement->execute($parameters);
        $loan = $statement->fetch();

        return $loan !== false ? $loan : null;
    }

    private function fetchRecentLoans(?int $userId, int $limit): array
    {
        $this->syncOverdueStatuses();

        $sql = "
            SELECT
                pm.id,
                pm.borrow_date,
                pm.due_date,
                pm.returned_date,
                pm.status,
                pm.notes,
                u.full_name,
                b.title AS book_title,
                b.author AS book_author,
                CASE
                    WHEN pm.status IN ('borrowed', 'overdue') AND pm.due_date < CURDATE() THEN 'overdue'
                    ELSE pm.status
                END AS display_status
             FROM phieu_muon pm
             INNER JOIN users u ON u.id = pm.user_id
             INNER JOIN books b ON b.id = pm.book_id
        ";
        $parameters = [];

        if ($userId !== null) {
            $sql .= " WHERE pm.user_id = :user_id";
            $parameters['user_id'] = $userId;
        }

        $sql .= " ORDER BY pm.created_at DESC, pm.id DESC LIMIT :limit";

        $statement = $this->connection->prepare($sql);
        foreach ($parameters as $key => $value) {
            $statement->bindValue(':' . $key, $value, PDO::PARAM_INT);
        }
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    private function syncOverdueStatuses(): void
    {
        $this->connection->exec(
            "UPDATE phieu_muon
             SET status = 'overdue'
             WHERE status = 'borrowed'
               AND due_date < CURDATE()
               AND returned_date IS NULL"
        );

        $this->connection->exec(
            "UPDATE phieu_muon
             SET status = 'borrowed'
             WHERE status = 'overdue'
               AND due_date >= CURDATE()
               AND returned_date IS NULL"
        );
    }

    private function getForUpdate(int $id): ?array
    {
        $statement = $this->connection->prepare(
            "SELECT id, user_id, book_id, status
             FROM phieu_muon
             WHERE id = :id
             FOR UPDATE"
        );
        $statement->execute(['id' => $id]);
        $loan = $statement->fetch();

        return $loan !== false ? $loan : null;
    }

    private function affectsInventory(string $status): bool
    {
        return in_array($status, ['borrowed', 'overdue'], true);
    }

    private function reserveBook(int $bookId): void
    {
        $book = $this->getBookForUpdate($bookId);

        if ($book === null) {
            throw new \RuntimeException('Sach duoc chon khong ton tai.');
        }

        $availableQuantity = (int) $book['available_quantity'];

        if ($availableQuantity < 1) {
            throw new \RuntimeException('Sach nay da het ban sao kha dung.');
        }

        $this->updateBookAvailability($bookId, $availableQuantity - 1);
    }

    private function releaseBook(int $bookId): void
    {
        $book = $this->getBookForUpdate($bookId);

        if ($book === null) {
            return;
        }

        $quantity = (int) $book['quantity'];
        $availableQuantity = (int) $book['available_quantity'];
        $newAvailableQuantity = min($quantity, $availableQuantity + 1);

        $this->updateBookAvailability($bookId, $newAvailableQuantity);
    }

    private function getBookForUpdate(int $bookId): ?array
    {
        $statement = $this->connection->prepare(
            "SELECT id, quantity, available_quantity
             FROM books
             WHERE id = :id
             FOR UPDATE"
        );
        $statement->execute(['id' => $bookId]);
        $book = $statement->fetch();

        return $book !== false ? $book : null;
    }

    private function updateBookAvailability(int $bookId, int $availableQuantity): void
    {
        $statement = $this->connection->prepare(
            "UPDATE books
             SET available_quantity = :available_quantity,
                 status = :status
             WHERE id = :id"
        );
        $statement->execute([
            'id' => $bookId,
            'available_quantity' => $availableQuantity,
            'status' => $this->computeBookStatus($availableQuantity),
        ]);
    }

    private function computeBookStatus(int $availableQuantity): string
    {
        if ($availableQuantity <= 0) {
            return 'out_of_stock';
        }

        if ($availableQuantity <= 2) {
            return 'low_stock';
        }

        return 'available';
    }
}
