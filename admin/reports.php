<?php
/**
 * Admin Reports Module
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
requireRole('admin');

$pageTitleHeader = 'Reports & Audits';
$pageTitle = 'Generate Reports';

$conn = getDBConnection();

$reportType = $_GET['report_type'] ?? 'bookings';
$startDate  = $_GET['start_date'] ?? date('Y-m-01');
$endDate    = $_GET['end_date'] ?? date('Y-m-d');

// CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="littlesteps_' . $reportType . '_report_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');

    if ($reportType === 'bookings') {
        fputcsv($output, ['Booking Code', 'Parent', 'Child', 'Center', 'Amount', 'Payment Status', 'Status', 'Date']);
        $res = $conn->query("
            SELECT b.booking_code, CONCAT(u.first_name, ' ', u.last_name) as parent, b.child_name, c.name as center,
                   b.final_amount, b.payment_status, b.status, b.created_at
            FROM bookings b
            JOIN users u ON b.user_id = u.id
            JOIN daycare_centers c ON b.center_id = c.id
            WHERE DATE(b.created_at) BETWEEN '$startDate' AND '$endDate'
            ORDER BY b.created_at DESC
        ");
        while($r = $res->fetch_assoc()) fputcsv($output, $r);
    } elseif ($reportType === 'revenue') {
        fputcsv($output, ['Date', 'Transactions Count', 'Gross Revenue', 'Platform Fee (10%)']);
        $res = $conn->query("
            SELECT DATE(created_at) as tx_date, COUNT(id) as tx_count, SUM(final_amount) as gross_rev, (SUM(final_amount) * 0.10) as net_fee
            FROM bookings
            WHERE payment_status = 'paid' AND DATE(created_at) BETWEEN '$startDate' AND '$endDate'
            GROUP BY DATE(created_at)
            ORDER BY tx_date DESC
        ");
        while($r = $res->fetch_assoc()) fputcsv($output, $r);
    }
    fclose($output);
    exit();
}

// Fetch Report Data based on Type
$reportData = [];
$totalGross = 0;
$totalCount = 0;

if ($reportType === 'bookings') {
    $q = "SELECT b.booking_code, CONCAT(u.first_name, ' ', u.last_name) as parent_name, b.child_name,
                 c.name as center_name, p.business_name, b.final_amount, b.status, b.payment_status, b.created_at
          FROM bookings b
          JOIN users u ON b.user_id = u.id
          JOIN daycare_centers c ON b.center_id = c.id
          JOIN providers p ON c.provider_id = p.id
          WHERE DATE(b.created_at) BETWEEN '$startDate' AND '$endDate'
          ORDER BY b.created_at DESC";
    $res = $conn->query($q);
    if ($res) {
        while($row = $res->fetch_assoc()) {
            $reportData[] = $row;
            $totalGross += $row['final_amount'];
            $totalCount++;
        }
    }
} elseif ($reportType === 'revenue') {
    $q = "SELECT DATE(created_at) as date_val, COUNT(id) as count_val, SUM(final_amount) as gross_amount
          FROM bookings
          WHERE payment_status = 'paid' AND DATE(created_at) BETWEEN '$startDate' AND '$endDate'
          GROUP BY DATE(created_at)
          ORDER BY date_val DESC";
    $res = $conn->query($q);
    if ($res) {
        while($row = $res->fetch_assoc()) {
            $reportData[] = $row;
            $totalGross += $row['gross_amount'];
            $totalCount += $row['count_val'];
        }
    }
} elseif ($reportType === 'providers') {
    $q = "SELECT p.business_name, p.city, COUNT(b.id) as bookings_count, SUM(b.final_amount) as total_volume, p.rating
          FROM providers p
          LEFT JOIN daycare_centers c ON p.id = c.provider_id
          LEFT JOIN bookings b ON c.id = b.center_id AND DATE(b.created_at) BETWEEN '$startDate' AND '$endDate'
          GROUP BY p.id
          ORDER BY total_volume DESC";
    $res = $conn->query($q);
    if ($res) {
        while($row = $res->fetch_assoc()) {
            $reportData[] = $row;
            $totalGross += ($row['total_volume'] ?? 0);
            $totalCount += $row['bookings_count'];
        }
    }
}

$conn->close();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Report Configuration Filter Card -->
<div class="filter-card">
    <form method="GET" class="filter-form">
        <div>
            <label class="form-label" style="font-size: 12px; margin-bottom: 2px;">Report Type</label>
            <select name="report_type" class="filter-input">
                <option value="bookings" <?= $reportType === 'bookings' ? 'selected' : '' ?>>📋 Detailed Bookings Report</option>
                <option value="revenue" <?= $reportType === 'revenue' ? 'selected' : '' ?>>💰 Revenue & Platform Fees</option>
                <option value="providers" <?= $reportType === 'providers' ? 'selected' : '' ?>>🏢 Provider Performance</option>
            </select>
        </div>
        
        <div>
            <label class="form-label" style="font-size: 12px; margin-bottom: 2px;">Start Date</label>
            <input type="date" name="start_date" class="filter-input" value="<?= htmlspecialchars($startDate) ?>">
        </div>
        
        <div>
            <label class="form-label" style="font-size: 12px; margin-bottom: 2px;">End Date</label>
            <input type="date" name="end_date" class="filter-input" value="<?= htmlspecialchars($endDate) ?>">
        </div>
        
        <div style="align-self: flex-end;">
            <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">
                <i class="fas fa-sync-alt"></i> Generate Report
            </button>
        </div>
        
        <div style="margin-left: auto; align-self: flex-end; display: flex; gap: 8px;">
            <button type="button" onclick="window.print()" class="btn btn-secondary" style="padding: 10px 16px;">
                <i class="fas fa-print"></i> Print
            </button>
            <a href="reports.php?report_type=<?= $reportType ?>&start_date=<?= $startDate ?>&end_date=<?= $endDate ?>&export=csv" class="btn btn-secondary" style="padding: 10px 16px;">
                <i class="fas fa-file-csv"></i> Export CSV
            </a>
        </div>
    </form>
</div>

<!-- Report Summary Metrics -->
<div class="report-metrics-grid">
    <div class="report-metric-box">
        <div class="metric-num"><?= $totalCount ?></div>
        <div class="metric-desc">Total Transactions / Items</div>
    </div>
    
    <div class="report-metric-box">
        <div class="metric-num"><?= formatCurrency($totalGross) ?></div>
        <div class="metric-desc">Gross Monetary Volume</div>
    </div>
    
    <div class="report-metric-box">
        <div class="metric-num"><?= formatCurrency($totalGross * 0.10) ?></div>
        <div class="metric-desc">Platform Net Commission (10%)</div>
    </div>
</div>

<!-- Generated Report Table Output -->
<div class="table-container">
    <div class="table-header-bar">
        <h3 class="table-header-title">
            <i class="fas fa-file-invoice"></i> <?= strtoupper(htmlspecialchars($reportType)) ?> Summary (<?= formatDate($startDate) ?> to <?= formatDate($endDate) ?>)
        </h3>
    </div>
    
    <div style="overflow-x: auto;">
        <table class="admin-table">
            <?php if ($reportType === 'bookings'): ?>
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Parent Name</th>
                        <th>Child</th>
                        <th>Center</th>
                        <th>Gross Amount</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reportData)): ?>
                        <tr><td colspan="8" style="text-align: center; padding: 24px; color: #9E9E9E;">No records found for date range.</td></tr>
                    <?php else: ?>
                        <?php foreach($reportData as $row): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($row['booking_code']) ?></strong></td>
                                <td><?= htmlspecialchars($row['parent_name']) ?></td>
                                <td><?= htmlspecialchars($row['child_name']) ?></td>
                                <td><?= htmlspecialchars($row['center_name']) ?></td>
                                <td><strong><?= formatCurrency($row['final_amount']) ?></strong></td>
                                <td><span class="badge badge-<?= htmlspecialchars($row['payment_status']) ?>"><?= htmlspecialchars($row['payment_status']) ?></span></td>
                                <td><span class="badge badge-<?= htmlspecialchars($row['status']) ?>"><?= htmlspecialchars($row['status']) ?></span></td>
                                <td><?= formatDate($row['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>

            <?php elseif ($reportType === 'revenue'): ?>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Transactions Count</th>
                        <th>Gross Booking Volume</th>
                        <th>Platform Fee Revenue (10%)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reportData)): ?>
                        <tr><td colspan="4" style="text-align: center; padding: 24px; color: #9E9E9E;">No revenue generated in date range.</td></tr>
                    <?php else: ?>
                        <?php foreach($reportData as $row): ?>
                            <tr>
                                <td><strong><?= formatDate($row['date_val']) ?></strong></td>
                                <td><?= $row['count_val'] ?> Bookings</td>
                                <td><strong><?= formatCurrency($row['gross_amount']) ?></strong></td>
                                <td><strong style="color: var(--admin-pink);"><?= formatCurrency($row['gross_amount'] * 0.10) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>

            <?php elseif ($reportType === 'providers'): ?>
                <thead>
                    <tr>
                        <th>Daycare Provider</th>
                        <th>City</th>
                        <th>Bookings Completed</th>
                        <th>Total Revenue</th>
                        <th>Average Rating</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($reportData)): ?>
                        <tr><td colspan="5" style="text-align: center; padding: 24px; color: #9E9E9E;">No provider data available.</td></tr>
                    <?php else: ?>
                        <?php foreach($reportData as $row): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($row['business_name']) ?></strong></td>
                                <td><?= htmlspecialchars($row['city']) ?></td>
                                <td><?= $row['bookings_count'] ?> Bookings</td>
                                <td><strong><?= formatCurrency($row['total_volume'] ?? 0) ?></strong></td>
                                <td>★ <?= $row['rating'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            <?php endif; ?>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
