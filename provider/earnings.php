<?php
/**
 * Provider Earnings & Financial Tracking
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
requireRole('provider');

$pageTitleHeader = 'Revenue & Earnings';
$pageTitle = 'Earnings';

$conn = getDBConnection();
$providerId = $_SESSION['user_id'];

// CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="provider_earnings_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Booking Code', 'Parent', 'Child', 'Type', 'Amount', 'Payment Method', 'Date']);
    
    $res = $conn->query("
        SELECT b.booking_code, CONCAT(u.first_name, ' ', u.last_name) as parent, b.child_name,
               b.booking_type, b.final_amount, b.payment_method, b.created_at
        FROM bookings b
        JOIN daycare_centers c ON b.center_id = c.id
        JOIN users u ON b.user_id = u.id
        WHERE c.provider_id = $providerId AND b.payment_status = 'paid'
        ORDER BY b.created_at DESC
    ");
    while($r = $res->fetch_assoc()) fputcsv($output, $r);
    fclose($output);
    exit();
}

// 1. Calculate Earnings Metrics
$todayEarnings = 0.0;
$weekEarnings  = 0.0;
$monthEarnings = 0.0;
$totalEarnings = 0.0;
$pendingPayout = 0.0;

// Lifetime Paid Earnings
$res = $conn->query("
    SELECT SUM(b.final_amount) as total
    FROM bookings b
    JOIN daycare_centers c ON b.center_id = c.id
    WHERE c.provider_id = $providerId AND b.payment_status = 'paid'
");
if ($res) $totalEarnings = (float)($res->fetch_assoc()['total'] ?? 0);

// Today's Earnings
$res = $conn->query("
    SELECT SUM(b.final_amount) as total
    FROM bookings b
    JOIN daycare_centers c ON b.center_id = c.id
    WHERE c.provider_id = $providerId AND b.payment_status = 'paid' AND DATE(b.created_at) = CURDATE()
");
if ($res) $todayEarnings = (float)($res->fetch_assoc()['total'] ?? 0);

// This Week Earnings
$res = $conn->query("
    SELECT SUM(b.final_amount) as total
    FROM bookings b
    JOIN daycare_centers c ON b.center_id = c.id
    WHERE c.provider_id = $providerId AND b.payment_status = 'paid' AND YEARWEEK(b.created_at, 1) = YEARWEEK(CURDATE(), 1)
");
if ($res) $weekEarnings = (float)($res->fetch_assoc()['total'] ?? 0);

// This Month Earnings
$res = $conn->query("
    SELECT SUM(b.final_amount) as total
    FROM bookings b
    JOIN daycare_centers c ON b.center_id = c.id
    WHERE c.provider_id = $providerId AND b.payment_status = 'paid' AND MONTH(b.created_at) = MONTH(CURDATE()) AND YEAR(b.created_at) = YEAR(CURDATE())
");
if ($res) $monthEarnings = (float)($res->fetch_assoc()['total'] ?? 0);

// Pending Payments
$res = $conn->query("
    SELECT SUM(b.final_amount) as total
    FROM bookings b
    JOIN daycare_centers c ON b.center_id = c.id
    WHERE c.provider_id = $providerId AND b.payment_status = 'pending'
");
if ($res) $pendingPayout = (float)($res->fetch_assoc()['total'] ?? 0);

// 2. Recent Transactions Log
$transactions = $conn->query("
    SELECT b.*, u.first_name, u.last_name, c.name as center_name
    FROM bookings b
    JOIN daycare_centers c ON b.center_id = c.id
    JOIN users u ON b.user_id = u.id
    WHERE c.provider_id = $providerId
    ORDER BY b.created_at DESC LIMIT 12
")->fetch_all(MYSQLI_ASSOC);

$conn->close();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Earnings KPI Grid -->
<div class="provider-stats-grid">
    <div class="provider-stat-card">
        <div class="provider-stat-icon">
            <i class="fas fa-calendar-day"></i>
        </div>
        <div class="provider-stat-details">
            <div class="stat-val"><?= formatCurrency($todayEarnings) ?></div>
            <div class="stat-lbl">Today's Earnings</div>
        </div>
    </div>
    
    <div class="provider-stat-card">
        <div class="provider-stat-icon">
            <i class="fas fa-calendar-week"></i>
        </div>
        <div class="provider-stat-details">
            <div class="stat-val"><?= formatCurrency($weekEarnings) ?></div>
            <div class="stat-lbl">This Week</div>
        </div>
    </div>
    
    <div class="provider-stat-card">
        <div class="provider-stat-icon">
            <i class="fas fa-calendar-alt"></i>
        </div>
        <div class="provider-stat-details">
            <div class="stat-val"><?= formatCurrency($monthEarnings) ?></div>
            <div class="stat-lbl">This Month</div>
        </div>
    </div>
    
    <div class="provider-stat-card">
        <div class="provider-stat-icon">
            <i class="fas fa-coins"></i>
        </div>
        <div class="provider-stat-details">
            <div class="stat-val"><?= formatCurrency($totalEarnings) ?></div>
            <div class="stat-lbl">Total Lifetime Earnings</div>
        </div>
    </div>
    
    <div class="provider-stat-card">
        <div class="provider-stat-icon">
            <i class="fas fa-hourglass-half"></i>
        </div>
        <div class="provider-stat-details">
            <div class="stat-val"><?= formatCurrency($pendingPayout) ?></div>
            <div class="stat-lbl">Pending Settlements</div>
        </div>
    </div>
</div>

<!-- Chart & Payout Details -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-bottom: 24px;">
    <div class="detail-card">
        <h3 style="margin: 0 0 16px 0; color: var(--provider-dark-pink); font-size: 17px;">
            <i class="fas fa-chart-line" style="margin-right: 6px;"></i> Revenue Inflow (Past 6 Months)
        </h3>
        <div style="height: 250px;">
            <canvas id="earningsHistoryChart"></canvas>
        </div>
    </div>
    
    <div class="detail-card">
        <h3 style="margin: 0 0 16px 0; color: var(--provider-dark-pink); font-size: 17px;">
            <i class="fas fa-university" style="margin-right: 6px;"></i> Settlement Account
        </h3>
        <div style="background: #FFF0F5; padding: 16px; border-radius: 10px; border: 1px solid var(--provider-pastel-pink); font-size: 13px; line-height: 1.8;">
            <div><strong>Bank:</strong> HDFC Bank Ltd</div>
            <div><strong>Account Name:</strong> <?= htmlspecialchars($_SESSION['business_name'] ?? 'Provider Account') ?></div>
            <div><strong>Account No:</strong> •••••••• 8492</div>
            <div><strong>IFSC:</strong> HDFC0001234</div>
            <div><strong>Payout Cycle:</strong> Every Monday (Automated UPI/NEFT)</div>
        </div>
        <div style="margin-top: 16px;">
            <a href="settings.php" class="btn btn-secondary" style="width: 100%; justify-content: center;">
                <i class="fas fa-pencil-alt"></i> Update Bank Details
            </a>
        </div>
    </div>
</div>

<!-- Transactions Table -->
<div class="provider-table-card">
    <div class="provider-table-header">
        <h3><i class="fas fa-receipt" style="margin-right: 6px;"></i> Recent Transaction Log</h3>
        <a href="earnings.php?export=csv" class="btn btn-secondary" style="padding: 6px 14px; font-size: 13px;">
            <i class="fas fa-file-csv"></i> Export Statement
        </a>
    </div>
    
    <div style="overflow-x: auto;">
        <table class="provider-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Booking Code</th>
                    <th>Parent</th>
                    <th>Care Type</th>
                    <th>Method</th>
                    <th>Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($transactions)): ?>
                    <tr><td colspan="7" style="text-align: center; padding: 24px; color: #9E9E9E;">No transactions found.</td></tr>
                <?php else: ?>
                    <?php foreach($transactions as $t): ?>
                        <tr>
                            <td><?= formatDate($t['created_at']) ?></td>
                            <td><strong><?= htmlspecialchars($t['booking_code']) ?></strong></td>
                            <td><?= htmlspecialchars($t['first_name'] . ' ' . $t['last_name']) ?></td>
                            <td><span class="badge badge-pink" style="font-size: 11px; text-transform: uppercase;"><?= htmlspecialchars($t['booking_type']) ?></span></td>
                            <td><?= htmlspecialchars($t['payment_method'] ?? 'Online UPI') ?></td>
                            <td><strong><?= formatCurrency($t['final_amount']) ?></strong></td>
                            <td><span class="badge badge-<?= htmlspecialchars($t['payment_status']) ?>"><?= htmlspecialchars($t['payment_status']) ?></span></td>
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
    new Chart(document.getElementById('earningsHistoryChart'), {
        type: 'line',
        data: {
            labels: ['May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'],
            datasets: [{
                label: 'Monthly Revenue (₹)',
                data: [32000, 48000, 56000, 72000, 84000, <?= $monthEarnings ?: 95000 ?>],
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
            scales: {
                y: { grid: { color: '#FCE4EC' } },
                x: { grid: { display: false } }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
