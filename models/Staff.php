<?php
require_once 'models/User.php';
require_once 'models/Observer.php';
require_once 'services/NotificationService.php';
require_once 'models/Bug.php';

class Staff extends User implements Observer {
    private $specialization;
    private $skills;
    
    public function __construct($id = null, $name = null, $email = null, $password = null) {
        parent::__construct($id, $name, $email, $password);
        $this->role = 'staff';
    }
    
    public function login($email, $password) {
        global $db;
        
        if (!$db) {
            // If $db is still null, try to reconnect
            require_once 'config/database.php';
        }
        
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND role = 'staff'");
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
            
            // Load specialization and skills
            $this->loadSpecializationAndSkills();
            
            return true;
        }
        
        return false;
    }

    public function logout() {
        // Clear session data
        return true;
    }

    // Fixed update method to match the expected parameters
    public function update($subject, $status) {
        // Use notification service to handle both in-app and email notifications if it exists
        if (class_exists('NotificationService')) {
            $notificationService = NotificationService::getInstance();
            // Pass the old status as an empty string since we don't have it
            return $notificationService->notifyBugStatusChange($this, $subject, $status, '');
        }
        return true;
    }

    public function getNotifications() {
        if (class_exists('NotificationService')) {
            $notificationService = NotificationService::getInstance();
            return $notificationService->getUserNotifications($this->id);
        }
        return [];
    }

    public function save() {
        global $db;
        
        if ($this->id) {
            // Update existing staff
            $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, password = ?, status = ?, updated_at = ? WHERE id = ?");
            return $stmt->execute([$this->name, $this->email, $this->password, $this->status, date('Y-m-d H:i:s'), $this->id]);
        } else {
            // Insert new staff
            $stmt = $db->prepare("INSERT INTO users (name, email, password, role, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
            return $stmt->execute([$this->name, $this->email, $this->password, $this->role, $this->status, $this->created_at, $this->updated_at]);
        }
    }

    public function delete() {
        global $db;
        
        if ($this->id) {
            // Delete notifications if NotificationService exists
            if (class_exists('NotificationService')) {
                $notificationService = NotificationService::getInstance();
                $notificationService->deleteAllNotifications($this->id);
            }
            
            // Delete user
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            return $stmt->execute([$this->id]);
        }
        
        return false;
    }

    // Add this method to handle bug assignment to other staff
    public function assignBugToOtherStaff($bugId, $newStaffId) {
        global $db;
        
        // Check if the bug exists
        $bug = new Bug($bugId);
        if (!$bug->getId()) {
            return false;
        }
        
        // Check if the current staff is assigned to this bug
        if ($bug->getAssignedTo() != $this->id) {
            return false;
        }
        
        // Update the bug assignment
        $bug->setAssignedTo($newStaffId);
        $bug->setStatus('assigned');
        $result = $bug->save();
        
        if ($result) {
            // Log activity
            $system = BugTrackingSystem::getInstance();
            $system->logActivity($this->id, 'reassign_bug', "Reassigned bug #{$bug->getTicketNumber()} to staff ID: {$newStaffId}");
            
            // Get the new staff member
            $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND role = 'staff'");
            $stmt->execute([$newStaffId]);
            $staffData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($staffData) {
                $newStaff = new Staff($staffData['id'], $staffData['name'], $staffData['email'], $staffData['password']);
                
                // Send notification
                $notificationService = NotificationService::getInstance();
                $notificationService->notifyBugAssignment($newStaff, $bug);
            }
        }
        
        return $result;
    }
    
    // Add methods for staff to handle bugs
    public function getBugsAssignedToMe() {
        global $db;
        
        $stmt = $db->prepare("SELECT b.*, p.name as project_name, u.name as reporter_name 
                             FROM bugs b 
                             LEFT JOIN projects p ON b.project_id = p.id 
                             LEFT JOIN users u ON b.reported_by = u.id 
                             WHERE b.assigned_to = ? 
                             ORDER BY b.created_at DESC");
        $stmt->execute([$this->id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function updateBugStatus($bugId, $status, $comment = null) {
        $bug = new Bug($bugId);
        
        if (!$bug->getId()) {
            return false;
        }
        
        // Check if the bug is assigned to this staff
        if ($bug->getAssignedTo() != $this->id) {
            return false;
        }
        
        $oldStatus = $bug->getStatus();
        $bug->setStatus($status);
        $result = $bug->save();
        
        if ($result && $comment) {
            $bug->addComment($this->id, $comment);
        }
        
        return $result;
    }

    private function loadSpecializationAndSkills() {
        global $db;

        $stmt = $db->prepare("SELECT specialization, skills FROM staff_details WHERE user_id = ?");
        $stmt->execute([$this->id]);
        $details = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($details) {
            $this->specialization = $details['specialization'];
            $this->skills = $details['skills'];
        } else {
            $this->specialization = null;
            $this->skills = null;
        }
    }

    public function getSpecialization() {
        return $this->specialization;
    }

    public function getSkills() {
        return $this->skills;
    }

    public function setSpecialization($specialization) {
        $this->specialization = $specialization;
    }

    public function setSkills($skills) {
        $this->skills = $skills;
    }
}
