<?php
/**
 * About Us Page
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/constants.php';
$pageTitle = 'About Us';
require_once __DIR__ . '/includes/header.php';
?>

<div style="background-color: var(--baby-pink); padding: var(--space-3xl) 0;">
    <div class="container text-center">
        <h1 style="color: var(--dark-pink); margin-bottom: var(--space-md);">About Little Steps</h1>
        <p style="font-size: 1.125rem; color: var(--dark-gray); max-width: 700px; margin: 0 auto;">
            We are on a mission to simplify childcare for modern, working parents by connecting them with trusted, verified, and flexible daycare centers.
        </p>
    </div>
</div>

<div class="container" style="padding: var(--space-3xl) 0;">
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-3xl); align-items: center;">
        <div>
            <h2 style="color: var(--main-pink); margin-bottom: var(--space-md);">Our Story</h2>
            <p>Founded in 2026, Little Steps was born out of a simple realization: working parents need more flexible childcare options, and daycare centers need a better way to manage their operations and connect with families.</p>
            <p>We built this platform to bridge that gap. Whether it's standard daytime care, late-night shifts, or emergency drop-ins, we ensure that parents can always find a safe haven for their little ones.</p>
            
            <div style="margin-top: var(--space-lg); display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-md);">
                <div class="card" style="padding: var(--space-md); text-align: center; margin: 0;">
                    <h3 style="color: var(--main-pink); font-size: 2rem; margin: 0;">10k+</h3>
                    <p style="font-size: 14px; margin: 0; color: var(--medium-gray);">Happy Parents</p>
                </div>
                <div class="card" style="padding: var(--space-md); text-align: center; margin: 0;">
                    <h3 style="color: var(--main-pink); font-size: 2rem; margin: 0;">500+</h3>
                    <p style="font-size: 14px; margin: 0; color: var(--medium-gray);">Verified Centers</p>
                </div>
            </div>
        </div>
        <div>
            <div style="width: 100%; height: 400px; background: var(--light-pink); border-radius: var(--radius-xl); overflow: hidden; box-shadow: var(--shadow-lg);">
                <div style="width: 100%; height: 100%; background: url('<?= SITE_URL ?>/assets/images/about-story.jpg') center/cover;"></div>
            </div>
        </div>
    </div>
</div>

<style>
    @media (max-width: 991px) {
        .container > div[style*="grid-template-columns"] { grid-template-columns: 1fr !important; }
    }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
