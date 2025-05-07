<?php
require_once 'models/User.php';
require_once 'models/Administrator.php';
require_once 'models/Staff.php';
require_once 'models/Customer.php';
require_once 'services/NotificationService.php';

class UserService {
    private static $instance = null;
    
    private function __construct() {
        // Private constructor to prevent direct creation
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new UserService();
        }
        return self::$instance;
    }
    
    public function getUserById($id) {
        global $db;
        
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$userData) {
            return null;
        }
        
        switch ($userData['role']) {
            case 'administrator':
                return new Administrator($userData['id'], $userData['name'], $userData['email'], $userData['password']);
            case 'staff':
                return new Staff($userData['id'], $userData['name'], $userData['email'], $userData['password']);
            case 'customer':
                return new Customer($userData['id'], $userData['name'], $userData['email'], $userData['password']);
            default:
                return null;
        }
    }
    
    public function getUserByEmail($email) {
        global $db;
        
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$userData) {
            return null;
        }
        
        switch ($userData['role']) {
            case 'administrator':
                return new Administrator($userData['id'], $userData['name'], $userData['email'], $userData['password']);
            case 'staff':
                return new Staff($userData['id'], $userData['name'], $userData['email'], $userData['password']);
            case 'customer':
                return new Customer($userData['id'], $userData['name'], $userData['email'], $userData['password']);
            default:
                return null;
        }
    }
    
    public function createUser($name, $email, $password, $role) {
        global $db;
        
        // Check if email already exists
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            return false; // Email already exists
        }
        
        switch ($role) {
            case 'administrator':
                $user = new Administrator(null, $name, $email, $password);
                break;
            case 'staff':
                $user = new Staff(null, $name, $email, $password);
                break;
            case 'customer':
                $user = new Customer(null, $name, $email, $password);
                break;
            default:
                return false;
        }
        
        if ($user->save()) {
            // Send welcome email
            $notificationService = NotificationService::getInstance();
            $notificationService->notifyNewUserCreation($user, $password);
            
            return $user->getId();
        }
        
        return false;
    }
    
    public function updateUserStatus($userId, $status, $adminId) {
        $user = $this->getUserById($userId);
        
        if (!$user) {
            return false;
        }
        
        $user->setStatus($status);
        
        if ($user->save()) {
            // Log activity
            $system = BugTrackingSystem::getInstance();
            $system->logActivity($adminId, 'update_user_status', "Updated user status to {$status} for user: {$user->getName()}");
            
            // Create notification
            $notificationService = NotificationService::getInstance();
            $message = "Your account status has been updated to " . ucfirst($status);
            $link = "index.php?controller=user&action=profile";
            $notificationService->createInAppNotification($userId, $message, 'warning', $link);
            
            return true;
        }
        
        return false;
    }
    
    public function resetUserPassword($userId, $newPassword, $adminId) {
        $user = $this->getUserById($userId);
        
        if (!$user) {
            return false;
        }
        
        $user->setPassword($newPassword);
        
        if ($user->save()) {
            // Log activity
            $system = BugTrackingSystem::getInstance();
            $system->logActivity($adminId, 'reset_password', "Reset password for user: {$user->getName()}");
            
            // Send notification
            $notificationService = NotificationService::getInstance();
            $message = "Your password has been reset by an administrator. Please use your new password to login.";
            $link = "index.php?controller=auth&action=login";
            $notificationService->createInAppNotification($userId, $message, 'warning', $link);
            
            return true;
        }
        
        return false;
    }
    
    public function deleteUser($userId, $adminId) {
        $user = $this->getUserById($userId);
        
        if (!$user) {
            return false;
        }
        
        $userName = $user->getName();
        $userEmail = $user->getEmail();
        
        if ($user->delete()) {
            // Log activity
            $system = BugTrackingSystem::getInstance();
            $system->logActivity($adminId, 'delete_user', "Deleted user: {$userName} ({$userEmail})");
            
            return true;
        }
        
        return false;
    }
    
    public function getActiveStaffMembers() {
        global $db;
        
        $stmt = $db->prepare("SELECT id, name FROM users WHERE role = 'staff' AND status = 'active'");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getActiveAdministrators() {
        global $db;
        
        $stmt = $db->prepare("SELECT id, name FROM users WHERE role = 'administrator' AND status = 'active'");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getUserStatistics() {
        global $db;
        
        $stmt = $db->prepare("SELECT 
                             COUNT(*) as total,
                             SUM(CASE WHEN role = 'administrator' THEN 1 ELSE 0 END) as administrators,
                             SUM(CASE WHEN role = 'staff' THEN 1 ELSE 0 END) as staff,
                             SUM(CASE WHEN role = 'customer' THEN 1 ELSE 0 END) as customers,
                             SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                             SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive,
                             SUM(CASE WHEN status = 'banned' THEN 1 ELSE 0 END) as banned
                             FROM users");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function assignBugToStaff($bugId, $staffId, $userId) {
        $bugService = BugService::getInstance();
        return $bugService->assignBug($bugId, $staffId, $userId);
    }
}
