<?php
/**
 * Admin Subscription Plans & Active Subscriptions Management
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
requireRole('admin');

$pageTitleHeader = 'Subscription Oversight';
$pageTitle = 'Manage Subscriptions';

$conn = getDBConnection();

// Handle Status Actions (Pause, Cancel, Extend)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['sub_id'])) {
    if (verifyCSRFToken($_POST['csrf_token'])) {
        $subId  = (int)$_POST['sub_id'];
        $action = $_POST['action'];

        if ($action === 'pause') {
            $stmt = $conn->prepare("UPDATE subscriptions SET status = 'paused' WHERE id = ?");
            $stmt->bind_param("i", $subId);
            $stmt->execute();
            setFlashMessage('warning', 'Subscription paused.');
            
        } elseif ($action === 'activate') {
            $stmt = $conn->prepare("UPDATE subscriptions SET status = 'active' WHERE id = ?");
            $stmt->bind_param("i", $subId);
            $stmt->execute();
            setFlashMessage('success', 'Subscription activated.');
            
        } elseif ($action === 'cancel') {
            $stmt = $conn->prepare("UPDATE subscriptions SET status = 'cancelled', cancelled_at = NOW(), cancellation_reason = 'Cancelled by Admin' WHERE id = ?");
            $stmt->bind_param("i", $subId);
            $stmt->execute();
            setFlashMessage('error', 'Subscription cancelled.');
            
        } elseif ($action === 'extend') {
            $stmt = $conn->prepare("UPDATE subscriptions SET end_date = DATE_ADD(end_date, INTERVAL 30 DAY) WHERE id = ?");
            $stmt->bind_param("i", $subId);
            $stmt->execute();
            setFlashMessage('success', 'Subscription extended by 30 days.');
        }
    }
    redirect('/admin/subscriptions.php');
}

// Fetch Active Subscriptions
$subsQuery = "
    SELECT s.*, u.first_name, u.last_name, u.email as parent_email,
           c.name as center_name, p.business_name
    FROM subscriptions s
    JOIN users u ON s.user_id = u.id
    JOIN daycare_centers c ON s.center_id = c.id
    JOIN providers p ON c.provider_id = p.id
    ORDER BY s.created_at DESC
";
$subscriptions = $conn->query($subsQuery)->fetch_all(MYSQLI_ASSOC);

// Calculate Subscription Metrics
$totalActiveSubs = 0;
$monthlyRecurring = 0.0;
foreach($subscriptions as $s) {
    if ($s['status'] === 'active') {
        $totalActiveSubs++;
        $monthlyRecurring += (float)$s['monthly_amount'];
    }
}

$conn->close();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Metrics Summary Grid -->
<div class="admin-stats-grid">
    <div class="admin-stat-card">
        <div class="admin-stat-icon">
            <i class="fas fa-sync-alt"></i>
        </div>
        <div class="admin-stat-details">
            <div class="stat-val"><?= $totalActiveSubs ?></div>
            <div class="stat-lbl">Active Subscriptions</div>
        </div>
    </div>
    
    <div class="admin-stat-card">
        <div class="admin-stat-icon">
            <i class="fas fa-money-bill-wave"></i>
        </div>
        <div class="admin-stat-details">
            <div class="stat-val"><?= formatCurrency($monthlyRecurring) ?></div>
            <div class="stat-lbl">Monthly Recurring Revenue</div>
        </div>
    </div>
    
    <div class="admin-stat-card">
        <div class="admin-stat-icon">
            <i class="fas fa-file-contract"></i>
        </div>
        <div class="admin-stat-details">
            <div class="stat-val"><?= count($subscriptions) ?></div>
            <div class="stat-lbl">Total Plans Created</div>
        </div>
    </div>
</div>

<!-- Active Subscriptions Table -->
<div class="table-container">
    <div class="table-header-bar">
        <h3 class="table-header-title">
            <i class="fas fa-credit-card"></i> Recurring Childcare Subscriptions (<?= count($subscriptions) ?>)
        </h3>
    </div>
    
    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Sub Code</th>
                    <th>Parent & Child</th>
                    <th>Plan & Center</th>
                    <th>Cycle</th>
                    <th>Duration</th>
                    <th>Monthly Rate</th>
                    <th>Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($subscriptions)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 36px; color: #9E9E9E;">
                            No active recurring subscriptions found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($subscriptions as $s): ?>
                        <tr>
                            <td>
                                <strong style="color: var(--admin-pink);"><?= htmlspecialchars($s['subscription_code']) ?></strong>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></strong>
                                <div style="font-size: 12px; color: #757575;">Child: <?= htmlspecialchars($s['child_name']) ?></div>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($s['plan_name']) ?></strong>
                                <div style="font-size: 12px; color: #757575;"><?= htmlspecialchars($s['center_name']) ?></div>
                            </td>
                            <td>
                                <span class="badge badge-pink" style="font-size: 11px; text-transform: uppercase;">
                                    <?= htmlspecialchars($s['plan_type']) ?>
                                </span>
                            </td>
                            <td>
                                <div style="font-size: 13px;"><?= formatDate($s['start_date']) ?> → <?= formatDate($s['end_date']) ?></div>
                                <div style="font-size: 11px; color: #757575;">
                                    <?= $s['included_hours_per_day'] ?> hrs/day • <?= $s['included_days_per_week'] ?> days/wk
                                </div>
                            </td>
                            <td>
                                <strong><?= formatCurrency($s['monthly_amount']) ?></strong>
                            </td>
                            <td>
                                <span class="badge badge-<?= htmlspecialchars($s['status']) ?>">
                                    <?= htmlspecialchars($s['status']) ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <div class="btn-group-action">
                                    <!-- Extend 30 Days -->
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Extend subscription by 30 days?');">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="extend">
                                        <input type="hidden" name="sub_id" value="<?= $s['id'] ?>">
                                        <button type="submit" class="btn-icon btn-icon-approve" title="Extend 30 Days">
                                            <i class="fas fa-calendar-plus"></i>
                                        </button>
                                    </form>
                                    
                                    <!-- Pause or Activate -->
                                    <?php if ($s['status'] === 'active'): ?>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Pause this subscription?');">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="pause">
                                            <input type="hidden" name="sub_id" value="<?= $s['id'] ?>">
                                            <button type="submit" class="btn-icon btn-icon-edit" title="Pause Subscription">
                                                <i class="fas fa-pause"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Activate this subscription?');">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="activate">
                                            <input type="hidden" name="sub_id" value="<?= $s['id'] ?>">
                                            <button type="submit" class="btn-icon btn-icon-approve" title="Activate Subscription">
                                                <i class="fas fa-play"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <!-- Cancel -->
                                    <?php if ($s['status'] !== 'cancelled'): ?>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Cancel this subscription?');">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="cancel">
                                            <input type="hidden" name="sub_id" value="<?= $s['id'] ?>">
                                            <button type="submit" class="btn-icon btn-icon-delete" title="Cancel Subscription">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
