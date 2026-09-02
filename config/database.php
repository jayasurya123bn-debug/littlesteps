<?php
/**
 * Database Connection Helper
 * Little Steps Childcare Platform
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // XAMPP default
define('DB_PASS', ''); // XAMPP default
define('DB_NAME', 'littlesteps_db');

/**
 * Get database connection
 * @return mysqli Connection object
 */
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset to utf8mb4 for full unicode support
    if (!$conn->set_charset("utf8mb4")) {
        die("Error loading character set utf8mb4: " . $conn->error);
    }
    
    return $conn;
}
