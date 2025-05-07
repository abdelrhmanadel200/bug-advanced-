<?php
require_once 'models/BugTrackingSystem.php';
require_once 'models/Bug.php';
require_once 'models/Project.php';

class DashboardController {
    private $system;
    
    public function __construct() {
        $this->system = BugTrackingSystem::getInstance();
    }
    
    public function index() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }
        
        global $db;
        
        // Get bug statistics
        $bug_stats = $this->system->getBugStatistics();
        
        // Get project statistics
        $project_stats = $this->system->getProjectStatistics();
        
        // Get recent bugs
        if ($_SESSION['user_role'] === 'customer') {
            $stmt = $db->prepare("SELECT b.*, p.name as project_name, u.name as assignee_name 
                                 FROM bugs b 
                                 LEFT JOIN projects p ON b.project_id = p.id 
                                 LEFT JOIN users u ON b.assigned_to = u.id 
                                 WHERE b.reported_by = ? 
                                 ORDER BY b.created_at DESC 
                                 LIMIT 5");
            $stmt->execute([$_SESSION['user_id']]);
        } elseif ($_SESSION['user_role'] === 'staff') {
            $stmt = $db->prepare("SELECT b.*, p.name as project_name, u.name as reporter_name 
                                 FROM bugs b 
                                 LEFT JOIN projects p ON b.project_id = p.id 
                                 LEFT JOIN users u ON b.reported_by = u.id 
                                 WHERE b.assigned_to = ? OR b.status = 'open' 
                                 ORDER BY b.created_at DESC 
                                 LIMIT 5");
            $stmt->execute([$_SESSION['user_id']]);
        } else {
            $stmt = $db->prepare("SELECT b.*, p.name as project_name, u1.name as reporter_name, u2.name as assignee_name 
                                 FROM bugs b 
                                 LEFT JOIN projects p ON b.project_id = p.id 
                                 LEFT JOIN users u1 ON b.reported_by = u1.id 
                                 LEFT JOIN users u2 ON b.assigned_to = u2.id 
                                 ORDER BY b.created_at DESC 
                                 LIMIT 5");
            $stmt->execute();
        }
        // $recent_bugs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get recent activity
        // $recent_activity = $this->system->getRecentActivity(5);
        
        // Display dashboard
        include 'views/dashboard.php';
    }
    
    public function reports() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }
        
        // Check if user has permission to view reports
        if ($_SESSION['user_role'] !== 'administrator') {
            $_SESSION['errors'] = ['You do not have permission to view reports'];
            header('Location: index.php?controller=dashboard&action=index');
            exit;
        }
        
        $report_type = $_GET['type'] ?? 'bugs';
        $filters = [];
        
        // Apply filters if provided
        if (isset($_GET['status']) && !empty($_GET['status'])) {
            $filters['status'] = $_GET['status'];
        }
        
        if (isset($_GET['severity']) && !empty($_GET['severity'])) {
            $filters['severity'] = $_GET['severity'];
        }
        
        if (isset($_GET['severity']) && !empty($_GET['severity'])) {
            $filters['severity'] = $_GET['severity'];
        }
        
        if (isset($_GET['project_id']) && !empty($_GET['project_id'])) {
            $filters['project_id'] = $_GET['project_id'];
        }
        
        if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
            $filters['date_from'] = $_GET['date_from'];
        }
        
        if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
            $filters['date_to'] = $_GET['date_to'];
        }
        
        // Generate report
        $report_data = $this->system->generateReports($report_type, $filters);
        
        // Get projects for filter dropdown
        $projects = Project::getAllProjects();
        
        // Display reports
        include 'views/dashboard/reports.php';
    }
    
    public function printReport() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }
        
        // Check if user has permission to print reports
        if ($_SESSION['user_role'] !== 'administrator') {
            $_SESSION['errors'] = ['You do not have permission to print reports'];
            header('Location: index.php?controller=dashboard&action=index');
            exit;
        }
        
        $report_type = $_GET['type'] ?? 'bugs';
        $filters = [];
        
        // Apply filters if provided
        if (isset($_GET['status']) && !empty($_GET['status'])) {
            $filters['status'] = $_GET['status'];
        }
        
        if (isset($_GET['severity']) && !empty($_GET['severity'])) {
            $filters['severity'] = $_GET['severity'];
        }
        
        if (isset($_GET['project_id']) && !empty($_GET['project_id'])) {
            $filters['project_id'] = $_GET['project_id'];
        }
        
        if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
            $filters['date_from'] = $_GET['date_from'];
        }
        
        if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
            $filters['date_to'] = $_GET['date_to'];
        }
        
        // Generate report
        $report_data = $this->system->generateReports($report_type, $filters);
        
        // Display printable report
        include 'views/dashboard/print-report.php';
    }
}
