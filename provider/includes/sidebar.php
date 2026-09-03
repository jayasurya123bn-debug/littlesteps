<?php
/**
 * Provider Sidebar Navigation
 * Little Steps Childcare Platform
 * 40% Pink Gradient Theme
 */
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar" style="background: linear-gradient(180deg, #E91E63 0%, #AD1457 100%);">
    <div class="sidebar-logo">
        <div class="logo-icon" style="background: white; border: 2px solid rgba(255,255,255,0.4); box-shadow: 0 4px 16px rgba(0,0,0,0.15); overflow: hidden; display: flex; align-items: center; justify-content: center; padding: 0; width: 42px; height: 42px; border-radius: 50%;">
            <span style="font-size: 22px;">🏢</span>
        </div>
        <div class="brand-text">
            <span class="brand-name" style="color: #FFFFFF; font-weight: 700; letter-spacing: 1px;">LITTLE STEPS</span>
            <span class="brand-tagline" style="color: rgba(255,255,255,0.8); font-size: 11px;">Provider Portal</span>
        </div>
    </div>
    
    <div class="sidebar-nav">
        <a href="dashboard.php" class="nav-item <?= $currentPage == 'dashboard.php' ? 'active' : '' ?>">
            <i class="fas fa-tachometer-alt"></i> <span>Dashboard</span>
        </a>
        
        <div class="nav-section" style="color: rgba(255,255,255,0.6); font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 1.5px; padding: 16px 24px 8px;">Daycare Operations</div>
        
        <a href="center.php" class="nav-item <?= in_array($currentPage, ['center.php', 'center_edit.php', 'center_profile.php']) ? 'active' : '' ?>">
            <i class="fas fa-school"></i> <span>My Centers</span>
        </a>
        <a href="caregivers.php" class="nav-item <?= in_array($currentPage, ['caregivers.php', 'caregiver_add.php']) ? 'active' : '' ?>">
            <i class="fas fa-user-nurse"></i> <span>Caregivers / Staff</span>
        </a>
        <a href="availability.php" class="nav-item <?= $currentPage == 'availability.php' ? 'active' : '' ?>">
            <i class="fas fa-calendar-alt"></i> <span>Availability & Slots</span>
        </a>
        <a href="bookings.php" class="nav-item <?= in_array($currentPage, ['bookings.php', 'booking_details.php']) ? 'active' : '' ?>">
            <i class="fas fa-calendar-check"></i> <span>Booking Requests</span>
        </a>
        
        <div class="nav-section" style="color: rgba(255,255,255,0.6); font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 1.5px; padding: 16px 24px 8px;">Business & Growth</div>
        
        <a href="earnings.php" class="nav-item <?= in_array($currentPage, ['earnings.php', 'finances.php']) ? 'active' : '' ?>">
            <i class="fas fa-wallet"></i> <span>Earnings</span>
        </a>
        <a href="analytics.php" class="nav-item <?= $currentPage == 'analytics.php' ? 'active' : '' ?>">
            <i class="fas fa-chart-line"></i> <span>Analytics</span>
        </a>
        <a href="documents.php" class="nav-item <?= $currentPage == 'documents.php' ? 'active' : '' ?>">
            <i class="fas fa-file-contract"></i> <span>Documents</span>
        </a>
        <a href="profile.php" class="nav-item <?= $currentPage == 'profile.php' ? 'active' : '' ?>">
            <i class="fas fa-building"></i> <span>Business Profile</span>
        </a>
        <a href="settings.php" class="nav-item <?= $currentPage == 'settings.php' ? 'active' : '' ?>">
            <i class="fas fa-cog"></i> <span>Settings</span>
        </a>
    </div>
    
    <div class="sidebar-footer" style="background: rgba(0,0,0,0.18); border-top: 1px solid rgba(255,255,255,0.12); padding: 16px 24px;">
        <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['user_name'] ?? 'Provider') ?>&background=FFF0F5&color=E91E63" alt="Avatar" class="user-avatar" style="border: 2px solid #FFFFFF;">
        <div class="user-info">
            <div class="user-name" style="color: #FFFFFF; font-weight: 500; font-size: 14px;"><?= htmlspecialchars($_SESSION['business_name'] ?? $_SESSION['user_name'] ?? 'Provider') ?></div>
            <div class="user-role" style="color: rgba(255,255,255,0.7); font-size: 12px;">Verified Provider</div>
            <a href="<?= SITE_URL ?>/logout.php" class="logout-btn" style="text-decoration: none; font-size: 12px; display: block; margin-top: 4px; color: #FFCDD2;">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
</div>
