<?php
/**
 * Services Page
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/constants.php';

$pageTitle = 'Our Services';
require_once __DIR__ . '/includes/header.php';
?>

<div style="background-color: var(--baby-pink); padding: var(--space-3xl) 0;">
    <div class="container text-center">
        <h1 style="color: var(--dark-pink); margin-bottom: var(--space-md);">Our Services</h1>
        <p style="font-size: 1.125rem; color: var(--dark-gray); max-width: 700px; margin: 0 auto;">
            Flexible childcare options designed to fit the diverse needs of modern working families.
        </p>
    </div>
</div>

<div class="container" style="padding: var(--space-3xl) 0;">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: var(--space-xl);">
        
        <!-- Service 1 -->
        <div class="card" style="margin: 0; display: flex; flex-direction: column; height: 100%;">
            <div style="width: 64px; height: 64px; background: var(--baby-pink); color: var(--main-pink); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 28px; margin-bottom: var(--space-md);">
                <i class="fas fa-sun"></i>
            </div>
            <h3 style="color: var(--dark-pink); margin-bottom: var(--space-sm);">Standard Daycare</h3>
            <p style="color: var(--medium-gray); flex-grow: 1;">Regular daytime care for infants and toddlers with structured activities, meals, and nap times. Perfect for standard 9-to-5 working parents.</p>
            <div style="margin-top: var(--space-md); padding-top: var(--space-md); border-top: 1px solid var(--light-pink);">
                <a href="search.php?type=daycare" class="btn btn-outline" style="width: 100%;">Find Daycares</a>
            </div>
        </div>
        
        <!-- Service 2 -->
        <div class="card" style="margin: 0; display: flex; flex-direction: column; height: 100%;">
            <div style="width: 64px; height: 64px; background: var(--baby-pink); color: var(--main-pink); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 28px; margin-bottom: var(--space-md);">
                <i class="fas fa-moon"></i>
            </div>
            <h3 style="color: var(--dark-pink); margin-bottom: var(--space-sm);">Night Care (24x7)</h3>
            <p style="color: var(--medium-gray); flex-grow: 1;">Overnight care designed for parents who work night shifts. Safe, secure sleeping arrangements with trained night-time caregivers.</p>
            <div style="margin-top: var(--space-md); padding-top: var(--space-md); border-top: 1px solid var(--light-pink);">
                <a href="search.php?is_24x7=1" class="btn btn-outline" style="width: 100%;">Find 24x7 Centers</a>
            </div>
        </div>
        
        <!-- Service 3 -->
        <div class="card" style="margin: 0; display: flex; flex-direction: column; height: 100%;">
            <div style="width: 64px; height: 64px; background: var(--baby-pink); color: var(--main-pink); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 28px; margin-bottom: var(--space-md);">
                <i class="fas fa-school"></i>
            </div>
            <h3 style="color: var(--dark-pink); margin-bottom: var(--space-sm);">After-School Care</h3>
            <p style="color: var(--medium-gray); flex-grow: 1;">For school-aged children needing care between school dismissal and when parents finish work. Includes homework help and activities.</p>
            <div style="margin-top: var(--space-md); padding-top: var(--space-md); border-top: 1px solid var(--light-pink);">
                <a href="search.php?type=after_school" class="btn btn-outline" style="width: 100%;">Find After-School Care</a>
            </div>
        </div>
        
        <!-- Service 4 -->
        <div class="card" style="margin: 0; display: flex; flex-direction: column; height: 100%;">
            <div style="width: 64px; height: 64px; background: var(--baby-pink); color: var(--main-pink); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 28px; margin-bottom: var(--space-md);">
                <i class="fas fa-clock"></i>
            </div>
            <h3 style="color: var(--dark-pink); margin-bottom: var(--space-sm);">Hourly Drop-in</h3>
            <p style="color: var(--medium-gray); flex-grow: 1;">Need a few hours to run errands or attend a meeting? Book hourly drop-in slots at participating centers near you.</p>
            <div style="margin-top: var(--space-md); padding-top: var(--space-md); border-top: 1px solid var(--light-pink);">
                <a href="search.php?booking_type=hourly" class="btn btn-outline" style="width: 100%;">Find Hourly Care</a>
            </div>
        </div>
        
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
