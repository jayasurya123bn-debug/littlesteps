<?php
/**
 * Application Constants
 * Little Steps Childcare Platform
 */

// Dynamic protocol and host detection supporting HTTP, HTTPS, reverse proxies & various hosts
$isHttps = (
    (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') ||
    (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ||
    (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') ||
    (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on') ||
    (!empty($_SERVER['HTTP_CF_VISITOR']) && strpos($_SERVER['HTTP_CF_VISITOR'], '"scheme":"https"') !== false)
);
$protocol = $isHttps ? "https" : "http";
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
$hostOnly = explode(':', $host)[0];

// Detect if running locally vs online
$isLocal = in_array($hostOnly, ['localhost', '127.0.0.1', '::1']) || 
           str_ends_with($hostOnly, '.local') ||
           str_ends_with($hostOnly, '.test');

if ($isLocal) {
    // Local development runs in /little-steps subdirectory
    define('SITE_URL', $protocol . '://' . $host . '/little-steps');
} else {
    // Online hosting (any domain) runs at web root
    define('SITE_URL', $protocol . '://' . $host);
}

// Allow override via environment variable for edge cases
if (getenv('SITE_URL') !== false) {
    define('SITE_URL', rtrim(getenv('SITE_URL'), '/'));
}

// Application Info
define('APP_NAME', 'Little Steps');
define('APP_VERSION', '1.0.0');

// Paths
define('BASE_PATH', dirname(__DIR__));
define('UPLOADS_PATH', BASE_PATH . '/uploads');
define('PROFILES_PATH', UPLOADS_PATH . '/profiles');
define('DOCUMENTS_PATH', UPLOADS_PATH . '/documents');
define('CENTERS_PATH', UPLOADS_PATH . '/centers');
define('CAREGIVERS_PATH', UPLOADS_PATH . '/caregivers');

// File Upload Limits
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp']);
define('ALLOWED_DOC_TYPES', ['application/pdf', 'image/jpeg', 'image/png']);

// Timezone
date_default_timezone_set('Asia/Kolkata'); // Adjust as needed
