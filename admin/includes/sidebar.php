<?php
/**
 * Admin Sidebar
 * Little Steps Childcare Platform
 */
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar">
    <div class="sidebar-logo">
        <div class="logo-icon" style="background: white; border: 2px solid rgba(233,30,99,0.15); box-shadow: 0 4px 16px rgba(233,30,99,0.25); overflow: hidden; display: flex; align-items: center; justify-content: center; padding: 0;">
            <img src="<?= SITE_URL ?>/assets/images/logo-baby-legs.jpg" alt="Little Steps Logo" style="width: 100%; height: 100%; object-fit: cover;">
        </div>
        <div class="brand-text">
            <span class="brand-name">LITTLE STEPS</span>
            <span class="brand-tagline">Admin Portal</span>
        </div>
    </div>
    
    <div class="sidebar-nav">
        <a href="dashboard.php" class="nav-item <?= $currentPage == 'dashboard.php' ? 'active' : '' ?>">
            <i class="fas fa-chart-line"></i> Overview
        </a>
        
        <div class="nav-section">Management</div>
        <a href="users.php" class="nav-item <?= $currentPage == 'users.php' ? 'active' : '' ?>">
            <i class="fas fa-users"></i> Users (Parents & Providers)
        </a>
        <a href="centers.php" class="nav-item <?= $currentPage == 'centers.php' ? 'active' : '' ?>">
            <i class="fas fa-building"></i> Daycare Centers
        </a>
        <a href="center_approvals.php" class="nav-item <?= $currentPage == 'center_approvals.php' ? 'active' : '' ?>">
            <i class="fas fa-clipboard-check"></i> Pending Approvals
        </a>
        <a href="bookings.php" class="nav-item <?= $currentPage == 'bookings.php' ? 'active' : '' ?>">
            <i class="fas fa-calendar-alt"></i> All Bookings
        </a>
        
        <div class="nav-section">Reports & System</div>
        <a href="finances.php" class="nav-item <?= $currentPage == 'finances.php' ? 'active' : '' ?>">
            <i class="fas fa-file-invoice-dollar"></i> Financial Reports
        </a>
        <a href="settings.php" class="nav-item <?= $currentPage == 'settings.php' ? 'active' : '' ?>">
            <i class="fas fa-cogs"></i> System Settings
        </a>
    </div>
    
    <div class="sidebar-footer">
        <img src="https://ui-avatars.com/api/?name=Admin&background=1A237E&color=fff" alt="Admin" class="user-avatar">
        <div class="user-info">
            <div class="user-name">System Administrator</div>
            <div class="user-role">Super Admin</div>
            <a href="<?= SITE_URL ?>/logout.php" class="logout-btn" style="text-decoration: none; font-size: 12px; display: block; margin-top: 4px;">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
</div>
