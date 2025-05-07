<?php
// Database configuration
$db_host = 'localhost';
$db_name = 'bug_tracking_system';
$db_user = 'root';
$db_pass = '';

global $db; // Explicitly declare $db as global

try {
    $db = new PDO("mysql:host={$db_host};dbname={$db_name}", $db_user, $db_pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
