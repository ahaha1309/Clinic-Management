<?php
// =====================================================
// DATABASE CONFIGURATION - CUSTOMER PORTAL
// Sử dụng chung database với hệ thống quản lý
// =====================================================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '123456');
define('DB_NAME', 'clinic_system');
define('DB_CHARSET', 'utf8mb4');

// =====================================================
// DATABASE CONNECTION
// =====================================================

class Database {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ];
            
            $this->conn = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch(PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
}

// =====================================================
// HELPER FUNCTIONS
// =====================================================

function getDB() {
    return Database::getInstance()->getConnection();
}

// Hàm tạo mã tự động (cho patient_code, appointment_code)
function generateCode($prefix, $table, $column) {
    $db = getDB();
    $stmt = $db->query("SELECT MAX(CAST(SUBSTRING($column, LENGTH('$prefix')+1) AS UNSIGNED)) as max_num FROM $table WHERE $column LIKE '$prefix%'");
    $max = $stmt->fetch()['max_num'] ?? 0;
    return $prefix . str_pad($max + 1, 3, '0', STR_PAD_LEFT);
}

// Hàm escape output
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
?>
