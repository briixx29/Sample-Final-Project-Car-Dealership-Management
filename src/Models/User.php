<?php

namespace App\Models;

/**
 * Class User
 * Represents a system user (Administrator or Dealership Staff).
 */
class User
{
    private ?int $id;
    private string $username;
    private string $passwordHash;
    private string $fullName;
    private string $email;
    private string $role; // 'admin' or 'staff'
    private string $status; // 'active' or 'inactive'
    private ?string $createdAt;
    private ?string $updatedAt;

    public function __construct(
        ?int $id = null,
        string $username = '',
        string $passwordHash = '',
        string $fullName = '',
        string $email = '',
        string $role = 'staff',
        string $status = 'active',
        ?string $createdAt = null,
        ?string $updatedAt = null
    ) {
        $this->id = $id;
        $this->username = $username;
        $this->passwordHash = $passwordHash;
        $this->fullName = $fullName;
        $this->email = $email;
        $this->role = $role;
        $this->status = $status;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            isset($data['id']) ? (int)$data['id'] : null,
            $data['username'] ?? '',
            $data['password'] ?? $data['password_hash'] ?? '',
            $data['full_name'] ?? '',
            $data['email'] ?? '',
            $data['role'] ?? 'staff',
            $data['status'] ?? 'active',
            $data['created_at'] ?? null,
            $data['updated_at'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'full_name' => $this->fullName,
            'email' => $this->email,
            'role' => $this->role,
            'status' => $this->status,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }

    public function getId(): ?int { return $this->id; }
    public function getUsername(): string { return $this->username; }
    public function getPasswordHash(): string { return $this->passwordHash; }
    public function getFullName(): string { return $this->fullName; }
    public function getEmail(): string { return $this->email; }
    public function getRole(): string { return $this->role; }
    public function getStatus(): string { return $this->status; }
    public function getCreatedAt(): ?string { return $this->createdAt; }
    public function getUpdatedAt(): ?string { return $this->updatedAt; }

    public function setId(int $id): void { $this->id = $id; }
    public function setUsername(string $username): void { $this->username = $username; }
    public function setPasswordHash(string $hash): void { $this->passwordHash = $hash; }
    public function setFullName(string $fullName): void { $this->fullName = $fullName; }
    public function setEmail(string $email): void { $this->email = $email; }
    public function setRole(string $role): void { $this->role = $role; }
    public function setStatus(string $status): void { $this->status = $status; }

    public function isAdmin(): bool { return strtolower($this->role) === 'admin'; }
    public function isStaff(): bool { return strtolower($this->role) === 'staff'; }
    public function isActive(): bool { return strtolower($this->status) === 'active'; }
}
