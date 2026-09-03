<?php
/**
 * Database Connection Configuration
 * Little Steps Childcare Platform
 * Supports both local development (XAMPP) and ProFreeHost online deployment.
 */

// Detect environment: local XAMPP or remote online host
$isLocal = (
    php_sapi_name() === 'cli' ||
    (isset($_SERVER['HTTP_HOST']) && in_array(explode(':', $_SERVER['HTTP_HOST'])[0], ['localhost', '127.0.0.1']))
);

if ($isLocal) {
    // ==========================================
    // 💻 LOCALHOST (XAMPP) CONFIGURATION
    // ==========================================
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'littlesteps_db');
} else {
    // ==========================================
    // 🌐 PROFREEHOST ONLINE CONFIGURATION
    // Fill these with details from ProFreeHost cPanel -> MySQL Databases:
    // ==========================================
    define('DB_HOST', 'sql200.profreehost.com');     // Replace with your MySQL Host (e.g., sql105.profreehost.com)
    define('DB_USER', 'your_profreehost_db_user');    // Replace with your MySQL Username (e.g., ezyro_12345678)
    define('DB_PASS', 'your_profreehost_password');   // Replace with your ProFreeHost account password
    define('DB_NAME', 'your_profreehost_db_name');    // Replace with your Database Name (e.g., ezyro_12345678_littlesteps)
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

    // Suppress default PHP warning so we can show friendly helpful guide
    $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    // Check connection
    if ($conn->connect_error) {
        $errorMsg = htmlspecialchars($conn->connect_error);
        $isProFreeHost = (DB_USER === 'your_profreehost_db_user' || DB_NAME === 'your_profreehost_db_name');
        
        die("<!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Little Steps - Database Setup</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #FFF0F5; color: #212121; padding: 40px 20px; margin: 0; }
                .setup-card { max-width: 640px; margin: 0 auto; background: white; border-radius: 16px; box-shadow: 0 10px 30px rgba(233,30,99,0.1); border: 2px solid #FCE4EC; padding: 36px; }
                h2 { color: #E91E63; margin: 0 0 12px 0; font-size: 22px; display: flex; align-items: center; gap: 10px; }
                p { line-height: 1.6; color: #616161; font-size: 14px; margin: 0 0 14px 0; }
                .box { background: #FFF0F5; border-left: 4px solid #E91E63; padding: 14px 18px; border-radius: 0 8px 8px 0; font-family: monospace; font-size: 13px; color: #AD1457; margin: 16px 0; }
                .steps { text-align: left; background: #FAFAFA; border: 1px solid #EEEEEE; padding: 18px 24px; border-radius: 10px; font-size: 13px; line-height: 1.8; color: #424242; }
                .steps strong { color: #E91E63; }
                code { background: #FCE4EC; padding: 2px 6px; border-radius: 4px; color: #C2185B; font-weight: 600; }
            </style>
        </head>
        <body>
            <div class='setup-card'>
                <h2>🌸 Little Steps Database Configuration</h2>
                <p>The application could not establish a connection to your MySQL database on <strong>" . htmlspecialchars(DB_HOST) . "</strong>.</p>
                <div class='box'>Error: {$errorMsg}</div>
                
                " . ($isProFreeHost ? "
                <div class='steps'>
                    <strong>How to configure ProFreeHost Database:</strong><br>
                    1. Log into your <strong>ProFreeHost Control Panel (cPanel)</strong>.<br>
                    2. Go to <strong>MySQL Databases</strong> and find your <em>MySQL Host</em>, <em>Username</em>, and <em>Database Name</em>.<br>
                    3. Open <code>config/database.php</code> in the ProFreeHost File Manager.<br>
                    4. Update lines 26–29 with your actual database credentials and save.<br>
                    5. Go to <strong>phpMyAdmin</strong> in cPanel and import <code>profreehost_database.sql</code>.
                </div>
                " : "
                <div class='steps'>
                    Please check that your MySQL service is running and that your database credentials in <code>config/database.php</code> are correct.
                </div>
                ") . "
            </div>
        </body>
        </html>");
    }
    
    // Set charset to utf8mb4 for full unicode support
    if (!$conn->set_charset("utf8mb4")) {
        die("Error loading character set utf8mb4: " . $conn->error);
    }
    
    return $conn;
}