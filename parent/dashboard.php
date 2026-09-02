<?php
/**
 * Parent Dashboard
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
requireRole('parent');

$pageTitleHeader = 'Dashboard';
$pageTitle = 'Dashboard';

$conn = getDBConnection();
$userId = $_SESSION['user_id'];

// Get Upcoming Bookings
$upcomingStmt = $conn->prepare("
    SELECT b.*, c.name as center_name, c.area, c.city 
    FROM bookings b
    JOIN daycare_centers c ON b.center_id = c.id
    WHERE b.user_id = ? AND b.start_datetime > NOW() AND b.status IN ('pending', 'confirmed')
    ORDER BY b.start_datetime ASC LIMIT 3
");
$upcomingStmt->bind_param("i", $userId);
$upcomingStmt->execute();
$upcomingBookings = $upcomingStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get Active Subscriptions
$subStmt = $conn->prepare("
    SELECT s.*, c.name as center_name 
    FROM subscriptions s
    JOIN daycare_centers c ON s.center_id = c.id
    WHERE s.user_id = ? AND s.status = 'active'
");
$subStmt->bind_param("i", $userId);
$subStmt->execute();
$subscriptions = $subStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$conn->close();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Quick Actions -->
<div class="stat-card-row">
    <div class="card stat-card" style="margin-bottom: 0;">
        <div class="stat-icon" style="background: var(--info-bg); color: var(--info);">
            <i class="fas fa-calendar-check"></i>
        </div>
        <div class="stat-details" style="flex-grow: 1;">
            <div class="stat-value"><?= count($upcomingBookings) ?></div>
            <div class="stat-label">Upcoming Bookings</div>
        </div>
        <a href="bookings.php" class="btn btn-sm btn-outline">View All</a>
    </div>
    
    <div class="card stat-card" style="margin-bottom: 0;">
        <div class="stat-icon" style="background: var(--success-bg); color: var(--success);">
            <i class="fas fa-redo"></i>
        </div>
        <div class="stat-details" style="flex-grow: 1;">
            <div class="stat-value"><?= count($subscriptions) ?></div>
            <div class="stat-label">Active Subscriptions</div>
        </div>
        <a href="subscriptions.php" class="btn btn-sm btn-outline">Manage</a>
    </div>
    
    <div class="card" style="margin-bottom: 0; display: flex; align-items: center; justify-content: center; background: var(--main-pink); color: white;">
        <a href="centers.php" style="color: white; text-align: center; text-decoration: none; width: 100%; display: block; padding: var(--space-sm) 0;">
            <i class="fas fa-plus-circle" style="font-size: 32px; margin-bottom: 8px; display: block;"></i>
            <span style="font-weight: 600;">Book New Childcare</span>
        </a>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: var(--space-lg); margin-top: var(--space-xl);">
    
    <!-- Upcoming Bookings -->
    <div>
        <h3 style="color: var(--dark-pink); margin-bottom: var(--space-md);">Upcoming Care</h3>
        
        <?php if (count($upcomingBookings) > 0): ?>
            <?php foreach ($upcomingBookings as $booking): ?>
                <div class="card" style="display: flex; gap: var(--space-md); align-items: stretch; padding: 0; overflow: hidden; margin-bottom: var(--space-md);">
                    <div style="background: var(--baby-pink); padding: var(--space-md); text-align: center; display: flex; flex-direction: column; justify-content: center; min-width: 100px;">
                        <span style="font-size: 14px; font-weight: 600; color: var(--main-pink); text-transform: uppercase;"><?= date('M', strtotime($booking['start_datetime'])) ?></span>
                        <span style="font-size: 28px; font-weight: 700; color: var(--dark-pink); line-height: 1;"><?= date('d', strtotime($booking['start_datetime'])) ?></span>
                        <span style="font-size: 12px; color: var(--medium-gray);"><?= date('l', strtotime($booking['start_datetime'])) ?></span>
                    </div>
                    <div style="padding: var(--space-md) var(--space-md) var(--space-md) 0; flex-grow: 1;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <h4 style="margin-bottom: 4px; font-size: 16px;"><a href="center-details.php?id=<?= $booking['center_id'] ?>" style="color: var(--dark-gray);"><?= htmlspecialchars($booking['center_name']) ?></a></h4>
                            <?php if ($booking['status'] == 'confirmed'): ?>
                                <span class="badge badge-success">Confirmed</span>
                            <?php else: ?>
                                <span class="badge badge-warning">Pending</span>
                            <?php endif; ?>
                        </div>
                        <p style="color: var(--medium-gray); font-size: 13px; margin-bottom: 8px;">
                            <i class="fas fa-clock"></i> <?= date('h:i A', strtotime($booking['start_datetime'])) ?> - <?= date('h:i A', strtotime($booking['end_datetime'])) ?>
                        </p>
                        <p style="color: var(--medium-gray); font-size: 13px; margin-bottom: 0;">
                            <i class="fas fa-child"></i> Care for <strong><?= $booking['child_name'] ?></strong> (<?= round($booking['child_age_months'] / 12, 1) ?> yrs)
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="card text-center" style="padding: var(--space-2xl) var(--space-md);">
                <div style="font-size: 48px; color: var(--light-pink); margin-bottom: var(--space-md);">
                    <i class="fas fa-calendar-times"></i>
                </div>
                <p style="color: var(--medium-gray); margin-bottom: var(--space-md);">You don't have any upcoming bookings.</p>
                <a href="centers.php" class="btn btn-primary">Find a Center</a>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Sidebar / Active Subscriptions -->
    <div>
        <h3 style="color: var(--dark-pink); margin-bottom: var(--space-md);">Active Subscriptions</h3>
        
        <?php if (count($subscriptions) > 0): ?>
            <?php foreach ($subscriptions as $sub): ?>
                <div class="card subscription-card active" style="margin-bottom: var(--space-md); text-align: left; padding: var(--space-md);">
                    <h4 style="font-size: 16px; margin-bottom: 4px;"><?= htmlspecialchars($sub['center_name']) ?></h4>
                    <span class="badge badge-success mb-2">Monthly Plan</span>
                    <p style="color: var(--medium-gray); font-size: 13px; margin-bottom: 8px;">
                        Child: <strong><?= htmlspecialchars($sub['child_name']) ?></strong>
                    </p>
                    <p style="color: var(--medium-gray); font-size: 13px; margin-bottom: 0;">
                        Renews: <?= date('d M Y', strtotime($sub['end_date'])) ?>
                    </p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="card text-center" style="padding: var(--space-xl) var(--space-md);">
                <p style="color: var(--medium-gray); font-size: 14px; margin-bottom: var(--space-md);">No active monthly subscriptions.</p>
                <a href="centers.php" class="btn btn-outline btn-sm">Explore Plans</a>
            </div>
        <?php endif; ?>
    </div>
    
</div>

<style>
    @media (max-width: 991px) {
        div[style*="grid-template-columns: 2fr 1fr"] { grid-template-columns: 1fr !important; }
    }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
