<?php
require_once 'models/Administrator.php';
require_once 'models/Staff.php';
require_once 'models/Customer.php';
require_once 'models/BugTrackingSystem.php';

class UserController {
    private $system;
    
    public function __construct() {
        $this->system = BugTrackingSystem::getInstance();
    }
    
    public function list() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }
        
        // Check if user has permission to view user list
        if ($_SESSION['user_role'] !== 'administrator') {
            $_SESSION['errors'] = ['You do not have permission to view user list'];
            header('Location: index.php?controller=dashboard&action=index');
            exit;
        }
        
        global $db;
        
        // Apply filters if provided
        $where = "1=1";
        $params = [];
        
        if (isset($_GET['role']) && !empty($_GET['role'])) {
            $where .= " AND role = ?";
            $params[] = $_GET['role'];
        }
        
        if (isset($_GET['status']) && !empty($_GET['status'])) {
            $where .= " AND status = ?";
            $params[] = $_GET['status'];
        }
        
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $where .= " AND (name LIKE ? OR email LIKE ?)";
            $searchTerm = '%' . $_GET['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $stmt = $db->prepare("SELECT * FROM users WHERE {$where} ORDER BY created_at DESC");
        $stmt->execute($params);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Save filters in session for reset functionality
        $_SESSION['user_filters'] = [
            'role' => $_GET['role'] ?? '',
            'status' => $_GET['status'] ?? '',
            'search' => $_GET['search'] ?? ''
        ];
        
        // Display user list
        include 'views/users/list.php';
    }
    
    public function resetFilters() {
        // Clear filters from session
        if (isset($_SESSION['user_filters'])) {
            unset($_SESSION['user_filters']);
        }
        
        // Redirect to user list without filters
        header('Location: index.php?controller=user&action=list');
        exit;
    }
    
    public function profile() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }
        
        global $db;
        if (!$db) {
            require_once 'config/database.php';
        }
        
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            $_SESSION['errors'] = ['User not found'];
            header('Location: index.php?controller=dashboard&action=index');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'] ?? '';
            $email = $_POST['email'] ?? '';
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
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
            
            // Check if email is already in use by another user
            if ($email !== $user['email']) {
                $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $_SESSION['user_id']]);
                $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($existingUser) {
                    $errors[] = 'Email is already in use';
                }
            }
            
            // Check if password change is requested
            if (!empty($current_password)) {
                if (!password_verify($current_password, $user['password'])) {
                    $errors[] = 'Current password is incorrect';
                }
                
                if (empty($new_password)) {
                    $errors[] = 'New password is required';
                } elseif (strlen($new_password) < 6) {
                    $errors[] = 'New password must be at least 6 characters';
                }
                
                if ($new_password !== $confirm_password) {
                    $errors[] = 'Passwords do not match';
                }
            }
            
            if (empty($errors)) {
                // Create appropriate user object
                switch ($user['role']) {
                    case 'administrator':
                        $userObj = new Administrator($_SESSION['user_id'], $user['name'], $user['email'], $user['password']);
                        break;
                    case 'staff':
                        $userObj = new Staff($_SESSION['user_id'], $user['name'], $user['email'], $user['password']);
                        break;
                    case 'customer':
                        $userObj = new Customer($_SESSION['user_id'], $user['name'], $user['email'], $user['password']);
                        break;
                    default:
                        $_SESSION['errors'] = ['Invalid user role'];
                        header('Location: index.php?controller=dashboard&action=index');
                        exit;
                }
                
                $userObj->setName($name);
                $userObj->setEmail($email);
                
                // Update password if requested
                if (!empty($new_password)) {
                    $userObj->setPassword($new_password);
                }
                
                if ($userObj->save()) {
                    // Update session data
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $email;
                    
                    // Log activity
                    $this->system->logActivity($_SESSION['user_id'], 'update_profile', "Updated profile");
                    
                    $_SESSION['success'] = 'Profile updated successfully';
                    header('Location: index.php?controller=user&action=profile');
                    exit;
                } else {
                    $errors[] = 'Failed to update profile';
                }
            }
            
            $_SESSION['errors'] = $errors;
        }
        
        // Display user profile
        include 'views/users/profile.php';
    }
    
    public function edit() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }
        
        // Check if user has permission to edit users
        if ($_SESSION['user_role'] !== 'administrator') {
            $_SESSION['errors'] = ['You do not have permission to edit users'];
            header('Location: index.php?controller=dashboard&action=index');
            exit;
        }
        
        $id = $_GET['id'] ?? 0;
        
        if (!$id) {
            $_SESSION['errors'] = ['User not found'];
            header('Location: index.php?controller=user&action=list');
            exit;
        }
        
        global $db;
        
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$userData) {
            $_SESSION['errors'] = ['User not found'];
            header('Location: index.php?controller=user&action=list');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'] ?? '';
            $email = $_POST['email'] ?? '';
            $role = $_POST['role'] ?? '';
            $status = $_POST['status'] ?? '';
            $role = $_POST['role'] ?? '';
            $status = $_POST['status'] ?? '';
            
            $errors = [];
            
            if (empty($name)) {
                $errors[] = 'Name is required';
            }
            
            if (empty($email)) {
                $errors[] = 'Email is required';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Invalid email format';
            }
            
            if (empty($role)) {
                $errors[] = 'Role is required';
            }
            
            if (empty($status)) {
                $errors[] = 'Status is required';
            }
            
            // Check if email is already in use by another user
            if ($email !== $userData['email']) {
                $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $id]);
                $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($existingUser) {
                    $errors[] = 'Email is already in use';
                }
            }
            
            if (empty($errors)) {
                // Update user details
                $admin = new Administrator($_SESSION['user_id']);
                $details = [
                    'name' => $name,
                    'email' => $email,
                    'role' => $role,
                    'status' => $status
                ];
                
                if ($admin->updateUserDetails($id, $details)) {
                    // Log activity
                    $this->system->logActivity($_SESSION['user_id'], 'edit_user', "Edited user: {$name}");
                    
                    $_SESSION['success'] = 'User updated successfully';
                    header('Location: index.php?controller=user&action=list');
                    exit;
                } else {
                    $errors[] = 'Failed to update user';
                }
            }
            
            $_SESSION['errors'] = $errors;
        }
        
        // Display user edit form
        include 'views/users/edit.php';
    }
    
    public function addStaff() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }
        
        // Check if user has permission to add staff
        if ($_SESSION['user_role'] !== 'administrator') {
            $_SESSION['errors'] = ['You do not have permission to add staff'];
            header('Location: index.php?controller=dashboard&action=index');
            exit;
        }
        
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
            
            // Check if email is already in use
            global $db;
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingUser) {
                $errors[] = 'Email is already in use';
            }
            
            if (empty($errors)) {
                $admin = new Administrator($_SESSION['user_id']);
                
                if ($admin->addStaff($name, $email, $password)) {
                    // Log activity
                    $this->system->logActivity($_SESSION['user_id'], 'add_staff', "Added staff member: {$name}");
                    
                    $_SESSION['success'] = 'Staff member added successfully';
                    header('Location: index.php?controller=user&action=list');
                    exit;
                } else {
                    $errors[] = 'Failed to add staff member';
                }
            }
            
            $_SESSION['errors'] = $errors;
        }
        
        // Display add staff form
        include 'views/users/add-staff.php';
    }
    
    public function delete() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }
        
        // Check if user has permission to delete users
        if ($_SESSION['user_role'] !== 'administrator') {
            $_SESSION['errors'] = ['You do not have permission to delete users'];
            header('Location: index.php?controller=dashboard&action=index');
            exit;
        }
        
        $id = $_GET['id'] ?? 0;
        
        if (!$id) {
            $_SESSION['errors'] = ['User not found'];
            header('Location: index.php?controller=user&action=list');
            exit;
        }
        
        // Cannot delete yourself
        if ($id == $_SESSION['user_id']) {
            $_SESSION['errors'] = ['You cannot delete your own account'];
            header('Location: index.php?controller=user&action=list');
            exit;
        }
        
        global $db;
        
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            $_SESSION['errors'] = ['User not found'];
            header('Location: index.php?controller=user&action=list');
            exit;
        }
        
        $admin = new Administrator($_SESSION['user_id']);
        
        if ($admin->manageUser($id, 'delete')) {
            // Log activity
            $this->system->logActivity($_SESSION['user_id'], 'delete_user', "Deleted user: {$user['name']}");
            
            $_SESSION['success'] = 'User deleted successfully';
        } else {
            $_SESSION['errors'] = ['Failed to delete user'];
        }
        
        header('Location: index.php?controller=user&action=list');
        exit;
    }
    
    public function setActive() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }
        
        // Check if user has permission to manage user status
        if ($_SESSION['user_role'] !== 'administrator') {
            $_SESSION['errors'] = ['You do not have permission to manage user status'];
            header('Location: index.php?controller=dashboard&action=index');
            exit;
        }
        
        $id = $_GET['id'] ?? 0;
        
        if (!$id) {
            $_SESSION['errors'] = ['User not found'];
            header('Location: index.php?controller=user&action=list');
            exit;
        }
        
        global $db;
        
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            $_SESSION['errors'] = ['User not found'];
            header('Location: index.php?controller=user&action=list');
            exit;
        }
        
        $admin = new Administrator($_SESSION['user_id']);
        
        if ($admin->manageUser($id, 'activate')) {
            // Log activity
            $this->system->logActivity($_SESSION['user_id'], 'activate_user', "Activated user: {$user['name']}");
            
            $_SESSION['success'] = 'User activated successfully';
        } else {
            $_SESSION['errors'] = ['Failed to activate user'];
        }
        
        header('Location: index.php?controller=user&action=list');
        exit;
    }
    
    public function setInactive() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }
        
        // Check if user has permission to manage user status
        if ($_SESSION['user_role'] !== 'administrator') {
            $_SESSION['errors'] = ['You do not have permission to manage user status'];
            header('Location: index.php?controller=dashboard&action=index');
            exit;
        }
        
        $id = $_GET['id'] ?? 0;
        
        if (!$id) {
            $_SESSION['errors'] = ['User not found'];
            header('Location: index.php?controller=user&action=list');
            exit;
        }
        
        // Cannot deactivate yourself
        if ($id == $_SESSION['user_id']) {
            $_SESSION['errors'] = ['You cannot deactivate your own account'];
            header('Location: index.php?controller=user&action=list');
            exit;
        }
        
        global $db;
        
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            $_SESSION['errors'] = ['User not found'];
            header('Location: index.php?controller=user&action=list');
            exit;
        }
        
        $admin = new Administrator($_SESSION['user_id']);
        
        if ($admin->manageUser($id, 'deactivate')) {
            // Log activity
            $this->system->logActivity($_SESSION['user_id'], 'deactivate_user', "Deactivated user: {$user['name']}");
            
            $_SESSION['success'] = 'User deactivated successfully';
        } else {
            $_SESSION['errors'] = ['Failed to deactivate user'];
        }
        
        header('Location: index.php?controller=user&action=list');
        exit;
    }
    
    public function banUser() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }
        
        // Check if user has permission to manage user status
        if ($_SESSION['user_role'] !== 'administrator') {
            $_SESSION['errors'] = ['You do not have permission to manage user status'];
            header('Location: index.php?controller=dashboard&action=index');
            exit;
        }
        
        $id = $_GET['id'] ?? 0;
        
        if (!$id) {
            $_SESSION['errors'] = ['User not found'];
            header('Location: index.php?controller=user&action=list');
            exit;
        }
        
        // Cannot ban yourself
        if ($id == $_SESSION['user_id']) {
            $_SESSION['errors'] = ['You cannot ban your own account'];
            header('Location: index.php?controller=user&action=list');
            exit;
        }
        
        global $db;
        
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            $_SESSION['errors'] = ['User not found'];
            header('Location: index.php?controller=user&action=list');
            exit;
        }
        
        $admin = new Administrator($_SESSION['user_id']);
        
        if ($admin->manageUser($id, 'ban')) {
            // Log activity
            $this->system->logActivity($_SESSION['user_id'], 'ban_user', "Banned user: {$user['name']}");
            
            $_SESSION['success'] = 'User banned successfully';
        } else {
            $_SESSION['errors'] = ['Failed to ban user'];
        }
        
        header('Location: index.php?controller=user&action=list');
        exit;
    }
    
    public function resetPassword() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }
        
        // Check if user has permission to reset passwords
        if ($_SESSION['user_role'] !== 'administrator') {
            $_SESSION['errors'] = ['You do not have permission to reset passwords'];
            header('Location: index.php?controller=dashboard&action=index');
            exit;
        }
        
        $id = $_GET['id'] ?? 0;
        
        if (!$id) {
            $_SESSION['errors'] = ['User not found'];
            header('Location: index.php?controller=user&action=list');
            exit;
        }
        
        global $db;
        if (!$db) {
            require_once 'config/database.php';
        }
        
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$userData) {
            $_SESSION['errors'] = ['User not found'];
            header('Location: index.php?controller=user&action=list');
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
                $admin = new Administrator($_SESSION['user_id']);
                
                if ($admin->resetUserPassword($id, $password)) {
                    // Log activity
                    $this->system->logActivity($_SESSION['user_id'], 'reset_password', "Reset password for user: {$userData['name']}");
                    
                    $_SESSION['success'] = 'Password reset successfully';
                    header('Location: index.php?controller=user&action=list');
                    exit;
                } else {
                    $errors[] = 'Failed to reset password';
                }
            }
            
            $_SESSION['errors'] = $errors;
        }
        
        // Display reset password form
        include 'views/users/reset-password.php';
    }
    
    public function activity() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }
        
        // Check if user has permission to view activity logs
        if ($_SESSION['user_role'] !== 'administrator') {
            $_SESSION['errors'] = ['You do not have permission to view activity logs'];
            header('Location: index.php?controller=dashboard&action=index');
            exit;
        }
        
        $id = $_GET['id'] ?? 0;
        
        if (!$id) {
            $_SESSION['errors'] = ['User not found'];
            header('Location: index.php?controller=user&action=list');
            exit;
        }
        
        global $db;
        
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$userData) {
            $_SESSION['errors'] = ['User not found'];
            header('Location: index.php?controller=user&action=list');
            exit;
        }
        
        // Get activity logs
        $stmt = $db->prepare("SELECT * FROM activity_log WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$id]);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Display activity logs
        include 'views/users/activity.php';
    }
    
    public function contactAdmin() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }
        
        // Only customers can contact admin
        if ($_SESSION['user_role'] !== 'customer') {
            $_SESSION['errors'] = ['Only customers can contact administrators'];
            header('Location: index.php?controller=dashboard&action=index');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $subject = $_POST['subject'] ?? '';
            $message = $_POST['message'] ?? '';
            
            $errors = [];
            
            if (empty($subject)) {
                $errors[] = 'Subject is required';
            }
            
            if (empty($message)) {
                $errors[] = 'Message is required';
            }
            
            if (empty($errors)) {
                $customer = new Customer($_SESSION['user_id']);
                
                if ($customer->contactAdmin($subject, $message)) {
                    // Log activity
                    $this->system->logActivity($_SESSION['user_id'], 'contact_admin', "Sent message to administrator");
                    
                    $_SESSION['success'] = 'Message sent successfully';
                    header('Location: index.php?controller=dashboard&action=index');
                    exit;
                } else {
                    $errors[] = 'Failed to send message';
                }
            }
            
            $_SESSION['errors'] = $errors;
        }
        
        // Display contact admin form
        include 'views/users/contact-admin.php';
    }
}
