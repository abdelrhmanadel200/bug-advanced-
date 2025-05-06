<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'bug_tracking_system');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application Configuration
define('APP_NAME', 'Bug Tracking System');
define('APP_URL', 'http://localhost/bug_tracking_system');
define('APP_VERSION', '1.0.0');

// Session Configuration
define('SESSION_NAME', 'bug_tracking_system');
define('SESSION_LIFETIME', 86400); // 24 hours

// File Upload Configuration
define('UPLOAD_DIR', 'uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_EXTENSIONS', 'jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,zip,rar');

// SMTP Configuration
define('SMTP_HOST', ''); // e.g., smtp.gmail.com
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls'); // tls or ssl
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('SMTP_FROM_EMAIL', '');
define('SMTP_FROM_NAME', APP_NAME);

// GitHub Integration
define('GITHUB_CLIENT_ID', '');
define('GITHUB_CLIENT_SECRET', '');
define('GITHUB_REDIRECT_URI', APP_URL . '/github-callback.php');

// Initialize session
session_name(SESSION_NAME);
session_start();

// Include database connection
require_once 'database.php';

// Include helper functions
require_once 'functions.php';
