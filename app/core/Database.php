<?php
// FILE: /app/core/Database.php

/**
 * Database class - PDO wrapper for MySQL connections
 *
 * Provides singleton pattern database connection and query execution
 * with prepared statements for security.
 */
class Database
{
    private static $instance = null;
    private $pdo;
    private $stmt;

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct()
    {
        $dsn = 'mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_NAME') . ';charset=utf8mb4';
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];

        try {
            $this->pdo = new PDO($dsn, getenv('DB_USER'), getenv('DB_PASS'), $options);
        } catch (PDOException $e) {
            error_log('Database Connection Error: ' . $e->getMessage());
            throw new Exception('Database connection failed. Please check your configuration.');
        }
    }

    /**
     * Get singleton instance
     *
     * @return Database
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get PDO instance
     *
     * @return PDO
     */
    public function getConnection()
    {
        return $this->pdo;
    }

    /**
     * Prepare a SQL query
     *
     * @param string $sql
     * @return void
     */
    public function query($sql)
    {
        $this->stmt = $this->pdo->prepare($sql);
    }

    /**
     * Bind values to prepared statement
     *
     * @param mixed $param
     * @param mixed $value
     * @param int|null $type
     * @return void
     */
    public function bind($param, $value, $type = null)
    {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
    }

    /**
     * Execute prepared statement
     *
     * @return bool
     */
    public function execute()
    {
        try {
            return $this->stmt->execute();
        } catch (PDOException $e) {
            error_log('Query Execution Error: ' . $e->getMessage());
            throw new Exception('Database query failed.');
        }
    }

    /**
     * Fetch all results
     *
     * @return array
     */
    public function fetchAll()
    {
        $this->execute();
        return $this->stmt->fetchAll();
    }

    /**
     * Fetch single result
     *
     * @return mixed
     */
    public function fetch()
    {
        $this->execute();
        return $this->stmt->fetch();
    }

    /**
     * Get row count
     *
     * @return int
     */
    public function rowCount()
    {
        return $this->stmt->rowCount();
    }

    /**
     * Get last insert ID
     *
     * @return string
     */
    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * Begin transaction
     *
     * @return bool
     */
    public function beginTransaction()
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit transaction
     *
     * @return bool
     */
    public function commit()
    {
        return $this->pdo->commit();
    }

    /**
     * Rollback transaction
     *
     * @return bool
     */
    public function rollback()
    {
        return $this->pdo->rollback();
    }
}
