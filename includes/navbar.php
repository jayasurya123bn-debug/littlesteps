<?php
/**
 * Shared Public Navigation Bar
 * Little Care Childcare Platform
 */
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<nav style="background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); box-shadow: 0 4px 15px rgba(233,30,99,0.05); position: sticky; top: 0; z-index: 1000; border-bottom: 1px solid rgba(255,255,255,0.5);">
    <div class="container" style="display: flex; align-items: center; justify-content: space-between; height: 80px;">
        
        <!-- Logo -->
        <a href="<?= SITE_URL ?>/index.php" style="display: flex; align-items: center; gap: 8px; text-decoration: none;">
            <div style="position: relative; width: 44px; height: 44px; background: linear-gradient(135deg, #FF6B9D, #E91E63); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 16px rgba(233,30,99,0.35); overflow: hidden; flex-shrink: 0;">
                <!-- Baby legs SVG -->
                <svg viewBox="0 0 40 40" width="28" height="28" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <!-- Left leg -->
                    <rect x="8" y="6" width="9" height="16" rx="4.5" fill="white" opacity="0.95"/>
                    <!-- Left shoe -->
                    <ellipse cx="10" cy="23" rx="7" ry="4" fill="white" opacity="0.85"/>
                    <ellipse cx="14" cy="24" rx="3" ry="2.5" fill="rgba(255,255,255,0.6)"/>
                    <!-- Right leg -->
                    <rect x="23" y="6" width="9" height="16" rx="4.5" fill="white" opacity="0.95"/>
                    <!-- Right shoe -->
                    <ellipse cx="25" cy="23" rx="7" ry="4" fill="white" opacity="0.85"/>
                    <ellipse cx="29" cy="24" rx="3" ry="2.5" fill="rgba(255,255,255,0.6)"/>
                    <!-- Socks stripes -->
                    <rect x="8" y="17" width="9" height="2.5" rx="1" fill="rgba(255,182,193,0.7)"/>
                    <rect x="23" y="17" width="9" height="2.5" rx="1" fill="rgba(255,182,193,0.7)"/>
                </svg>
            </div>
            <span style="font-weight: 700; font-size: 1.5rem; color: var(--near-black); letter-spacing: -0.5px;">Little <span style="color: var(--main-pink);">Steps</span></span>
        </a>

        <!-- Desktop Menu -->
        <div style="display: flex; align-items: center; gap: var(--space-xl);" class="desktop-menu">
            <a href="<?= SITE_URL ?>/index.php" style="color: <?= $currentPage == 'index.php' ? 'var(--main-pink)' : 'var(--dark-gray)' ?>; font-weight: <?= $currentPage == 'index.php' ? '600' : '500' ?>; font-size: 15px;">Home</a>
            <a href="<?= SITE_URL ?>/search.php" style="color: <?= $currentPage == 'search.php' ? 'var(--main-pink)' : 'var(--dark-gray)' ?>; font-weight: <?= $currentPage == 'search.php' ? '600' : '500' ?>; font-size: 15px;">Find Care</a>
            <a href="<?= SITE_URL ?>/index.php#how-it-works" style="color: var(--dark-gray); font-weight: 500; font-size: 15px;">How It Works</a>
            <a href="<?= SITE_URL ?>/index.php#features" style="color: var(--dark-gray); font-weight: 500; font-size: 15px;">Features</a>
            <a href="<?= SITE_URL ?>/index.php#pricing" style="color: var(--dark-gray); font-weight: 500; font-size: 15px;">Pricing</a>
            <a href="<?= SITE_URL ?>/about.php" style="color: <?= $currentPage == 'about.php' ? 'var(--main-pink)' : 'var(--dark-gray)' ?>; font-weight: <?= $currentPage == 'about.php' ? '600' : '500' ?>; font-size: 15px;">About Us</a>
            
            <div style="width: 1px; height: 24px; background: #E0E0E0; margin: 0 var(--space-xs);"></div>
            
            <?php if(isLoggedIn()): ?>
                <a href="<?= SITE_URL ?>/<?= $_SESSION['user_role'] ?>/dashboard.php" style="color: var(--dark-gray); font-weight: 600;">Dashboard</a>
                <a href="<?= SITE_URL ?>/logout.php" class="btn btn-primary" style="padding: 10px 24px;">Logout</a>
            <?php else: ?>
                <a href="<?= SITE_URL ?>/login.php" style="color: var(--medium-gray); font-weight: 500; font-size: 15px; transition: color 0.3s;">Log In</a>
                <a href="<?= SITE_URL ?>/register.php" class="btn btn-primary" style="padding: 10px 24px;">Sign Up</a>
            <?php endif; ?>
        </div>

        <!-- Mobile Menu Button (Hidden on Desktop) -->
        <button class="mobile-menu-btn" style="display: none; background: none; border: none; font-size: 24px; color: var(--main-pink); cursor: pointer;" onclick="document.getElementById('mobileMenu').classList.toggle('active')">
            <i class="fas fa-bars"></i>
        </button>
    </div>
</nav>

<!-- Mobile Menu Overlay -->
<div id="mobileMenu" style="position: fixed; top: 80px; left: 0; width: 100%; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); box-shadow: var(--shadow-md); z-index: 999; display: none; flex-direction: column; padding: var(--space-md);">
    <a href="<?= SITE_URL ?>/index.php" style="padding: 12px; border-bottom: 1px solid rgba(0,0,0,0.05); color: var(--dark-gray); font-weight: 500;">Home</a>
    <a href="<?= SITE_URL ?>/search.php" style="padding: 12px; border-bottom: 1px solid rgba(0,0,0,0.05); color: var(--dark-gray); font-weight: 500;">Find Care</a>
    <a href="<?= SITE_URL ?>/index.php#how-it-works" style="padding: 12px; border-bottom: 1px solid rgba(0,0,0,0.05); color: var(--dark-gray); font-weight: 500;">How It Works</a>
    <a href="<?= SITE_URL ?>/index.php#features" style="padding: 12px; border-bottom: 1px solid rgba(0,0,0,0.05); color: var(--dark-gray); font-weight: 500;">Features</a>
    <a href="<?= SITE_URL ?>/index.php#pricing" style="padding: 12px; border-bottom: 1px solid rgba(0,0,0,0.05); color: var(--dark-gray); font-weight: 500;">Pricing</a>
    <a href="<?= SITE_URL ?>/about.php" style="padding: 12px; border-bottom: 1px solid rgba(0,0,0,0.05); color: var(--dark-gray); font-weight: 500;">About Us</a>
    
    <div style="display: flex; flex-direction: column; gap: 8px; margin-top: 16px;">
        <?php if(isLoggedIn()): ?>
            <a href="<?= SITE_URL ?>/<?= $_SESSION['user_role'] ?>/dashboard.php" class="btn btn-outline" style="width: 100%;">Dashboard</a>
            <a href="<?= SITE_URL ?>/logout.php" class="btn btn-primary" style="width: 100%;">Logout</a>
        <?php else: ?>
            <a href="<?= SITE_URL ?>/login.php" class="btn btn-outline" style="width: 100%;">Log In</a>
            <a href="<?= SITE_URL ?>/register.php" class="btn btn-primary" style="width: 100%;">Sign Up</a>
        <?php endif; ?>
    </div>
</div>

<style>
    @media (max-width: 991px) {
        .desktop-menu { display: none !important; }
        .mobile-menu-btn { display: block !important; }
    }
    #mobileMenu.active { display: flex !important; }
</style>
