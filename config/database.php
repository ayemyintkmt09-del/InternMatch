<?php
class Database {
    private $host = "localhost";
    private $port = "3307"; // Configured to port 3307 as requested
    private $db_name = "internmatch_db";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $dsn = "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            $this->conn = new PDO($dsn, $this->username, $this->password);
            
            // Set PDO error mode to exception for proper debugging and OOSAD error handling
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Set default fetch mode to associative array for cleaner data retrieval
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
        } catch(PDOException $exception) {
            // Log or display connection error safely
            echo "Database Connection Error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
?>