<?php
/**
 * Model.php
 * Abstract base model – wraps PDO with safe query helpers.
 * Dependencies: Database.php, config.php
 */

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/config.php';

abstract class Model
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Execute a prepared statement and return the PDOStatement.
     */
    protected function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Fetch a single row.
     */
    protected function fetchOne(string $sql, array $params = []): ?array
    {
        $row = $this->query($sql, $params)->fetch();
        return $row ?: null;
    }

    /**
     * Fetch all matching rows.
     */
    protected function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Execute INSERT/UPDATE/DELETE and return affected rows.
     */
    protected function execute(string $sql, array $params = []): int
    {
        return $this->query($sql, $params)->rowCount();
    }

    /**
     * Return last inserted ID.
     */
    protected function lastId(): string
    {
        return $this->db->lastInsertId();
    }
}
