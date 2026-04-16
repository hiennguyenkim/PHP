<?php
declare(strict_types=1);

namespace Library\Model\Entity;

class User
{
    public int    $id        = 0;
    public string $username  = '';
    public string $password  = '';
    public string $fullName  = '';
    public string $role      = 'student';
    public string $createdAt = '';

    public function exchangeArray(array $data): void
    {
        $this->id        = (int)    ($data['id']         ?? 0);
        $this->username  = (string) ($data['username']   ?? '');
        $this->password  = (string) ($data['password']   ?? '');
        $this->fullName  = (string) ($data['full_name']  ?? '');
        $this->role      = (string) ($data['role']       ?? 'student');
        $this->createdAt = (string) ($data['created_at'] ?? '');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function getArrayCopy(): array
    {
        return [
            'id'        => $this->id,
            'username'  => $this->username,
            'full_name' => $this->fullName,
            'role'      => $this->role,
        ];
    }
}
