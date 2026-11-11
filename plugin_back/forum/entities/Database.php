<?php

namespace Forum\Entities;

use PDO;
use PDOException;

defined('ROOT') or exit('Access denied!');

class Database
{
    private static $instance = null;
    private $db;

    private function __construct()
    {
        $dbPath = DATA_PLUGIN . 'forum/database.sqlite3';

        if (!file_exists($dbPath)) {
            throw new \Exception('Forum database not found. Please install the plugin first.');
        }

        try {
            $this->db = \Core\Database::getInstance()->getSqlitePdo($dbPath);
        } catch (PDOException $e) {
            throw new \Exception('Database connection failed: ' . $e->getMessage());
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): PDO
    {
        return $this->db;
    }

    public function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    public function fetchOne(string $sql, array $params = []): ?array
    {
        $result = $this->query($sql, $params)->fetch();
        return $result ?: null;
    }

    public function execute(string $sql, array $params = []): bool
    {
        try {
            $this->query($sql, $params);
            return true;
        } catch (PDOException $e) {
            \Core\Core::getInstance()->getLogger()->log(\Core\Logger::LEVEL_ERROR, 'Forum DB error: ' . $e->getMessage());
            return false;
        }
    }

    public function lastInsertId(): int
    {
        return (int) $this->db->lastInsertId();
    }
}

