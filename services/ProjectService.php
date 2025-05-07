<?php
require_once 'models/Project.php';

class ProjectService {
    private static $instance = null;
    
    private function __construct() {
        // Private constructor to prevent direct creation
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new ProjectService();
        }
        return self::$instance;
    }
    
    public function getProjectById($id) {
        return new Project($id);
    }
    
    public function getActiveProjects() {
        return Project::getActiveProjects();
    }
    
    public function createProject($name, $description, $userId) {
        $project = new Project();
        $project->setName($name);
        $project->setDescription($description);
         $project->setStatus('active');
        
        if ($project->save()) {
            // Log activity
            $system = BugTrackingSystem::getInstance();
            $system->logActivity($userId, 'create_project', "Created project: {$name}");
            
            return $project->getId();
        }
        
        return false;
    }
    
    public function updateProject($id, $name, $description, $status, $userId) {
        $project = new Project($id);
        
        if (!$project->getId()) {
            return false;
        }
        
        $project->setName($name);
        $project->setDescription($description);
        $project->setStatus($status);
        
        if ($project->save()) {
            // Log activity
            $system = BugTrackingSystem::getInstance();
            $system->logActivity($userId, 'update_project', "Updated project: {$name}");
            
            return true;
        }
        
        return false;
    }
    
    public function deleteProject($id, $userId) {
        $project = new Project($id);
        
        if (!$project->getId()) {
            return false;
        }
        
        $projectName = $project->getName();
        
        if ($project->delete()) {
            // Log activity
            $system = BugTrackingSystem::getInstance();
            $system->logActivity($userId, 'delete_project', "Deleted project: {$projectName}");
            
            return true;
        }
        
        return false;
    }
    
    public function getProjectStatistics() {
        global $db;
        
        $stmt = $db->prepare("SELECT 
                             COUNT(*) as total,
                             SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                             SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive
                             FROM projects");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getProjectBugs($projectId) {
        global $db;
        
        $stmt = $db->prepare("SELECT b.*, u1.name as reporter_name, u2.name as assignee_name 
                             FROM bugs b 
                             LEFT JOIN users u1 ON b.reported_by = u1.id 
                             LEFT JOIN users u2 ON b.assigned_to = u2.id 
                             WHERE b.project_id = ? 
                             ORDER BY b.created_at DESC");
        $stmt->execute([$projectId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
