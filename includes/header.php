<?php
/**
 * Shared Public Header
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

// Define page title if not set
$pageTitle = isset($pageTitle) ? $pageTitle . ' - ' . APP_NAME : APP_NAME . ' - Trusted 24x7 Childcare Platform';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/responsive.css">
    
    <?php if(isset($extraCss)): ?>
        <style><?= $extraCss ?></style>
    <?php endif; ?>
</head>
<body>
    <?php require_once __DIR__ . '/navbar.php'; ?>
    
    <div class="flash-messages-container" style="position: fixed; top: 80px; right: 24px; z-index: 1050; min-width: 300px;">
        <?php displayFlashMessage(); ?>
    </div>
