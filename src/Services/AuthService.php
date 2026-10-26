<?php

namespace App\Services;

use App\Config\Database;
use App\Models\User;
use PDO;

/**
 * Class AuthService
 * Handles user authentication, session security, and role authorization.
 */
class AuthService
{
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Authenticate user credentials
     * 
     * @param string $username
     * @param string $password
     * @return array [success => bool, message => string, user => ?User]
     */
    public static function login(string $username, string $password): array
    {
        self::startSession();

        $username = trim($username);
        $password = trim($password);

        if ($username === '' || $password === '') {
            return [
                'success' => false,
                'message' => 'Please provide both username and password.'
            ];
        }

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
            $stmt->execute(['username' => $username]);
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$userData) {
                return [
                    'success' => false,
                    'message' => 'Invalid username or password.'
                ];
            }

            if (!password_verify($password, $userData['password_hash'])) {
                return [
                    'success' => false,
                    'message' => 'Invalid username or password.'
                ];
            }

            if (strtolower($userData['status'] ?? '') !== 'active') {
                return [
                    'success' => false,
                    'message' => 'This account has been deactivated. Please contact an Administrator.'
                ];
            }

            $user = User::fromArray($userData);

            // Store in session
            $_SESSION['user_id'] = $user->getId();
            $_SESSION['username'] = $user->getUsername();
            $_SESSION['full_name'] = $user->getFullName();
            $_SESSION['role'] = $user->getRole();
            $_SESSION['logged_in'] = true;

            return [
                'success' => true,
                'message' => 'Login successful.',
                'user' => $user
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Terminate session
     */
    public static function logout(): void
    {
        self::startSession();
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }

    /**
     * Check if current user is logged in
     */
    public static function check(): bool
    {
        self::startSession();
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    /**
     * Require login or redirect to login.php
     */
    public static function requireAuth(string $redirect = 'login.php'): void
    {
        if (!self::check()) {
            header("Location: {$redirect}?error=" . urlencode("Please log in to access this page."));
            exit;
        }
    }

    /**
     * Require Admin role or redirect with error
     */
    public static function requireAdmin(string $redirect = 'index.php'): void
    {
        self::requireAuth();
        if (!self::isAdmin()) {
            header("Location: {$redirect}?error=" . urlencode("Access denied: Administrator privileges required."));
            exit;
        }
    }

    public static function getCurrentUser(): ?array
    {
        self::startSession();
        if (!self::check()) {
            return null;
        }

        return [
            'id' => $_SESSION['user_id'] ?? null,
            'username' => $_SESSION['username'] ?? '',
            'full_name' => $_SESSION['full_name'] ?? '',
            'role' => $_SESSION['role'] ?? 'staff',
        ];
    }

    public static function isAdmin(): bool
    {
        self::startSession();
        return isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin';
    }

    public static function isStaff(): bool
    {
        self::startSession();
        return isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'staff';
    }
}
