<?php
// Class untuk mengelola data user
class User {
    private $conn;
    private $table = "users";

    public $id;
    public $username;
    public $password;
    public $email;
    public $role;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Method untuk login
    public function login() {
        $query = "SELECT id, username, password, email, role 
                  FROM " . $this->table . " 
                  WHERE username = :username";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $this->username);
        $stmt->execute();
        
        return $stmt;
    }

    // Method untuk register user baru
    public function register() {
        $query = "INSERT INTO " . $this->table . " 
                  (username, password, email, role) 
                  VALUES (:username, :password, :email, :role)";
        
        $stmt = $this->conn->prepare($query);
        
        // Hash password
        $hashed_password = password_hash($this->password, PASSWORD_DEFAULT);
        
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":password", $hashed_password);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":role", $this->role);
        
        return $stmt->execute();
    }

    // Method untuk cek apakah username sudah ada
    public function usernameExists() {
        $query = "SELECT id FROM " . $this->table . " WHERE username = :username";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $this->username);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
}
?>