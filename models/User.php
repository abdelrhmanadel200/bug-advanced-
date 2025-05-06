<?php
abstract class User {
    protected $id;
    protected $name;
    protected $email;
    protected $password;
    protected $status;
    protected $role;
    protected $created_at;
    protected $updated_at;
    protected $last_login;

    public function __construct($id = null, $name = null, $email = null, $password = null) {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
        $this->status = 'active';
        $this->created_at = date('Y-m-d H:i:s');
        $this->updated_at = date('Y-m-d H:i:s');
    }

    public function getId() {
        return $this->id;
    }

    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->name = $name;
        $this->updated_at = date('Y-m-d H:i:s');
    }

    public function getEmail() {
        return $this->email;
    }

    public function setEmail($email) {
        $this->email = $email;
        $this->updated_at = date('Y-m-d H:i:s');
    }

    public function getPassword() {
        return $this->password;
    }

    public function setPassword($password) {
        $this->password = password_hash($password, PASSWORD_DEFAULT);
        $this->updated_at = date('Y-m-d H:i:s');
    }

    public function getStatus() {
        return $this->status;
    }

    public function setStatus($status) {
        $this->status = $status;
        $this->updated_at = date('Y-m-d H:i:s');
    }

    public function getRole() {
        return $this->role;
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

    public function setLastLogin($last_login) {
        $this->last_login = $last_login;
    }

    public function login($email, $password) {
        // This will be implemented in the concrete classes
        return false;
    }

    public function logout() {
        // This will be implemented in the concrete classes
        return false;
    }

    public function getProfile() {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'last_login' => $this->last_login
        ];
    }

    public function setProfile($profile) {
        if (isset($profile['name'])) {
            $this->name = $profile['name'];
        }
        if (isset($profile['email'])) {
            $this->email = $profile['email'];
        }
        if (isset($profile['status'])) {
            $this->status = $profile['status'];
        }
        $this->updated_at = date('Y-m-d H:i:s');
    }

    abstract public function save();
    abstract public function delete();
}
