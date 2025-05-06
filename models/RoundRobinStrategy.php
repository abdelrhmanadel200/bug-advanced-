<?php
require_once 'AssignmentStrategy.php';
require_once 'Bug.php';
require_once 'Staff.php';

class RoundRobinStrategy implements AssignmentStrategy {
    private $index = 0;
    
    public function assign(Bug $bug, array $availableStaff): ?Staff {
        if (empty($availableStaff)) {
            return null;
        }
        
        if ($this->index >= count($availableStaff)) {
            $this->index = 0;
        }
        
        $staff = $availableStaff[$this->index];
        $this->index++;
        
        return $staff;
    }
}
