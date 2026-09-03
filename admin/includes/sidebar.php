<?php
/**
 * Admin Sidebar Navigation
 * Little Steps Childcare Platform
 * 40% Pink Gradient Sidebar
 */
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar" style="background: linear-gradient(180deg, #E91E63 0%, #AD1457 100%);">
    <div class="sidebar-logo">
        <div class="logo-icon" style="background: white; border: 2px solid rgba(255,255,255,0.4); box-shadow: 0 4px 16px rgba(0,0,0,0.15); overflow: hidden; display: flex; align-items: center; justify-content: center; padding: 0; width: 42px; height: 42px; border-radius: 50%;">
            <span style="font-size: 22px;">👶</span>
        </div>
        <div class="brand-text">
            <span class="brand-name" style="color: #FFFFFF; font-weight: 700; letter-spacing: 1px;">LITTLE STEPS</span>
            <span class="brand-tagline" style="color: rgba(255,255,255,0.8); font-size: 11px;">Admin Portal</span>
        </div>
    </div>
    
    <div class="sidebar-nav">
        <a href="dashboard.php" class="nav-item <?= $currentPage == 'dashboard.php' ? 'active' : '' ?>">
            <i class="fas fa-home"></i> <span>Dashboard</span>
        </a>
        
        <div class="nav-section" style="color: rgba(255,255,255,0.6); font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 1.5px; padding: 16px 24px 8px;">Management</div>
        
        <a href="users.php" class="nav-item <?= in_array($currentPage, ['users.php', 'user_edit.php']) ? 'active' : '' ?>">
            <i class="fas fa-users"></i> <span>Parents / Users</span>
        </a>
        <a href="providers.php" class="nav-item <?= in_array($currentPage, ['providers.php', 'provider_view.php', 'center_approvals.php']) ? 'active' : '' ?>">
            <i class="fas fa-building"></i> <span>Providers</span>
        </a>
        <a href="daycare_centers.php" class="nav-item <?= in_array($currentPage, ['daycare_centers.php', 'centers.php']) ? 'active' : '' ?>">
            <i class="fas fa-school"></i> <span>Daycare Centers</span>
        </a>
        <a href="caregivers.php" class="nav-item <?= $currentPage == 'caregivers.php' ? 'active' : '' ?>">
            <i class="fas fa-user-nurse"></i> <span>Caregivers</span>
        </a>
        <a href="bookings.php" class="nav-item <?= $currentPage == 'bookings.php' ? 'active' : '' ?>">
            <i class="fas fa-calendar-check"></i> <span>Bookings</span>
        </a>
        <a href="subscriptions.php" class="nav-item <?= $currentPage == 'subscriptions.php' ? 'active' : '' ?>">
            <i class="fas fa-credit-card"></i> <span>Subscriptions</span>
        </a>
        
        <div class="nav-section" style="color: rgba(255,255,255,0.6); font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 1.5px; padding: 16px 24px 8px;">Insights & Config</div>
        
        <a href="analytics.php" class="nav-item <?= in_array($currentPage, ['analytics.php', 'finances.php']) ? 'active' : '' ?>">
            <i class="fas fa-chart-pie"></i> <span>Analytics</span>
        </a>
        <a href="reports.php" class="nav-item <?= $currentPage == 'reports.php' ? 'active' : '' ?>">
            <i class="fas fa-file-alt"></i> <span>Reports</span>
        </a>
        <a href="settings.php" class="nav-item <?= $currentPage == 'settings.php' ? 'active' : '' ?>">
            <i class="fas fa-cog"></i> <span>Platform Settings</span>
        </a>
    </div>
    
    <div class="sidebar-footer" style="background: rgba(0,0,0,0.18); border-top: 1px solid rgba(255,255,255,0.12); padding: 16px 24px;">
        <img src="https://ui-avatars.com/api/?name=Admin&background=FFF0F5&color=E91E63" alt="Admin" class="user-avatar" style="border: 2px solid #FFFFFF;">
        <div class="user-info">
            <div class="user-name" style="color: #FFFFFF; font-weight: 500; font-size: 14px;">System Admin</div>
            <div class="user-role" style="color: rgba(255,255,255,0.7); font-size: 12px;">Super Administrator</div>
            <a href="<?= SITE_URL ?>/logout.php" class="logout-btn" style="text-decoration: none; font-size: 12px; display: block; margin-top: 4px; color: #FFCDD2;">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
</div>
