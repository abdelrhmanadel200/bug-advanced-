<?php
class Project {
    private $id;
    private $name;
    private $description;
    private $status;
    private $created_at;
    private $updated_at;

    public function __construct($id = null, $name = null, $description = null, $status = 'active') {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->status = $status;
        $this->created_at = date('Y-m-d H:i:s');
        $this->updated_at = date('Y-m-d H:i:s');
        
        if ($id) {
            $this->loadProject();
        }
    }

    private function loadProject() {
        global $db;
        
        $stmt = $db->prepare("SELECT * FROM projects WHERE id = ?");
        $stmt->execute([$this->id]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($project) {
            $this->name = $project['name'];
            $this->description = $project['description'];
            $this->status = $project['status'];
            $this->created_at = $project['created_at'];
            $this->updated_at = $project['updated_at'];
        }
    }

    public function getId() {
        return $this->id;
    }

    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->name = $name;
        $this->updated_at = date('Y-m-d H:i:s');
    }

    public function getDescription() {
        return $this->description;
    }

    public function setDescription($description) {
        $this->description = $description;
        $this->updated_at = date('Y-m-d H:i:s');
    }

    public function getStatus() {
        return $this->status;
    }

    public function setStatus($status) {
        $this->status = $status;
        $this->updated_at = date('Y-m-d H:i:s');
    }

    public function getCreatedAt() {
        return $this->created_at;
    }

    public function getUpdatedAt() {
        return $this->updated_at;
    }

    public function getBugs() {
        global $db;
        
        $stmt = $db->prepare("SELECT b.*, u1.name as reporter_name, u2.name as assignee_name 
                             FROM bugs b 
                             LEFT JOIN users u1 ON b.reported_by = u1.id 
                             LEFT JOIN users u2 ON b.assigned_to = u2.id 
                             WHERE b.project_id = ? 
                             ORDER BY b.created_at DESC");
        $stmt->execute([$this->id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getBugStats() {
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
                             FROM bugs 
                             WHERE project_id = ?");
        $stmt->execute([$this->id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function save() {
        global $db;
        
        if ($this->id) {
            // Update existing project
            $stmt = $db->prepare("UPDATE projects SET name = ?, description = ?, status = ?, updated_at = ? WHERE id = ?");
            return $stmt->execute([$this->name, $this->description, $this->status, date('Y-m-d H:i:s'), $this->id]);
        } else {
            // Insert new project
            $stmt = $db->prepare("INSERT INTO projects (name, description, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?)");
            $result = $stmt->execute([$this->name, $this->description, $this->status, $this->created_at, $this->updated_at]);
            
            if ($result) {
                $this->id = $db->lastInsertId();
                return true;
            }
            
            return false;
        }
    }

    public function delete() {
        global $db;
        
        if ($this->id) {
            // Check if there are bugs associated with this project
            $stmt = $db->prepare("SELECT COUNT(*) as bug_count FROM bugs WHERE project_id = ?");
            $stmt->execute([$this->id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['bug_count'] > 0) {
                return false; // Cannot delete project with bugs
            }
            
            // Delete project
            $stmt = $db->prepare("DELETE FROM projects WHERE id = ?");
            return $stmt->execute([$this->id]);
        }
        
        return false;
    }

    public static function getAllProjects() {
        global $db;
        
        $stmt = $db->prepare("SELECT p.*, 
                             (SELECT COUNT(*) FROM bugs WHERE project_id = p.id) as bug_count 
                             FROM projects p 
                             ORDER BY p.name ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getActiveProjects() {
        global $db;
        
        $stmt = $db->prepare("SELECT p.*, 
                             (SELECT COUNT(*) FROM bugs WHERE project_id = p.id) as bug_count 
                             FROM projects p 
                             WHERE p.status = 'active' 
                             ORDER BY p.name ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
