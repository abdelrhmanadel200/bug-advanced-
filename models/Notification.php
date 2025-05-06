<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

class Notification {
    private static function getMailer() {
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_ENCRYPTION;
        $mail->Port       = SMTP_PORT;
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        
        return $mail;
    }
    
    public static function sendBugStatusNotification($user, $bug, $status) {
        try {
            $mail = self::getMailer();
            
            // Recipients
            $mail->addAddress($user->getEmail(), $user->getName());
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = "Bug #{$bug->getTicketNumber()} Status Update";
            
            // Create HTML content
            $htmlContent = "
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
                        <p><strong>Project:</strong> " . self::getProjectName($bug->getProjectId()) . "</p>
                        <p><strong>Severity:</strong> " . ucfirst($bug->getSeverity()) . "</p>
                        <p><strong>Priority:</strong> " . ucfirst($bug->getPriority()) . "</p>
                        <p>You can view the full details of this bug by clicking the button below:</p>
                        <p><a href='" . getBaseUrl() . "/bugs/view.php?id={$bug->getId()}' class='btn'>View Bug Details</a></p>
                    </div>
                    <div class='footer'>
                        <p>This is an automated message. Please do not reply to this email.</p>
                        <p>Bug Tracking System</p>
                    </div>
                </div>
            </body>
            </html>
            ";
            
            $mail->Body = $htmlContent;
            
            // Plain text alternative
            $textContent = "Hello {$user->getName()},\n\n";
            $textContent .= "The status of bug #{$bug->getTicketNumber()} has been updated to " . ucfirst($status) . ".\n\n";
            $textContent .= "Bug Title: {$bug->getTitle()}\n";
            $textContent .= "Project: " . self::getProjectName($bug->getProjectId()) . "\n";
            $textContent .= "Severity: " . ucfirst($bug->getSeverity()) . "\n";
            $textContent .= "Priority: " . ucfirst($bug->getPriority()) . "\n\n";
            $textContent .= "You can view the full details of this bug at: " . getBaseUrl() . "/bugs/view.php?id={$bug->getId()}\n\n";
            $textContent .= "This is an automated message. Please do not reply to this email.\n\n";
            $textContent .= "Bug Tracking System";
            
            $mail->AltBody = $textContent;
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
            return false;
        }
    }
    
    public static function sendBugAssignmentNotification($staff, $bug) {
        try {
            $mail = self::getMailer();
            
            // Recipients
            $mail->addAddress($staff->getEmail(), $staff->getName());
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = "New Bug Assignment: #{$bug->getTicketNumber()}";
            
            // Create HTML content
            $htmlContent = "
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
                        <p><strong>Project:</strong> " . self::getProjectName($bug->getProjectId()) . "</p>
                        <p><strong>Severity:</strong> " . ucfirst($bug->getSeverity()) . "</p>
                        <p><strong>Priority:</strong> " . ucfirst($bug->getPriority()) . "</p>
                        <p>Please review this bug and update its status as you work on it.</p>
                        <p><a href='" . getBaseUrl() . "/bugs/view.php?id={$bug->getId()}' class='btn'>View Bug Details</a></p>
                    </div>
                    <div class='footer'>
                        <p>This is an automated message. Please do not reply to this email.</p>
                        <p>Bug Tracking System</p>
                    </div>
                </div>
            </body>
            </html>
            ";
            
            $mail->Body = $htmlContent;
            
            // Plain text alternative
            $textContent = "Hello {$staff->getName()},\n\n";
            $textContent .= "A new bug has been assigned to you:\n\n";
            $textContent .= "Bug ID: #{$bug->getTicketNumber()}\n";
            $textContent .= "Title: {$bug->getTitle()}\n";
            $textContent .= "Project: " . self::getProjectName($bug->getProjectId()) . "\n";
            $textContent .= "Severity: " . ucfirst($bug->getSeverity()) . "\n";
            $textContent .= "Priority: " . ucfirst($bug->getPriority()) . "\n\n";
            $textContent .= "Please review this bug and update its status as you work on it.\n\n";
            $textContent .= "You can view the full details at: " . getBaseUrl() . "/bugs/view.php?id={$bug->getId()}\n\n";
            $textContent .= "This is an automated message. Please do not reply to this email.\n\n";
            $textContent .= "Bug Tracking System";
            
            $mail->AltBody = $textContent;
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
            return false;
        }
    }
    
    public static function sendPasswordResetNotification($user, $resetToken) {
        try {
            $mail = self::getMailer();
            
            // Recipients
            $mail->addAddress($user->getEmail(), $user->getName());
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = "Password Reset Request";
            
            $resetUrl = getBaseUrl() . "/reset-password.php?token=" . $resetToken;
            
            // Create HTML content
            $htmlContent = "
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
            
            $mail->Body = $htmlContent;
            
            // Plain text alternative
            $textContent = "Hello {$user->getName()},\n\n";
            $textContent .= "We received a request to reset your password. If you didn't make this request, you can ignore this email.\n\n";
            $textContent .= "To reset your password, click the link below:\n\n";
            $textContent .= "{$resetUrl}\n\n";
            $textContent .= "This link will expire in 24 hours.\n\n";
            $textContent .= "This is an automated message. Please do not reply to this email.\n\n";
            $textContent .= "Bug Tracking System";
            
            $mail->AltBody = $textContent;
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
            return false;
        }
    }
    
    public static function sendNewUserNotification($user, $password) {
        try {
            $mail = self::getMailer();
            
            // Recipients
            $mail->addAddress($user->getEmail(), $user->getName());
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = "Welcome to Bug Tracking System";
            
            $loginUrl = getBaseUrl() . "/login.php";
            
            // Create HTML content
            $htmlContent = "
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
            
            $mail->Body = $htmlContent;
            
            // Plain text alternative
            $textContent = "Hello {$user->getName()},\n\n";
            $textContent .= "Your account has been created in the Bug Tracking System.\n\n";
            $textContent .= "Here are your login details:\n\n";
            $textContent .= "Email: {$user->getEmail()}\n";
            $textContent .= "Password: {$password}\n\n";
            $textContent .= "Please change your password after your first login.\n\n";
            $textContent .= "Login URL: {$loginUrl}\n\n";
            $textContent .= "This is an automated message. Please do not reply to this email.\n\n";
            $textContent .= "Bug Tracking System";
            
            $mail->AltBody = $textContent;
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
            return false;
        }
    }
    
    public static function sendAdminContactNotification($admin, $customer, $subject, $message) {
        try {
            $mail = self::getMailer();
            
            // Recipients
            $mail->addAddress($admin->getEmail(), $admin->getName());
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = "Customer Contact: {$subject}";
            
            // Create HTML content
            $htmlContent = "
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
                        <h2>Customer Contact</h2>
                    </div>
                    <div class='content'>
                        <p>Hello {$admin->getName()},</p>
                        <p>A customer has sent you a message:</p>
                        <p><strong>From:</strong> {$customer->getName()} ({$customer->getEmail()})</p>
                        <p><strong>Subject:</strong> {$subject}</p>
                        <p><strong>Message:</strong></p>
                        <div style='background-color: #f8f9fa; padding: 15px; border-left: 4px solid #007bff;'>
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
            
            $mail->Body = $htmlContent;
            
            // Plain text alternative
            $textContent = "Hello {$admin->getName()},\n\n";
            $textContent .= "A customer has sent you a message:\n\n";
            $textContent .= "From: {$customer->getName()} ({$customer->getEmail()})\n";
            $textContent .= "Subject: {$subject}\n\n";
            $textContent .= "Message:\n";
            $textContent .= "{$message}\n\n";
            $textContent .= "You can reply to this customer by sending an email to {$customer->getEmail()}.\n\n";
            $textContent .= "This is an automated message from the Bug Tracking System.";
            
            $mail->AltBody = $textContent;
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
            return false;
        }
    }
    
    private static function getProjectName($projectId) {
        global $db;
        
        $stmt = $db->prepare("SELECT name FROM projects WHERE id = ?");
        $stmt->execute([$projectId]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $project ? $project['name'] : 'Unknown Project';
    }
}
