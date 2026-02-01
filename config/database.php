<?php
/**
 * DATABASE.PHP - Kết nối PDO SQL
 * Đọc cấu hình từ config.php
 */

// Load config if not already loaded
if (!defined('DB_HOST')) {
    require_once __DIR__ . '/config.php';
}

class Database {
    private $host;
    private $db_name;
    private $db_user;
    private $db_pass;
    private $db_port;
    private $charset = 'utf8mb4';
    
    private $pdo;
    private $stmt;
    
    public function __construct() {
        // Load từ config.php constants
        $this->host = DB_HOST;
        $this->db_name = DB_NAME;
        $this->db_user = DB_USER;
        $this->db_pass = DB_PASS;
        $this->db_port = DB_PORT;
    }
    
    public function connect() {
        $dsn = "mysql:host={$this->host};port={$this->db_port};dbname={$this->db_name};charset={$this->charset}";
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        try {
            $this->pdo = new PDO($dsn, $this->db_user, $this->db_pass, $options);
            return $this->pdo;
        } catch(PDOException $e) {
            if (defined('APP_DEBUG') && APP_DEBUG) {
                die('Database Connection Error: ' . $e->getMessage());
            } else {
                die('Database connection failed. Please contact administrator.');
            }
        }
    }
    
    public function getConnection() {
        if ($this->pdo === null) {
            $this->connect();
        }
        return $this->pdo;
    }
    
    public function query($sql) {
        $this->stmt = $this->getConnection()->prepare($sql);
        return $this;
    }
    
    public function bind($param, $value, $type = null) {
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
        return $this;
    }
    
    public function execute() {
        return $this->stmt->execute();
    }
    
    public function fetchAll() {
        $this->execute();
        return $this->stmt->fetchAll();
    }
    
    public function fetch() {
        $this->execute();
        return $this->stmt->fetch();
    }
    
    public function rowCount() {
        return $this->stmt->rowCount();
    }
}
