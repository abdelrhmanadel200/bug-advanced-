<?php
session_start();

// Load database configuration first
require_once 'config/database.php';

// Load helper functions
require_once 'config/functions.php';

// Define controllers
$controllers = [
    'auth' => 'AuthController',
    'dashboard' => 'DashboardController',
    'bug' => 'BugController',
    'project' => 'ProjectController',
    'user' => 'UserController',
    'github' => 'GitHubController',
    'notification' => 'NotificationController'
];

// Get controller and action from URL
$controller = $_GET['controller'] ?? 'auth';
$action = $_GET['action'] ?? 'login';

// Check if controller exists
if (!isset($controllers[$controller])) {
    die('Invalid controller');
}

// Load controller
$controller_name = $controllers[$controller];
require_once "controllers/{$controller_name}.php";

// Create controller instance
$controller_instance = new $controller_name();

// Check if action exists
if (!method_exists($controller_instance, $action)) {
    die('Invalid action');
}

// Call action
$controller_instance->$action();
