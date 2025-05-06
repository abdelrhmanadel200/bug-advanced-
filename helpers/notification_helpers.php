<?php
/**
 * Helper functions for notifications
 */

/**
 * Get unread notifications count for the current user
 * 
 * @return int
 */
function getUnreadNotificationsCount() {
    if (!isset($_SESSION['user_id'])) {
        return 0;
    }
    
    $notificationService = NotificationService::getInstance();
    return $notificationService->getUnreadCount($_SESSION['user_id']);
}

/**
 * Format notification time
 * 
 * @param string $datetime
 * @return string
 */
function formatNotificationTime($datetime) {
    $now = new DateTime();
    $time = new DateTime($datetime);
    $diff = $now->diff($time);
    
    if ($diff->y > 0) {
        return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    }
    
    if ($diff->m > 0) {
        return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    }
    
    if ($diff->d > 0) {
        return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    }
    
    if ($diff->h > 0) {
        return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    }
    
    if ($diff->i > 0) {
        return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    }
    
    return 'Just now';
}

/**
 * Get notification icon based on type
 * 
 * @param string $type
 * @return string
 */
function getNotificationIcon($type) {
    switch ($type) {
        case 'success':
            return '<i class="fas fa-check-circle text-success"></i>';
        case 'warning':
            return '<i class="fas fa-exclamation-triangle text-warning"></i>';
        case 'danger':
            return '<i class="fas fa-times-circle text-danger"></i>';
        case 'info':
            return '<i class="fas fa-info-circle text-info"></i>';
        case 'comment':
            return '<i class="fas fa-comment text-primary"></i>';
        case 'message':
            return '<i class="fas fa-envelope text-primary"></i>';
        default:
            return '<i class="fas fa-bell text-secondary"></i>';
    }
}
