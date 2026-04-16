<?php
declare(strict_types=1);

namespace Library\Model\Table;

use Library\Model\Entity\User;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Select;
use Laminas\Db\TableGateway\TableGateway;

class UserTable
{
    private TableGateway $tableGateway;

    public function __construct(TableGateway $tableGateway)
    {
        $this->tableGateway = $tableGateway;
    }

    public function getByUsername(string $username): ?User
    {
        $rowset = $this->tableGateway->select(['username' => $username]);
        $row    = $rowset->current();
        return ($row instanceof User) ? $row : null;
    }

    public function getUser(int $id): User
    {
        $rowset = $this->tableGateway->select(['id' => $id]);
        $row    = $rowset->current();

        if (! $row instanceof User) {
            throw new \RuntimeException(sprintf('Không tìm thấy tài khoản có ID %d.', $id));
        }

        return $row;
    }

    public function fetchAll(array $filters = []): \Laminas\Db\ResultSet\ResultSetInterface
    {
        return $this->tableGateway->select(function (Select $select) use ($filters): void {
            if (($filters['search'] ?? '') !== '') {
                $search = '%' . $filters['search'] . '%';
                $select->where->nest
                    ->like('full_name', $search)
                    ->or
                    ->like('username', $search)
                    ->unnest();
            }

            if (($filters['role'] ?? '') !== '') {
                $select->where(['role' => $filters['role']]);
            }

            $select->order([
                new Expression("CASE WHEN role = 'admin' THEN 0 ELSE 1 END"),
                'full_name ASC',
                'id DESC',
            ]);
        });
    }

    public function fetchStudentOptions(): array
    {
        return iterator_to_array($this->fetchAll(['role' => 'student']));
    }

    public function countAll(): int
    {
        $sql    = $this->tableGateway->getSql();
        $select = $sql->select()->columns(['c' => new Expression('COUNT(*)')]);
        $stmt   = $sql->prepareStatementForSqlObject($select);
        $result = $stmt->execute();

        return (int) $result->current()['c'];
    }

    public function countByRole(string $role): int
    {
        $sql    = $this->tableGateway->getSql();
        $select = $sql->select()
            ->columns(['c' => new Expression('COUNT(*)')])
            ->where(['role' => $role]);
        $stmt   = $sql->prepareStatementForSqlObject($select);
        $result = $stmt->execute();

        return (int) $result->current()['c'];
    }

    public function usernameExists(string $username, ?int $excludeId = null): bool
    {
        $rowset = $this->tableGateway->select(function (Select $select) use ($username, $excludeId): void {
            $select->columns(['id']);
            $select->where(['username' => $username]);

            if ($excludeId !== null) {
                $select->where->notEqualTo('id', $excludeId);
            }

            $select->limit(1);
        });

        return $rowset->count() > 0;
    }

    public function saveUser(User $user, ?string $passwordHash = null): void
    {
        $data = [
            'username'  => $user->username,
            'full_name' => $user->fullName,
            'role'      => $user->role,
        ];

        if ($passwordHash !== null) {
            $data['password'] = $passwordHash;
        }

        if ($user->id === 0) {
            if ($passwordHash === null) {
                throw new \InvalidArgumentException('Tài khoản mới bắt buộc phải có mật khẩu.');
            }

            $this->tableGateway->insert($data);
            $user->id = (int) $this->tableGateway->getLastInsertValue();
            $user->password = $passwordHash;

            return;
        }

        $this->tableGateway->update($data, ['id' => $user->id]);

        if ($passwordHash !== null) {
            $user->password = $passwordHash;
        }
    }
}
