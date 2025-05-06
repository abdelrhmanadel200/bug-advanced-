<?php
interface AssignmentStrategy {
    public function assign(Bug $bug, array $availableStaff): ?Staff;
}
