<?php
/**
 * Database Connection Configuration
 * Little Steps Childcare Platform
 *
 * Supports multiple environments:
 *  1. Railway / Cloud (via MYSQLHOST, RAILWAY_ENVIRONMENT env vars)
 *  2. Local XAMPP (localhost / 127.0.0.1)
 *  3. Production (via DB_* environment variables or ProFreeHost/cPanel)
 */

// Load .env file if exists (for local development)
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#') || strpos($line, '=') === false) continue;
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (!array_key_exists($key, $_SERVER) && !getenv($key)) {
            putenv("$key=$value");
            $_SERVER[$key] = $value;
            $_ENV[$key] = $value;
        }
    }
}

// ──────────────────────────────────────────────
// 1. CLOUD/RAILWAY DEPLOYMENT (auto-detected)
//    Railway/Cloud injects MYSQLHOST, MYSQLUSER, MYSQLPASSWORD,
//    MYSQLDATABASE, MYSQLPORT automatically
// ──────────────────────────────────────────────
if (getenv('MYSQLHOST') !== false || getenv('RAILWAY_ENVIRONMENT') !== false) {
    define('DB_HOST', getenv('MYSQLHOST') ?: 'localhost');
    define('DB_USER', getenv('MYSQLUSER') ?: 'root');
    define('DB_PASS', getenv('MYSQLPASSWORD') ?: '');
    define('DB_NAME', getenv('MYSQLDATABASE') ?: 'littlesteps_db');
    define('DB_PORT', (int)(getenv('MYSQLPORT') ?: 3306));

// ──────────────────────────────────────────────
// 2. LOCAL XAMPP / DEVELOPMENT
// ──────────────────────────────────────────────
} elseif (
    php_sapi_name() === 'cli' ||
    (isset($_SERVER['HTTP_HOST']) && in_array(explode(':', $_SERVER['HTTP_HOST'])[0], ['localhost', '127.0.0.1', '::1']))
) {
    define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
    define('DB_USER', getenv('DB_USER') ?: 'root');
    define('DB_PASS', getenv('DB_PASS') ?: '');
    define('DB_NAME', getenv('DB_NAME') ?: 'littlesteps_db');
    define('DB_PORT', (int)(getenv('DB_PORT') ?: 3306));

// ──────────────────────────────────────────────
// 3. PRODUCTION ONLINE (ProFreeHost, Hostinger, etc.)
//    Uses DB_* environment variables or .env file
//    Fallback to manual credentials (update these for your hosting)
// ──────────────────────────────────────────────
} else {
    define('DB_HOST', getenv('DB_HOST') ?: 'sql103.ezyro.com');
    define('DB_USER', getenv('DB_USER') ?: 'ezyro_42742254');
    define('DB_PASS', getenv('DB_PASS') ?: 'c45483b8a78869e');
    define('DB_NAME', getenv('DB_NAME') ?: 'ezyro_42742254_childcare');
    define('DB_PORT', (int)(getenv('DB_PORT') ?: 3306));
}

/**
 * Get database connection (singleton)
 * @return mysqli
 * @throws Exception if connection fails
 */
function getDBConnection() {
    static $conn = null;

    if ($conn !== null && $conn instanceof mysqli) {
        try {
            if (@$conn->ping()) {
                return $conn;
            }
        } catch (\Throwable $e) {
            $conn = null;
        }
    }

    $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

    if ($conn->connect_error) {
        $errorMsg = $conn->connect_error;
        
        // Log the error for debugging
        error_log("Database connection failed: $errorMsg | Host: " . DB_HOST . " | DB: " . DB_NAME);
        
        // Check if this is an AJAX/fetch request
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        $isApi = strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') === 0;
        
        if ($isAjax || $isApi) {
            header('Content-Type: application/json');
            http_response_code(503);
            echo json_encode([
                'error' => 'Database connection failed',
                'message' => 'Unable to connect to database. Please try again later.'
            ]);
            exit();
        }
        
        // For regular page loads, show user-friendly error
        $isUsingDefaults = (
            DB_HOST === 'sql103.ezyro.com' || 
            DB_USER === 'ezyro_42742254' ||
            DB_NAME === 'ezyro_42742254_childcare'
        );
        
        $setupInstructions = $isUsingDefaults ? "
        <div class='steps'>
            <strong>Production Setup Required:</strong><br>
            1. Set <code>DB_HOST</code>, <code>DB_USER</code>, <code>DB_PASS</code>, <code>DB_NAME</code> environment variables in your hosting control panel.<br>
            2. Or create a <code>.env</code> file in the project root with your database credentials.<br>
            3. Import <code>database/littlesteps.sql</code> via phpMyAdmin.
        </div>" : "
        <div class='steps'>
            <strong>Local Setup:</strong> Ensure MySQL is running and database <code>" . htmlspecialchars(DB_NAME) . "</code> exists.<br>
            <strong>Cloud Setup:</strong> Make sure MySQL service is added and environment variables are configured.
        </div>";

        die("<!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Little Steps - Database Connection Error</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #FFF0F5; color: #212121; padding: 40px 20px; margin: 0; }
                .setup-card { max-width: 640px; margin: 0 auto; background: white; border-radius: 16px; box-shadow: 0 10px 30px rgba(233,30,99,0.1); border: 2px solid #FCE4EC; padding: 36px; }
                h2 { color: #E91E63; margin: 0 0 12px 0; font-size: 22px; display: flex; align-items: center; gap: 10px; }
                p { line-height: 1.6; color: #616161; font-size: 14px; margin: 0 0 14px 0; }
                .box { background: #FFF0F5; border-left: 4px solid #E91E63; padding: 14px 18px; border-radius: 0 8px 8px 0; font-family: monospace; font-size: 13px; color: #AD1457; margin: 16px 0; }
                .steps { text-align: left; background: #FAFAFA; border: 1px solid #EEEEEE; padding: 18px 24px; border-radius: 10px; font-size: 13px; line-height: 1.8; color: #424242; }
                .steps strong { color: #E91E63; }
                code { background: #FCE4EC; padding: 2px 6px; border-radius: 4px; color: #C2185B; font-weight: 600; }
                .retry-btn { display: inline-block; margin-top: 20px; padding: 12px 24px; background: #E91E63; color: white; text-decoration: none; border-radius: 8px; font-weight: 600; }
                .retry-btn:hover { background: #C2185B; }
            </style>
        </head>
        <body>
            <div class='setup-card'>
                <h2>🌸 Little Steps — Database Connection Error</h2>
                <p>Could not connect to MySQL on <strong>" . htmlspecialchars(DB_HOST) . "</strong>.</p>
                <div class='box'>Error: " . htmlspecialchars($errorMsg) . "</div>
                {$setupInstructions}
                <a href='' class='retry-btn' onclick='location.reload(); return false;'>Retry Connection</a>
            </div>
        </body>
        </html>");
    }

    if (!$conn->set_charset("utf8mb4")) {
        throw new Exception("Error loading character set utf8mb4: " . $conn->error);
    }

    return $conn;
}