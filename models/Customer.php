<?php
require_once 'User.php';
require_once 'Observable.php';
require_once 'services/NotificationService.php';

class Customer extends User implements Observer {
    public function __construct($id = null, $name = null, $email = null, $password = null) {
        parent::__construct($id, $name, $email, $password);
        $this->role = 'customer';
    }

    public function login($email, $password) {
        global $db;
        
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND role = 'customer'");
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

    public function register() {
        global $db;
        
        // Check if email already exists
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$this->email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            return false; // Email already exists
        }
        
        // Hash the password
        $this->password = password_hash($this->password, PASSWORD_DEFAULT);
        
        // Insert new customer
        $stmt = $db->prepare("INSERT INTO users (name, email, password, role, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $result = $stmt->execute([$this->name, $this->email, $this->password, $this->role, $this->status, $this->created_at, $this->updated_at]);
        
        if ($result) {
            $this->id = $db->lastInsertId();
            
            // Send welcome email
            $notificationService = NotificationService::getInstance();
            $notificationService->notifyNewUserCreation($this, $this->password);
        }
        
        return $result;
    }

    public function reportBug($title, $description, $project_id, $severity, $priority = 'medium', $steps = null, $expected_result = null, $actual_result = null) {
        $bug = new Bug();
        $bug->setTitle($title);
        $bug->setDescription($description);
        $bug->setProjectId($project_id);
        $bug->setReportedBy($this->id);
        $bug->setSeverity($severity);
        $bug->setPriority($priority);
        $bug->setSteps($steps);
        $bug->setExpectedResult($expected_result);
        $bug->setActualResult($actual_result);
        $bug->setStatus('open');
        
        if ($bug->save()) {
            // Automatically attach the customer as an observer
            $bug->attach($this);
            
            // Create notification for customer
            $notificationService = NotificationService::getInstance();
            $message = "You have reported a new bug: {$title}";
            $link = "index.php?controller=bug&action=view&id=" . $bug->getId();
            $notificationService->createInAppNotification($this->id, $message, 'info', $link);
            
            return $bug->getId();
        }
        
        return false;
    }

    public function attachScreenshot($bugId, $file) {
        global $db;
        
        // Check if the bug belongs to this customer
        $stmt = $db->prepare("SELECT * FROM bugs WHERE id = ? AND reported_by = ?");
        $stmt->execute([$bugId, $this->id]);
        $bugData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$bugData) {
            return false;
        }
        
        // Process and save the screenshot
        $filename = time() . '_' . $file['name'];
        $target_dir = "uploads/screenshots/";
        
        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $target_file = $target_dir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            $bug = new Bug($bugId);
            return $bug->attachScreenshot($filename);
        }
        
        return false;
    }

    public function monitorBugStatus($ticketNumber) {
        $bug = Bug::findByTicketNumber($ticketNumber);
        
        if (!$bug) {
            return null;
        }
        
        return $bug->getDetails();
    }

    public function addComment($bugId, $content) {
        $bug = new Bug($bugId);
        
        if (!$bug->getId()) {
            return false;
        }
        
        return $bug->addComment($this->id, $content);
    }

    public function contactAdmin($subject, $message) {
        global $db;
        
        // Insert message into database
        $stmt = $db->prepare("INSERT INTO messages (sender_id, receiver_role, subject, content, created_at) VALUES (?, 'administrator', ?, ?, ?)");
        $result = $stmt->execute([$this->id, $subject, $message, date('Y-m-d H:i:s')]);
        
        if ($result) {
            // Send email notification to all administrators
            $stmt = $db->prepare("SELECT * FROM users WHERE role = 'administrator' AND status = 'active'");
            $stmt->execute();
            $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $notificationService = NotificationService::getInstance();
            
            foreach ($admins as $adminData) {
                $admin = new Administrator($adminData['id'], $adminData['name'], $adminData['email'], $adminData['password']);
                $notificationService->notifyAdminContact($admin, $this, $subject, $message);
            }
            
            return true;
        }
        
        return false;
    }

    public function update($subject, $status) {
        // Use notification service to handle both in-app and email notifications
        $notificationService = NotificationService::getInstance();
        return $notificationService->notifyBugStatusChange($this, $subject, $status);
    }

    public function getReportedBugs() {
        global $db;
        
        $stmt = $db->prepare("SELECT b.*, p.name as project_name, u.name as assignee_name 
                             FROM bugs b 
                             LEFT JOIN projects p ON b.project_id = p.id 
                             LEFT JOIN users u ON b.assigned_to = u.id 
                             WHERE b.reported_by = ? 
                             ORDER BY b.created_at DESC");
        $stmt->execute([$this->id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getNotifications() {
        $notificationService = NotificationService::getInstance();
        return $notificationService->getUserNotifications($this->id);
    }

    public function save() {
        global $db;
        
        if ($this->id) {
            // Update existing customer
            $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, password = ?, status = ?, updated_at = ? WHERE id = ?");
            return $stmt->execute([$this->name, $this->email, $this->password, $this->status, date('Y-m-d H:i:s'), $this->id]);
        } else {
            // Insert new customer
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
            
            // Delete messages
            $stmt = $db->prepare("DELETE FROM messages WHERE sender_id = ?");
            $stmt->execute([$this->id]);
            
            // Delete bug observers
            $stmt = $db->prepare("DELETE FROM bug_observers WHERE user_id = ?");
            $stmt->execute([$this->id]);
            
            // Delete user
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            return $stmt->execute([$this->id]);
        }
        
        return false;
    }
}
