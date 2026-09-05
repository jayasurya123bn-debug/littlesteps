<?php
/**
 * Database Connection Configuration
 * Little Steps Childcare Platform
 *
 * Supports three environments automatically:
 *  1. Railway (via MYSQLHOST / RAILWAY_ENVIRONMENT env vars)
 *  2. Local XAMPP (localhost)
 *  3. ProFreeHost (manual credentials below)
 */

// ──────────────────────────────────────────────
// 1. RAILWAY DEPLOYMENT (auto-detected)
//    Railway injects MYSQLHOST, MYSQLUSER, MYSQLPASSWORD,
//    MYSQLDATABASE, MYSQLPORT automatically when you add
//    a MySQL service to your Railway project.
// ──────────────────────────────────────────────
if (getenv('MYSQLHOST') !== false || getenv('RAILWAY_ENVIRONMENT') !== false) {
    define('DB_HOST', getenv('MYSQLHOST') ?: 'localhost');
    define('DB_USER', getenv('MYSQLUSER') ?: 'root');
    define('DB_PASS', getenv('MYSQLPASSWORD') ?: '');
    define('DB_NAME', getenv('MYSQLDATABASE') ?: 'littlesteps_db');
    define('DB_PORT', (int)(getenv('MYSQLPORT') ?: 3306));

// ──────────────────────────────────────────────
// 2. LOCAL XAMPP
// ──────────────────────────────────────────────
} elseif (
    php_sapi_name() === 'cli' ||
    (isset($_SERVER['HTTP_HOST']) && in_array(explode(':', $_SERVER['HTTP_HOST'])[0], ['localhost', '127.0.0.1']))
) {
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'littlesteps_db');
    define('DB_PORT', 3306);

// ──────────────────────────────────────────────
// 3. PROFREEHOST ONLINE DEPLOYMENT (manual)
//    Fill in cPanel → MySQL Databases details below:
// ──────────────────────────────────────────────
} else {
    define('DB_HOST', 'sql200.profreehost.com');    // e.g. sql105.profreehost.com
    define('DB_USER', 'your_profreehost_db_user');  // e.g. ezyro_12345678
    define('DB_PASS', 'your_profreehost_password'); // Your ProFreeHost account password
    define('DB_NAME', 'your_profreehost_db_name');  // e.g. ezyro_12345678_littlesteps
    define('DB_PORT', 3306);
}

/**
 * Get database connection (singleton)
 * @return mysqli
 */
function getDBConnection() {
    static $conn = null;

    if ($conn !== null && $conn instanceof mysqli) {
        if (@$conn->ping()) {
            return $conn;
        }
    }

    $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

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
                <h2>🌸 Little Steps — Database Error</h2>
                <p>Could not connect to MySQL on <strong>" . htmlspecialchars(DB_HOST) . "</strong>.</p>
                <div class='box'>Error: {$errorMsg}</div>
                " . ($isProFreeHost ? "
                <div class='steps'>
                    <strong>ProFreeHost Setup:</strong><br>
                    1. Log into your <strong>ProFreeHost cPanel</strong>.<br>
                    2. Go to <strong>MySQL Databases</strong> and copy your credentials.<br>
                    3. Edit <code>config/database.php</code> lines 44–47 with your real credentials.<br>
                    4. Import <code>database/littlesteps.sql</code> via phpMyAdmin.
                </div>
                " : "
                <div class='steps'>
                    <strong>Railway Setup:</strong> Make sure you added a <strong>MySQL</strong> service to your Railway project and the environment variables (<code>MYSQLHOST</code>, <code>MYSQLUSER</code>, <code>MYSQLPASSWORD</code>, <code>MYSQLDATABASE</code>) are injected.<br><br>
                    <strong>Local:</strong> Ensure XAMPP MySQL is running and the database exists.
                </div>
                ") . "
            </div>
        </body>
        </html>");
    }

    if (!$conn->set_charset("utf8mb4")) {
        die("Error loading character set utf8mb4: " . $conn->error);
    }

    return $conn;
}