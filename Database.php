<?php
/**
 * ANTALYA SHAWARMA - Database Class
 * PDO MySQL Connection Handler
 */

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;
    private static $instance = null;

    public function __construct() {
        $this->host = DB_HOST;
        $this->db_name = DB_NAME;
        $this->username = DB_USER;
        $this->password = DB_PASS;
    }

    // Singleton pattern
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Get database connection
    public function getConnection() {
        if ($this->conn === null) {
            try {
                $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset=" . DB_CHARSET;
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
                $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            } catch (PDOException $e) {
                die(json_encode([
                    'success' => false,
                    'message' => 'Database connection failed: ' . $e->getMessage()
                ]));
            }
        }
        return $this->conn;
    }

    // Execute query with parameters
    public function query($sql, $params = []) {
        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new Exception('Query failed: ' . $e->getMessage());
        }
    }

    // Get single row
    public function fetch($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }

    // Get all rows
    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }

    // Insert and return last ID
    public function insert($sql, $params = []) {
        $this->query($sql, $params);
        return $this->getConnection()->lastInsertId();
    }

    // Update and return affected rows
    public function update($sql, $params = []) {
        return $this->query($sql, $params)->rowCount();
    }

    // Delete and return affected rows
    public function delete($sql, $params = []) {
        return $this->query($sql, $params)->rowCount();
    }

    // Begin transaction
    public function beginTransaction() {
        return $this->getConnection()->beginTransaction();
    }

    // Commit transaction
    public function commit() {
        return $this->getConnection()->commit();
    }

    // Rollback transaction
    public function rollback() {
        return $this->getConnection()->rollBack();
    }
}

// Helper function to get database instance
function db() {
    return Database::getInstance();
}
