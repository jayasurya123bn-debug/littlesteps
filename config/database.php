<?php
/**
 * Database Connection Helper
 * Little Steps Childcare Platform
 */

if ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1') {
    // Local XAMPP Environment
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'littlesteps_db');
} else {
    // Production Unaux Environment
    define('DB_HOST', 'sql103.ezyro.com');
    define('DB_USER', 'ezyro_42742254');
    define('DB_PASS', 'c45483b8a78869e');
    define('DB_NAME', 'ezyro_42742254_childcare');
}

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
