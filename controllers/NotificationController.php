<?php
require_once 'services/NotificationService.php';

class NotificationController {
    private $notificationService;
    
    public function __construct() {
        $this->notificationService = NotificationService::getInstance();
    }
    
    public function getUnreadNotifications() {
        if (!isset($_SESSION['user_id'])) {
            return [];
        }
        
        return $this->notificationService->getUnreadNotifications($_SESSION['user_id']);
    }
    
    public function getAllNotifications() {
        if (!isset($_SESSION['user_id'])) {
            return [];
        }
        
        return $this->notificationService->getAllNotifications($_SESSION['user_id']);
    }
    
    public function markAsRead() {
        if (!isset($_SESSION['user_id']) || !isset($_POST['notification_id'])) {
            return false;
        }
        
        return $this->notificationService->markAsRead($_POST['notification_id']);
    }
    
    public function markAllAsRead() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        return $this->notificationService->markAllAsRead($_SESSION['user_id']);
    }
    
    public function deleteNotification() {
        if (!isset($_SESSION['user_id']) || !isset($_POST['notification_id'])) {
            return false;
        }
        
        return $this->notificationService->deleteNotification($_POST['notification_id']);
    }
    
    public function deleteAllNotifications() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        return $this->notificationService->deleteAllNotifications($_SESSION['user_id']);
    }
    
    public function getNotificationCount() {
        if (!isset($_SESSION['user_id'])) {
            return 0;
        }
        
        $notifications = $this->notificationService->getUnreadNotifications($_SESSION['user_id']);
        return count($notifications);
    }
    
    public function renderNotificationDropdown() {
        $notifications = $this->getUnreadNotifications();
        $count = count($notifications);
        
        include 'views/components/notification-dropdown.php';
    }
}
