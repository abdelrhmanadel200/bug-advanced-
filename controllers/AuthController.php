<?php
require_once 'models/Administrator.php';
require_once 'models/Staff.php';
require_once 'models/Customer.php';
require_once 'models/BugTrackingSystem.php';
require_once 'services/NotificationService.php';

class AuthController {
    private $system;
    private $notificationService;
    
    public function __construct() {
        $this->system = BugTrackingSystem::getInstance();
        $this->notificationService = NotificationService::getInstance();
    }
    
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            
            if (empty($email) || empty($password)) {
                $_SESSION['errors'] = ['Email and password are required'];
                return false;
            }
            
            // Try to login as administrator
            $admin = new Administrator();
            if ($admin->login($email, $password)) {
                $_SESSION['user_id'] = $admin->getId();
                $_SESSION['user_name'] = $admin->getName();
                $_SESSION['user_email'] = $admin->getEmail();
                $_SESSION['user_role'] = $admin->getRole();
                
                // Log activity
                $this->system->logActivity($admin->getId(), 'login', 'Administrator logged in');
                
                // Redirect to dashboard
                header('Location: index.php?controller=dashboard&action=index');
                exit;
            }
            
            // Try to login as staff
            $staff = new Staff();
            if ($staff->login($email, $password)) {
                $_SESSION['user_id'] = $staff->getId();
                $_SESSION['user_name'] = $staff->getName();
                $_SESSION['user_email'] = $staff->getEmail();
                $_SESSION['user_role'] = $staff->getRole();
                
                // Log activity
                $this->system->logActivity($staff->getId(), 'login', 'Staff logged in');
                
                // Redirect to dashboard
                header('Location: index.php?controller=dashboard&action=index');
                exit;
            }
            
            // Try to login as customer
            $customer = new Customer();
            if ($customer->login($email, $password)) {
                $_SESSION['user_id'] = $customer->getId();
                $_SESSION['user_name'] = $customer->getName();
                $_SESSION['user_email'] = $customer->getEmail();
                $_SESSION['user_role'] = $customer->getRole();
                
                // Log activity
                $this->system->logActivity($customer->getId(), 'login', 'Customer logged in');
                
                // Redirect to dashboard
                header('Location: index.php?controller=dashboard&action=index');
                exit;
            }
            
            $_SESSION['errors'] = ['Invalid email or password'];
        }
        
        // Display login form
        include 'views/auth/login.php';
    }
    
    public function register() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            $errors = [];
            
            if (empty($name)) {
                $errors[] = 'Name is required';
            }
            
            if (empty($email)) {
                $errors[] = 'Email is required';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Invalid email format';
            }
            
            if (empty($password)) {
                $errors[] = 'Password is required';
            } elseif (strlen($password) < 6) {
                $errors[] = 'Password must be at least 6 characters';
            }
            
            if ($password !== $confirm_password) {
                $errors[] = 'Passwords do not match';
            }
            
            if (empty($errors)) {
                $customer = new Customer(null, $name, $email, $password);
                $customer->setStatus('active');
                $customer->setCreatedAt(date('Y-m-d H:i:s'));
                $customer->setUpdatedAt(date('Y-m-d H:i:s'));
                
                if ($customer->register()) {
                    // Get the new customer ID
                    $customerId = $customer->getId();
                    
                    // Log activity with the new customer ID or use 0 if not available
                    $this->system->logActivity($customerId ?: 0, 'register', "New customer registered: {$name}");
                    
                    // Notify administrators about new registration
                    global $db;
                    if (!$db) {
                        require_once 'config/database.php';
                    }
                    
                    $stmt = $db->prepare("SELECT * FROM users WHERE role = 'administrator' AND status = 'active'");
                    $stmt->execute();
                    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach ($admins as $adminData) {
                        $message = "New customer registered: {$name} ({$email})";
                        $link = "index.php?controller=user&action=view&id=" . $customer->getId();
                        $this->notificationService->createInAppNotification($adminData['id'], $message, 'info', $link);
                    }
                    
                    $_SESSION['success'] = 'Registration successful. You can now login.';
                    header('Location: index.php?controller=auth&action=login');
                    exit;
                } else {
                    $errors[] = 'Email already exists or registration failed';
                }
            }
            
            $_SESSION['errors'] = $errors;
        }
        
        // Display registration form
        include 'views/auth/register.php';
    }
    
    public function logout() {
        // Log activity before destroying session
        if (isset($_SESSION['user_id'])) {
            $this->system->logActivity($_SESSION['user_id'], 'logout', 'User logged out');
        }
        
        // Destroy session
        session_destroy();
        
        // Redirect to login page
        header('Location: index.php?controller=auth&action=login');
        exit;
    }
    
    public function forgotPassword() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            
            if (empty($email)) {
                $_SESSION['errors'] = ['Email is required'];
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['errors'] = ['Invalid email format'];
            } else {
                global $db;
                if (!$db) {
                    require_once 'config/database.php';
                }
                
                $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    // Generate reset token
                    $token = bin2hex(random_bytes(32));
                    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                    
                    // Check if password_resets table exists, if not create it
                    try {
                        // First, check if the table exists
                        $tableExists = false;
                        try {
                            $checkTable = $db->query("SELECT 1 FROM password_resets LIMIT 1");
                            $tableExists = true;
                        } catch (PDOException $e) {
                            $tableExists = false;
                        }
                        
                        if (!$tableExists) {
                            // Create the table if it doesn't exist
                            $db->exec("CREATE TABLE IF NOT EXISTS password_resets (
                                id INT AUTO_INCREMENT PRIMARY KEY,
                                email VARCHAR(100) NOT NULL,
                                token VARCHAR(255) NOT NULL,
                                expires_at DATETIME NOT NULL,
                                created_at DATETIME NOT NULL
                            )");
                        }
                        
                        // Now insert the reset token
                        $stmt = $db->prepare("INSERT INTO password_resets (email, token, expires_at, created_at) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$email, $token, $expires, date('Y-m-d H:i:s')]);
                    } catch (PDOException $e) {
                        // Log the error
                        error_log("Error in forgotPassword: " . $e->getMessage());
                        $_SESSION['errors'] = ['An error occurred. Please try again later.'];
                        header('Location: index.php?controller=auth&action=forgotPassword');
                        exit;
                    }
                    
                    // Create user object based on role
                    switch ($user['role']) {
                        case 'administrator':
                            $userObj = new Administrator($user['id'], $user['name'], $user['email'], $user['password']);
                            break;
                        case 'staff':
                            $userObj = new Staff($user['id'], $user['name'], $user['email'], $user['password']);
                            break;
                        case 'customer':
                            $userObj = new Customer($user['id'], $user['name'], $user['email'], $user['password']);
                            break;
                    }
                    
                    // Send password reset notification
                    $this->notificationService->notifyPasswordReset($userObj, $token);
                    
                    $_SESSION['success'] = 'Password reset instructions have been sent to your email.';
                } else {
                    // Don't reveal that the email doesn't exist
                    $_SESSION['success'] = 'Password reset instructions have been sent to your email if it exists in our system.';
                }
                
                header('Location: index.php?controller=auth&action=login');
                exit;
            }
        }
        
        // Display forgot password form
        include 'views/auth/forgot-password.php';
    }
    
    public function resetPassword() {
        $token = $_GET['token'] ?? '';
        
        if (empty($token)) {
            $_SESSION['errors'] = ['Invalid token'];
            header('Location: index.php?controller=auth&action=login');
            exit;
        }
        
        global $db;
        if (!$db) {
            require_once 'config/database.php';
        }
        
        // Check if password_resets table exists
        try {
            $tableExists = false;
            try {
                $checkTable = $db->query("SELECT 1 FROM password_resets LIMIT 1");
                $tableExists = true;
            } catch (PDOException $e) {
                $tableExists = false;
            }
            
            if (!$tableExists) {
                $_SESSION['errors'] = ['Invalid or expired token'];
                header('Location: index.php?controller=auth&action=login');
                exit;
            }
            
            $stmt = $db->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()");
            $stmt->execute([$token]);
            $reset = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$reset) {
                $_SESSION['errors'] = ['Invalid or expired token'];
                header('Location: index.php?controller=auth&action=login');
                exit;
            }
        } catch (PDOException $e) {
            // Log the error
            error_log("Error in resetPassword: " . $e->getMessage());
            $_SESSION['errors'] = ['An error occurred. Please try again later.'];
            header('Location: index.php?controller=auth&action=login');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            $errors = [];
            
            if (empty($password)) {
                $errors[] = 'Password is required';
            } elseif (strlen($password) < 6) {
                $errors[] = 'Password must be at least 6 characters';
            }
            
            if ($password !== $confirm_password) {
                $errors[] = 'Passwords do not match';
            }
            
            if (empty($errors)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $db->prepare("UPDATE users SET password = ? WHERE email = ?");
                $stmt->execute([$hashed_password, $reset['email']]);
                
                $stmt = $db->prepare("DELETE FROM password_resets WHERE email = ?");
                $stmt->execute([$reset['email']]);
                
                // Get user details for notification
                $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
                $stmt->execute([$reset['email']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    // Create notification
                    $message = "Your password has been reset successfully.";
                    $link = "index.php?controller=auth&action=login";
                    $this->notificationService->createInAppNotification($user['id'], $message, 'success', $link);
                    
                    // Log activity
                    $this->system->logActivity($user['id'], 'reset_password', 'Password reset successfully');
                }
                
                $_SESSION['success'] = 'Password has been reset successfully. You can now login with your new password.';
                header('Location: index.php?controller=auth&action=login');
                exit;
            }
            
            $_SESSION['errors'] = $errors;
        }
        
        // Display reset password form
        include 'views/auth/reset-password.php';
    }
}
