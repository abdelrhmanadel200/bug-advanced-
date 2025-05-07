<?php
// Include database configuration
require_once 'config/database.php';

// Check if the database connection is established
if (!$db) {
    die("Database connection failed. Please check your configuration.");
}

// Create password_resets table if it doesn't exist
try {
    $db->exec("CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(100) NOT NULL,
        token VARCHAR(255) NOT NULL,
        expires_at DATETIME NOT NULL,
        created_at DATETIME NOT NULL
    )");
    
    echo "Password resets table created or already exists.<br>";
} catch (PDOException $e) {
    echo "Error creating password_resets table: " . $e->getMessage() . "<br>";
}

// Create activity_log table if it doesn't exist
try {
    $db->exec("CREATE TABLE IF NOT EXISTS activity_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        action VARCHAR(100) NOT NULL,
        details TEXT,
        created_at DATETIME NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )");
    
    echo "Activity log table created or already exists.<br>";
} catch (PDOException $e) {
    echo "Error creating activity_log table: " . $e->getMessage() . "<br>";
}

// Create activity_log_system table if it doesn't exist
try {
    $db->exec("CREATE TABLE IF NOT EXISTS activity_log_system (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        action VARCHAR(100) NOT NULL,
        details TEXT,
        created_at DATETIME NOT NULL
    )");
    
    echo "Activity log system table created or already exists.<br>";
} catch (PDOException $e) {
    echo "Error creating activity_log_system table: " . $e->getMessage() . "<br>";
}

echo "Setup completed successfully!";
