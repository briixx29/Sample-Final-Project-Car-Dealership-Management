<?php

namespace App\Services;

use App\Config\Database;
use App\Models\User;
use PDO;
use Exception;

/**
 * Class UserService
 * Manages administrator and dealership staff accounts.
 */
class UserService
{
    /**
     * Get all users
     * @return User[]
     */
    public static function getAll(): array
    {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT * FROM users ORDER BY id ASC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $users = [];
        foreach ($rows as $row) {
            $users[] = User::fromArray($row);
        }

        return $users;
    }

    /**
     * Get user by ID
     */
    public static function getById(int $id): ?User
    {
        if ($id <= 0) return null;

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? User::fromArray($row) : null;
    }

    /**
     * Validate user input
     */
    public static function validate(array $data, bool $isUpdate = false, ?int $userId = null): array
    {
        $errors = [];

        // Username
        if (!isset($data['username']) || trim($data['username']) === '') {
            $errors['username'] = 'Username is required.';
        } elseif (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', trim($data['username']))) {
            $errors['username'] = 'Username must be 3-30 characters (letters, numbers, underscore).';
        } else {
            // Check uniqueness
            $db = Database::getConnection();
            $sql = "SELECT id FROM users WHERE username = :username";
            if ($isUpdate && $userId) {
                $sql .= " AND id != {$userId}";
            }
            $stmt = $db->prepare($sql);
            $stmt->execute(['username' => trim($data['username'])]);
            if ($stmt->fetch()) {
                $errors['username'] = 'This username is already taken.';
            }
        }

        // Full Name
        if (!isset($data['full_name']) || trim($data['full_name']) === '') {
            $errors['full_name'] = 'Full name is required.';
        }

        // Email
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email address.';
        }

        // Password
        if (!$isUpdate) {
            if (empty($data['password']) || strlen($data['password']) < 6) {
                $errors['password'] = 'Password must be at least 6 characters.';
            }
        } elseif (!empty($data['password']) && strlen($data['password']) < 6) {
            $errors['password'] = 'New password must be at least 6 characters.';
        }

        // Role
        if (isset($data['role']) && !in_array(strtolower($data['role']), ['admin', 'staff'])) {
            $errors['role'] = 'Role must be Admin or Staff.';
        }

        return $errors;
    }

    /**
     * Create user account
     */
    public static function create(array $data): array
    {
        $errors = self::validate($data);
        if (!empty($errors)) {
            return ['success' => false, 'message' => 'Please correct form errors.', 'errors' => $errors];
        }

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                INSERT INTO users (username, password_hash, full_name, email, role, status, created_at, updated_at)
                VALUES (:username, :password_hash, :full_name, :email, :role, :status, NOW(), NOW())
            ");

            $stmt->execute([
                'username' => trim($data['username']),
                'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
                'full_name' => trim($data['full_name']),
                'email' => trim($data['email'] ?? ''),
                'role' => strtolower($data['role'] ?? 'staff'),
                'status' => $data['status'] ?? 'active'
            ]);

            return ['success' => true, 'message' => "User '{$data['username']}' created successfully."];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Update user account
     */
    public static function update(int $id, array $data): array
    {
        $existing = self::getById($id);
        if (!$existing) {
            return ['success' => false, 'message' => 'User not found.'];
        }

        $errors = self::validate($data, true, $id);
        if (!empty($errors)) {
            return ['success' => false, 'message' => 'Please correct form errors.', 'errors' => $errors];
        }

        try {
            $db = Database::getConnection();
            $sql = "
                UPDATE users SET
                    username = :username,
                    full_name = :full_name,
                    email = :email,
                    role = :role,
                    status = :status,
                    updated_at = NOW()
            ";

            $params = [
                'username' => trim($data['username']),
                'full_name' => trim($data['full_name']),
                'email' => trim($data['email'] ?? ''),
                'role' => strtolower($data['role'] ?? $existing->getRole()),
                'status' => $data['status'] ?? $existing->getStatus(),
                'id' => $id
            ];

            if (!empty($data['password'])) {
                $sql .= ", password_hash = :hash";
                $params['hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }

            $sql .= " WHERE id = :id";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            return ['success' => true, 'message' => "User account updated successfully."];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Toggle active/inactive status
     */
    public static function toggleStatus(int $id): array
    {
        $user = self::getById($id);
        if (!$user) {
            return ['success' => false, 'message' => 'User not found.'];
        }

        $newStatus = $user->isActive() ? 'inactive' : 'active';

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("UPDATE users SET status = :status, updated_at = NOW() WHERE id = :id");
            $stmt->execute(['status' => $newStatus, 'id' => $id]);

            return ['success' => true, 'message' => "User status changed to '{$newStatus}'."];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
