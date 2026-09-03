<?php
/**
 * Parent Subscriptions Management
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
requireRole('parent');

$pageTitleHeader = 'My Subscriptions';
$pageTitle = 'Subscriptions';

$conn = getDBConnection();
$userId = $_SESSION['user_id'];

// Handle Actions (Auto-Renew toggle, Cancel, Pause)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['sub_id'])) {
    if (verifyCSRFToken($_POST['csrf_token'])) {
        $subId  = (int)$_POST['sub_id'];
        $action = $_POST['action'];

        if ($action === 'toggle_autorenew') {
            $newVal = (int)$_POST['new_val'];
            $stmt = $conn->prepare("UPDATE subscriptions SET auto_renew = ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("iii", $newVal, $subId, $userId);
            $stmt->execute();
            setFlashMessage('success', 'Auto-renew preference updated.');
            
        } elseif ($action === 'cancel') {
            $stmt = $conn->prepare("UPDATE subscriptions SET status = 'cancelled', cancelled_at = NOW(), cancellation_reason = 'Cancelled by parent' WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $subId, $userId);
            $stmt->execute();
            setFlashMessage('warning', 'Subscription cancelled.');
            
        } elseif ($action === 'pause') {
            $stmt = $conn->prepare("UPDATE subscriptions SET status = 'paused' WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $subId, $userId);
            $stmt->execute();
            setFlashMessage('info', 'Subscription paused.');
            
        } elseif ($action === 'resume') {
            $stmt = $conn->prepare("UPDATE subscriptions SET status = 'active' WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $subId, $userId);
            $stmt->execute();
            setFlashMessage('success', 'Subscription resumed.');
        }
    }
    redirect('/parent/subscriptions.php');
}

// Get Subscriptions
$stmt = $conn->prepare("
    SELECT s.*, c.name as center_name, c.area, c.city 
    FROM subscriptions s
    JOIN daycare_centers c ON s.center_id = c.id
    WHERE s.user_id = ?
    ORDER BY (CASE WHEN s.status = 'active' THEN 0 ELSE 1 END), s.end_date DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$subscriptions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$conn->close();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Header Toolbar -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
    <div>
        <h2 style="margin: 0 0 4px 0; color: var(--parent-dark-pink); font-size: 22px;">Recurring Childcare Subscriptions</h2>
        <p style="margin: 0; color: #757575; font-size: 14px;">Guaranteed slots, preferential pricing, and dedicated infant care</p>
    </div>
    <a href="centers.php" class="btn btn-primary" style="padding: 10px 22px;">
        <i class="fas fa-plus-circle" style="margin-right: 6px;"></i> Subscribe to New Plan
    </a>
</div>

<!-- Subscriptions Grid -->
<?php if (empty($subscriptions)): ?>
    <div class="detail-card" style="text-align: center; padding: 48px 24px;">
        <div style="font-size: 48px; color: var(--parent-pastel-pink); margin-bottom: 12px;">
            <i class="fas fa-credit-card"></i>
        </div>
        <h3 style="margin: 0 0 6px 0; color: #212121;">No Recurring Subscriptions</h3>
        <p style="color: #757575; max-width: 480px; margin: 0 auto 20px; font-size: 14px;">
            Enrolling in a monthly or quarterly subscription saves up to 25% on daily childcare and reserves your child's spot year-round.
        </p>
        <a href="centers.php" class="btn btn-primary">Browse Partner Centers</a>
    </div>
<?php else: ?>
    <div class="sub-grid">
        <?php foreach ($subscriptions as $sub): ?>
            <?php 
            $daysLeft = max(0, ceil((strtotime($sub['end_date']) - time()) / 86400));
            ?>
            <div class="sub-card <?= $sub['status'] === 'active' ? 'active' : '' ?>">
                <div class="sub-card-header">
                    <div>
                        <span class="badge badge-pink" style="font-size: 11px; text-transform: uppercase; margin-bottom: 6px;">
                            <?= htmlspecialchars($sub['plan_type']) ?> Care
                        </span>
                        <h3 style="margin: 0 0 4px 0; font-size: 18px; color: #212121;"><?= htmlspecialchars($sub['plan_name']) ?></h3>
                        <div style="font-size: 13px; color: #757575;">
                            <a href="<?= SITE_URL ?>/center-details.php?id=<?= $sub['center_id'] ?>" target="_blank" style="color: var(--parent-pink); text-decoration: none; font-weight: 500;">
                                <?= htmlspecialchars($sub['center_name']) ?>
                            </a>
                        </div>
                    </div>
                    <span class="badge badge-<?= htmlspecialchars($sub['status']) ?>">
                        <?= htmlspecialchars($sub['status']) ?>
                    </span>
                </div>
                
                <div style="background: #FFF0F5; padding: 14px; border-radius: 10px; border: 1px solid var(--parent-pastel-pink); margin-bottom: 16px; font-size: 13px; line-height: 1.8;">
                    <div><strong>Enrolled Child:</strong> <?= htmlspecialchars($sub['child_name']) ?></div>
                    <div><strong>Active Period:</strong> <?= formatDate($sub['start_date']) ?> → <?= formatDate($sub['end_date']) ?></div>
                    <div><strong>Days Remaining:</strong> <span style="color: var(--parent-pink); font-weight: 700;"><?= $daysLeft ?> Days</span></div>
                    <div><strong>Coverage:</strong> <?= $sub['included_hours_per_day'] ?> hrs/day • <?= $sub['included_days_per_week'] ?> days/wk</div>
                    <div style="font-size: 15px; font-weight: 700; color: var(--parent-dark-pink); margin-top: 4px;">
                        <?= formatCurrency($sub['monthly_amount']) ?> / month
                    </div>
                </div>
                
                <!-- Auto-renew Toggle & Actions -->
                <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--parent-light-pink); padding-top: 14px;">
                    <form method="POST" style="display: inline;">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="toggle_autorenew">
                        <input type="hidden" name="sub_id" value="<?= $sub['id'] ?>">
                        <input type="hidden" name="new_val" value="<?= $sub['auto_renew'] ? 0 : 1 ?>">
                        <button type="submit" style="background: none; border: none; padding: 0; cursor: pointer; display: flex; align-items: center; gap: 6px; font-size: 12px; color: #424242;">
                            <i class="fas <?= $sub['auto_renew'] ? 'fa-toggle-on' : 'fa-toggle-off' ?>" style="font-size: 18px; color: <?= $sub['auto_renew'] ? 'var(--parent-pink)' : '#9E9E9E' ?>;"></i>
                            Auto-Renew
                        </button>
                    </form>
                    
                    <div class="btn-group-action">
                        <?php if ($sub['status'] === 'active'): ?>
                            <form method="POST" style="display: inline;">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="pause">
                                <input type="hidden" name="sub_id" value="<?= $sub['id'] ?>">
                                <button type="submit" class="btn btn-secondary" style="padding: 4px 10px; font-size: 12px;">Pause</button>
                            </form>
                        <?php elseif ($sub['status'] === 'paused'): ?>
                            <form method="POST" style="display: inline;">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="resume">
                                <input type="hidden" name="sub_id" value="<?= $sub['id'] ?>">
                                <button type="submit" class="btn btn-success" style="padding: 4px 10px; font-size: 12px;">Resume</button>
                            </form>
                        <?php endif; ?>
                        
                        <?php if ($sub['status'] !== 'cancelled'): ?>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Cancel subscription renewal?');">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="cancel">
                                <input type="hidden" name="sub_id" value="<?= $sub['id'] ?>">
                                <button type="submit" class="btn btn-danger" style="padding: 4px 10px; font-size: 12px;">Cancel</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
