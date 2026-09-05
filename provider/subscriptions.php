<?php
/**
 * Provider Subscriptions Management
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

$pageTitleHeader = 'Active Subscriptions';
$pageTitle = 'Subscriptions';

$conn = getDBConnection();
$providerId = $_SESSION['user_id'];

// Get center ID
$stmt = $conn->prepare("SELECT id FROM daycare_centers WHERE provider_id = ? LIMIT 1");
$stmt->bind_param("i", $providerId);
$stmt->execute();
$centerResult = $stmt->get_result();
$centerId = $centerResult->num_rows > 0 ? $centerResult->fetch_assoc()['id'] : 0;

$subscriptions = [];
if ($centerId > 0) {
    $subStmt = $conn->prepare("
        SELECT s.*, u.first_name, u.last_name, u.phone 
        FROM subscriptions s
        JOIN users u ON s.user_id = u.id
        WHERE s.center_id = ? AND s.status = 'active'
        ORDER BY s.end_date ASC
    ");
    $subStmt->bind_param("i", $centerId);
    $subStmt->execute();
    $subscriptions = $subStmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$conn->close();

require_once __DIR__ . '/includes/header.php';
?>

<?php if ($centerId == 0): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i> You must <a href="center_profile.php" style="font-weight: 600;">create a center profile</a> before managing subscriptions.
    </div>
<?php else: ?>

    <div class="card" style="padding: 0; overflow: hidden;">
        <div style="padding: var(--space-md) var(--space-lg); border-bottom: 1px solid var(--light-pink); background: var(--baby-pink); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; color: var(--dark-pink); font-size: 18px;">Active Monthly Plans</h3>
            <span class="badge badge-success"><?= count($subscriptions) ?> Active</span>
        </div>
        
        <div class="table-container" style="box-shadow: none;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Parent</th>
                        <th>Child</th>
                        <th>Plan Type</th>
                        <th>Billing Cycle</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($subscriptions) > 0): ?>
                        <?php foreach ($subscriptions as $sub): ?>
                            <tr>
                                <td>
                                    <strong style="color: var(--dark-gray);"><?= htmlspecialchars($sub['first_name'] . ' ' . $sub['last_name']) ?></strong><br>
                                    <span style="font-size: 12px; color: var(--medium-gray);"><i class="fas fa-phone-alt"></i> <?= htmlspecialchars($sub['phone']) ?></span>
                                </td>
                                <td>
                                    <?= htmlspecialchars($sub['child_name']) ?>
                                </td>
                                <td>
                                    <span style="text-transform: capitalize;"><?= htmlspecialchars($sub['plan_type']) ?></span><br>
                                    <strong style="font-size: 12px;"><?= formatCurrency($sub['price']) ?>/mo</strong>
                                </td>
                                <td>
                                    <span style="font-size: 12px; color: var(--medium-gray);">Starts:</span> <?= date('d M Y', strtotime($sub['start_date'])) ?><br>
                                    <span style="font-size: 12px; color: var(--medium-gray);">Renews:</span> <strong style="color: var(--main-pink);"><?= date('d M Y', strtotime($sub['end_date'])) ?></strong>
                                </td>
                                <td>
                                    <span class="badge badge-success">Active</span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline" onclick="alert('View full details functionality would go here.')"><i class="fas fa-eye"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: var(--space-2xl) 0;">
                                <div style="font-size: 48px; color: var(--light-pink); margin-bottom: var(--space-md);">
                                    <i class="fas fa-sync"></i>
                                </div>
                                <p style="color: var(--medium-gray);">You have no active subscriptions.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
