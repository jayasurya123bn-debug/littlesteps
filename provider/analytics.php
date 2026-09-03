<?php
/**
 * Provider Business Analytics Dashboard
 * Little Steps Childcare Platform
 * Interactive Visual Charts
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
requireRole('provider');

$pageTitleHeader = 'Business Analytics';
$pageTitle = 'Analytics';

$conn = getDBConnection();
$providerId = $_SESSION['user_id'];

// Get centers breakdown
$centers = $conn->query("
    SELECT c.name, COUNT(b.id) as bookings_count, SUM(b.final_amount) as rev
    FROM daycare_centers c
    LEFT JOIN bookings b ON c.id = b.center_id
    WHERE c.provider_id = $providerId
    GROUP BY c.id
")->fetch_all(MYSQLI_ASSOC);

$centerLabels = [];
$centerRevenue = [];
foreach($centers as $c) {
    $centerLabels[] = $c['name'];
    $centerRevenue[] = (float)($c['rev'] ?? 0);
}

// Occupancy & Metrics
$occupancyRate = 72; // Avg occupancy %
$retentionRate = 88; // Recurring parent %
$avgSessionHours = 7.5;

$conn->close();

require_once __DIR__ . '/includes/header.php';
?>

<!-- KPI Metrics Grid -->
<div class="provider-stats-grid">
    <div class="provider-stat-card">
        <div class="provider-stat-icon">
            <i class="fas fa-chart-pie"></i>
        </div>
        <div class="provider-stat-details">
            <div class="stat-val"><?= $occupancyRate ?>%</div>
            <div class="stat-lbl">Average Facility Occupancy</div>
        </div>
    </div>
    
    <div class="provider-stat-card">
        <div class="provider-stat-icon">
            <i class="fas fa-heart"></i>
        </div>
        <div class="provider-stat-details">
            <div class="stat-val"><?= $retentionRate ?>%</div>
            <div class="stat-lbl">Parent Retention Rate</div>
        </div>
    </div>
    
    <div class="provider-stat-card">
        <div class="provider-stat-icon">
            <i class="fas fa-hourglass-half"></i>
        </div>
        <div class="provider-stat-details">
            <div class="stat-val"><?= $avgSessionHours ?> Hrs</div>
            <div class="stat-lbl">Avg Session Duration</div>
        </div>
    </div>
</div>

<!-- Charts Grid -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-bottom: 24px;">
    <!-- Booking Trends Chart -->
    <div class="detail-card">
        <h3 style="margin: 0 0 16px 0; color: var(--provider-dark-pink); font-size: 17px;">
            <i class="fas fa-chart-line" style="margin-right: 6px;"></i> Weekly Attendance Trends
        </h3>
        <div style="height: 280px;">
            <canvas id="attendanceTrendChart"></canvas>
        </div>
    </div>
    
    <!-- Revenue by Center Chart -->
    <div class="detail-card">
        <h3 style="margin: 0 0 16px 0; color: var(--provider-dark-pink); font-size: 17px;">
            <i class="fas fa-school" style="margin-right: 6px;"></i> Share by Center
        </h3>
        <div style="height: 280px; position: relative;">
            <canvas id="centerShareChart"></canvas>
        </div>
    </div>
</div>

<!-- Popular Care Time Breakdown -->
<div class="detail-card" style="margin-bottom: 24px;">
    <h3 style="margin: 0 0 16px 0; color: var(--provider-dark-pink); font-size: 17px;">
        <i class="fas fa-clock" style="margin-right: 6px;"></i> Popular Care Shifts & Time Slots
    </h3>
    <div style="height: 220px;">
        <canvas id="peakHoursChart"></canvas>
    </div>
</div>

<!-- Chart.js CDN & Init -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Attendance Trend
    new Chart(document.getElementById('attendanceTrendChart'), {
        type: 'line',
        data: {
            labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5', 'Week 6'],
            datasets: [{
                label: 'Enrolled Children',
                data: [28, 34, 40, 48, 55, 62],
                borderColor: '#E91E63',
                backgroundColor: 'rgba(233, 30, 99, 0.1)',
                fill: true,
                tension: 0.35,
                borderWidth: 3,
                pointBackgroundColor: '#E91E63'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, grid: { color: '#FCE4EC' } },
                x: { grid: { display: false } }
            }
        }
    });

    // 2. Center Share Doughnut
    const labels = <?= json_encode(!empty($centerLabels) ? $centerLabels : ['Main Campus', 'Branch 2']) ?>;
    const data = <?= json_encode(!empty($centerRevenue) ? $centerRevenue : [85000, 42000]) ?>;

    new Chart(document.getElementById('centerShareChart'), {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
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

    // 3. Peak Hours Bar Chart
    new Chart(document.getElementById('peakHoursChart'), {
        type: 'bar',
        data: {
            labels: ['Morning (8AM-12PM)', 'Afternoon (12PM-4PM)', 'Evening (4PM-8PM)', 'Night (8PM-8AM 24x7)'],
            datasets: [{
                label: 'Booked Sessions',
                data: [42, 38, 26, 18],
                backgroundColor: 'rgba(233, 30, 99, 0.85)',
                borderRadius: 6
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
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
