<?php
/**
 * Parent Sidebar
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
            <span class="brand-tagline">Childcare Platform</span>
        </div>
    </div>
    
    <div class="sidebar-nav">
        <a href="dashboard.php" class="nav-item <?= $currentPage == 'dashboard.php' ? 'active' : '' ?>">
            <i class="fas fa-home"></i> Dashboard
        </a>
        
        <div class="nav-section">Bookings</div>
        <a href="bookings.php" class="nav-item <?= $currentPage == 'bookings.php' ? 'active' : '' ?>">
            <i class="fas fa-calendar-alt"></i> My Bookings
        </a>
        <a href="booking_create.php" class="nav-item <?= $currentPage == 'booking_create.php' ? 'active' : '' ?>">
            <i class="fas fa-plus-circle"></i> New Booking
        </a>
        <a href="booking_history.php" class="nav-item <?= $currentPage == 'booking_history.php' ? 'active' : '' ?>">
            <i class="fas fa-history"></i> Booking History
        </a>
        
        <div class="nav-section">Services</div>
        <a href="subscriptions.php" class="nav-item <?= $currentPage == 'subscriptions.php' ? 'active' : '' ?>">
            <i class="fas fa-credit-card"></i> Subscriptions
        </a>
        <a href="centers.php" class="nav-item <?= $currentPage == 'centers.php' ? 'active' : '' ?>">
            <i class="fas fa-school"></i> Browse Centers
        </a>
        <a href="reviews.php" class="nav-item <?= $currentPage == 'reviews.php' ? 'active' : '' ?>">
            <i class="fas fa-star"></i> Reviews
        </a>
        
        <div class="nav-section">Account</div>
        <a href="notifications.php" class="nav-item <?= $currentPage == 'notifications.php' ? 'active' : '' ?>">
            <i class="fas fa-bell"></i> Notifications
        </a>
        <a href="profile.php" class="nav-item <?= $currentPage == 'profile.php' ? 'active' : '' ?>">
            <i class="fas fa-user"></i> My Profile
        </a>
        <a href="settings.php" class="nav-item <?= $currentPage == 'settings.php' ? 'active' : '' ?>">
            <i class="fas fa-cog"></i> Settings
        </a>
    </div>
    
    <div class="sidebar-footer">
        <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['user_name']) ?>&background=FCE4EC&color=E91E63" alt="Avatar" class="user-avatar">
        <div class="user-info">
            <div class="user-name"><?= htmlspecialchars($_SESSION['user_name']) ?></div>
            <div class="user-role"><?= htmlspecialchars($_SESSION['user_role']) ?></div>
            <a href="<?= SITE_URL ?>/logout.php" class="logout-btn" style="text-decoration: none; font-size: 12px; display: block; margin-top: 4px;">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
</div>
