<?php
/**
 * Parent Dashboard Header
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';

// Check role
requireRole('parent');

$pageTitle = isset($pageTitle) ? $pageTitle . ' - Parent Dashboard' : 'Parent Dashboard - Little Steps';

// Get unread notifications count
$conn = getDBConnection();
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$notifCount = $stmt->get_result()->fetch_assoc()['count'];
$conn->close();
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
    <link rel="stylesheet" href="<?= SITE_URL ?>/parent/assets/parent.css">
    
    <?php if(isset($extraCss)): ?>
        <style><?= $extraCss ?></style>
    <?php endif; ?>
</head>
<body>

<?php require_once __DIR__ . '/sidebar.php'; ?>

<div class="main-content">
    <header class="top-header">
        <div class="header-left" style="display: flex; align-items: center;">
            <button class="mobile-menu-btn" style="display: none; background: transparent; border: none; font-size: 20px; margin-right: 16px; cursor: pointer;">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="page-title"><?= isset($pageTitleHeader) ? $pageTitleHeader : 'Dashboard' ?></h1>
        </div>
        
        <div class="header-right">
            <a href="notifications.php" style="position: relative; color: var(--dark-gray); font-size: 20px; text-decoration: none;">
                <i class="fas fa-bell"></i>
                <?php if ($notifCount > 0): ?>
                    <span style="position: absolute; top: -5px; right: -8px; background: var(--danger); color: white; font-size: 10px; font-weight: bold; height: 18px; min-width: 18px; border-radius: 9px; display: flex; align-items: center; justify-content: center; padding: 0 4px;">
                        <?= $notifCount ?>
                    </span>
                <?php endif; ?>
            </a>
            
            <div class="user-profile-menu">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['user_name']) ?>&background=FCE4EC&color=E91E63" alt="Avatar" class="user-avatar">
                <div class="dropdown-menu">
                    <a href="profile.php" class="dropdown-item"><i class="fas fa-user"></i> My Profile</a>
                    <a href="settings.php" class="dropdown-item"><i class="fas fa-cog"></i> Settings</a>
                    <div style="height: 1px; background: var(--light-pink); margin: 4px 0;"></div>
                    <a href="<?= SITE_URL ?>/logout.php" class="dropdown-item" style="color: var(--danger);"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </header>
    
    <div class="flash-messages-container" style="padding: var(--space-md) var(--space-lg) 0;">
        <?php displayFlashMessage(); ?>
    </div>
    
    <div class="content-wrapper">
