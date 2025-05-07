<?php
require_once 'config/config.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class NotificationService {
    private static $instance = null;
    private $mailer;
    
    private function __construct() {
        // Initialize PHPMailer
        if (defined('SMTP_HOST') && SMTP_HOST) {
            require_once 'vendor/autoload.php';
            
            $this->mailer = new PHPMailer(true);
            $this->mailer->isSMTP();
            $this->mailer->Host = SMTP_HOST;
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = SMTP_USERNAME;
            $this->mailer->Password = SMTP_PASSWORD;
             $this->mailer->Port = SMTP_PORT;
            $this->mailer->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $this->mailer->isHTML(true);
        } else {
            require_once 'vendor/autoload.php';
            
            $this->mailer = new PHPMailer(true);
            $this->mailer->isSMTP();
            $this->mailer->Host = 'smtp.gmail.com'; // Update with your SMTP server
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = 'your-email@gmail.com'; // Update with your email
            $this->mailer->Password = 'your-password'; // Update with your password or app password
            $this->mailer->SMTPSecure = 'tls';
            $this->mailer->Port = 587;
            $this->mailer->setFrom('your-email@gmail.com', 'Bug Tracking System'); // Update with your email and name
        }
    }
    
    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new NotificationService();
        }
        return self::$instance;
    }
    
    // Method to get all notifications
    public function getAllNotifications() {
        global $db;
        
        if (!$db) {
            // If $db is still null, try to reconnect
            require_once 'config/database.php';
        }
        
        $stmt = $db->prepare("SELECT * FROM notifications ORDER BY created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Create a database notification (renamed from createNotification to match the expected method name)
    public function createInAppNotification($userId, $message, $type = 'info', $link = null) {
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
    
    // Get all notifications for a user (renamed from getAllNotifications to match the expected method name)
    public function getUserNotifications($userId, $limit = 10, $offset = 0) {
        global $db;
        
        $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $stmt->execute([$userId, $limit, $offset]);
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
    public function sendEmail($to, $toName, $subject, $body, $textBody = '') {
        if (!$this->mailer) {
            return false;
        }
        
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($to, $toName);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;
            $this->mailer->AltBody = $textBody ?: strip_tags($body);
            
            return $this->mailer->send();
        } catch (Exception $e) {
            error_log('Email could not be sent. Mailer Error: ' . $this->mailer->ErrorInfo);
            return false;
        }
    }
    
    // Notify about bug assignment
    public function notifyBugAssignment($staff, $bug) {
        // Create in-app notification
        $message = "Bug #{$bug->getTicketNumber()} has been assigned to you";
        $link = "index.php?controller=bug&action=view&id=" . $bug->getId();
        $this->createInAppNotification($staff->getId(), $message, 'info', $link);
        
        // Send email notification
        if ($this->mailer) {
            $subject = "New Bug Assignment: #{$bug->getTicketNumber()}";
            $body = $this->getBugAssignmentEmailTemplate($staff, $bug);
            $textBody = $this->getBugAssignmentTextEmail($staff, $bug);
            $this->sendEmail($staff->getEmail(), $staff->getName(), $subject, $body, $textBody);
        }
        
        return true;
    }
    
    // Notify about bug status change - fixed parameter count
    public function notifyBugStatusChange($user, $bug, $status, $oldStatus = '') {
        // Create in-app notification
        $message = "Bug #{$bug->getTicketNumber()} status has been updated to " . ucfirst($status);
        $link = "index.php?controller=bug&action=view&id=" . $bug->getId();
        $this->createInAppNotification($user->getId(), $message, 'info', $link);
        
        // Send email notification
        if ($this->mailer) {
            $subject = "Bug #{$bug->getTicketNumber()} Status Update";
            $body = $this->getBugStatusEmailTemplate($user, $bug, $status);
            $textBody = $this->getBugStatusTextEmail($user, $bug, $status);
            $this->sendEmail($user->getEmail(), $user->getName(), $subject, $body, $textBody);
        }
        
        return true;
    }
    
    // Notify about new comment
    public function notifyNewComment($user, $bug, $commentContent, $commenter) {
        // Create in-app notification
        $message = "{$commenter->getName()} commented on bug #{$bug->getTicketNumber()}";
        $link = "index.php?controller=bug&action=view&id=" . $bug->getId() . "#comments";
        $this->createInAppNotification($user->getId(), $message, 'comment', $link);
        
        // Send email notification
        if ($this->mailer) {
            $subject = "New Comment on Bug #{$bug->getTicketNumber()}";
            $body = $this->getNewCommentEmailTemplate($user, $bug, $commentContent, $commenter);
            $textBody = $this->getNewCommentTextEmail($user, $bug, $commentContent, $commenter);
            $this->sendEmail($user->getEmail(), $user->getName(), $subject, $body, $textBody);
        }
        
        return true;
    }
    
    // Notify about password reset
    public function notifyPasswordReset($user, $resetToken) {
        $subject = "Password Reset Request";
        $resetUrl = getBaseUrl() . "/index.php?controller=auth&action=resetPassword&token=" . $resetToken;
        
        // HTML email
        $body = $this->getPasswordResetEmailTemplate($user, $resetUrl);
        
        // Plain text email
        $textBody = $this->getPasswordResetTextEmail($user, $resetUrl);
        
        // Send email
        return $this->sendEmail($user->getEmail(), $user->getName(), $subject, $body, $textBody);
    }
    
    // Notify new user about account creation
    public function notifyNewUserCreation($user, $password) {
        $subject = "Welcome to Bug Tracking System";
        $loginUrl = getBaseUrl() . "/index.php?controller=auth&action=login";
        
        // HTML email
        $body = $this->getNewUserEmailTemplate($user, $password, $loginUrl);
        
        // Plain text email
        $textBody = $this->getNewUserTextEmail($user, $password, $loginUrl);
        
        // Send email
        return $this->sendEmail($user->getEmail(), $user->getName(), $subject, $body, $textBody);
    }
    
    // Notify admin about customer contact
    public function notifyAdminContact($admin, $customer, $subject, $message) {
        // Create in-app notification
        $notificationMessage = "New message from {$customer->getName()}: " . substr($subject, 0, 30) . (strlen($subject) > 30 ? '...' : '');
        $link = "index.php?controller=message&action=view&id=" . $this->getLastMessageId($customer->getId());
        $this->createInAppNotification($admin->getId(), $notificationMessage, 'message', $link);
        
        // Email subject
        $emailSubject = "Customer Contact: {$subject}";
        
        // HTML email
        $body = $this->getAdminContactEmailTemplate($admin, $customer, $subject, $message);
        
        // Plain text email
        $textBody = $this->getAdminContactTextEmail($admin, $customer, $subject, $message);
        
        // Send email
        return $this->sendEmail($admin->getEmail(), $admin->getName(), $emailSubject, $body, $textBody);
    }
    
    // Get the last message ID from a sender
    private function getLastMessageId($senderId) {
        global $db;
        
        $stmt = $db->prepare("SELECT id FROM messages WHERE sender_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$senderId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $result['id'] : 0;
    }
    
    // Email templates
    private function getBugAssignmentEmailTemplate($staff, $bug) {
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #f8f9fa; padding: 15px; border-bottom: 3px solid #007bff; }
                .content { padding: 20px 0; }
                .footer { font-size: 12px; color: #6c757d; border-top: 1px solid #dee2e6; padding-top: 15px; }
                .btn { display: inline-block; padding: 10px 20px; background-color: #007bff; color: #fff; text-decoration: none; border-radius: 4px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>New Bug Assignment</h2>
                </div>
                <div class='content'>
                    <p>Hello {$staff->getName()},</p>
                    <p>A new bug has been assigned to you:</p>
                    <p><strong>Bug ID:</strong> #{$bug->getTicketNumber()}</p>
                    <p><strong>Title:</strong> {$bug->getTitle()}</p>
                    <p><strong>Severity:</strong> " . ucfirst($bug->getSeverity()) . "</p>
                    <p><strong>Priority:</strong> " . ucfirst($bug->getPriority()) . "</p>
                    <p>Please review this bug and update its status as you work on it.</p>
                    <p><a href='" . getBaseUrl() . "/index.php?controller=bug&action=view&id={$bug->getId()}' class='btn'>View Bug Details</a></p>
                </div>
                <div class='footer'>
                    <p>This is an automated message. Please do not reply to this email.</p>
                    <p>Bug Tracking System</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    private function getBugAssignmentTextEmail($staff, $bug) {
        $text = "Hello {$staff->getName()},\n\n";
        $text .= "A new bug has been assigned to you:\n\n";
        $text .= "Bug ID: #{$bug->getTicketNumber()}\n";
        $text .= "Title: {$bug->getTitle()}\n";
        $text .= "Severity: " . ucfirst($bug->getSeverity()) . "\n";
        $text .= "Priority: " . ucfirst($bug->getPriority()) . "\n\n";
        $text .= "Please review this bug and update its status as you work on it.\n\n";
        $text .= "You can view the full details at: " . getBaseUrl() . "/index.php?controller=bug&action=view&id={$bug->getId()}\n\n";
        $text .= "This is an automated message. Please do not reply to this email.\n\n";
        $text .= "Bug Tracking System";
        
        return $text;
    }
    
    private function getBugStatusEmailTemplate($user, $bug, $status) {
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #f8f9fa; padding: 15px; border-bottom: 3px solid #007bff; }
                .content { padding: 20px 0; }
                .footer { font-size: 12px; color: #6c757d; border-top: 1px solid #dee2e6; padding-top: 15px; }
                .btn { display: inline-block; padding: 10px 20px; background-color: #007bff; color: #fff; text-decoration: none; border-radius: 4px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Bug Status Update</h2>
                </div>
                <div class='content'>
                    <p>Hello {$user->getName()},</p>
                    <p>The status of bug <strong>#{$bug->getTicketNumber()}</strong> has been updated to <strong>" . ucfirst($status) . "</strong>.</p>
                    <p><strong>Bug Title:</strong> {$bug->getTitle()}</p>
                    <p><strong>Severity:</strong> " . ucfirst($bug->getSeverity()) . "</p>
                    <p><strong>Priority:</strong> " . ucfirst($bug->getPriority()) . "</p>
                    <p>You can view the full details of this bug by clicking the button below:</p>
                    <p><a href='" . getBaseUrl() . "/index.php?controller=bug&action=view&id={$bug->getId()}' class='btn'>View Bug Details</a></p>
                </div>
                <div class='footer'>
                    <p>This is an automated message. Please do not reply to this email.</p>
                    <p>Bug Tracking System</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    private function getBugStatusTextEmail($user, $bug, $status) {
        $text = "Hello {$user->getName()},\n\n";
        $text .= "The status of bug #{$bug->getTicketNumber()} has been updated to " . ucfirst($status) . ".\n\n";
        $text .= "Bug Title: {$bug->getTitle()}\n";
        $text .= "Severity: " . ucfirst($bug->getSeverity()) . "\n";
        $text .= "Priority: " . ucfirst($bug->getPriority()) . "\n\n";
        $text .= "You can view the full details at: " . getBaseUrl() . "/index.php?controller=bug&action=view&id={$bug->getId()}\n\n";
        $text .= "This is an automated message. Please do not reply to this email.\n\n";
        $text .= "Bug Tracking System";
        
        return $text;
    }
    
    private function getNewCommentEmailTemplate($user, $bug, $commentContent, $commenter) {
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #f8f9fa; padding: 15px; border-bottom: 3px solid #007bff; }
                .content { padding: 20px 0; }
                .footer { font-size: 12px; color: #6c757d; border-top: 1px solid #dee2e6; padding-top: 15px; }
                .btn { display: inline-block; padding: 10px 20px; background-color: #007bff; color: #fff; text-decoration: none; border-radius: 4px; }
                .comment-box { background-color: #f8f9fa; padding: 15px; border-left: 4px solid #007bff; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>New Comment on Bug #{$bug->getTicketNumber()}</h2>
                </div>
                <div class='content'>
                    <p>Hello {$user->getName()},</p>
                    <p>{$commenter->getName()} has added a comment to bug <strong>#{$bug->getTicketNumber()}: {$bug->getTitle()}</strong>.</p>
                    <p><strong>Comment:</strong></p>
                    <div class='comment-box'>
                        " . nl2br(htmlspecialchars($commentContent)) . "
                    </div>
                    <p>You can view the full bug details and all comments by clicking the button below:</p>
                    <p><a href='" . getBaseUrl() . "/index.php?controller=bug&action=view&id={$bug->getId()}#comments' class='btn'>View Comments</a></p>
                </div>
                <div class='footer'>
                    <p>This is an automated message. Please do not reply to this email.</p>
                    <p>Bug Tracking System</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    private function getNewCommentTextEmail($user, $bug, $commentContent, $commenter) {
        $text = "Hello {$user->getName()},\n\n";
        $text .= "{$commenter->getName()} has added a comment to bug #{$bug->getTicketNumber()}: {$bug->getTitle()}.\n\n";
        $text .= "Comment:\n";
        $text .= "{$commentContent}\n\n";
        $text .= "You can view the full bug details and all comments at: " . getBaseUrl() . "/index.php?controller=bug&action=view&id={$bug->getId()}#comments\n\n";
        $text .= "This is an automated message. Please do not reply to this email.\n\n";
        $text .= "Bug Tracking System";
        
        return $text;
    }
    
    private function getPasswordResetEmailTemplate($user, $resetUrl) {
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #f8f9fa; padding: 15px; border-bottom: 3px solid #007bff; }
                .content { padding: 20px 0; }
                .footer { font-size: 12px; color: #6c757d; border-top: 1px solid #dee2e6; padding-top: 15px; }
                .btn { display: inline-block; padding: 10px 20px; background-color: #007bff; color: #fff; text-decoration: none; border-radius: 4px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Password Reset Request</h2>
                </div>
                <div class='content'>
                    <p>Hello {$user->getName()},</p>
                    <p>We received a request to reset your password. If you didn't make this request, you can ignore this email.</p>
                    <p>To reset your password, click the button below:</p>
                    <p><a href='{$resetUrl}' class='btn'>Reset Password</a></p>
                    <p>Or copy and paste this URL into your browser:</p>
                    <p>{$resetUrl}</p>
                    <p>This link will expire in 24 hours.</p>
                </div>
                <div class='footer'>
                    <p>This is an automated message. Please do not reply to this email.</p>
                    <p>Bug Tracking System</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    private function getPasswordResetTextEmail($user, $resetUrl) {
        $text = "Hello {$user->getName()},\n\n";
        $text .= "We received a request to reset your password. If you didn't make this request, you can ignore this email.\n\n";
        $text .= "To reset your password, click the link below:\n\n";
        $text .= "{$resetUrl}\n\n";
        $text .= "This link will expire in 24 hours.\n\n";
        $text .= "This is an automated message. Please do not reply to this email.\n\n";
        $text .= "Bug Tracking System";
        
        return $text;
    }
    
    private function getNewUserEmailTemplate($user, $password, $loginUrl) {
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #f8f9fa; padding: 15px; border-bottom: 3px solid #007bff; }
                .content { padding: 20px 0; }
                .footer { font-size: 12px; color: #6c757d; border-top: 1px solid #dee2e6; padding-top: 15px; }
                .btn { display: inline-block; padding: 10px 20px; background-color: #007bff; color: #fff; text-decoration: none; border-radius: 4px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Welcome to Bug Tracking System</h2>
                </div>
                <div class='content'>
                    <p>Hello {$user->getName()},</p>
                    <p>Your account has been created in the Bug Tracking System.</p>
                    <p>Here are your login details:</p>
                    <p><strong>Email:</strong> {$user->getEmail()}</p>
                    <p><strong>Password:</strong> {$password}</p>
                    <p>Please change your password after your first login.</p>
                    <p><a href='{$loginUrl}' class='btn'>Login to Your Account</a></p>
                </div>
                <div class='footer'>
                    <p>This is an automated message. Please do not reply to this email.</p>
                    <p>Bug Tracking System</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    private function getNewUserTextEmail($user, $password, $loginUrl) {
        $text = "Hello {$user->getName()},\n\n";
        $text .= "Your account has been created in the Bug Tracking System.\n\n";
        $text .= "Here are your login details:\n\n";
        $text .= "Email: {$user->getEmail()}\n";
        $text .= "Password: {$password}\n\n";
        $text .= "Please change your password after your first login.\n\n";
        $text .= "Login URL: {$loginUrl}\n\n";
        $text .= "This is an automated message. Please do not reply to this email.\n\n";
        $text .= "Bug Tracking System";
        
        return $text;
    }
    
    private function getAdminContactEmailTemplate($admin, $customer, $subject, $message) {
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #f8f9fa; padding: 15px; border-bottom: 3px solid #007bff; }
                .content { padding: 20px 0; }
                .footer { font-size: 12px; color: #6c757d; border-top: 1px solid #dee2e6; padding-top: 15px; }
                .btn { display: inline-block; padding: 10px 20px; background-color: #007bff; color: #fff; text-decoration: none; border-radius: 4px; }
                .message-box { background-color: #f8f9fa; padding: 15px; border-left: 4px solid #007bff; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Customer Contact</h2>
                </div>
                <div class='content'>
                    <p>Hello {$admin->getName()},</p>
                    <p>A customer has sent you a message:</p>
                    <p><strong>From:</strong> {$customer->getName()} ({$customer->getEmail()})</p>
                    <p><strong>Subject:</strong> {$subject}</p>
                    <p><strong>Message:</strong></p>
                    <div class='message-box'>
                        " . nl2br(htmlspecialchars($message)) . "
                    </div>
                    <p>You can reply to this customer by sending an email to {$customer->getEmail()}.</p>
                </div>
                <div class='footer'>
                    <p>This is an automated message from the Bug Tracking System.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    private function getAdminContactTextEmail($admin, $customer, $subject, $message) {
        $text = "Hello {$admin->getName()},\n\n";
        $text .= "A customer has sent you a message:\n\n";
        $text .= "From: {$customer->getName()} ({$customer->getEmail()})\n";
        $text .= "Subject: {$subject}\n\n";
        $text .= "Message:\n";
        $text .= "{$message}\n\n";
        $text .= "You can reply to this customer by sending an email to {$customer->getEmail()}.\n\n";
        $text .= "This is an automated message from the Bug Tracking System.";
        
        return $text;
    }
}
