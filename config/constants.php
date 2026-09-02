<?php
/**
 * Application Constants
 * Little Steps Childcare Platform
 */

// Site URL - Change this based on your environment
define('SITE_URL', 'http://localhost/little-steps');

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
