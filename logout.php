<?php
/**
 * Logout script
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/constants.php';

// Log out the user using the helper function
logoutUser();

// Start a fresh session just to set a flash message
session_start();
$_SESSION['flash_message'] = [
    'type' => 'info',
    'message' => 'You have been successfully logged out.'
];

// Redirect to login page
header('Location: ' . SITE_URL . '/login.php');
exit();
