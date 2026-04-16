<?php
declare(strict_types=1);

namespace Models;

use Core\Database;
use PDO;

class BookModel
{
    private PDO $connection;

    public function __construct()
    {
        $this->connection = Database::getInstance()->getConnection();
    }

    public function getAll(array $filters = []): array
    {
        $sql = "
            SELECT
                b.id,
                b.title,
                b.author,
                b.category,
                b.publisher,
                b.published_year,
                b.isbn,
                b.language,
                b.description,
                b.cover_image,
                b.quantity,
                b.available_quantity,
                b.status,
                b.created_at,
                (b.quantity - b.available_quantity) AS borrowed_quantity
            FROM books b
            WHERE 1 = 1
        ";
        $parameters = [];

        if (($filters['search'] ?? '') !== '') {
            $sql .= " AND (
                b.title LIKE :search
                OR b.author LIKE :search
                OR COALESCE(b.isbn, '') LIKE :search
            )";
            $parameters['search'] = '%' . $filters['search'] . '%';
        }

        if (($filters['category'] ?? '') !== '') {
            $sql .= " AND b.category = :category";
            $parameters['category'] = $filters['category'];
        }

        if (($filters['status'] ?? '') !== '') {
            $sql .= " AND b.status = :status";
            $parameters['status'] = $filters['status'];
        }

        $sql .= " ORDER BY b.created_at DESC, b.id DESC";

        $statement = $this->connection->prepare($sql);
        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    public function getById(int $id): ?array
    {
        $statement = $this->connection->prepare(
            "SELECT
                id,
                title,
                author,
                category,
                publisher,
                published_year,
                isbn,
                language,
                description,
                cover_image,
                quantity,
                available_quantity,
                status,
                created_at,
                (quantity - available_quantity) AS borrowed_quantity
             FROM books
             WHERE id = :id
             LIMIT 1"
        );
        $statement->execute(['id' => $id]);
        $book = $statement->fetch();

        return $book !== false ? $book : null;
    }

    public function getOptions(): array
    {
        $statement = $this->connection->query(
            "SELECT id, title, author, category, quantity, available_quantity, status
             FROM books
             ORDER BY title ASC"
        );

        return $statement->fetchAll();
    }

    public function getBorrowableOptions(int $limit = 0): array
    {
        $sql = "
            SELECT id, title, author, category, quantity, available_quantity, status
            FROM books
            WHERE available_quantity > 0
            ORDER BY
                CASE WHEN status = 'available' THEN 0 ELSE 1 END,
                title ASC
        ";

        if ($limit > 0) {
            $sql .= " LIMIT :limit";
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('limit', $limit, PDO::PARAM_INT);
            $statement->execute();

            return $statement->fetchAll();
        }

        $statement = $this->connection->query($sql);

        return $statement->fetchAll();
    }

    public function getCategories(): array
    {
        $statement = $this->connection->query(
            "SELECT DISTINCT category
             FROM books
             WHERE category <> ''
             ORDER BY category ASC"
        );

        return array_map(
            static fn (array $row): string => (string) $row['category'],
            $statement->fetchAll()
        );
    }

    public function getSummary(): array
    {
        $statement = $this->connection->query(
            "SELECT
                COUNT(*) AS total_books,
                SUM(available_quantity) AS available_copies,
                SUM(CASE WHEN available_quantity > 0 THEN 1 ELSE 0 END) AS available_books,
                SUM(CASE WHEN status = 'low_stock' THEN 1 ELSE 0 END) AS low_stock_books
             FROM books"
        );
        $summary = $statement->fetch() ?: [];

        return [
            'total_books' => (int) ($summary['total_books'] ?? 0),
            'available_copies' => (int) ($summary['available_copies'] ?? 0),
            'available_books' => (int) ($summary['available_books'] ?? 0),
            'low_stock_books' => (int) ($summary['low_stock_books'] ?? 0),
        ];
    }

    public function getLowStock(int $limit = 5): array
    {
        $statement = $this->connection->prepare(
            "SELECT id, title, author, category, quantity, available_quantity, status
             FROM books
             WHERE status IN ('low_stock', 'out_of_stock')
             ORDER BY available_quantity ASC, title ASC
             LIMIT :limit"
        );
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function getAvailableHighlights(int $limit = 5): array
    {
        $statement = $this->connection->prepare(
            "SELECT id, title, author, category, available_quantity, status
             FROM books
             WHERE available_quantity > 0
             ORDER BY
                CASE WHEN status = 'available' THEN 0 ELSE 1 END,
                available_quantity DESC,
                title ASC
             LIMIT :limit"
        );
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function create(array $data): int
    {
        $statement = $this->connection->prepare(
            "INSERT INTO books (
                title,
                author,
                category,
                publisher,
                published_year,
                isbn,
                language,
                description,
                cover_image,
                quantity,
                available_quantity,
                status
             ) VALUES (
                :title,
                :author,
                :category,
                :publisher,
                :published_year,
                :isbn,
                :language,
                :description,
                :cover_image,
                :quantity,
                :available_quantity,
                :status
             )"
        );
        $statement->execute([
            'title' => $data['title'],
            'author' => $data['author'],
            'category' => $data['category'],
            'publisher' => $data['publisher'],
            'published_year' => $data['published_year'],
            'isbn' => $data['isbn'],
            'language' => $data['language'],
            'description' => $data['description'],
            'cover_image' => $data['cover_image'],
            'quantity' => $data['quantity'],
            'available_quantity' => $data['available_quantity'],
            'status' => $this->computeStatus((int) $data['available_quantity']),
        ]);

        return (int) $this->connection->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $statement = $this->connection->prepare(
            "UPDATE books
             SET title = :title,
                 author = :author,
                 category = :category,
                 publisher = :publisher,
                 published_year = :published_year,
                 isbn = :isbn,
                 language = :language,
                 description = :description,
                 cover_image = :cover_image,
                 quantity = :quantity,
                 available_quantity = :available_quantity,
                 status = :status
             WHERE id = :id"
        );

        return $statement->execute([
            'id' => $id,
            'title' => $data['title'],
            'author' => $data['author'],
            'category' => $data['category'],
            'publisher' => $data['publisher'],
            'published_year' => $data['published_year'],
            'isbn' => $data['isbn'],
            'language' => $data['language'],
            'description' => $data['description'],
            'cover_image' => $data['cover_image'],
            'quantity' => $data['quantity'],
            'available_quantity' => $data['available_quantity'],
            'status' => $this->computeStatus((int) $data['available_quantity']),
        ]);
    }

    public function hasLoanHistory(int $id): bool
    {
        $statement = $this->connection->prepare(
            "SELECT id
             FROM phieu_muon
             WHERE book_id = :book_id
             LIMIT 1"
        );
        $statement->execute(['book_id' => $id]);

        return $statement->fetch() !== false;
    }

    public function delete(int $id): bool
    {
        $statement = $this->connection->prepare('DELETE FROM books WHERE id = :id');
        return $statement->execute(['id' => $id]);
    }

    private function computeStatus(int $availableQuantity): string
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
