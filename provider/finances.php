<?php
/**
 * Provider Revenue & Finances
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

$pageTitleHeader = 'Revenue & Finances';
$pageTitle = 'Finances';

$conn = getDBConnection();
$providerId = $_SESSION['user_id'];

// Get center ID
$stmt = $conn->prepare("SELECT id FROM daycare_centers WHERE provider_id = ? LIMIT 1");
$stmt->bind_param("i", $providerId);
$stmt->execute();
$centerResult = $stmt->get_result();
$centerId = $centerResult->num_rows > 0 ? $centerResult->fetch_assoc()['id'] : 0;

// Get transactions (Completed bookings)
$transactions = [];
$totalRevenue = 0;
$thisMonthRevenue = 0;

if ($centerId > 0) {
    // Get all completed bookings (transactions)
    $transStmt = $conn->prepare("
        SELECT b.id, b.total_price as amount, b.start_time as date, b.child_name, u.first_name, u.last_name, 'Booking' as type
        FROM bookings b
        JOIN users u ON b.parent_id = u.id
        WHERE b.center_id = ? AND b.status = 'completed'
        ORDER BY b.start_time DESC
        LIMIT 50
    ");
    $transStmt->bind_param("i", $centerId);
    $transStmt->execute();
    $transactions = $transStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Calculate totals
    $qTotal = $conn->prepare("SELECT SUM(total_price) as total FROM bookings WHERE center_id = ? AND status = 'completed'");
    $qTotal->bind_param("i", $centerId);
    $qTotal->execute();
    $totalRevenue = $qTotal->get_result()->fetch_assoc()['total'] ?? 0;
    
    $qMonth = $conn->prepare("SELECT SUM(total_price) as total FROM bookings WHERE center_id = ? AND status = 'completed' AND MONTH(start_time) = MONTH(CURDATE()) AND YEAR(start_time) = YEAR(CURDATE())");
    $qMonth->bind_param("i", $centerId);
    $qMonth->execute();
    $thisMonthRevenue = $qMonth->get_result()->fetch_assoc()['total'] ?? 0;
}
$conn->close();

require_once __DIR__ . '/includes/header.php';
?>

<?php if ($centerId == 0): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i> You must <a href="center_profile.php" style="font-weight: 600;">create a center profile</a> to view financial data.
    </div>
<?php else: ?>

    <!-- Summary Cards -->
    <div class="stat-card-row">
        <div class="card stat-card" style="margin-bottom: 0;">
            <div class="stat-icon" style="background: var(--success-bg); color: var(--success);">
                <i class="fas fa-chart-pie"></i>
            </div>
            <div class="stat-details" style="flex-grow: 1;">
                <div class="stat-value"><?= formatCurrency($thisMonthRevenue) ?></div>
                <div class="stat-label">This Month</div>
            </div>
        </div>
        
        <div class="card stat-card" style="margin-bottom: 0;">
            <div class="stat-icon" style="background: var(--info-bg); color: var(--info);">
                <i class="fas fa-wallet"></i>
            </div>
            <div class="stat-details" style="flex-grow: 1;">
                <div class="stat-value"><?= formatCurrency($totalRevenue) ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
        </div>
        
        <div class="card stat-card" style="margin-bottom: 0;">
            <div class="stat-icon" style="background: var(--warning-bg); color: var(--warning);">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
            <div class="stat-details" style="flex-grow: 1;">
                <div class="stat-value"><?= count($transactions) ?></div>
                <div class="stat-label">Total Transactions</div>
            </div>
            <button class="btn btn-sm btn-outline"><i class="fas fa-download"></i></button>
        </div>
    </div>

    <!-- Transactions Table -->
    <div class="card" style="margin-top: var(--space-xl); padding: 0; overflow: hidden;">
        <div style="padding: var(--space-md) var(--space-lg); border-bottom: 1px solid var(--light-pink); background: var(--white); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; color: var(--dark-gray); font-size: 18px;">Transaction History</h3>
        </div>
        
        <div class="table-container" style="box-shadow: none;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Parent</th>
                        <th>Details</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($transactions) > 0): ?>
                        <?php foreach ($transactions as $t): ?>
                            <tr>
                                <td><?= date('d M Y, h:i A', strtotime($t['date'])) ?></td>
                                <td><?= htmlspecialchars($t['first_name'] . ' ' . $t['last_name']) ?></td>
                                <td>Child: <?= htmlspecialchars($t['child_name']) ?></td>
                                <td><span class="badge badge-pink"><?= $t['type'] ?></span></td>
                                <td><span class="badge badge-success">Paid</span></td>
                                <td><strong style="color: var(--success);"><?= formatCurrency($t['amount']) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: var(--space-2xl) 0;">
                                <p style="color: var(--medium-gray);">No transactions found yet.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
