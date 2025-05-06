<?php
// Sanitize input data
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Generate random string
function generateRandomString($length = 10) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

// Generate ticket number for bugs
function generateTicketNumber() {
    $prefix = 'BUG';
    $timestamp = time();
    $random = generateRandomString(4);
    return $prefix . '-' . $timestamp . '-' . $random;
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check user role
function hasRole($role) {
    if (!isLoggedIn()) {
        return false;
    }
    
    return $_SESSION['user_role'] === $role;
}

// Redirect to URL
function redirect($url) {
    header("Location: $url");
    exit;
}

// Flash messages
function setFlashMessage($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Format date
function formatDate($date) {
    return date('M d, Y h:i A', strtotime($date));
}