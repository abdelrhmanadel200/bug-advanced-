<?php
// Function to get the base URL of the application
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['SCRIPT_NAME']);
    $path = $path === '/' ? '' : $path;
    
    return "{$protocol}://{$host}{$path}";
}

// Function to format time ago
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'just now';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 2592000) {
        $weeks = floor($diff / 604800);
        return $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 31536000) {
        $months = floor($diff / 2592000);
        return $months . ' month' . ($months > 1 ? 's' : '') . ' ago';
    } else {
        $years = floor($diff / 31536000);
        return $years . ' year' . ($years > 1 ? 's' : '') . ' ago';
    }
}

// Function to sanitize input
function sanitize($input) {
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to check if user has a specific role
function hasRole($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

// Function to redirect to a specific page
function redirect($page) {
    header("Location: {$page}");
    exit;
}

// Function to display error messages
function displayErrors($errors) {
    if (!empty($errors)) {
        echo '<div class="alert alert-danger">';
        echo '<ul class="mb-0">';
        foreach ($errors as $error) {
            echo '<li>' . sanitize($error) . '</li>';
        }
        echo '</ul>';
        echo '</div>';
    }
}

// Function to display success messages
function displaySuccess($message) {
    if (!empty($message)) {
        echo '<div class="alert alert-success">';
        echo sanitize($message);
        echo '</div>';
    }
}

// Function to log activity
function logActivity($userId, $action, $details = null) {
    global $db;
    
    $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, details, created_at) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$userId, $action, $details, date('Y-m-d H:i:s')]);
}
