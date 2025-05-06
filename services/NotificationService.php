<?php
require_once 'config/config.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class NotificationService {
    private static $instance = null;
    private $mailer;
    
    private function __construct() {
        // Initialize PHPMailer if SMTP settings are configured
        if (defined('SMTP_HOST') && SMTP_HOST) {
            require_once 'vendor/autoload.php';
            
            $this->mailer = new PHPMailer(true);
            $this->mailer->isSMTP();
            $this->mailer->Host = SMTP_HOST;
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = SMTP_USERNAME;
            $this->mailer->Password = SMTP_PASSWORD;
            $this->mailer->SMTPSecure = SMTP_SECURE;
            $this->mailer->Port = SMTP_PORT;
            $this->mailer->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $this->mailer->isHTML(true);
        }
    }
    
    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new NotificationService();
        }
        return self::$instance;
    }
    
    // Create a database notification
    public function createNotification($userId, $message, $type = 'info', $link = null) {
        global $db;
        
        $stmt = $db->prepare("INSERT INTO notifications (user_id, message, type, link, created_at) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$userId, $message, $type, $link, date('Y-m-d H:i:s')]);
    }
    
    // Get unread notifications for a user
    public function getUnreadNotifications($userId) {
        global $db;
        
        $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get all notifications for a user
    public function getAllNotifications($userId) {
        global $db;
        
        $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Mark a notification as read
    public function markAsRead($notificationId) {
        global $db;
        
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1, read_at = ? WHERE id = ?");
        return $stmt->execute([date('Y-m-d H:i:s'), $notificationId]);
    }
    
    // Mark all notifications as read for a user
    public function markAllAsRead($userId) {
        global $db;
        
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1, read_at = ? WHERE user_id = ? AND is_read = 0");
        return $stmt->execute([date('Y-m-d H:i:s'), $userId]);
    }
    
    // Delete a notification
    public function deleteNotification($notificationId) {
        global $db;
        
        $stmt = $db->prepare("DELETE FROM notifications WHERE id = ?");
        return $stmt->execute([$notificationId]);
    }
    
    // Delete all notifications for a user
    public function deleteAllNotifications($userId) {
        global $db;
        
        $stmt = $db->prepare("DELETE FROM notifications WHERE user_id = ?");
        return $stmt->execute([$userId]);
    }
    
    // Send an email notification
    public function sendEmail($to, $subject, $body) {
        if (!$this->mailer) {
            return false;
        }
        
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($to);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;
            
            return $this->mailer->send();
        } catch (Exception $e) {
            error_log('Email could not be sent. Mailer Error: ' . $this->mailer->ErrorInfo);
            return false;
        }
    }
    
    // Notify about bug assignment
    public function notifyBugAssignment($user, $bug) {
        // Create in-app notification
        $message = "You have been assigned to bug #{$bug->getTicketNumber()}: {$bug->getTitle()}";
        $link = "bug.php?id={$bug->getId()}";
        $this->createNotification($user->getId(), $message, 'assignment', $link);
        
        // Send email notification
        if ($this->mailer) {
            $subject = "Bug Assignment: {$bug->getTicketNumber()}";
            $body = $this->getBugAssignmentEmailTemplate($user, $bug);
            $this->sendEmail($user->getEmail(), $subject, $body);
        }
    }
    
    // Notify about bug status change
    public function notifyBugStatusChange($user, $bug, $oldStatus, $newStatus) {
        // Create in-app notification
        $message = "Bug #{$bug->getTicketNumber()} status changed from {$oldStatus} to {$newStatus}";
        $link = "bug.php?id={$bug->getId()}";
        $this->createNotification($user->getId(), $message, 'status', $link);
        
        // Send email notification
        if ($this->mailer) {
            $subject = "Bug Status Update: {$bug->getTicketNumber()}";
            $body = $this->getBugStatusChangeEmailTemplate($user, $bug, $oldStatus, $newStatus);
            $this->sendEmail($user->getEmail(), $subject, $body);
        }
    }
    
    // Notify about new comment
    public function notifyNewComment($user, $bug, $comment, $commenter) {
        // Create in-app notification
        $message = "{$commenter->getName()} commented on bug #{$bug->getTicketNumber()}";
        $link = "bug.php?id={$bug->getId()}";
        $this->createNotification($user->getId(), $message, 'comment', $link);
        
        // Send email notification
        if ($this->mailer) {
            $subject = "New Comment on Bug: {$bug->getTicketNumber()}";
            $body = $this->getNewCommentEmailTemplate($user, $bug, $comment, $commenter);
            $this->sendEmail($user->getEmail(), $subject, $body);
        }
    }
    
    // Notify about password reset
    public function notifyPasswordReset($user, $resetToken) {
        // Create in-app notification
        $message = "A password reset has been requested for your account";
        $this->createNotification($user->getId(), $message, 'security');
        
        // Send email notification
        if ($this->mailer) {
            $subject = "Password Reset Request";
            $body = $this->getPasswordResetEmailTemplate($user, $resetToken);
            $this->sendEmail($user->getEmail(), $subject, $body);
        }
    }
    
    // Email templates
    private function getBugAssignmentEmailTemplate($user, $bug) {
        $appUrl = APP_URL ?? 'http://localhost';
        $bugUrl = "{$appUrl}/bug.php?id={$bug->getId()}";
        
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #4a6fdc; color: white; padding: 10px 20px; }
                .content { padding: 20px; border: 1px solid #ddd; }
                .footer { font-size: 12px; color: #777; margin-top: 20px; }
                .button { display: inline-block; background-color: #4a6fdc; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Bug Assignment</h2>
                </div>
                <div class='content'>
                    <p>Hello {$user->getName()},</p>
                    <p>You have been assigned to the following bug:</p>
                    <p><strong>Bug ID:</strong> {$bug->getTicketNumber()}<br>
                    <strong>Title:</strong> {$bug->getTitle()}<br>
                    <strong>Severity:</strong> {$bug->getSeverity()}<br>
                    <strong>Priority:</strong> {$bug->getPriority()}</p>
                    <p>Please review the bug details and take appropriate action.</p>
                    <p><a href='{$bugUrl}' class='button'>View Bug Details</a></p>
                </div>
                <div class='footer'>
                    <p>This is an automated message from the Bug Tracking System. Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    private function getBugStatusChangeEmailTemplate($user, $bug, $oldStatus, $newStatus) {
        $appUrl = APP_URL ?? 'http://localhost';
        $bugUrl = "{$appUrl}/bug.php?id={$bug->getId()}";
        
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #4a6fdc; color: white; padding: 10px 20px; }
                .content { padding: 20px; border: 1px solid #ddd; }
                .footer { font-size: 12px; color: #777; margin-top: 20px; }
                .button { display: inline-block; background-color: #4a6fdc; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; }
                .status-change { background-color: #f9f9f9; padding: 10px; border-left: 4px solid #4a6fdc; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Bug Status Update</h2>
                </div>
                <div class='content'>
                    <p>Hello {$user->getName()},</p>
                    <p>The status of bug #{$bug->getTicketNumber()} has been updated:</p>
                    <div class='status-change'>
                        <p><strong>Title:</strong> {$bug->getTitle()}<br>
                        <strong>Previous Status:</strong> {$oldStatus}<br>
                        <strong>New Status:</strong> {$newStatus}</p>
                    </div>
                    <p><a href='{$bugUrl}' class='button'>View Bug Details</a></p>
                </div>
                <div class='footer'>
                    <p>This is an automated message from the Bug Tracking System. Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    private function getNewCommentEmailTemplate($user, $bug, $comment, $commenter) {
        $appUrl = APP_URL ?? 'http://localhost';
        $bugUrl = "{$appUrl}/bug.php?id={$bug->getId()}";
        
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #4a6fdc; color: white; padding: 10px 20px; }
                .content { padding: 20px; border: 1px solid #ddd; }
                .footer { font-size: 12px; color: #777; margin-top: 20px; }
                .button { display: inline-block; background-color: #4a6fdc; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; }
                .comment { background-color: #f9f9f9; padding: 10px; border-left: 4px solid #4a6fdc; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>New Comment on Bug</h2>
                </div>
                <div class='content'>
                    <p>Hello {$user->getName()},</p>
                    <p>{$commenter->getName()} has commented on bug #{$bug->getTicketNumber()}:</p>
                    <div class='comment'>
                        <p><strong>Bug:</strong> {$bug->getTitle()}<br>
                        <strong>Comment by:</strong> {$commenter->getName()}</p>
                        <p>{$comment}</p>
                    </div>
                    <p><a href='{$bugUrl}' class='button'>View Bug Details</a></p>
                </div>
                <div class='footer'>
                    <p>This is an automated message from the Bug Tracking System. Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    private function getPasswordResetEmailTemplate($user, $resetToken) {
        $appUrl = APP_URL ?? 'http://localhost';
        $resetUrl = "{$appUrl}/reset-password.php?token={$resetToken}";
        
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #4a6fdc; color: white; padding: 10px 20px; }
                .content { padding: 20px; border: 1px solid #ddd; }
                .footer { font-size: 12px; color: #777; margin-top: 20px; }
                .button { display: inline-block; background-color: #4a6fdc; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; }
                .warning { color: #d9534f; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Password Reset Request</h2>
                </div>
                <div class='content'>
                    <p>Hello {$user->getName()},</p>
                    <p>We received a request to reset your password. If you did not make this request, please ignore this email.</p>
                    <p>To reset your password, click the button below:</p>
                    <p><a href='{$resetUrl}' class='button'>Reset Password</a></p>
                    <p class='warning'>This link will expire in 24 hours.</p>
                </div>
                <div class='footer'>
                    <p>This is an automated message from the Bug Tracking System. Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}
