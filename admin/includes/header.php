<?php
/**
 * Admin Dashboard Header
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';

// Check role
requireRole('admin');

$pageTitle = isset($pageTitle) ? $pageTitle . ' - Admin Panel' : 'Admin Panel - Little Steps';

// Get pending centers count
$pendingCentersCount = 0;
try {
    $conn = getDBConnection();
    $res = $conn->query("SELECT COUNT(*) as count FROM daycare_centers WHERE status = 'pending'");
    if ($res && $row = $res->fetch_assoc()) {
        $pendingCentersCount = (int)$row['count'];
    }
} catch (Exception $e) {
    error_log("Pending centers count error: " . $e->getMessage());
    $pendingCentersCount = 0;
}
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
    <link rel="stylesheet" href="<?= SITE_URL ?>/admin/assets/admin.css">
    
    <?php if(isset($extraCss)): ?>
        <style><?= $extraCss ?></style>
    <?php endif; ?>
</head>
<body>

<?php require_once __DIR__ . '/sidebar.php'; ?>

<div class="main-content">
    <header class="top-header" style="border-bottom: 2px solid var(--admin-primary);">
        <div class="header-left" style="display: flex; align-items: center;">
            <button class="mobile-menu-btn" style="display: none; background: transparent; border: none; font-size: 20px; margin-right: 16px; cursor: pointer;">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="page-title" style="color: var(--admin-primary);"><?= isset($pageTitleHeader) ? $pageTitleHeader : 'Dashboard' ?></h1>
        </div>
        
        <div class="header-right">
            <a href="center_approvals.php" style="position: relative; color: var(--dark-gray); font-size: 20px; text-decoration: none; margin-right: 16px;" title="Pending Approvals">
                <i class="fas fa-clipboard-check"></i>
                <?php if ($pendingCentersCount > 0): ?>
                    <span style="position: absolute; top: -5px; right: -8px; background: var(--danger); color: white; font-size: 10px; font-weight: bold; height: 18px; min-width: 18px; border-radius: 9px; display: flex; align-items: center; justify-content: center; padding: 0 4px;">
                        <?= $pendingCentersCount ?>
                    </span>
                <?php endif; ?>
            </a>
            
            <div class="user-profile-menu">
                <img src="https://ui-avatars.com/api/?name=Admin&background=1A237E&color=fff" alt="Admin" class="user-avatar">
                <div class="dropdown-menu">
                    <a href="settings.php" class="dropdown-item"><i class="fas fa-cogs"></i> System Settings</a>
                    <div style="height: 1px; background: var(--light-gray); margin: 4px 0;"></div>
                    <a href="<?= SITE_URL ?>/logout.php" class="dropdown-item" style="color: var(--danger);"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </header>
    
    <div class="flash-messages-container" style="padding: var(--space-md) var(--space-lg) 0;">
        <?php displayFlashMessage(); ?>
    </div>
    
    <div class="content-wrapper">
