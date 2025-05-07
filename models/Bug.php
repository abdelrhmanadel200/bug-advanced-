<?php
require_once 'Observable.php';
require_once 'Observer.php';

class Bug implements Observable {
    private $id;
    private $ticket_number;
    private $title;
    private $description;
    private $project_id;
    private $reported_by;
    private $assigned_to;
    private $severity;
    private $priority;
    private $status;
    private $steps;
    private $expected_result;
    private $actual_result;
    private $created_at;
    private $updated_at;
    private $observers = [];

    public function __construct($id = null) {
        if ($id) {
            $this->id = $id;
            $this->loadBug();
        } else {
            $this->status = 'open';
            $this->created_at = date('Y-m-d H:i:s');
            $this->updated_at = date('Y-m-d H:i:s');
            $this->generateTicketNumber();
        }
    }

    private function loadBug() {
        global $db;
        
        $stmt = $db->prepare("SELECT * FROM bugs WHERE id = ?");
        $stmt->execute([$this->id]);
        $bug = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($bug) {
            $this->ticket_number = $bug['ticket_number'];
            $this->title = $bug['title'];
            $this->description = $bug['description'];
            $this->project_id = $bug['project_id'];
            $this->reported_by = $bug['reported_by'];
            $this->assigned_to = $bug['assigned_to'];
            $this->severity = $bug['severity'];
            $this->priority = $bug['priority'] ?? 'medium';
            $this->status = $bug['status'];
            $this->steps = $bug['steps'] ?? null;
            $this->expected_result = $bug['expected_result'] ?? null;
            $this->actual_result = $bug['actual_result'] ?? null;
            $this->created_at = $bug['created_at'];
            $this->updated_at = $bug['updated_at'];
            
            // Load observers
            $this->loadObservers();
        }
    }
    
    private function loadObservers() {
        global $db;
        
        // Check if bug_observers table exists
        try {
            $stmt = $db->prepare("SELECT user_id FROM bug_observers WHERE bug_id = ?");
            $stmt->execute([$this->id]);
            $observers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($observers as $observer) {
                $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$observer['user_id']]);
                $userData = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($userData) {
                    switch ($userData['role']) {
                        case 'administrator':
                            $this->observers[] = new Administrator($userData['id'], $userData['name'], $userData['email'], $userData['password']);
                            break;
                        case 'staff':
                            $this->observers[] = new Staff($userData['id'], $userData['name'], $userData['email'], $userData['password']);
                            break;
                        case 'customer':
                            $this->observers[] = new Customer($userData['id'], $userData['name'], $userData['email'], $userData['password']);
                            break;
                    }
                }
            }
        } catch (PDOException $e) {
            // Table doesn't exist yet, create it
            $this->createBugObserversTable();
        }
    }
    
    private function createBugObserversTable() {
        global $db;
        
        $sql = "CREATE TABLE IF NOT EXISTS bug_observers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            bug_id INT NOT NULL,
            user_id INT NOT NULL,
            created_at DATETIME NOT NULL,
            FOREIGN KEY (bug_id) REFERENCES bugs(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY bug_user (bug_id, user_id)
        )";
        
        $db->exec($sql);
    }
    
    private function generateTicketNumber() {
        $prefix = 'BUG';
        $timestamp = time();
        $random = mt_rand(1000, 9999);
        $this->ticket_number = $prefix . '-' . $timestamp . '-' . $random;
    }
    
    public function save() {
        global $db;
        
        $this->updated_at = date('Y-m-d H:i:s');
        
        if ($this->id) {
            // Get the current status before update
            $stmt = $db->prepare("SELECT status FROM bugs WHERE id = ?");
            $stmt->execute([$this->id]);
            $currentBug = $stmt->fetch(PDO::FETCH_ASSOC);
            $oldStatus = $currentBug['status'];
            
            // Update existing bug
            $stmt = $db->prepare("UPDATE bugs SET 
                                 ticket_number = ?, 
                                 title = ?, 
                                 description = ?, 
                                 project_id = ?, 
                                 reported_by = ?, 
                                 assigned_to = ?, 
                                 status = ?, 
                                 severity = ?, 
                                 priority = ?, 
                                 updated_at = ? 
                                 WHERE id = ?");
            
            $result = $stmt->execute([
                $this->ticket_number,
                $this->title,
                $this->description,
                $this->project_id,
                $this->reported_by,
                $this->assigned_to,
                $this->status,
                $this->severity,
                $this->priority,
                $this->updated_at,
                $this->id
            ]);
            
            // Log the status change in bug history
            if ($result && $oldStatus !== $this->status) {
                $this->logHistory('status', $oldStatus, $this->status);
                
                // Notify observers about status change
                $this->notifyObservers();
                
                // If bug is assigned to someone, add them as an observer
                if ($this->assigned_to && $this->status === 'assigned') {
                    $this->addStaffAsObserver();
                }
            }
            
            return $result;
        } else {
            // Insert new bug
            $stmt = $db->prepare("INSERT INTO bugs (
                                ticket_number, 
                                title, 
                                description, 
                                project_id, 
                                reported_by, 
                                assigned_to, 
                                status, 
                                severity, 
                                priority, 
                                created_at, 
                                updated_at
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $result = $stmt->execute([
                $this->ticket_number,
                $this->title,
                $this->description,
                $this->project_id,
                $this->reported_by,
                $this->assigned_to,
                $this->status,
                $this->severity,
                $this->priority,
                $this->created_at,
                $this->updated_at
            ]);
            
            if ($result) {
                $this->id = $db->lastInsertId();
                
                // Log the creation in bug history
                $this->logHistory('created', null, 'Bug created');
                
                // Add reporter as observer
                $this->addReporterAsObserver();
                
                // If bug is assigned to someone, add them as an observer
                if ($this->assigned_to) {
                    $this->addStaffAsObserver();
                }
            }
            
            return $result;
        }
    }
    
    private function addReporterAsObserver() {
        global $db;
        
        // Get reporter details
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$this->reported_by]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($userData) {
            $reporter = null;
            
            switch ($userData['role']) {
                case 'administrator':
                    $reporter = new Administrator($userData['id'], $userData['name'], $userData['email'], $userData['password']);
                    break;
                case 'staff':
                    $reporter = new Staff($userData['id'], $userData['name'], $userData['email'], $userData['password']);
                    break;
                case 'customer':
                    $reporter = new Customer($userData['id'], $userData['name'], $userData['email'], $userData['password']);
                    break;
            }
            
            if ($reporter) {
                $this->attach($reporter);
            }
        }
    }
    
    private function addStaffAsObserver() {
        global $db;
        
        // Get staff details
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND role = 'staff'");
        $stmt->execute([$this->assigned_to]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($userData) {
            $staff = new Staff($userData['id'], $userData['name'], $userData['email'], $userData['password']);
            $this->attach($staff);
            
            // Send notification to staff about assignment
            if (class_exists('NotificationService')) {
                $notificationService = NotificationService::getInstance();
                $notificationService->notifyBugAssignment($staff, $this);
            }
        }
    }
    
    public function delete() {
        global $db;
        
        if ($this->id) {
            // Delete bug observers
            try {
                $stmt = $db->prepare("DELETE FROM bug_observers WHERE bug_id = ?");
                $stmt->execute([$this->id]);
            } catch (PDOException $e) {
                // Table might not exist yet
            }
            
            // Delete bug history
            $stmt = $db->prepare("DELETE FROM bug_history WHERE bug_id = ?");
            $stmt->execute([$this->id]);
            
            // Delete bug comments
            $stmt = $db->prepare("DELETE FROM comments WHERE bug_id = ?");
            $stmt->execute([$this->id]);
            
            // Delete bug attachments
            $stmt = $db->prepare("DELETE FROM bug_attachments WHERE bug_id = ?");
            $stmt->execute([$this->id]);
            
            // Delete bug
            $stmt = $db->prepare("DELETE FROM bugs WHERE id = ?");
            return $stmt->execute([$this->id]);
        }
        
        return false;
    }
    
    public function getDetails() {
        if (!$this->id) {
            return null;
        }
        
        return [
            'id' => $this->id,
            'ticket_number' => $this->ticket_number,
            'title' => $this->title,
            'description' => $this->description,
            'project_id' => $this->project_id,
            'reported_by' => $this->reported_by,
            'assigned_to' => $this->assigned_to,
            'status' => $this->status,
            'severity' => $this->severity,
            'priority' => $this->priority,
            'steps' => $this->steps,
            'expected_result' => $this->expected_result,
            'actual_result' => $this->actual_result,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
    
    public function getComments() {
        global $db;
        
        if (!$this->id) {
            return [];
        }
        
        $stmt = $db->prepare("SELECT c.*, u.name as user_name, u.role as user_role 
                             FROM comments c 
                             LEFT JOIN users u ON c.user_id = u.id 
                             WHERE c.bug_id = ? 
                             ORDER BY c.created_at ASC");
        $stmt->execute([$this->id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function addComment($userId, $content) {
        global $db;
        
        if (!$this->id) {
            return false;
        }
        
        $stmt = $db->prepare("INSERT INTO comments (bug_id, user_id, comment, created_at) VALUES (?, ?, ?, ?)");
        $result = $stmt->execute([$this->id, $userId, $content, date('Y-m-d H:i:s')]);
        
        if ($result) {
            // Log the comment in bug history
            $this->logHistory('comment', null, "Comment added by user ID: {$userId}");
            
            // Get commenter details
            $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($userData) {
                $commenter = null;
                
                switch ($userData['role']) {
                    case 'administrator':
                        $commenter = new Administrator($userData['id'], $userData['name'], $userData['email'], $userData['password']);
                        break;
                    case 'staff':
                        $commenter = new Staff($userData['id'], $userData['name'], $userData['email'], $userData['password']);
                        break;
                    case 'customer':
                        $commenter = new Customer($userData['id'], $userData['name'], $userData['email'], $userData['password']);
                        break;
                }
                
                // Notify all observers about the new comment
                if ($commenter && class_exists('NotificationService')) {
                    $notificationService = NotificationService::getInstance();
                    
                    foreach ($this->observers as $observer) {
                        // Don't notify the commenter
                        if ($observer->getId() != $userId) {
                            $notificationService->notifyNewComment($observer, $this, $content, $commenter);
                        }
                    }
                }
            }
        }
        
        return $result;
    }
    
    public function getHistory() {
        global $db;
        
        if (!$this->id) {
            return [];
        }
        
        $stmt = $db->prepare("SELECT h.*, u.name as user_name 
                             FROM bug_history h 
                             LEFT JOIN users u ON h.changed_by = u.id 
                             WHERE h.bug_id = ? 
                             ORDER BY h.created_at DESC");
        $stmt->execute([$this->id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function logHistory($field, $old_value, $new_value) {
        global $db;
        
        if (!$this->id) {
            return false;
        }
        
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        
        $stmt = $db->prepare("INSERT INTO bug_history (bug_id, changed_by, field_name, old_value, new_value, created_at) VALUES (?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$this->id, $userId, $field, $old_value, $new_value, date('Y-m-d H:i:s')]);
    }
    
    public function attachScreenshot($filename) {
        global $db;
        
        if (!$this->id) {
            return false;
        }
        
        // Get file information
        $file_path = 'uploads/screenshots/' . $filename;
        $file_type = pathinfo($filename, PATHINFO_EXTENSION);
        $uploaded_by = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : $this->reported_by;
        
        $stmt = $db->prepare("INSERT INTO bug_attachments (bug_id, file_name, file_path, file_type, uploaded_by, created_at) VALUES (?, ?, ?, ?, ?, ?)");
        $result = $stmt->execute([$this->id, $filename, $file_path, $file_type, $uploaded_by, date('Y-m-d H:i:s')]);
        
        if ($result) {
            // Log the screenshot in bug history
            $this->logHistory('attachment', null, "Screenshot added: {$filename}");
        }
        
        return $result;
    }
    
    public function getAttachments() {
        global $db;
        
        if (!$this->id) {
            return [];
        }
        
        $stmt = $db->prepare("SELECT * FROM bug_attachments WHERE bug_id = ? ORDER BY created_at DESC");
        $stmt->execute([$this->id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public static function findByTicketNumber($ticketNumber) {
        global $db;
        
        $stmt = $db->prepare("SELECT id FROM bugs WHERE ticket_number = ?");
        $stmt->execute([$ticketNumber]);
        $bug = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($bug) {
            return new Bug($bug['id']);
        }
        
        return null;
    }
    
    // Observer pattern methods
    public function attach(Observer $observer) {
        global $db;
        
        // Check if observer is already attached
        foreach ($this->observers as $existingObserver) {
            if ($existingObserver->getId() === $observer->getId()) {
                return false;
            }
        }
        
        // Add observer to array
        $this->observers[] = $observer;
        
        // Add observer to database
        if ($this->id) {
            try {
                $stmt = $db->prepare("INSERT INTO bug_observers (bug_id, user_id, created_at) VALUES (?, ?, ?)");
                return $stmt->execute([$this->id, $observer->getId(), date('Y-m-d H:i:s')]);
            } catch (PDOException $e) {
                // Table might not exist yet
                $this->createBugObserversTable();
                $stmt = $db->prepare("INSERT INTO bug_observers (bug_id, user_id, created_at) VALUES (?, ?, ?)");
                return $stmt->execute([$this->id, $observer->getId(), date('Y-m-d H:i:s')]);
            }
        }
        
        return true;
    }
    
    public function detach(Observer $observer) {
        global $db;
        
        // Remove observer from array
        foreach ($this->observers as $key => $existingObserver) {
            if ($existingObserver->getId() === $observer->getId()) {
                unset($this->observers[$key]);
                
                // Remove observer from database
                if ($this->id) {
                    try {
                        $stmt = $db->prepare("DELETE FROM bug_observers WHERE bug_id = ? AND user_id = ?");
                        return $stmt->execute([$this->id, $observer->getId()]);
                    } catch (PDOException $e) {
                        // Table might not exist yet
                        return true;
                    }
                }
                
                return true;
            }
        }
        
        return false;
    }
    
    public function notifyObservers() {
        foreach ($this->observers as $observer) {
            $observer->update($this, $this->status);
        }
    }
    
    // Getters and setters
    public function getId() {
        return $this->id;
    }
    
    public function getTicketNumber() {
        return $this->ticket_number;
    }
    
    public function getTitle() {
        return $this->title;
    }
    
    public function setTitle($title) {
        $this->title = $title;
    }
    
    public function getDescription() {
        return $this->description;
    }
    
    public function setDescription($description) {
        $this->description = $description;
    }
    
    public function getProjectId() {
        return $this->project_id;
    }
    
    public function setProjectId($projectId) {
        $this->project_id = $projectId;
    }
    
    public function getReportedBy() {
        return $this->reported_by;
    }
    
    public function setReportedBy($reportedBy) {
        $this->reported_by = $reportedBy;
    }
    
    public function getAssignedTo() {
        return $this->assigned_to;
    }
    
    public function setAssignedTo($assignedTo) {
        $this->assigned_to = $assignedTo;
    }
    
    public function getStatus() {
        return $this->status;
    }
    
    public function setStatus($status) {
        $this->status = $status;
    }
    
    public function getSeverity() {
        return $this->severity;
    }
    
    public function setSeverity($severity) {
        $this->severity = $severity;
    }
    
    public function getPriority() {
        return $this->priority;
    }
    
    public function setPriority($priority) {
        $this->priority = $priority;
    }
    
    public function getSteps() {
        return $this->steps;
    }
    
    public function setSteps($steps) {
        $this->steps = $steps;
    }
    
    public function getExpectedResult() {
        return $this->expected_result;
    }
    
    public function setExpectedResult($expectedResult) {
        $this->expected_result = $expectedResult;
    }
    
    public function getActualResult() {
        return $this->actual_result;
    }
    
    public function setActualResult($actualResult) {
        $this->actual_result = $actualResult;
    }
    
    public function getCreatedAt() {
        return $this->created_at;
    }
    
    public function getUpdatedAt() {
        return $this->updated_at;
    }
}
