<?php
require_once 'AssignmentService.php';
require_once 'services/NotificationService.php';
require_once 'models/Bug.php';
require_once 'models/Administrator.php';
require_once 'models/Staff.php';
require_once 'models/Customer.php';

class BugTrackingSystem {
    private static $instance = null;
    private $assignmentService;
    
    private function __construct() {
        $this->assignmentService = new AssignmentService();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new BugTrackingSystem();
        }
        
        return self::$instance;
    }
    
    // Prevent cloning
    private function __clone() {}
    
    // Prevent unserialization
    public function __wakeup() {}
    public function trackBug($ticketNumber) {
        // findByTicketNumber already returns either a Bug instance or null
        return Bug::findByTicketNumber($ticketNumber);
    }
    public function reportBug($title, $description, $projectId, $reportedBy, $severity, $priority = 'medium', $steps = null, $expectedResult = null, $actualResult = null) {
        $bug = new Bug();
        $bug->setTitle($title);
        $bug->setDescription($description);
        $bug->setProjectId($projectId);
        $bug->setReportedBy($reportedBy);
        $bug->setSeverity($severity);
        $bug->setPriority($priority);
        $bug->setSteps($steps);
        $bug->setExpectedResult($expectedResult);
        $bug->setActualResult($actualResult);
        $bug->setStatus('open');
        
        if ($bug->save()) {
            // Log activity
            $this->logActivity($reportedBy, 'report_bug', "Reported bug: {$title}");
            
            // Create notification for administrators
            $notificationService = NotificationService::getInstance();
            
            // Get all administrators
            global $db;
            if (!$db) {
                require_once 'config/database.php';
            }
            
            $stmt = $db->prepare("SELECT * FROM users WHERE role = 'administrator' AND status = 'active'");
            $stmt->execute();
            $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($admins as $adminData) {
                $message = "New bug reported: {$title}";
                $link = "index.php?controller=bug&action=view&id=" . $bug->getId();
                $notificationService->createInAppNotification($adminData['id'], $message, 'info', $link);
            }
            
            return $bug;
        }
        
        return null;
    }
    
    public function assignBug($bugId, $staffId = null) {
        $bug = new Bug($bugId);
        
        if (!$bug->getId()) {
            return false;
        }
        
        if ($staffId) {
            // Manual assignment
            $bug->setAssignedTo($staffId);
            $bug->setStatus('assigned');
            
            // Log activity
            $this->logActivity($_SESSION['user_id'] ?? 0, 'assign_bug', "Assigned bug #{$bug->getTicketNumber()} to staff");
            
            // Get staff details for notification
            global $db;
            if (!$db) {
                require_once 'config/database.php';
            }
            
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
        } else {
            // Automatic assignment using strategy
            $availableStaff = AssignmentService::getAvailableStaff();
            
            if (empty($availableStaff)) {
                return false;
            }
            
            $staff = $this->assignmentService->assignBugToStaff($bug, $availableStaff);
            
            if ($staff) {
                // Log activity
                $this->logActivity($_SESSION['user_id'] ?? 0, 'assign_bug', "Automatically assigned bug #{$bug->getTicketNumber()} to {$staff->getName()}");
                
                return true;
            }
            
            return false;
        }
    }
    
    public function setAssignmentStrategy(AssignmentStrategy $strategy) {
        $this->assignmentService->setStrategy($strategy);
    }
    
    public function getBugStatistics() {
        global $db;
        if (!$db) {
            require_once 'config/database.php';
        }
        
        $stmt = $db->prepare("SELECT 
                             COUNT(*) as total,
                             SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open,
                             SUM(CASE WHEN status = 'assigned' THEN 1 ELSE 0 END) as assigned,
                             SUM(CASE WHEN status = 'in-progress' THEN 1 ELSE 0 END) as in_progress,
                             SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
                             SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed,
                             SUM(CASE WHEN severity = 'low' THEN 1 ELSE 0 END) as low,
                             SUM(CASE WHEN severity = 'medium' THEN 1 ELSE 0 END) as medium,
                             SUM(CASE WHEN severity = 'high' THEN 1 ELSE 0 END) as high,
                             SUM(CASE WHEN severity = 'critical' THEN 1 ELSE 0 END) as critical
                             FROM bugs");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getProjectStatistics() {
        global $db;
        if (!$db) {
            require_once 'config/database.php';
        }
        
        $stmt = $db->prepare("SELECT 
                             COUNT(*) as total,
                             SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                             SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive
                             FROM projects");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getUserStatistics() {
        global $db;
        if (!$db) {
            require_once 'config/database.php';
        }
        
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
    
    public function getRecentActivity($limit = 10) {
        global $db;
        if (!$db) {
            require_once 'config/database.php';
        }
        
        $stmt = $db->prepare("SELECT a.*, u.name as user_name 
                             FROM activity_log a 
                             LEFT JOIN users u ON a.user_id = u.id 
                             ORDER BY a.created_at DESC 
                             LIMIT ?");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function logActivity($userId, $action, $details) {
        global $db;
        if (!$db) {
            require_once 'config/database.php';
        }
        
        // Check if activity_log table has a NOT NULL constraint on user_id
        try {
            // First, check if the table structure allows NULL for user_id
            $stmt = $db->prepare("SHOW COLUMNS FROM activity_log WHERE Field = 'user_id'");
            $stmt->execute();
            $column = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // If NULL is not allowed and userId is null, use a system user ID (0) instead
            if ($column && strpos($column['Null'], 'NO') !== false && $userId === null) {
                $userId = 0; // Use 0 as a system user ID
            }
            
            // Insert the activity log
            $stmt = $db->prepare("INSERT INTO activity_log (user_id, action, details, created_at) VALUES (?, ?, ?, ?)");
            return $stmt->execute([$userId, $action, $details, date('Y-m-d H:i:s')]);
        } catch (PDOException $e) {
            // If there's an error, try an alternative approach
            try {
                // Create a modified activity_log table if it doesn't exist
                $db->exec("CREATE TABLE IF NOT EXISTS activity_log_system (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NULL,
                    action VARCHAR(100) NOT NULL,
                    details TEXT,
                    created_at DATETIME NOT NULL
                )");
                
                // Insert into the modified table
                $stmt = $db->prepare("INSERT INTO activity_log_system (user_id, action, details, created_at) VALUES (?, ?, ?, ?)");
                return $stmt->execute([$userId, $action, $details, date('Y-m-d H:i:s')]);
            } catch (PDOException $e2) {
                // If all else fails, just log to a file
                $logMessage = date('Y-m-d H:i:s') . " - User: " . ($userId ?? 'System') . " - Action: $action - Details: $details\n";
                file_put_contents('activity_log.txt', $logMessage, FILE_APPEND);
                return true;
            }
        }
    }
    
    public function generateReports($type, $filters = []) {
        global $db;
        if (!$db) {
            require_once 'config/database.php';
        }
        
        switch ($type) {
            case 'bugs':
                $sql = "SELECT b.*, p.name as project_name, u1.name as reporter_name, u2.name as assignee_name 
                       FROM bugs b 
                       LEFT JOIN projects p ON b.project_id = p.id 
                       LEFT JOIN users u1 ON b.reported_by = u1.id 
                       LEFT JOIN users u2 ON b.assigned_to = u2.id WHERE 1=1";
                
                $params = [];
                
                if (!empty($filters['status'])) {
                    $sql .= " AND b.status = ?";
                    $params[] = $filters['status'];
                }
                
                if (!empty($filters['severity'])) {
                    $sql .= " AND b.severity = ?";
                    $params[] = $filters['severity'];
                }
                
                if (!empty($filters['project_id'])) {
                    $sql .= " AND b.project_id = ?";
                    $params[] = $filters['project_id'];
                }
                
                if (!empty($filters['date_from'])) {
                    $sql .= " AND b.created_at >= ?";
                    $params[] = $filters['date_from'];
                }
                
                if (!empty($filters['date_to'])) {
                    $sql .= " AND b.created_at <= ?";
                    $params[] = $filters['date_to'];
                }
                
                $sql .= " ORDER BY b.created_at DESC";
                
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
                
            case 'users':
                $sql = "SELECT * FROM users WHERE 1=1";
                
                $params = [];
                
                if (!empty($filters['role'])) {
                    $sql .= " AND role = ?";
                    $params[] = $filters['role'];
                }
                
                if (!empty($filters['status'])) {
                    $sql .= " AND status = ?";
                    $params[] = $filters['status'];
                }
                
                $sql .= " ORDER BY created_at DESC";
                
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
                
            case 'projects':
                $sql = "SELECT p.*, COUNT(b.id) as bug_count 
                       FROM projects p 
                       LEFT JOIN bugs b ON p.id = b.project_id 
                       WHERE 1=1";
                
                $params = [];
                
                if (!empty($filters['status'])) {
                    $sql .= " AND p.status = ?";
                    $params[] = $filters['status'];
                }
                
                $sql .= " GROUP BY p.id ORDER BY p.created_at DESC";
                
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
                
            default:
                return [];
        }
    }
    
    public function manageUserStatus($userId, $status) {
        global $db;
        if (!$db) {
            require_once 'config/database.php';
        }
        
        $validStatuses = ['active', 'inactive', 'banned'];
        
        if (!in_array($status, $validStatuses)) {
            return false;
        }
        
        $stmt = $db->prepare("UPDATE users SET status = ?, updated_at = ? WHERE id = ?");
        $result = $stmt->execute([$status, date('Y-m-d H:i:s'), $userId]);
        
        if ($result) {
            // Log activity
            $this->logActivity($_SESSION['user_id'] ?? 0, 'update_user_status', "Updated user status to {$status}");
            
            // Get user details for notification
            $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($userData) {
                // Create notification
                $notificationService = NotificationService::getInstance();
                $message = "Your account status has been updated to " . ucfirst($status);
                $link = "index.php?controller=user&action=profile";
                $notificationService->createInAppNotification($userId, $message, 'warning', $link);
                
                // Send email notification
                switch ($userData['role']) {
                    case 'administrator':
                        $user = new Administrator($userData['id'], $userData['name'], $userData['email'], $userData['password']);
                        break;
                    case 'staff':
                        $user = new Staff($userData['id'], $userData['name'], $userData['email'], $userData['password']);
                        break;
                    case 'customer':
                        $user = new Customer($userData['id'], $userData['name'], $userData['email'], $userData['password']);
                        break;
                }
                
                $notificationService->sendEmail(
                    $userData['email'],
                    $userData['name'],
                    "Account Status Update",
                    "Your account status has been updated to " . ucfirst($status) . ". Please contact the administrator if you have any questions.",
                    "Your account status has been updated to " . ucfirst($status) . ". Please contact the administrator if you have any questions."
                );
            }
            
            return true;
        }
        
        return false;
    }
    
    public function resetFilters() {
        if (isset($_SESSION['filters'])) {
            unset($_SESSION['filters']);
        }
        
        return true;
    }
}
