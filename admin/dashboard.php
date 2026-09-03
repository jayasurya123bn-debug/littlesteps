<?php
/**
 * Admin Dashboard
 * Little Steps Childcare Platform
 * Complete 40% Pink Theme Implementation
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
requireRole('admin');

$pageTitleHeader = 'System Overview';
$pageTitle = 'Dashboard';

$conn = getDBConnection();

// 1. Fetch Stats
$stats = [
    'parents' => 0,
    'providers' => 0,
    'bookings' => 0,
    'revenue' => 0
];

// Parents count
$res = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE role = 'parent'");
if ($res) $stats['parents'] = $res->fetch_assoc()['cnt'];

// Providers count
$res = $conn->query("SELECT COUNT(*) as cnt FROM providers");
if ($res) $stats['providers'] = $res->fetch_assoc()['cnt'];

// Bookings count & Platform Revenue (Total booking volume * 10% platform share)
$res = $conn->query("SELECT COUNT(*) as cnt, SUM(final_amount) as total_val FROM bookings");
if ($res) {
    $row = $res->fetch_assoc();
    $stats['bookings'] = $row['cnt'] ?? 0;
    $stats['revenue'] = ($row['total_val'] ?? 0) * 0.10;
}

// 2. Pending Approvals count
$pendingProvidersCount = 0;
$res = $conn->query("SELECT COUNT(*) as cnt FROM providers WHERE status = 'pending'");
if ($res) $pendingProvidersCount = $res->fetch_assoc()['cnt'];

$pendingDocsCount = 0;
$res = $conn->query("SELECT COUNT(*) as cnt FROM documents WHERE status = 'pending'");
if ($res) $pendingDocsCount = $res->fetch_assoc()['cnt'];

// 3. Recent Bookings (latest 8)
$recentBookings = [];
$bQuery = "SELECT b.id, b.booking_code, b.child_name, b.final_amount, b.status, b.created_at,
                  u.first_name, u.last_name, 
                  c.name as center_name, p.business_name
           FROM bookings b
           JOIN users u ON b.user_id = u.id
           JOIN daycare_centers c ON b.center_id = c.id
           JOIN providers p ON c.provider_id = p.id
           ORDER BY b.created_at DESC LIMIT 8";
$res = $conn->query($bQuery);
if ($res) {
    while($row = $res->fetch_assoc()) {
        $recentBookings[] = $row;
    }
}

// 4. Pending Providers for approval widget
$pendingProviders = [];
$res = $conn->query("SELECT id, business_name, owner_name, city, created_at FROM providers WHERE status = 'pending' ORDER BY created_at DESC LIMIT 5");
if ($res) {
    while($row = $res->fetch_assoc()) {
        $pendingProviders[] = $row;
    }
}

$conn->close();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Stat Cards Row -->
<div class="admin-stats-grid">
    <div class="admin-stat-card">
        <div class="admin-stat-icon">
            <i class="fas fa-child"></i>
        </div>
        <div class="admin-stat-details">
            <div class="stat-val"><?= number_format($stats['parents']) ?></div>
            <div class="stat-lbl">Registered Parents</div>
        </div>
    </div>
    
    <div class="admin-stat-card">
        <div class="admin-stat-icon">
            <i class="fas fa-building"></i>
        </div>
        <div class="admin-stat-details">
            <div class="stat-val"><?= number_format($stats['providers']) ?></div>
            <div class="stat-lbl">Daycare Providers</div>
        </div>
    </div>
    
    <div class="admin-stat-card">
        <div class="admin-stat-icon">
            <i class="fas fa-calendar-check"></i>
        </div>
        <div class="admin-stat-details">
            <div class="stat-val"><?= number_format($stats['bookings']) ?></div>
            <div class="stat-lbl">Total Bookings</div>
        </div>
    </div>
    
    <div class="admin-stat-card">
        <div class="admin-stat-icon">
            <i class="fas fa-wallet"></i>
        </div>
        <div class="admin-stat-details">
            <div class="stat-val"><?= formatCurrency($stats['revenue']) ?></div>
            <div class="stat-lbl">Platform Revenue (10%)</div>
        </div>
    </div>
</div>

<!-- Pending Approvals & Quick Actions Row -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-bottom: 24px;">
    <!-- Pending Approvals Box -->
    <div class="detail-card" style="margin-bottom: 0;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
            <h3 style="margin: 0; font-size: 18px; color: var(--admin-dark-pink);">
                <i class="fas fa-user-check" style="margin-right: 8px;"></i> Pending Provider Approvals
            </h3>
            <span class="badge badge-pending"><?= $pendingProvidersCount ?> Awaiting</span>
        </div>
        
        <?php if (empty($pendingProviders)): ?>
            <div style="text-align: center; padding: 24px 0; color: #9E9E9E;">
                <i class="fas fa-check-circle" style="font-size: 36px; color: #4CAF50; margin-bottom: 8px;"></i>
                <p style="margin: 0;">All providers have been reviewed! No pending applications.</p>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <?php foreach($pendingProviders as $prov): ?>
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; background: #FFF0F5; border-radius: 8px; border: 1px solid var(--admin-pastel-pink);">
                        <div>
                            <strong style="color: #212121;"><?= htmlspecialchars($prov['business_name']) ?></strong>
                            <div style="font-size: 12px; color: #616161;">
                                Owner: <?= htmlspecialchars($prov['owner_name']) ?> • <?= htmlspecialchars($prov['city']) ?> • Applied <?= formatDate($prov['created_at']) ?>
                            </div>
                        </div>
                        <div style="display: flex; gap: 8px;">
                            <a href="provider_view.php?id=<?= $prov['id'] ?>" class="btn btn-primary" style="padding: 6px 12px; font-size: 12px;">Review</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Quick Actions Card -->
    <div class="detail-card" style="margin-bottom: 0;">
        <h3 style="margin: 0 0 16px 0; font-size: 18px; color: var(--admin-dark-pink);">
            <i class="fas fa-bolt" style="margin-right: 8px;"></i> Quick Actions
        </h3>
        <div style="display: flex; flex-direction: column; gap: 10px;">
            <a href="users.php" class="btn btn-secondary" style="justify-content: flex-start; gap: 10px; width: 100%;">
                <i class="fas fa-user-friends"></i> Manage Parents
            </a>
            <a href="providers.php" class="btn btn-secondary" style="justify-content: flex-start; gap: 10px; width: 100%;">
                <i class="fas fa-building"></i> Verify Providers
            </a>
            <a href="reports.php" class="btn btn-secondary" style="justify-content: flex-start; gap: 10px; width: 100%;">
                <i class="fas fa-chart-line"></i> Download Reports
            </a>
            <a href="settings.php" class="btn btn-secondary" style="justify-content: flex-start; gap: 10px; width: 100%;">
                <i class="fas fa-cogs"></i> System Settings
            </a>
        </div>
    </div>
</div>

<!-- Booking Statistics Chart -->
<div class="detail-card" style="margin-bottom: 24px;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
        <h3 style="margin: 0; font-size: 18px; color: var(--admin-dark-pink);">
            <i class="fas fa-chart-area" style="margin-right: 8px;"></i> Monthly Booking Activity
        </h3>
        <span style="font-size: 12px; color: #9E9E9E;">Current Year Performance</span>
    </div>
    <div style="height: 250px; position: relative;">
        <canvas id="adminBookingsChart"></canvas>
    </div>
</div>

<!-- Recent Bookings Table -->
<div class="table-container">
    <div class="table-header-bar">
        <h3 class="table-header-title">
            <i class="fas fa-history"></i> Recent Platform Bookings
        </h3>
        <a href="bookings.php" class="btn btn-primary" style="padding: 6px 14px; font-size: 13px;">View All Bookings</a>
    </div>
    
    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Booking Code</th>
                    <th>Parent</th>
                    <th>Child</th>
                    <th>Provider / Center</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($recentBookings)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 32px; color: #9E9E9E;">
                            No bookings found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach($recentBookings as $b): ?>
                        <tr>
                            <td><strong style="color: var(--admin-pink);"><?= htmlspecialchars($b['booking_code']) ?></strong></td>
                            <td><?= htmlspecialchars($b['first_name'] . ' ' . $b['last_name']) ?></td>
                            <td><?= htmlspecialchars($b['child_name']) ?></td>
                            <td>
                                <div><strong><?= htmlspecialchars($b['center_name']) ?></strong></div>
                                <div style="font-size: 12px; color: #757575;"><?= htmlspecialchars($b['business_name']) ?></div>
                            </td>
                            <td><strong><?= formatCurrency($b['final_amount']) ?></strong></td>
                            <td>
                                <span class="badge badge-<?= htmlspecialchars($b['status']) ?>">
                                    <?= htmlspecialchars($b['status']) ?>
                                </span>
                            </td>
                            <td><?= formatDate($b['created_at'], 'd M, H:i') ?></td>
                            <td style="text-align: right;">
                                <a href="bookings.php?code=<?= urlencode($b['booking_code']) ?>" class="btn-icon btn-icon-view" title="View Booking Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Chart.js CDN & Initialization -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('adminBookingsChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [{
                    label: 'Bookings Volume',
                    data: [12, 19, 28, 35, 42, 60, 75, 90, 110, 125, 140, 165],
                    borderColor: '#E91E63',
                    backgroundColor: 'rgba(233, 30, 99, 0.08)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.35,
                    pointBackgroundColor: '#E91E63'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#FCE4EC' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
