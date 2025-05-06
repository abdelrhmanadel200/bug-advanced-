<?php
require_once 'AssignmentStrategy.php';
require_once 'RoundRobinStrategy.php';
require_once 'Notification.php';

class AssignmentService {
    private $strategy;
    
    public function __construct() {
        // Default to RoundRobin strategy
        $this->strategy = new RoundRobinStrategy();
    }
    
    public function setStrategy(AssignmentStrategy $strategy) {
        $this->strategy = $strategy;
    }
    
    public function assignBugToStaff(Bug $bug, array $availableStaff) {
        $staff = $this->strategy->assign($bug, $availableStaff);
        
        if ($staff) {
            $bug->setAssignedTo($staff->getId());
            $bug->setStatus('assigned');
            
            if ($bug->save()) {
                // Send email notification to the assigned staff
                Notification::sendBugAssignmentNotification($staff, $bug);
                
                return $staff;
            }
        }
        
        return null;
    }
    
    public static function getAvailableStaff() {
        global $db;
        
        $stmt = $db->prepare("SELECT * FROM users WHERE role = 'staff' AND status = 'active'");
        $stmt->execute();
        $staffData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $staff = [];
        foreach ($staffData as $data) {
            $staff[] = new Staff($data['id'], $data['name'], $data['email'], $data['password']);
        }
        
        return $staff;
    }
}
