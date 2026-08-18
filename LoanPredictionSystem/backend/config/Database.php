<?php
/**
 * Database.php
 * PDO singleton connection manager.
 * Dependencies: PHP 7.4+, PDO, pdo_mysql extension
 */
class Database
{
    private static ?PDO $instance = null;

    // ── Connection settings ────────────────────────────────────────────────
    private static string $host     = 'localhost';
    private static string $dbname   = 'loansecure';
    private static string $username = 'root';
    private static string $password = '';          // change in production
    private static string $charset  = 'utf8mb4';

    /** Prevent direct instantiation */
    private function __construct() {}
    private function __clone() {}

    /**
     * Returns a singleton PDO connection.
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                self::$host,
                self::$dbname,
                self::$charset
            );

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            ];

            try {
                self::$instance = new PDO($dsn, self::$username, self::$password, $options);
            } catch (PDOException $e) {
                // Never expose DSN details in production
                http_response_code(500);
                die(json_encode(['success' => false, 'message' => 'Database connection failed.']));
            }
        }

        return self::$instance;
    }
}
