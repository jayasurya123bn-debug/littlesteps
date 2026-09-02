<?php
/**
 * Admin Finances & Platform Revenue
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
requireRole('admin');

$pageTitleHeader = 'Platform Revenue';
$pageTitle = 'Finances';

$conn = getDBConnection();

$totalPlatformRev = 0;
$monthlyPlatformRev = 0;
$totalTransactions = 0;

$qTotal = $conn->query("SELECT SUM(final_amount) as total, COUNT(*) as cnt FROM bookings WHERE status = 'completed'");
if ($qTotal) {
    $res = $qTotal->fetch_assoc();
    $totalPlatformRev = ($res['total'] ?? 0) * 0.10; // 10% platform fee
    $totalTransactions = $res['cnt'];
}

$qMonth = $conn->query("SELECT SUM(final_amount) as total FROM bookings WHERE status = 'completed' AND MONTH(start_datetime) = MONTH(CURDATE()) AND YEAR(start_datetime) = YEAR(CURDATE())");
if ($qMonth) {
    $monthlyPlatformRev = ($qMonth->fetch_assoc()['total'] ?? 0) * 0.10;
}

// Get recent transactions
$stmt = $conn->query("
    SELECT b.id, b.final_amount as total_price, b.created_at, c.name as center_name, u.first_name, u.last_name
    FROM bookings b
    JOIN daycare_centers c ON b.center_id = c.id
    JOIN users u ON b.user_id = u.id
    WHERE b.status = 'completed'
    ORDER BY b.created_at DESC
    LIMIT 20
");
$transactions = $stmt->fetch_all(MYSQLI_ASSOC);

$conn->close();

require_once __DIR__ . '/includes/header.php';
?>

<div class="stat-card-row">
    <div class="admin-stat-card" style="flex: 1; border-color: var(--success);">
        <div class="admin-stat-icon" style="background: var(--success-bg); color: var(--success);">
            <i class="fas fa-chart-pie"></i>
        </div>
        <div class="admin-stat-details">
            <h3><?= formatCurrency($monthlyPlatformRev) ?></h3>
            <p>Revenue This Month (10%)</p>
        </div>
    </div>
    
    <div class="admin-stat-card" style="flex: 1; border-color: var(--info);">
        <div class="admin-stat-icon" style="background: var(--info-bg); color: var(--info);">
            <i class="fas fa-wallet"></i>
        </div>
        <div class="admin-stat-details">
            <h3><?= formatCurrency($totalPlatformRev) ?></h3>
            <p>All-Time Revenue (10%)</p>
        </div>
    </div>
    
    <div class="admin-stat-card" style="flex: 1; border-color: var(--warning);">
        <div class="admin-stat-icon" style="background: var(--warning-bg); color: var(--warning);">
            <i class="fas fa-exchange-alt"></i>
        </div>
        <div class="admin-stat-details">
            <h3><?= $totalTransactions ?></h3>
            <p>Total Completed Bookings</p>
        </div>
    </div>
</div>

<div class="card" style="padding: 0; overflow: hidden; margin-top: var(--space-xl);">
    <div style="padding: var(--space-md) var(--space-lg); border-bottom: 1px solid var(--light-gray); background: #F8F9FA;">
        <h3 style="margin: 0; color: var(--dark-gray); font-size: 18px;">Recent Revenue Transactions (Platform Fee: 10%)</h3>
    </div>
    
    <div class="table-container" style="box-shadow: none;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Booking ID</th>
                    <th>Date</th>
                    <th>Center Name</th>
                    <th>Parent</th>
                    <th>Gross Amount</th>
                    <th>Platform Fee (10%)</th>
                    <th>Provider Payout</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($transactions) > 0): ?>
                    <?php foreach ($transactions as $t): 
                        $gross = $t['total_price'];
                        $fee = $gross * 0.10;
                        $payout = $gross - $fee;
                    ?>
                        <tr>
                            <td>#<?= $t['id'] ?></td>
                            <td style="font-size: 13px;"><?= date('d M Y', strtotime($t['created_at'])) ?></td>
                            <td><strong><?= htmlspecialchars($t['center_name']) ?></strong></td>
                            <td><?= htmlspecialchars($t['first_name'] . ' ' . $t['last_name']) ?></td>
                            <td><?= formatCurrency($gross) ?></td>
                            <td><strong style="color: var(--success);">+ <?= formatCurrency($fee) ?></strong></td>
                            <td><span style="color: var(--medium-gray);"><?= formatCurrency($payout) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center" style="padding: 30px;">No completed transactions found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
