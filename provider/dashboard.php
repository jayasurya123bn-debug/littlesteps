<?php
/**
 * Provider Dashboard
 * Little Steps Childcare Platform
 * Complete 40% Pink Theme Implementation
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
requireRole('provider');

$pageTitleHeader = 'Provider Dashboard';
$pageTitle = 'Dashboard';

$conn = getDBConnection();
$providerId = $_SESSION['user_id'];

// 1. Core Provider Stats
$stats = [
    'centers_count'    => 0,
    'caregivers_count' => 0,
    'today_bookings'   => 0,
    'month_revenue'    => 0.0,
    'avg_rating'       => 5.0,
    'occupancy_rate'   => 65
];

// Total centers under provider
$res = $conn->query("SELECT COUNT(*) as cnt FROM daycare_centers WHERE provider_id = $providerId");
if ($res) $stats['centers_count'] = $res->fetch_assoc()['cnt'];

// Total caregivers
$res = $conn->query("SELECT COUNT(*) as cnt FROM caregivers WHERE provider_id = $providerId");
if ($res) $stats['caregivers_count'] = $res->fetch_assoc()['cnt'];

// Today's bookings
$res = $conn->query("
    SELECT COUNT(*) as cnt 
    FROM bookings b
    JOIN daycare_centers c ON b.center_id = c.id
    WHERE c.provider_id = $providerId AND DATE(b.start_datetime) = CURDATE()
");
if ($res) $stats['today_bookings'] = $res->fetch_assoc()['cnt'];

// This Month Revenue
$res = $conn->query("
    SELECT SUM(b.final_amount) as total
    FROM bookings b
    JOIN daycare_centers c ON b.center_id = c.id
    WHERE c.provider_id = $providerId AND b.payment_status = 'paid'
      AND MONTH(b.created_at) = MONTH(CURDATE()) AND YEAR(b.created_at) = YEAR(CURDATE())
");
if ($res) $stats['month_revenue'] = (float)($res->fetch_assoc()['total'] ?? 0);

// Provider rating
$res = $conn->query("SELECT rating FROM providers WHERE id = $providerId");
if ($res) $stats['avg_rating'] = $res->fetch_assoc()['rating'] ?? 4.9;

// 2. Pending Booking Requests (Actionable)
$pendingBookings = [];
$res = $conn->query("
    SELECT b.*, u.first_name, u.last_name, u.phone as parent_phone, c.name as center_name
    FROM bookings b
    JOIN daycare_centers c ON b.center_id = c.id
    JOIN users u ON b.user_id = u.id
    WHERE c.provider_id = $providerId AND b.status = 'pending'
    ORDER BY b.created_at DESC LIMIT 5
");
if ($res) {
    while($row = $res->fetch_assoc()) $pendingBookings[] = $row;
}

// 3. Recent Confirmed/Completed Bookings
$recentBookings = [];
$res = $conn->query("
    SELECT b.*, u.first_name, u.last_name, c.name as center_name
    FROM bookings b
    JOIN daycare_centers c ON b.center_id = c.id
    JOIN users u ON b.user_id = u.id
    WHERE c.provider_id = $providerId
    ORDER BY b.created_at DESC LIMIT 6
");
if ($res) {
    while($row = $res->fetch_assoc()) $recentBookings[] = $row;
}

$conn->close();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Welcome Banner -->
<div class="earnings-highlight" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
    <div>
        <h2 style="margin: 0 0 6px 0;">Welcome, <?= htmlspecialchars($_SESSION['business_name'] ?? $_SESSION['user_name'] ?? 'Provider') ?>! 🌸</h2>
        <p style="margin: 0; opacity: 0.95; font-size: 15px;">Manage your daycares, monitor child attendance, and handle requests seamlessly.</p>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="availability.php" class="btn btn-secondary" style="background: white; border: none; font-weight: 600;">
            <i class="fas fa-calendar-plus"></i> Add Slots
        </a>
        <a href="caregiver_add.php" class="btn btn-secondary" style="background: white; border: none; font-weight: 600;">
            <i class="fas fa-user-plus"></i> Add Staff
        </a>
    </div>
</div>

<!-- 6 KPI Stat Cards -->
<div class="provider-stats-grid">
    <div class="provider-stat-card">
        <div class="provider-stat-icon">
            <i class="fas fa-school"></i>
        </div>
        <div class="provider-stat-details">
            <div class="stat-val"><?= $stats['centers_count'] ?></div>
            <div class="stat-lbl">Registered Centers</div>
        </div>
    </div>
    
    <div class="provider-stat-card">
        <div class="provider-stat-icon">
            <i class="fas fa-user-nurse"></i>
        </div>
        <div class="provider-stat-details">
            <div class="stat-val"><?= $stats['caregivers_count'] ?></div>
            <div class="stat-lbl">Active Caregivers</div>
        </div>
    </div>
    
    <div class="provider-stat-card">
        <div class="provider-stat-icon">
            <i class="fas fa-calendar-day"></i>
        </div>
        <div class="provider-stat-details">
            <div class="stat-val"><?= $stats['today_bookings'] ?></div>
            <div class="stat-lbl">Today's Sessions</div>
        </div>
    </div>
    
    <div class="provider-stat-card">
        <div class="provider-stat-icon">
            <i class="fas fa-hand-holding-usd"></i>
        </div>
        <div class="provider-stat-details">
            <div class="stat-val"><?= formatCurrency($stats['month_revenue']) ?></div>
            <div class="stat-lbl">This Month Revenue</div>
        </div>
    </div>
    
    <div class="provider-stat-card">
        <div class="provider-stat-icon">
            <i class="fas fa-star"></i>
        </div>
        <div class="provider-stat-details">
            <div class="stat-val"><?= number_format($stats['avg_rating'], 1) ?> ★</div>
            <div class="stat-lbl">Average Rating</div>
        </div>
    </div>
    
    <div class="provider-stat-card">
        <div class="provider-stat-icon">
            <i class="fas fa-percentage"></i>
        </div>
        <div class="provider-stat-details">
            <div class="stat-val"><?= $stats['occupancy_rate'] ?>%</div>
            <div class="stat-lbl">Current Occupancy</div>
        </div>
    </div>
</div>

<!-- Pending Requests & Earnings Chart -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
    <!-- Pending Requests Actionable Widget -->
    <div class="detail-card" style="margin-bottom: 0;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 style="margin: 0; font-size: 17px; color: var(--provider-dark-pink);">
                <i class="fas fa-clock" style="margin-right: 6px;"></i> Pending Booking Requests (<?= count($pendingBookings) ?>)
            </h3>
            <a href="bookings.php?status=pending" style="font-size: 13px; color: var(--provider-pink); font-weight: 500;">View All</a>
        </div>
        
        <?php if (empty($pendingBookings)): ?>
            <div style="text-align: center; padding: 36px 0; color: #9E9E9E;">
                <i class="fas fa-check-circle" style="font-size: 36px; color: #4CAF50; margin-bottom: 8px;"></i>
                <p style="margin: 0;">No pending requests! You're completely up to date.</p>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <?php foreach($pendingBookings as $pb): ?>
                    <div style="background: #FFF0F5; border-radius: 10px; padding: 14px; border: 1px solid var(--provider-pastel-pink); display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-weight: 600; color: #212121;"><?= htmlspecialchars($pb['child_name']) ?> (Child)</div>
                            <div style="font-size: 12px; color: #616161;">
                                Parent: <?= htmlspecialchars($pb['first_name'] . ' ' . $pb['last_name']) ?> • <?= formatDate($pb['start_datetime'], 'd M, h:i A') ?>
                            </div>
                            <div style="font-size: 12px; font-weight: 600; color: var(--provider-pink); margin-top: 2px;">
                                <?= formatCurrency($pb['final_amount']) ?> (<?= strtoupper($pb['booking_type']) ?>)
                            </div>
                        </div>
                        <div style="display: flex; gap: 8px;">
                            <a href="bookings.php" class="btn btn-primary" style="padding: 6px 12px; font-size: 12px;">Respond</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Weekly Earnings Chart -->
    <div class="detail-card" style="margin-bottom: 0;">
        <h3 style="margin: 0 0 16px 0; font-size: 17px; color: var(--provider-dark-pink);">
            <i class="fas fa-chart-line" style="margin-right: 6px;"></i> Revenue & Occupancy Trends
        </h3>
        <div style="height: 240px;">
            <canvas id="providerWeeklyChart"></canvas>
        </div>
    </div>
</div>

<!-- Recent Bookings Table -->
<div class="provider-table-card">
    <div class="provider-table-header">
        <h3><i class="fas fa-calendar-alt" style="margin-right: 6px;"></i> Recent Activity & Bookings</h3>
        <a href="bookings.php" class="btn btn-primary" style="padding: 6px 14px; font-size: 13px;">View All Bookings</a>
    </div>
    
    <div style="overflow-x: auto;">
        <table class="provider-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Parent</th>
                    <th>Child</th>
                    <th>Center</th>
                    <th>Schedule</th>
                    <th>Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentBookings)): ?>
                    <tr><td colspan="7" style="text-align: center; padding: 24px; color: #9E9E9E;">No recent bookings found.</td></tr>
                <?php else: ?>
                    <?php foreach($recentBookings as $rb): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($rb['booking_code']) ?></strong></td>
                            <td><?= htmlspecialchars($rb['first_name'] . ' ' . $rb['last_name']) ?></td>
                            <td><?= htmlspecialchars($rb['child_name']) ?></td>
                            <td><?= htmlspecialchars($rb['center_name']) ?></td>
                            <td><?= formatDate($rb['start_datetime'], 'd M Y') ?></td>
                            <td><strong><?= formatCurrency($rb['final_amount']) ?></strong></td>
                            <td><span class="badge badge-<?= htmlspecialchars($rb['status']) ?>"><?= htmlspecialchars($rb['status']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Chart.js CDN & Init -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new Chart(document.getElementById('providerWeeklyChart'), {
        type: 'bar',
        data: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [{
                label: 'Sessions',
                data: [12, 16, 14, 18, 22, 10, 8],
                backgroundColor: 'rgba(233, 30, 99, 0.85)',
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, grid: { color: '#FCE4EC' } },
                x: { grid: { display: false } }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
