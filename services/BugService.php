<?php
require_once 'models/Bug.php';
require_once 'services/NotificationService.php';

class BugService {
    private static $instance = null;
    
    private function __construct() {
        // Private constructor to prevent direct creation
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new BugService();
        }
        return self::$instance;
    }
    
    public function createBug($title, $description, $project_id, $reported_by, $severity, $priority = 'medium', $steps = null, $expected_result = null, $actual_result = null) {
        $bug = new Bug();
        $bug->setTitle($title);
        $bug->setDescription($description);
        $bug->setProjectId($project_id);
        $bug->setReportedBy($reported_by);
        $bug->setSeverity($severity);
        $bug->setPriority($priority);
        $bug->setSteps($steps);
        $bug->setExpectedResult($expected_result);
        $bug->setActualResult($actual_result);
        $bug->setStatus('open');
        
        if ($bug->save()) {
            // Log activity
            $system = BugTrackingSystem::getInstance();
            $system->logActivity($reported_by, 'report_bug', "Reported bug: {$title}");
            
            return $bug->getId();
        }
        
        return false;
    }
    
    public function getBugById($id) {
        return new Bug($id);
    }
    
    public function getBugByTicketNumber($ticketNumber) {
        return Bug::findByTicketNumber($ticketNumber);
    }
    
    public function updateBugStatus($bugId, $status, $userId, $comment = null) {
        $bug = new Bug($bugId);
        
        if (!$bug->getId()) {
            return false;
        }
        
        $oldStatus = $bug->getStatus();
        $bug->setStatus($status);
        
        if ($bug->save()) {
            // Add comment if provided
            if (!empty($comment)) {
                $bug->addComment($userId, $comment);
            }
            
            // Log activity
            $system = BugTrackingSystem::getInstance();
            $system->logActivity($userId, 'update_bug_status', "Updated bug status to {$status}: {$bug->getTitle()}");
            
            return true;
        }
        
        return false;
    }
    
    public function assignBug($bugId, $staffId, $userId) {
        $bug = new Bug($bugId);
        
        if (!$bug->getId()) {
            return false;
        }
        
        $bug->setAssignedTo($staffId);
        $bug->setStatus('assigned');
        
        if ($bug->save()) {
            // Log activity
            $system = BugTrackingSystem::getInstance();
            $system->logActivity($userId, 'assign_bug', "Assigned bug #{$bug->getTicketNumber()} to staff ID: {$staffId}");
            
            return true;
        }
        
        return false;
    }
    
    public function addComment($bugId, $userId, $content) {
        $bug = new Bug($bugId);
        
        if (!$bug->getId()) {
            return false;
        }
        
        if ($bug->addComment($userId, $content)) {
            // Log activity
            $system = BugTrackingSystem::getInstance();
            $system->logActivity($userId, 'add_comment', "Added comment to bug: {$bug->getTitle()}");
            
            return true;
        }
        
        return false;
    }
    
    public function attachScreenshot($bugId, $file, $userId) {
        $bug = new Bug($bugId);
        
        if (!$bug->getId()) {
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
            return $bug->attachScreenshot($filename);
        }
        
        return false;
    }
    
    public function deleteBug($bugId, $userId) {
        $bug = new Bug($bugId);
        
        if (!$bug->getId()) {
            return false;
        }
        
        $bugDetails = $bug->getDetails();
        
        if ($bug->delete()) {
            // Log activity
            $system = BugTrackingSystem::getInstance();
            $system->logActivity($userId, 'delete_bug', "Deleted bug: {$bugDetails['title']}");
            
            return true;
        }
        
        return false;
    }
    
    public function getBugsByFilters($filters = [], $userId = null, $userRole = null) {
        global $db;
        
        $where = "1=1";
        $params = [];
        
        if (isset($filters['status']) && !empty($filters['status'])) {
            $where .= " AND b.status = ?";
            $params[] = $filters['status'];
        }
        
        if (isset($filters['severity']) && !empty($filters['severity'])) {
            $where .= " AND b.severity = ?";
            $params[] = $filters['severity'];
        }
        
        if (isset($filters['project_id']) && !empty($filters['project_id'])) {
            $where .= " AND b.project_id = ?";
            $params[] = $filters['project_id'];
        }
        
        if (isset($filters['search']) && !empty($filters['search'])) {
            $where .= " AND (b.title LIKE ? OR b.description LIKE ? OR b.ticket_number LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        // Different queries based on user role
        if ($userRole === 'customer' && $userId) {
            $where .= " AND b.reported_by = ?";
            $params[] = $userId;
        } elseif ($userRole === 'staff' && $userId) {
            $where .= " AND (b.assigned_to = ? OR b.status = 'open')";
            $params[] = $userId;
        }
        
        $stmt = $db->prepare("SELECT b.*, p.name as project_name, u1.name as reporter_name, u2.name as assignee_name 
                             FROM bugs b 
                             LEFT JOIN projects p ON b.project_id = p.id 
                             LEFT JOIN users u1 ON b.reported_by = u1.id 
                             LEFT JOIN users u2 ON b.assigned_to = u2.id 
                             WHERE {$where} 
                             ORDER BY b.created_at DESC");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getBugStatistics() {
        global $db;
        
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
}
