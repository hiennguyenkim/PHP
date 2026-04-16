<?php
declare(strict_types=1);

namespace Models;

use Core\Database;
use PDO;

class UserModel
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
                u.id,
                u.username,
                u.full_name,
                u.email,
                u.role,
                u.status,
                u.created_at,
                SUM(CASE WHEN pm.status IN ('pending', 'borrowed', 'overdue') THEN 1 ELSE 0 END) AS active_loans,
                COUNT(pm.id) AS total_loans
            FROM users u
            LEFT JOIN phieu_muon pm ON pm.user_id = u.id
            WHERE 1 = 1
        ";
        $parameters = [];

        if (($filters['search'] ?? '') !== '') {
            $sql .= " AND (
                u.full_name LIKE :search
                OR u.username LIKE :search
                OR u.email LIKE :search
            )";
            $parameters['search'] = '%' . $filters['search'] . '%';
        }

        if (($filters['role'] ?? '') !== '') {
            $sql .= " AND u.role = :role";
            $parameters['role'] = $filters['role'];
        }

        if (($filters['status'] ?? '') !== '') {
            $sql .= " AND u.status = :status";
            $parameters['status'] = $filters['status'];
        }

        $sql .= "
            GROUP BY u.id, u.username, u.full_name, u.email, u.role, u.status, u.created_at
            ORDER BY
                CASE WHEN u.role = 'admin' THEN 0 ELSE 1 END,
                u.full_name ASC,
                u.id DESC
        ";

        $statement = $this->connection->prepare($sql);
        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    public function getById(int $id): ?array
    {
        $statement = $this->connection->prepare(
            "SELECT id, username, full_name, email, role, status, password, created_at
             FROM users
             WHERE id = :id
             LIMIT 1"
        );
        $statement->execute(['id' => $id]);
        $user = $statement->fetch();

        return $user !== false ? $user : null;
    }

    public function getByUsername(string $username): ?array
    {
        $statement = $this->connection->prepare(
            "SELECT id, username, full_name, email, role, status, password, created_at
             FROM users
             WHERE username = :username
             LIMIT 1"
        );
        $statement->execute(['username' => $username]);
        $user = $statement->fetch();

        return $user !== false ? $user : null;
    }

    public function getActiveOptions(): array
    {
        $statement = $this->connection->query(
            "SELECT id, full_name, username, role
             FROM users
             WHERE status = 'active'
               AND role = 'member'
             ORDER BY
                full_name ASC"
        );

        return $statement->fetchAll();
    }

    public function getRecentMembers(int $limit = 5): array
    {
        $statement = $this->connection->prepare(
            "SELECT id, full_name, username, role, created_at
             FROM users
             ORDER BY created_at DESC, id DESC
             LIMIT :limit"
        );
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function getSummary(): array
    {
        $statement = $this->connection->query(
            "SELECT
                COUNT(*) AS total_users,
                SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) AS admins,
                SUM(CASE WHEN role = 'member' AND status = 'active' THEN 1 ELSE 0 END) AS active_members
             FROM users"
        );
        $summary = $statement->fetch() ?: [];

        return [
            'total_users' => (int) ($summary['total_users'] ?? 0),
            'admins' => (int) ($summary['admins'] ?? 0),
            'active_members' => (int) ($summary['active_members'] ?? 0),
        ];
    }

    public function usernameExists(string $username, ?int $excludeId = null): bool
    {
        $sql = 'SELECT id FROM users WHERE username = :username';
        $parameters = ['username' => $username];

        if ($excludeId !== null) {
            $sql .= ' AND id <> :exclude_id';
            $parameters['exclude_id'] = $excludeId;
        }

        $sql .= ' LIMIT 1';

        $statement = $this->connection->prepare($sql);
        $statement->execute($parameters);

        return $statement->fetch() !== false;
    }

    public function create(array $data): int
    {
        $statement = $this->connection->prepare(
            "INSERT INTO users (username, password, full_name, email, role, status)
             VALUES (:username, :password, :full_name, :email, :role, :status)"
        );
        $statement->execute([
            'username' => $data['username'],
            'password' => $data['password'],
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'status' => $data['status'],
        ]);

        return (int) $this->connection->lastInsertId();
    }

    public function update(int $id, array $data, ?string $passwordHash = null): bool
    {
        if ($passwordHash !== null) {
            $statement = $this->connection->prepare(
                "UPDATE users
                 SET username = :username,
                     full_name = :full_name,
                     email = :email,
                     role = :role,
                     status = :status,
                     password = :password
                 WHERE id = :id"
            );

            return $statement->execute([
                'id' => $id,
                'username' => $data['username'],
                'full_name' => $data['full_name'],
                'email' => $data['email'],
                'role' => $data['role'],
                'status' => $data['status'],
                'password' => $passwordHash,
            ]);
        }

        $statement = $this->connection->prepare(
            "UPDATE users
             SET username = :username,
                 full_name = :full_name,
                 email = :email,
                 role = :role,
                 status = :status
             WHERE id = :id"
        );

        return $statement->execute([
            'id' => $id,
            'username' => $data['username'],
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'status' => $data['status'],
        ]);
    }

    public function hasLoanHistory(int $id): bool
    {
        $statement = $this->connection->prepare(
            "SELECT id
             FROM phieu_muon
             WHERE user_id = :user_id
             LIMIT 1"
        );
        $statement->execute(['user_id' => $id]);

        return $statement->fetch() !== false;
    }

    public function delete(int $id): bool
    {
        $statement = $this->connection->prepare('DELETE FROM users WHERE id = :id');
        return $statement->execute(['id' => $id]);
    }
}
