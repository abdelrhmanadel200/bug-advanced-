<?php
require_once 'models/User.php';
require_once 'services/NotificationService.php';
require_once 'models/BugTrackingSystem.php';
require_once 'models/Bug.php';
require_once 'models/Staff.php';
require_once 'models/Customer.php';

class Administrator extends User {
    public function __construct($id = null, $name = null, $email = null, $password = null) {
        parent::__construct($id, $name, $email, $password);
        $this->role = 'administrator';
    }
    
    public function login($email, $password) {
        global $db;
        
        if (!$db) {
            // If $db is still null, try to reconnect
            require_once 'config/database.php';
        }
        
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND role = 'administrator'");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            $this->id = $user['id'];
            $this->name = $user['name'];
            $this->email = $user['email'];
            $this->password = $user['password'];
            $this->status = $user['status'];
            $this->created_at = $user['created_at'];
            $this->updated_at = $user['updated_at'];
            $this->last_login = date('Y-m-d H:i:s');
            
            // Update last login
            $stmt = $db->prepare("UPDATE users SET last_login = ? WHERE id = ?");
            $stmt->execute([$this->last_login, $this->id]);
            
            return true;
        }
        
        return false;
    }

    public function logout() {
        // Clear session data
        return true;
    }

    public function assignBugToStaff($bugId, $staffId) {
        $bug = new Bug($bugId);
        
        if (!$bug->getId()) {
            return false;
        }
        
        $bug->setAssignedTo($staffId);
        $bug->setStatus('assigned');
        
        // Log activity
        $system = BugTrackingSystem::getInstance();
        $system->logActivity($this->id, 'assign_bug', "Assigned bug #{$bug->getTicketNumber()} to staff");
        
        // Get staff details for notification
        global $db;
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND role = 'staff'");
        $stmt->execute([$staffId]);
        $staffData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($staffData) {
            $staff = new Staff($staffData['id'], $staffData['name'], $staffData['email'], $staffData['password']);
            
            // Send notification
            $notificationService = NotificationService::getInstance();
            $notificationService->notifyBugAssignment($staff, $bug);
        }
        
        return $bug->save();
    }

    public function addStaff($name, $email, $password) {
        // Check if email already exists
        global $db;
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            return false; // Email already exists
        }
        
        $staff = new Staff(null, $name, $email, $password);
        $staff->setStatus('active');
        $staff->setCreatedAt(date('Y-m-d H:i:s'));
        $staff->setUpdatedAt(date('Y-m-d H:i:s'));
        
        if ($staff->save()) {
            // Log activity
            $system = BugTrackingSystem::getInstance();
            $system->logActivity($this->id, 'add_staff', "Added new staff member: {$name}");
            
            // Send notification to new staff
            $notificationService = NotificationService::getInstance();
            $notificationService->notifyNewUserCreation($staff, $password);
            
            return true;
        }
        
        return false;
    }

    public function viewReportedBugs() {
        global $db;
        
        $stmt = $db->prepare("SELECT b.*, p.name as project_name, u1.name as reporter_name, u2.name as assignee_name 
                             FROM bugs b 
                             LEFT JOIN projects p ON b.project_id = p.id 
                             LEFT JOIN users u1 ON b.reported_by = u1.id 
                             LEFT JOIN users u2 ON b.assigned_to = u2.id 
                             ORDER BY b.created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function viewBugCaseFlow($bugId) {
        $bug = new Bug($bugId);
        
        if (!$bug->getId()) {
            return null;
        }
        
        $details = $bug->getDetails();
        $comments = $bug->getComments();
        $history = $bug->getHistory();
        
        return [
            'details' => $details,
            'comments' => $comments,
            'history' => $history
        ];
    }

    public function updateUserDetails($userId, $details) {
        global $db;
        
        $sql = "UPDATE users SET ";
        $params = [];
        
        foreach ($details as $key => $value) {
            if ($key !== 'id' && $key !== 'role') {
                $sql .= "$key = ?, ";
                $params[] = $value;
            }
        }
        
        $sql .= "updated_at = ? WHERE id = ?";
        $params[] = date('Y-m-d H:i:s');
        $params[] = $userId;
        
        $stmt = $db->prepare($sql);
        $result = $stmt->execute($params);
        
        if ($result) {
            // Log activity
            $system = BugTrackingSystem::getInstance();
            $system->logActivity($this->id, 'update_user', "Updated user details for user ID: {$userId}");
            
            return true;
        }
        
        return false;
    }

    public function manageBug($bugId, $details) {
        $bug = new Bug($bugId);
        
        if (!$bug->getId()) {
            return false;
        }
        
        if (isset($details['title'])) {
            $bug->setTitle($details['title']);
        }
        
        if (isset($details['description'])) {
            $bug->setDescription($details['description']);
        }
        
        if (isset($details['project_id'])) {
            $bug->setProjectId($details['project_id']);
        }
        
        if (isset($details['severity'])) {
            $bug->setSeverity($details['severity']);
        }
        
        if (isset($details['priority'])) {
            $bug->setPriority($details['priority']);
        }
        
        if (isset($details['status'])) {
            $bug->setStatus($details['status']);
        }
        
        if (isset($details['assigned_to'])) {
            $bug->setAssignedTo($details['assigned_to']);
        }
        
        if (isset($details['steps'])) {
            $bug->setSteps($details['steps']);
        }
        
        if (isset($details['expected_result'])) {
            $bug->setExpectedResult($details['expected_result']);
        }
        
        if (isset($details['actual_result'])) {
            $bug->setActualResult($details['actual_result']);
        }
        
        // Log activity
        $system = BugTrackingSystem::getInstance();
        $system->logActivity($this->id, 'manage_bug', "Updated bug #{$bug->getTicketNumber()}");
        
        return $bug->save();
    }

    public function manageUser($userId, $action) {
        global $db;
        
        switch ($action) {
            case 'activate':
                $stmt = $db->prepare("UPDATE users SET status = 'active', updated_at = ? WHERE id = ?");
                $result = $stmt->execute([date('Y-m-d H:i:s'), $userId]);
                
                if ($result) {
                    // Log activity
                    $system = BugTrackingSystem::getInstance();
                    $system->logActivity($this->id, 'activate_user', "Activated user ID: {$userId}");
                    
                    return true;
                }
                
                return false;
                
            case 'deactivate':
                $stmt = $db->prepare("UPDATE users SET status = 'inactive', updated_at = ? WHERE id = ?");
                $result = $stmt->execute([date('Y-m-d H:i:s'), $userId]);
                
                if ($result) {
                    // Log activity
                    $system = BugTrackingSystem::getInstance();
                    $system->logActivity($this->id, 'deactivate_user', "Deactivated user ID: {$userId}");
                    
                    return true;
                }
                
                return false;
                
            case 'ban':
                $stmt = $db->prepare("UPDATE users SET status = 'banned', updated_at = ? WHERE id = ?");
                $result = $stmt->execute([date('Y-m-d H:i:s'), $userId]);
                
                if ($result) {
                    // Log activity
                    $system = BugTrackingSystem::getInstance();
                    $system->logActivity($this->id, 'ban_user', "Banned user ID: {$userId}");
                    
                    return true;
                }
                
                return false;
                
            case 'delete':
                // Get user details for logging
                $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$user) {
                    return false;
                }
                
                // Create appropriate user object
                switch ($user['role']) {
                    case 'administrator':
                        $userObj = new Administrator($userId);
                        break;
                    case 'staff':
                        $userObj = new Staff($userId);
                        break;
                    case 'customer':
                        $userObj = new Customer($userId);
                        break;
                    default:
                        return false;
                }
                
                if ($userObj->delete()) {
                    // Log activity
                    $system = BugTrackingSystem::getInstance();
                    $system->logActivity($this->id, 'delete_user', "Deleted user: {$user['name']} ({$user['email']})");
                    
                    return true;
                }
                
                return false;
                
            default:
                return false;
        }
    }

    public function resetUserPassword($userId, $newPassword) {
        global $db;
        
        // Get user details
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return false;
        }
        
        // Create appropriate user object
        switch ($user['role']) {
            case 'administrator':
                $userObj = new Administrator($userId, $user['name'], $user['email'], $user['password']);
                break;
            case 'staff':
                $userObj = new Staff($userId, $user['name'], $user['email'], $user['password']);
                break;
            case 'customer':
                $userObj = new Customer($userId, $user['name'], $user['email'], $user['password']);
                break;
            default:
                return false;
        }
        
        $userObj->setPassword($newPassword);
        
        if ($userObj->save()) {
            // Log activity
            $system = BugTrackingSystem::getInstance();
            $system->logActivity($this->id, 'reset_password', "Reset password for user: {$user['name']}");
            
            // Send notification
            $notificationService = NotificationService::getInstance();
            $message = "Your password has been reset by an administrator. Please use your new password to login.";
            $link = "index.php?controller=auth&action=login";
            $notificationService->createInAppNotification($userId, $message, 'warning', $link);
            
            // Send email notification
            $notificationService->sendEmail(
                $user['email'],
                $user['name'],
                "Your Password Has Been Reset",
                "Your password has been reset by an administrator. Please use your new password to login.",
                "Your password has been reset by an administrator. Please use your new password to login."
            );
            
            return true;
        }
        
        return false;
    }

    public function generateReports($type, $filters = []) {
        $system = BugTrackingSystem::getInstance();
        return $system->generateReports($type, $filters);
    }

    public function save() {
        global $db;
        
        if ($this->id) {
            // Update existing administrator
            $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, password = ?, status = ?, updated_at = ? WHERE id = ?");
            return $stmt->execute([$this->name, $this->email, $this->password, $this->status, date('Y-m-d H:i:s'), $this->id]);
        } else {
            // Insert new administrator
            $stmt = $db->prepare("INSERT INTO users (name, email, password, role, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
            return $stmt->execute([$this->name, $this->email, $this->password, $this->role, $this->status, $this->created_at, $this->updated_at]);
        }
    }

    public function delete() {
        global $db;
        
        if ($this->id) {
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            return $stmt->execute([$this->id]);
        }
        
        return false;
    }
}
