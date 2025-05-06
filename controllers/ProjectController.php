<?php
require_once 'models/Project.php';
require_once 'models/BugTrackingSystem.php';

class ProjectController {
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
        
        // Get all projects
        $projects = Project::getAllProjects();
        
        // Display project list
        include 'views/projects/list.php';
    }
    
    public function view() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }
        
        $id = $_GET['id'] ?? 0;
        
        if (!$id) {
            $_SESSION['errors'] = ['Project not found'];
            header('Location: index.php?controller=project&action=list');
            exit;
        }
        
        $project = new Project($id);
        
        if (!$project->getId()) {
            $_SESSION['errors'] = ['Project not found'];
            header('Location: index.php?controller=project&action=list');
            exit;
        }
        
        // Get project bugs
        $bugs = $project->getBugs();
        
        // Get bug statistics
        $bug_stats = $project->getBugStats();
        
        // Display project details
        include 'views/projects/view.php';
    }
    
    public function add() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }
        
        // Check if user has permission to add projects
        if ($_SESSION['user_role'] !== 'administrator') {
            $_SESSION['errors'] = ['You do not have permission to add projects'];
            header('Location: index.php?controller=project&action=list');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'] ?? '';
            $description = $_POST['description'] ?? '';
            $status = $_POST['status'] ?? 'active';
            
            $errors = [];
            
            if (empty($name)) {
                $errors[] = 'Name is required';
            }
            
            if (empty($description)) {
                $errors[] = 'Description is required';
            }
            
            if (empty($errors)) {
                $project = new Project(null, $name, $description, $status);
                
                if ($project->save()) {
                    // Log activity
                    $this->system->logActivity($_SESSION['user_id'], 'add_project', "Added project: {$name}");
                    
                    $_SESSION['success'] = 'Project added successfully';
                    header('Location: index.php?controller=project&action=view&id=' . $project->getId());
                    exit;
                } else {
                    $errors[] = 'Failed to add project';
                }
            }
            
            $_SESSION['errors'] = $errors;
        }
        
        // Display project add form
        include 'views/projects/add.php';
    }
    
    public function edit() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }
        
        // Check if user has permission to edit projects
        if ($_SESSION['user_role'] !== 'administrator') {
            $_SESSION['errors'] = ['You do not have permission to edit projects'];
            header('Location: index.php?controller=project&action=list');
            exit;
        }
        
        $id = $_GET['id'] ?? 0;
        
        if (!$id) {
            $_SESSION['errors'] = ['Project not found'];
            header('Location: index.php?controller=project&action=list');
            exit;
        }
        
        $project = new Project($id);
        
        if (!$project->getId()) {
            $_SESSION['errors'] = ['Project not found'];
            header('Location: index.php?controller=project&action=list');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'] ?? '';
            $description = $_POST['description'] ?? '';
            $status = $_POST['status'] ?? '';
            
            $errors = [];
            
            if (empty($name)) {
                $errors[] = 'Name is required';
            }
            
            if (empty($description)) {
                $errors[] = 'Description is required';
            }
            
            if (empty($status)) {
                $errors[] = 'Status is required';
            }
            
            if (empty($errors)) {
                $project->setName($name);
                $project->setDescription($description);
                $project->setStatus($status);
                
                if ($project->save()) {
                    // Log activity
                    $this->system->logActivity($_SESSION['user_id'], 'edit_project', "Edited project: {$name}");
                    
                    $_SESSION['success'] = 'Project updated successfully';
                    header('Location: index.php?controller=project&action=view&id=' . $id);
                    exit;
                } else {
                    $errors[] = 'Failed to update project';
                }
            }
            
            $_SESSION['errors'] = $errors;
        }
        
        // Display project edit form
        include 'views/projects/edit.php';
    }
    
    public function delete() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }
        
        // Check if user has permission to delete projects
        if ($_SESSION['user_role'] !== 'administrator') {
            $_SESSION['errors'] = ['You do not have permission to delete projects'];
            header('Location: index.php?controller=project&action=list');
            exit;
        }
        
        $id = $_GET['id'] ?? 0;
        
        if (!$id) {
            $_SESSION['errors'] = ['Project not found'];
            header('Location: index.php?controller=project&action=list');
            exit;
        }
        
        $project = new Project($id);
        
        if (!$project->getId()) {
            $_SESSION['errors'] = ['Project not found'];
            header('Location: index.php?controller=project&action=list');
            exit;
        }
        
        if ($project->delete()) {
            // Log activity
            $this->system->logActivity($_SESSION['user_id'], 'delete_project', "Deleted project: {$project->getName()}");
            
            $_SESSION['success'] = 'Project deleted successfully';
        } else {
            $_SESSION['errors'] = ['Failed to delete project. Make sure there are no bugs associated with this project.'];
        }
        
        header('Location: index.php?controller=project&action=list');
        exit;
    }
}
