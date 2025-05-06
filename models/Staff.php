<?php
require_once 'User.php';
require_once 'services/NotificationService.php';

class Staff extends User implements Observer {
    public function __construct($id = null, $name = null, $email = null, $password = null) {
        parent::__construct($id, $name, $email, $password);
        $this->role = 'staff';
    }

    public function login($email, $password) {
        global $db;
        
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
            
            return true;
        }
        
        return false;
    }

    public function logout() {
        // Clear session data
        return true;
    }

    public function viewAssignedBugs() {
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

    public function viewBugDetails($bugId) {
        $bug = new Bug($bugId);
        
        if (!$bug->getId()) {
            return null;
        }
        
        $details = $bug->getDetails();
        
        // Check if staff has permission to view this bug
        if ($details['assigned_to'] != $this->id && $details['status'] !== 'open') {
            return null;
        }
        
        $comments = $bug->getComments();
        $history = $bug->getHistory();
        
        return [
            'details' => $details,
            'comments' => $comments,
            'history' => $history
        ];
    }

    public function updateBugStatus($bugId, $status, $comment = null) {
        $bug = new Bug($bugId);
        
        if (!$bug->getId()) {
            return false;
        }
        
        $details = $bug->getDetails();
        
        // Check if staff has permission to update this bug
        if ($details['assigned_to'] != $this->id) {
            return false;
        }
        
        global $db;
        
        $db->beginTransaction();
        
        try {
            // Update bug status
            $bug->setStatus($status);
            
            // Add comment if provided
            if ($comment) {
                $bug->addComment($this->id, $comment);
            }
            
            // Log activity
            $system = BugTrackingSystem::getInstance();
            $system->logActivity($this->id, 'update_bug_status', "Updated bug #{$bug->getTicketNumber()} status to {$status}");
            
            // Save bug (this will trigger notifications to observers)
            $bug->save();
            
            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            return false;
        }
    }

    public function assignBugToOtherStaff($bugId, $staffId) {
        $bug = new Bug($bugId);
        
        if (!$bug->getId()) {
            return false;
        }
        
        $details = $bug->getDetails();
        
        // Check if staff has permission to reassign this bug
        if ($details['assigned_to'] != $this->id) {
            return false;
        }
        
        $bug->setAssignedTo($staffId);
        $bug->setStatus('assigned');
        
        // Log activity
        $system = BugTrackingSystem::getInstance();
        $system->logActivity($this->id, 'reassign_bug', "Reassigned bug #{$bug->getTicketNumber()} to another staff member");
        
        // Get staff details for notification
        global $db;
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND role = 'staff'");
        $stmt->execute([$staffId]);
        $staffData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($staffData) {
            $newStaff = new Staff($staffData['id'], $staffData['name'], $staffData['email'], $staffData['password']);
            
            // Send notification
            $notificationService = NotificationService::getInstance();
            $notificationService->notifyBugAssignment($newStaff, $bug);
        }
        
        return $bug->save();
    }

    public function addComment($bugId, $content) {
        $bug = new Bug($bugId);
        
        if (!$bug->getId()) {
            return false;
        }
        
        $details = $bug->getDetails();
        
        // Check if staff has permission to comment on this bug
        if ($details['assigned_to'] != $this->id && $details['status'] !== 'open') {
            return false;
        }
        
        $result = $bug->addComment($this->id, $content);
        
        if ($result) {
            // Log activity
            $system = BugTrackingSystem::getInstance();
            $system->logActivity($this->id, 'add_comment', "Added comment to bug #{$bug->getTicketNumber()}");
            
            return true;
        }
        
        return false;
    }

    public function editBug($bugId, $details) {
        $bug = new Bug($bugId);
        
        if (!$bug->getId()) {
            return false;
        }
        
        $bugDetails = $bug->getDetails();
        
        // Check if staff has permission to edit this bug
        if ($bugDetails['assigned_to'] != $this->id) {
            return false;
        }
        
        if (isset($details['title'])) {
            $bug->setTitle($details['title']);
        }
        
        if (isset($details['description'])) {
            $bug->setDescription($details['description']);
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
        $system->logActivity($this->id, 'edit_bug', "Edited bug #{$bug->getTicketNumber()}");
        
        return $bug->save();
    }
    
    public function update($subject, $status) {
        // Use notification service to handle both in-app and email notifications
        $notificationService = NotificationService::getInstance();
        return $notificationService->notifyBugStatusChange($this, $subject, $status);
    }

    public function getNotifications() {
        $notificationService = NotificationService::getInstance();
        return $notificationService->getUserNotifications($this->id);
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
            // Delete notifications
            $notificationService = NotificationService::getInstance();
            $notificationService->deleteAllNotifications($this->id);
            
            // Delete user
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            return $stmt->execute([$this->id]);
        }
        
        return false;
    }
}
