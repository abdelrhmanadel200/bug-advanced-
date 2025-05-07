<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'bug_tracker');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Application configuration
define('APP_NAME', 'Bug Tracking System');
define('APP_URL', 'http://localhost/bug-tracker');


// Session configuration
define('SESSION_NAME  ','1.0.0');
define('APP_TIMEZONE', 'UTC');

// Session configuration
define('SESSION_NAME', 'bug_tracker_session');
define('SESSION_LIFETIME', 86400); // 24 hours

// SMTP configuration for email notifications
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USERNAME', 'booda9963@gmail.com');
define('SMTP_PASSWORD', 'bsrl cmwe ckwe wcxd');
define('SMTP_ENCRYPTION', 'tls');
define('SMTP_PORT', 587);
define('SMTP_FROM_EMAIL', 'noreply@bugtracker.com');
define('SMTP_FROM_NAME', 'Bug Tracking System');

// File upload configuration
define('MAX_UPLOAD_SIZE', 5242880); // 5MB
define('ALLOWED_EXTENSIONS', 'jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,zip');
define('UPLOAD_PATH', 'uploads/');

// GitHub integration
define('GITHUB_CLIENT_ID', '');
define('GITHUB_CLIENT_SECRET', '');
define('GITHUB_REDIRECT_URI', APP_URL . '/index.php?controller=github&action=callback');

// Helper function to get base URL
 
