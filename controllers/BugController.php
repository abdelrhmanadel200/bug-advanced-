<?php
require_once 'models/Bug.php';
require_once 'models/Project.php';
require_once 'models/Comment.php';
require_once 'models/BugTrackingSystem.php';
require_once 'models/Staff.php';
require_once 'models/Customer.php';

class BugController {
    private $system;
    
    public function __construct() {
        $this->system = BugTrackingSystem::getInstance();
    }
    
    public function list() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }
        
        global $db;
        
        // Apply filters if provided
        $where = "1=1";
        $params = [];
        
        if (isset($_GET['status']) && !empty($_GET['status'])) {
            $where .= " AND b.status = ?";
            $params[] = $_GET['status'];
        }
        
        if (isset($_GET['severity']) && !empty($_GET['severity'])) {
            $where .= " AND b.severity = ?";
            $params[] = $_GET['severity'];
        }
        
        if (isset($_GET['project_id']) && !empty($_GET['project_id'])) {
            $where .= " AND b.project_id = ?";
            $params[] = $_GET['project_id'];
        }
        
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $where .= " AND (b.title LIKE ? OR b.description LIKE ? OR b.ticket_number LIKE ?)";
            $searchTerm = '%' . $_GET['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        // Different queries based on user role
        if ($_SESSION['user_role'] === 'customer') {
            $where .= " AND b.reported_by = ?";
            $params[] = $_SESSION['user_id'];
        } elseif ($_SESSION['user_role'] === 'staff') {
            $where .= " AND (b.assigned_to = ? OR b.status = 'open')";
            $params[] = $_SESSION['user_id'];
        }
        
        $stmt = $db->prepare("SELECT b.*, p.name as project_name, u1.name as reporter_name, u2.name as assignee_name 
                             FROM bugs b 
                             LEFT JOIN projects p ON b.project_id = p.id 
                             LEFT JOIN users u1 ON b.reported_by = u1.id 
                             LEFT JOIN users u2 ON b.assigned_to = u2.id 
                             WHERE {$where} 
                             ORDER BY b.created_at DESC");
        $stmt->execute($params);
        $bugs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get projects for filter dropdown
        $projects = Project::getActiveProjects();
        
        // Save filters in session for reset functionality
        $_SESSION['filters'] = [
            'status' => $_GET['status'] ?? '',
            'severity' => $_GET['severity'] ?? '',
            'project_id' => $_GET['project_id'] ?? '',
            'search' => $_GET['search'] ?? ''
        ];
        
        // Display bug list
        include 'views/bugs/list.php';
    }
    
    public function resetFilters() {
        // Clear filters from session
        if (isset($_SESSION['filters'])) {
            unset($_SESSION['filters']);
        }
        
        // Redirect to bug list without filters
        header('Location: index.php?controller=bug&action=list');
        exit;
    }
    
    public function view() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }
        
        $id = $_GET['id'] ?? 0;
        
        if (!$id) {
            $_SESSION['errors'] = ['Bug not found'];
            header('Location: index.php?controller=bug&action=list');
            exit;
        }
        
        $bug = new Bug($id);
        $bugDetails = $bug->getDetails();
        
        if (!$bugDetails) {
            $_SESSION['errors'] = ['Bug not found'];
            header('Location: index.php?controller=bug&action=list');
            exit;
        }
        
        // Check if user has permission to view this bug
        if ($_SESSION['user_role'] === 'customer' && $bugDetails['reported_by'] != $_SESSION['user_id']) {
            $_SESSION['errors'] = ['You do not have permission to view this bug'];
            header('Location: index.php?controller=bug&action=list');
            exit;
        }
        
        // Get comments
        $comments = $bug->getComments();
        
        // Get bug history
        $history = $bug->getHistory();
        
        // Get staff members for assignment dropdown
        $staff_members = [];
        if ($_SESSION['user_role'] === 'administrator' || $_SESSION['user_role'] === 'staff') {
            global $db;
            $stmt = $db->prepare("SELECT id, name FROM users WHERE role = 'staff' AND status = 'active'");
            $stmt->execute();
            $staff_members = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        // Check if bug has GitHub issue
        $github_issue = null;
        if (isset($_SESSION['github_token'])) {
            global $db;
            $stmt = $db->prepare("SELECT * FROM github_issues WHERE bug_id = ?");
            $stmt->execute([$id]);
            $github_issue = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // Display bug details
        include 'views/bugs/view.php';
    }
    
    public function report() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = $_POST['title'] ?? '';
            $description = $_POST['description'] ?? '';
            $project_id = $_POST['project_id'] ?? '';
            $severity = $_POST['severity'] ?? 'medium';
            $priority = $_POST['priority'] ?? 'medium';
            $steps = $_POST['steps'] ?? '';
            $expected_result = $_POST['expected_result'] ?? '';
            $actual_result = $_POST['actual_result'] ?? '';
            
            $errors = [];
            
            if (empty($title)) {
                $errors[] = 'Title is required';
            }
            
            if (empty($description)) {
                $errors[] = 'Description is required';
            }
            
            if (empty($project_id)) {
                $errors[] = 'Project is required';
            }
            
            if (empty($severity)) {
                $errors[] = 'Severity is required';
            }
            
            if (empty($errors)) {
                $customer = new Customer($_SESSION['user_id']);
                $bugId = $customer->reportBug($title, $description, $project_id, $severity, $priority, $steps, $expected_result, $actual_result);
                
                if ($bugId) {
                    // Handle screenshot upload
                    if (isset($_FILES['screenshot']) && $_FILES['screenshot']['error'] === UPLOAD_ERR_OK) {
                        $customer->attachScreenshot($bugId, $_FILES['screenshot']);
                    }
                    
                    // Log activity
                    $this->system->logActivity($_SESSION['user_id'], 'report_bug', "Reported bug: {$title}");
                    
                    $_SESSION['success'] = 'Bug reported successfully';
                    header('Location: index.php?controller=bug&action=view&id=' . $bugId);
                    exit;
                } else {
                    $errors[] = 'Failed to report bug';
                }
            }
            
            $_SESSION['errors'] = $errors;
        }
        
        // Get active projects
        $projects = Project::getActiveProjects();
        
        // Display bug report form
        include 'views/bugs/report.php';
    }
    
    public function edit() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }
        
        // Check if user has permission to edit bugs
        if ($_SESSION['user_role'] === 'customer') {
            $_SESSION['errors'] = ['You do not have permission to edit bugs'];
            header('Location: index.php?controller=bug&action=list');
            exit;
        }
        
        $id = $_GET['id'] ?? 0;
        
        if (!$id) {
            $_SESSION['errors'] = ['Bug not found'];
            header('Location: index.php?controller=bug&action=list');
            exit;
        }
        
        $bug = new Bug($id);
        $bugDetails = $bug->getDetails();
        
        if (!$bugDetails) {
            $_SESSION['errors'] = ['Bug not found'];
            header('Location: index.php?controller=bug&action=list');
            exit;
        }
        
        // Check if staff has permission to edit this bug
        if ($_SESSION['user_role'] === 'staff' && $bugDetails['assigned_to'] != $_SESSION['user_id']) {
            $_SESSION['errors'] = ['You do not have permission to edit this bug'];
            header('Location: index.php?controller=bug&action=list');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = $_POST['title'] ?? '';
            $description = $_POST['description'] ?? '';
            $project_id = $_POST['project_id'] ?? '';
            $severity = $_POST['severity'] ?? '';
            $priority = $_POST['priority'] ?? '';
            $status = $_POST['status'] ?? '';
            $assigned_to = $_POST['assigned_to'] ?? null;
            $steps = $_POST['steps'] ?? '';
            $expected_result = $_POST['expected_result'] ?? '';
            $actual_result = $_POST['actual_result'] ?? '';
            
            $errors = [];
            
            if (empty($title)) {
                $errors[] = 'Title is required';
            }
            
            if (empty($description)) {
                $errors[] = 'Description is required';
            }
            
            if (empty($project_id)) {
                $errors[] = 'Project is required';
            }
            
            if (empty($severity)) {
                $errors[] = 'Severity is required';
            }
            
            if (empty($status)) {
                $errors[] = 'Status is required';
            }
            
            if (empty($errors)) {
                // Update bug details
                $bug->setTitle($title);
                $bug->setDescription($description);
                $bug->setProjectId($project_id);
                $bug->setSeverity($severity);
                $bug->setPriority($priority);
                $bug->setStatus($status);
                $bug->setSteps($steps);
                $bug->setExpectedResult($expected_result);
                $bug->setActualResult($actual_result);
                
                if ($assigned_to) {
                    $bug->setAssignedTo($assigned_to);
                }
                
                if ($bug->save()) {
                    // Handle screenshot upload
                    if (isset($_FILES['screenshot']) && $_FILES['screenshot']['error'] === UPLOAD_ERR_OK) {
                        $filename = time() . '_' . $_FILES['screenshot']['name'];
                        $target_dir = "uploads/screenshots/";
                        
                        // Create directory if it doesn't exist
                        if (!file_exists($target_dir)) {
                            mkdir($target_dir, 0777, true);
                        }
                        
                        $target_file = $target_dir . $filename;
                        
                        if (move_uploaded_file($_FILES['screenshot']['tmp_name'], $target_file)) {
                            $bug->attachScreenshot($filename);
                        }
                    }
                    
                    // Add comment if provided
                    if (!empty($_POST['comment'])) {
                        $bug->addComment($_SESSION['user_id'], $_POST['comment']);
                    }
                    
                    // Log activity
                    $this->system->logActivity($_SESSION['user_id'], 'edit_bug', "Edited bug: {$title}");
                    
                    $_SESSION['success'] = 'Bug updated successfully';
                    header('Location: index.php?controller=bug&action=view&id=' . $id);
                    exit;
                } else {
                    $errors[] = 'Failed to update bug';
                }
            }
            
            $_SESSION['errors'] = $errors;
        }
        
        // Get active projects
        $projects = Project::getActiveProjects();
        
        // Get staff members for assignment dropdown
        $staff_members = [];
        global $db;
        $stmt = $db->prepare("SELECT id, name FROM users WHERE role = 'staff' AND status = 'active'");
        $stmt->execute();
        $staff_members = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Display bug edit form
        include 'views/bugs/edit.php';
    }
    
    public function delete() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }
        
        // Check if user has permission to delete bugs
        if ($_SESSION['user_role'] !== 'administrator') {
            $_SESSION['errors'] = ['You do not have permission to delete bugs'];
            header('Location: index.php?controller=bug&action=list');
            exit;
        }
        
        $id = $_GET['id'] ?? 0;
        
        if (!$id) {
            $_SESSION['errors'] = ['Bug not found'];
            header('Location: index.php?controller=bug&action=list');
            exit;
        }
        
        $bug = new Bug($id);
        $bugDetails = $bug->getDetails();
        
        if (!$bugDetails) {
            $_SESSION['errors'] = ['Bug not found'];
            header('Location: index.php?controller=bug&action=list');
            exit;
        }
        
        if ($bug->delete()) {
            // Log activity
            $this->system->logActivity($_SESSION['user_id'], 'delete_bug', "Deleted bug: {$bugDetails['title']}");
            
            $_SESSION['success'] = 'Bug deleted successfully';
        } else {
            $_SESSION['errors'] = ['Failed to delete bug'];
        }
        
        header('Location: index.php?controller=bug&action=list');
        exit;
    }
    
    public function updateStatus() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }
        
        // Check if user has permission to update bug status
        if ($_SESSION['user_role'] === 'customer') {
            $_SESSION['errors'] = ['You do not have permission to update bug status'];
            header('Location: index.php?controller=bug&action=list');
            exit;
        }
        
        $id = $_GET['id'] ?? 0;
        
        if (!$id) {
            $_SESSION['errors'] = ['Bug not found'];
            header('Location: index.php?controller=bug&action=list');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $status = $_POST['status'] ?? '';
            $assigned_to = $_POST['assigned_to'] ?? null;
            $comment = $_POST['status_comment'] ?? '';
            
            if (empty($status)) {
                $_SESSION['errors'] = ['Status is required'];
                header('Location: index.php?controller=bug&action=view&id=' . $id);
                exit;
            }
            
            $bug = new Bug($id);
            $bugDetails = $bug->getDetails();
            
            if (!$bugDetails) {
                $_SESSION['errors'] = ['Bug not found'];
                header('Location: index.php?controller=bug&action=list');
                exit;
            }
            
            // Check if staff has permission to update this bug
            if ($_SESSION['user_role'] === 'staff' && $bugDetails['assigned_to'] != $_SESSION['user_id']) {
                $_SESSION['errors'] = ['You do not have permission to update this bug'];
                header('Location: index.php?controller=bug&action=list');
                exit;
            }
            
            // Update bug status
            $bug->setStatus($status);
            
            // Update assigned_to if provided
            if ($assigned_to) {
                $bug->setAssignedTo($assigned_to);
            }
            
            // Add comment if provided
            if (!empty($comment)) {
                $bug->addComment($_SESSION['user_id'], $comment);
            }
            
            // Log activity
            $this->system->logActivity($_SESSION['user_id'], 'update_bug_status', "Updated bug status to {$status}: {$bugDetails['title']}");
            
            $_SESSION['success'] = 'Bug status updated successfully';
        }
        
        header('Location: index.php?controller=bug&action=view&id=' . $id);
        exit;
    }
    
    public function addComment() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }
        
        $id = $_GET['id'] ?? 0;
        
        if (!$id) {
            $_SESSION['errors'] = ['Bug not found'];
            header('Location: index.php?controller=bug&action=list');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $comment = $_POST['comment'] ?? '';
            
            if (empty($comment)) {
                $_SESSION['errors'] = ['Comment is required'];
                header('Location: index.php?controller=bug&action=view&id=' . $id);
                exit;
            }
            
            $bug = new Bug($id);
            $bugDetails = $bug->getDetails();
            
            if (!$bugDetails) {
                $_SESSION['errors'] = ['Bug not found'];
                header('Location: index.php?controller=bug&action=list');
                exit;
            }
            
            // Check if user has permission to comment on this bug
            if ($_SESSION['user_role'] === 'customer' && $bugDetails['reported_by'] != $_SESSION['user_id']) {
                $_SESSION['errors'] = ['You do not have permission to comment on this bug'];
                header('Location: index.php?controller=bug&action=list');
                exit;
            }
            
            if ($bug->addComment($_SESSION['user_id'], $comment)) {
                // Log activity
                $this->system->logActivity($_SESSION['user_id'], 'add_comment', "Added comment to bug: {$bugDetails['title']}");
                
                $_SESSION['success'] = 'Comment added successfully';
            } else {
                $_SESSION['errors'] = ['Failed to add comment'];
            }
        }
        
        header('Location: index.php?controller=bug&action=view&id=' . $id);
        exit;
    }
    
    public function track() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }
        
        $bug = null;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ticket_number = $_POST['ticket_number'] ?? '';
            
            if (empty($ticket_number)) {
                $_SESSION['errors'] = ['Ticket number is required'];
            } else {
                $bug = $this->system->trackBug($ticket_number);
                
                if (!$bug) {
                    $_SESSION['errors'] = ['Bug not found'];
                } else {
                    // Log activity
                    $this->system->logActivity($_SESSION['user_id'], 'track_bug', "Tracked bug #{$ticket_number}");
                }
            }
        }
        
        // Display bug tracking form
        include 'views/bugs/track.php';
    }
    
    public function assignToStaff() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }
        
        // Check if user has permission to assign bugs
        if ($_SESSION['user_role'] !== 'administrator' && $_SESSION['user_role'] !== 'staff') {
            $_SESSION['errors'] = ['You do not have permission to assign bugs'];
            header('Location: index.php?controller=bug&action=list');
            exit;
        }
        
        $id = $_GET['id'] ?? 0;
        
        if (!$id) {
            $_SESSION['errors'] = ['Bug not found'];
            header('Location: index.php?controller=bug&action=list');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $staff_id = $_POST['staff_id'] ?? '';
            
            if (empty($staff_id)) {
                $_SESSION['errors'] = ['Staff member is required'];
                header('Location: index.php?controller=bug&action=view&id=' . $id);
                exit;
            }
            
            $bug = new Bug($id);
            $bugDetails = $bug->getDetails();
            
            if (!$bugDetails) {
                $_SESSION['errors'] = ['Bug not found'];
                header('Location: index.php?controller=bug&action=list');
                exit;
            }
            
            // Check if staff has permission to reassign this bug
            if ($_SESSION['user_role'] === 'staff' && $bugDetails['assigned_to'] != $_SESSION['user_id']) {
                $_SESSION['errors'] = ['You do not have permission to reassign this bug'];
                header('Location: index.php?controller=bug&action=list');
                exit;
            }
            
            if ($_SESSION['user_role'] === 'administrator') {
                $admin = new Administrator($_SESSION['user_id']);
                if ($admin->assignBugToStaff($id, $staff_id)) {
                    $_SESSION['success'] = 'Bug assigned successfully';
                } else {
                    $_SESSION['errors'] = ['Failed to assign bug'];
                }
            } else {
                $staff = new Staff($_SESSION['user_id']);
                if ($staff->assignBugToOtherStaff($id, $staff_id)) {
                    $_SESSION['success'] = 'Bug reassigned successfully';
                } else {
                    $_SESSION['errors'] = ['Failed to reassign bug'];
                }
            }
        }
        
        header('Location: index.php?controller=bug&action=view&id=' . $id);
        exit;
    }
    
    public function viewCaseFlow() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }
        
        $id = $_GET['id'] ?? 0;
        
        if (!$id) {
            $_SESSION['errors'] = ['Bug not found'];
            header('Location: index.php?controller=bug&action=list');
            exit;
        }
        
        $bug = new Bug($id);
        $bugDetails = $bug->getDetails();
        
        if (!$bugDetails) {
            $_SESSION['errors'] = ['Bug not found'];
            header('Location: index.php?controller=bug&action=list');
            exit;
        }
        
        // Check if user has permission to view this bug
        if ($_SESSION['user_role'] === 'customer' && $bugDetails['reported_by'] != $_SESSION['user_id']) {
            $_SESSION['errors'] = ['You do not have permission to view this bug'];
            header('Location: index.php?controller=bug&action=list');
            exit;
        }
        
        // Get bug history
        $history = $bug->getHistory();
        
        // Display bug case flow
        include 'views/bugs/case-flow.php';
    }
    
    public function statistics() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }
        
        // Get bug statistics
        $bugStats = $this->system->getBugStatistics();
        
        // Get project statistics
        $projectStats = $this->system->getProjectStatistics();
        
        // Display bug statistics
        include 'views/bugs/statistics.php';
    }
}
