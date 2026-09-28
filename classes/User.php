<?php
class User {
    private $conn;
    private $table_name = "users";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function register($name, $email, $password, $role) {
        // Check if email already exists
        $query = "SELECT user_id FROM " . $this->table_name . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return "Email is already registered.";
        }

        // Hash password securely[cite: 1]
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        
        // Default status: active for students, pending verification for companies[cite: 1]
        $status = ($role === 'company') ? 'pending' : 'active';

        $insertQuery = "INSERT INTO " . $this->table_name . " (name, email, password, role, status) VALUES (:name, :email, :password, :role, :status)";
        $insertStmt = $this->conn->prepare($insertQuery);
        
        $insertStmt->bindParam(":name", $name);
        $insertStmt->bindParam(":email", $email);
        $insertStmt->bindParam(":password", $hashed_password);
        $insertStmt->bindParam(":role", $role);
        $insertStmt->bindParam(":status", $status);

        if ($insertStmt->execute()) {
            return true;
        }
        return "Registration failed. Please try again.";
    }

    public function login($email, $password) {
        $query = "SELECT user_id, name, email, password, role, status FROM " . $this->table_name . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();

        if ($stmt->rowCount() === 1) {
            $row = $stmt->fetch();
            if (password_verify($password, $row['password'])) {
                if ($row['status'] === 'suspended') {
                    return "Your account has been suspended.";
                }
                session_start();
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['name'] = $row['name'];
                $_SESSION['email'] = $row['email'];
                $_SESSION['role'] = $row['role'];
                return true;
            }
        }
        return "Invalid email or password.";
    }
}
?>