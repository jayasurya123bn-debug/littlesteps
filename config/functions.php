<?php
/**
 * Utility Functions
 * Little Steps Childcare Platform
 */

// Include constants and session functions if not already included
require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/session.php';

/**
 * Sanitize user input
 * @param mysqli $conn Database connection
 * @param string $input
 * @return string Sanitized input
 */
function sanitizeInput($conn, $input) {
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    return $conn->real_escape_string($input);
}

/**
 * Validate email format
 * @param string $email
 * @return bool
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generate CSRF Token
 * @return string
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF Token
 * @param string $token
 * @return bool
 */
function verifyCSRFToken($token) {
    if (isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token)) {
        return true;
    }
    return false;
}

/**
 * Output CSRF Token field
 */
function csrfField() {
    $token = generateCSRFToken();
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Format currency
 * @param float $amount
 * @return string
 */
function formatCurrency($amount) {
    return '₹' . number_format($amount, 2);
}

/**
 * Format Date
 * @param string $date
 * @param string $format
 * @return string
 */
function formatDate($date, $format = 'd M Y') {
    if (empty($date)) return '';
    return date($format, strtotime($date));
}

/**
 * Handle File Upload
 * @param array $file $_FILES['input_name']
 * @param string $destination Directory path relative to UPLOADS_PATH
 * @param array $allowedTypes Array of allowed MIME types
 * @return string|bool File path on success, false on failure
 */
function uploadFile($file, $destination, $allowedTypes = ALLOWED_IMAGE_TYPES) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return false; // Too large
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowedTypes)) {
        return false; // Invalid file type
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = uniqid() . '_' . time() . '.' . $ext;
    
    $fullPath = UPLOADS_PATH . '/' . trim($destination, '/') . '/' . $fileName;
    $dbPath = 'uploads/' . trim($destination, '/') . '/' . $fileName;

    if (move_uploaded_file($file['tmp_name'], $fullPath)) {
        return $dbPath;
    }

    return false;
}

/**
 * Display flash messages
 */
function displayFlashMessage() {
    $flash = getFlashMessage();
    if ($flash) {
        $icon = '';
        switch ($flash['type']) {
            case 'success': $icon = 'check-circle'; break;
            case 'error': $icon = 'exclamation-circle'; break;
            case 'warning': $icon = 'exclamation-triangle'; break;
            case 'info': $icon = 'info-circle'; break;
        }
        
        echo '<div class="alert alert-' . htmlspecialchars($flash['type']) . '">';
        if ($icon) {
            echo '<i class="fas fa-' . $icon . '"></i> ';
        }
        echo htmlspecialchars($flash['message']);
        echo '</div>';
    }
}
