<?php
require_once 'AssignmentStrategy.php';
require_once 'Bug.php';
require_once 'Staff.php';

class SeverityBasedStrategy implements AssignmentStrategy {
    public function assign(Bug $bug, array $availableStaff): ?Staff {
        if (empty($availableStaff)) {
            return null;
        }
        
        // Get staff with the least number of high severity bugs assigned
        $staffWorkload = [];
        
        global $db;
        foreach ($availableStaff as $staff) {
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM bugs 
                                 WHERE assigned_to = ? 
                                 AND severity IN ('critical', 'high') 
                                 AND status IN ('assigned', 'in-progress')");
            $stmt->execute([$staff->getId()]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $staffWorkload[$staff->getId()] = $result['count'];
        }
        
        // Sort staff by workload (ascending)
        asort($staffWorkload);
        
        // Get the staff with the lowest workload
        $staffId = key($staffWorkload);
        
        foreach ($availableStaff as $staff) {
            if ($staff->getId() == $staffId) {
                return $staff;
            }
        }
        
        // Fallback to first staff member if something went wrong
        return $availableStaff[0];
    }
}
