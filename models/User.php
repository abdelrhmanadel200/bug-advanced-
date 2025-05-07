<?php
abstract class User {
    protected $id;
    protected $name;
    protected $email;
    protected $password;
    protected $role;
    protected $status;
    protected $created_at;
    protected $updated_at;
    protected $last_login;
    
    public function __construct($id = null, $name = null, $email = null, $password = null) {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
    }
    
    // Getters
    public function getId() {
        return $this->id;
    }
    
    public function getName() {
        return $this->name;
    }
    
    public function getEmail() {
        return $this->email;
    }
    
    public function getRole() {
        return $this->role;
    }
    
    public function getStatus() {
        return $this->status;
    }
    
    public function getCreatedAt() {
        return $this->created_at;
    }
    
    public function getUpdatedAt() {
        return $this->updated_at;
    }
    
    public function getLastLogin() {
        return $this->last_login;
    }
    
    // Setters
    public function setName($name) {
        $this->name = $name;
    }
    
    public function setEmail($email) {
        $this->email = $email;
    }
    
    public function setPassword($password) {
        $this->password = $password;
    }
    
    public function setStatus($status) {
        $this->status = $status;
    }
    
    public function setCreatedAt($created_at) {
        $this->created_at = $created_at;
    }
    
    public function setUpdatedAt($updated_at) {
        $this->updated_at = $updated_at;
    }
    
    public function setLastLogin($last_login) {
        $this->last_login = $last_login;
    }
    
    // Abstract methods
    abstract public function login($email, $password);
    abstract public function logout();
    abstract public function save();
    
    // Static methods
    public static function findById($id) {
        global $db;
        
        if (!$db) {
            // If $db is still null, try to reconnect
            require_once 'config/database.php';
        }
        
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public static function findByEmail($email) {
        global $db;
        
        if (!$db) {
            // If $db is still null, try to reconnect
            require_once 'config/database.php';
        }
        
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public static function getAll() {
        global $db;
        
        if (!$db) {
            // If $db is still null, try to reconnect
            require_once 'config/database.php';
        }
        
        $stmt = $db->prepare("SELECT * FROM users ORDER BY name ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
