<?php
// Start session
session_start();

// Include configuration files
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Simple router
$controller = isset($_GET['controller']) ? $_GET['controller'] : 'auth';
$action = isset($_GET['action']) ? $_GET['action'] : 'login';

// Validate controller and action
$validControllers = ['auth', 'bug', 'user', 'project', 'dashboard'];
$controller = in_array($controller, $validControllers) ? $controller : 'auth';

// Load the appropriate controller
$controllerFile = 'controllers/' . ucfirst($controller) . 'Controller.php';

if (file_exists($controllerFile)) {
    require_once $controllerFile;
    $controllerClass = ucfirst($controller) . 'Controller';
    $controllerObj = new $controllerClass();
    
    // Check if action exists
    if (method_exists($controllerObj, $action)) {
        $controllerObj->$action();
    } else {
        // Action not found
        header('Location: index.php?controller=auth&action=login');
    }
} else {
    // Controller not found
    header('Location: index.php?controller=auth&action=login');
}