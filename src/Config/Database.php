<?php

namespace App\Config;

use PDO;
use PDOException;

/**
 * Class Database
 * Handles PDO singleton connection to MySQL.
 */
class Database
{
    private static ?PDO $instance = null;
    private static array $config = [];

    /**
     * Prevent instantiation
     */
    private function __construct() {}

    /**
     * Get active PDO connection instance
     * 
     * @return PDO
     * @throws PDOException
     */
    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            if (empty(self::$config)) {
                $configFile = dirname(__DIR__, 2) . '/config/database.php';
                if (!file_exists($configFile)) {
                    throw new PDOException("Configuration file not found: {$configFile}");
                }
                self::$config = require $configFile;
            }

            $dsn = sprintf(
                "mysql:host=%s;port=%d;dbname=%s;charset=%s",
                self::$config['host'],
                self::$config['port'] ?? 3306,
                self::$config['dbname'],
                self::$config['charset'] ?? 'utf8mb4'
            );

            try {
                self::$instance = new PDO(
                    $dsn,
                    self::$config['username'],
                    self::$config['password'],
                    self::$config['options'] ?? []
                );
            } catch (PDOException $e) {
                // If database doesn't exist yet, we attempt connecting without dbname to allow auto-setup
                if ($e->getCode() == 1049) {
                    $initDsn = sprintf(
                        "mysql:host=%s;port=%d;charset=%s",
                        self::$config['host'],
                        self::$config['port'] ?? 3306,
                        self::$config['charset'] ?? 'utf8mb4'
                    );
                    return new PDO($initDsn, self::$config['username'], self::$config['password']);
                }
                throw $e;
            }
        }

        return self::$instance;
    }

    /**
     * Reset connection instance (useful after migrations/setup)
     */
    public static function reset(): void
    {
        self::$instance = null;
    }
}
