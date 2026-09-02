<?php
/**
 * Provider Sidebar
 * Little Steps Childcare Platform
 */
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar" style="background: linear-gradient(180deg, var(--near-black) 0%, var(--dark-gray) 100%);">
    <div class="sidebar-logo">
        <div class="logo-icon" style="background: white; border: 2px solid rgba(233,30,99,0.15); box-shadow: 0 4px 16px rgba(233,30,99,0.25); overflow: hidden; display: flex; align-items: center; justify-content: center; padding: 0;">
            <img src="<?= SITE_URL ?>/assets/images/logo-baby-legs.jpg" alt="Little Steps Logo" style="width: 100%; height: 100%; object-fit: cover;">
        </div>
        <div class="brand-text">
            <span class="brand-name">LITTLE STEPS</span>
            <span class="brand-tagline">Provider Portal</span>
        </div>
    </div>
    
    <div class="sidebar-nav">
        <a href="dashboard.php" class="nav-item <?= $currentPage == 'dashboard.php' ? 'active' : '' ?>">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
        
        <div class="nav-section">Operations</div>
        <a href="bookings.php" class="nav-item <?= in_array($currentPage, ['bookings.php', 'booking_details.php']) ? 'active' : '' ?>">
            <i class="fas fa-calendar-check"></i> Bookings & Requests
        </a>
        <a href="subscriptions.php" class="nav-item <?= $currentPage == 'subscriptions.php' ? 'active' : '' ?>">
            <i class="fas fa-sync"></i> Active Subscriptions
        </a>
        <a href="caregivers.php" class="nav-item <?= $currentPage == 'caregivers.php' ? 'active' : '' ?>">
            <i class="fas fa-users"></i> Staff / Caregivers
        </a>
        
        <div class="nav-section">Business</div>
        <a href="center_profile.php" class="nav-item <?= $currentPage == 'center_profile.php' ? 'active' : '' ?>">
            <i class="fas fa-building"></i> Center Profile
        </a>
        <a href="finances.php" class="nav-item <?= $currentPage == 'finances.php' ? 'active' : '' ?>">
            <i class="fas fa-chart-line"></i> Revenue & Finance
        </a>
        <a href="reviews.php" class="nav-item <?= $currentPage == 'reviews.php' ? 'active' : '' ?>">
            <i class="fas fa-star"></i> Parent Reviews
        </a>
        
        <div class="nav-section">Settings</div>
        <a href="settings.php" class="nav-item <?= $currentPage == 'settings.php' ? 'active' : '' ?>">
            <i class="fas fa-cog"></i> Account Settings
        </a>
    </div>
    
    <div class="sidebar-footer" style="background: rgba(0,0,0,0.3);">
        <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['user_name']) ?>&background=E0E0E0&color=212121" alt="Avatar" class="user-avatar">
        <div class="user-info">
            <div class="user-name"><?= htmlspecialchars($_SESSION['business_name'] ?? $_SESSION['user_name']) ?></div>
            <div class="user-role">Center Admin</div>
            <a href="<?= SITE_URL ?>/logout.php" class="logout-btn" style="text-decoration: none; font-size: 12px; display: block; margin-top: 4px; color: var(--medium-gray);">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
</div>
