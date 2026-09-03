<?php
/**
 * Database Connection Helper
 * Little Steps Childcare Platform
 * Supports both local development (XAMPP) and production environments.
 */

// Detect environment: local or remote host
$isLocal = (
    php_sapi_name() === 'cli' ||
    (isset($_SERVER['HTTP_HOST']) && in_array(explode(':', $_SERVER['HTTP_HOST'])[0], ['localhost', '127.0.0.1']))
);

if ($isLocal) {
    // Localhost / XAMPP Configuration
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'littlesteps_db');
} else {
    // Production / Remote Host Configuration
    define('DB_HOST', 'sql200.profreehost.com');
    define('DB_USER', 'your_profreehost_db_user');
    define('DB_PASS', 'your_profreehost_db_password');
    define('DB_NAME', 'your_profreehost_db_name');
}

/**
 * Get database connection
 * @return mysqli Connection object
 */
function getDBConnection() {
    static $conn = null;
    
    // If a connection is already open and alive, reuse it
    if ($conn !== null && $conn instanceof mysqli) {
        if (@$conn->ping()) {
            return $conn;
        }
    }

    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    // Check connection
    if ($conn->connect_error) {
        // Provide friendly message if local DB is not yet created/imported
        die("<div style='font-family:sans-serif;padding:30px;max-width:600px;margin:50px auto;border:2px solid #FCE4EC;border-radius:12px;background:#FFF0F5;color:#AD1457;text-align:center;'>
            <h2 style='color:#E91E63;margin-top:0;'>🌸 Little Steps Database Connection</h2>
            <p>Could not connect to database <strong>" . DB_NAME . "</strong> on <strong>" . DB_HOST . "</strong>.</p>
            <p style='font-size:14px;color:#616161;'>Error: " . htmlspecialchars($conn->connect_error) . "</p>
            <hr style='border:none;border-top:1px solid #F8BBD9;margin:20px 0;'>
            <p style='font-size:13px;color:#424242;'>Please ensure MySQL is running in XAMPP and import <code>database/littlesteps.sql</code> via phpMyAdmin.</p>
        </div>");
    }
    
    // Set charset to utf8mb4 for full unicode support
    if (!$conn->set_charset("utf8mb4")) {
        die("Error loading character set utf8mb4: " . $conn->error);
    }
    
    return $conn;
}