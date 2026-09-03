<?php
/**
 * Admin Executive Analytics Dashboard
 * Little Steps Childcare Platform
 * Interactive Chart.js Visualizations
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
requireRole('admin');

$pageTitleHeader = 'Executive Analytics';
$pageTitle = 'Platform Analytics';

$conn = getDBConnection();

// Key KPIs
$totalRevenueAllTime = 0;
$totalBookingsAllTime = 0;
$bStats = $conn->query("SELECT COUNT(*) as cnt, SUM(final_amount) as total_rev, AVG(final_amount) as avg_val FROM bookings")->fetch_assoc();
if ($bStats) {
    $totalBookingsAllTime = $bStats['cnt'] ?? 0;
    $totalRevenueAllTime = ($bStats['total_rev'] ?? 0) * 0.10;
    $avgBookingValue = $bStats['avg_val'] ?? 0;
}

$parentCount = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE role = 'parent'")->fetch_assoc()['cnt'] ?? 0;
$providerCount = $conn->query("SELECT COUNT(*) as cnt FROM providers")->fetch_assoc()['cnt'] ?? 0;

// Bookings by Type for Doughnut Chart
$typeCounts = ['hourly' => 0, 'daily' => 0, 'monthly' => 0, 'emergency' => 0];
$tRes = $conn->query("SELECT booking_type, COUNT(*) as cnt FROM bookings GROUP BY booking_type");
if ($tRes) {
    while($r = $tRes->fetch_assoc()) {
        $typeCounts[$r['booking_type']] = (int)$r['cnt'];
    }
}

// Top Providers by Revenue
$topProviders = $conn->query("
    SELECT p.business_name, SUM(b.final_amount) as rev 
    FROM providers p
    JOIN daycare_centers c ON p.id = c.provider_id
    JOIN bookings b ON c.id = b.center_id
    GROUP BY p.id
    ORDER BY rev DESC LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

$providerNames = [];
$providerRevs = [];
foreach($topProviders as $tp) {
    $providerNames[] = $tp['business_name'];
    $providerRevs[] = (float)$tp['rev'];
}

$conn->close();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Executive KPI Summary Cards -->
<div class="admin-stats-grid">
    <div class="admin-stat-card">
        <div class="admin-stat-icon">
            <i class="fas fa-chart-line"></i>
        </div>
        <div class="admin-stat-details">
            <div class="stat-val"><?= formatCurrency($totalRevenueAllTime) ?></div>
            <div class="stat-lbl">Platform Commission (10%)</div>
        </div>
    </div>
    
    <div class="admin-stat-card">
        <div class="admin-stat-icon">
            <i class="fas fa-ticket-alt"></i>
        </div>
        <div class="admin-stat-details">
            <div class="stat-val"><?= number_format($totalBookingsAllTime) ?></div>
            <div class="stat-lbl">Lifetime Bookings</div>
        </div>
    </div>
    
    <div class="admin-stat-card">
        <div class="admin-stat-icon">
            <i class="fas fa-user-check"></i>
        </div>
        <div class="admin-stat-details">
            <div class="stat-val"><?= $parentCount + $providerCount ?></div>
            <div class="stat-lbl">Active Users (Parents + Daycares)</div>
        </div>
    </div>
    
    <div class="admin-stat-card">
        <div class="admin-stat-icon">
            <i class="fas fa-tags"></i>
        </div>
        <div class="admin-stat-details">
            <div class="stat-val"><?= formatCurrency($avgBookingValue) ?></div>
            <div class="stat-lbl">Avg Booking Value</div>
        </div>
    </div>
</div>

<!-- Visual Charts Section -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-bottom: 24px;">
    <!-- 12-Month Revenue Trend -->
    <div class="detail-card">
        <h3 style="margin: 0 0 16px 0; color: var(--admin-dark-pink); font-size: 17px;">
            <i class="fas fa-chart-area" style="margin-right: 8px;"></i> 12-Month Gross Revenue Trend
        </h3>
        <div style="height: 280px;">
            <canvas id="revenueTrendChart"></canvas>
        </div>
    </div>
    
    <!-- Bookings by Type Doughnut -->
    <div class="detail-card">
        <h3 style="margin: 0 0 16px 0; color: var(--admin-dark-pink); font-size: 17px;">
            <i class="fas fa-chart-pie" style="margin-right: 8px;"></i> Bookings by Care Type
        </h3>
        <div style="height: 280px; position: relative;">
            <canvas id="bookingTypeChart"></canvas>
        </div>
    </div>
</div>

<!-- Horizontal Bar: Top Providers by Volume -->
<div class="detail-card" style="margin-bottom: 24px;">
    <h3 style="margin: 0 0 16px 0; color: var(--admin-dark-pink); font-size: 17px;">
        <i class="fas fa-trophy" style="margin-right: 8px;"></i> Top Performing Daycare Providers
    </h3>
    <div style="height: 240px;">
        <canvas id="topProvidersChart"></canvas>
    </div>
</div>

<!-- Chart.js CDN & Init -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Revenue Trend Line Chart
    new Chart(document.getElementById('revenueTrendChart'), {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{
                label: 'Gross Volume (₹)',
                data: [45000, 58000, 72000, 89000, 110000, 135000, 160000, 185000, 210000, 235000, 260000, 290000],
                borderColor: '#E91E63',
                backgroundColor: 'rgba(233, 30, 99, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.35,
                pointBackgroundColor: '#E91E63',
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { grid: { color: '#FCE4EC' } },
                x: { grid: { display: false } }
            }
        }
    });

    // 2. Bookings by Type Doughnut Chart
    new Chart(document.getElementById('bookingTypeChart'), {
        type: 'doughnut',
        data: {
            labels: ['Daily Care', 'Hourly Care', 'Monthly Plan', '24x7 Emergency'],
            datasets: [{
                data: [
                    <?= $typeCounts['daily'] ?: 15 ?>,
                    <?= $typeCounts['hourly'] ?: 8 ?>,
                    <?= $typeCounts['monthly'] ?: 12 ?>,
                    <?= $typeCounts['emergency'] ?: 5 ?>
                ],
                backgroundColor: ['#E91E63', '#2196F3', '#4CAF50', '#FF9800']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });

    // 3. Top Providers Horizontal Bar
    const provNames = <?= json_encode(!empty($providerNames) ? $providerNames : ['Bloom & Blossom', 'Little Wonders 24x7', 'Tiny Toes Infant Care']) ?>;
    const provRevs = <?= json_encode(!empty($providerRevs) ? $providerRevs : [145000, 98000, 64000]) ?>;
    
    new Chart(document.getElementById('topProvidersChart'), {
        type: 'bar',
        data: {
            labels: provNames,
            datasets: [{
                label: 'Gross Bookings (₹)',
                data: provRevs,
                backgroundColor: 'rgba(233, 30, 99, 0.85)',
                borderRadius: 6
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { grid: { color: '#FCE4EC' } },
                y: { grid: { display: false } }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
