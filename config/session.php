<?php
/**
 * Session Management
 * Little Steps Childcare Platform
 */

// Configure session settings before starting
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
// cookie_samesite only supported in PHP 7.3+
if (PHP_VERSION_ID >= 70300) {
    ini_set('session.cookie_samesite', 'Lax');
}

// Start the session if it hasn't been started already
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Require a specific role to access a page
 * @param string $role 'admin', 'provider', or 'parent'
 */
function requireRole($role) {
    if (!isLoggedIn()) {
        redirect('/login.php');
    }
    
    if ($_SESSION['user_role'] !== $role) {
        // User is logged in but doesn't have the right role
        // Redirect to their respective dashboard
        $redirectUrl = '/' . $_SESSION['user_role'] . '/dashboard.php';
        setFlashMessage('error', 'Unauthorized access.');
        redirect($redirectUrl);
    }
}

/**
 * Require user to be logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        setFlashMessage('error', 'Please login to access this page.');
        redirect('/login.php');
    }
}

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Login a user and set session variables
 */
function loginUser($user, $role) {
    session_regenerate_id(true); // Prevent session fixation
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    
    if ($role === 'provider') {
        $_SESSION['user_name'] = $user['owner_name'];
        $_SESSION['business_name'] = $user['business_name'];
    } else {
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
    }
    
    $_SESSION['user_role'] = $role;
    $_SESSION['login_time'] = time();
}

/**
 * Logout the user
 */
function logoutUser() {
    // Unset all session variables
    $_SESSION = array();

    // Destroy the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Destroy the session
    session_destroy();
}

/**
 * Set a flash message
 * @param string $type 'success', 'error', 'info', 'warning'
 * @param string $message
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear flash message
 * @return array|null
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

/**
 * Redirect to a path relative to SITE_URL or an absolute URL
 * @param string $path e.g., '/login.php' or 'login.php' or 'https://...'
 */
function redirect($path) {
    if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
        header('Location: ' . $path);
    } else {
        $cleanPath = '/' . ltrim($path, '/');
        header('Location: ' . SITE_URL . $cleanPath);
    }
    exit();
}
