<?php
/**
 * Parent Subscriptions Page
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

// Get Subscriptions
$stmt = $conn->prepare("
    SELECT s.*, c.name as center_name, c.area, c.city 
    FROM subscriptions s
    JOIN daycare_centers c ON s.center_id = c.id
    WHERE s.user_id = ?
    ORDER BY s.status ASC, s.end_date DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$subscriptions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$conn->close();

require_once __DIR__ . '/includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-lg);">
    <h2 style="margin: 0; color: var(--dark-pink);">Manage Subscriptions</h2>
    <a href="centers.php" class="btn btn-primary"><i class="fas fa-search"></i> Find Centers</a>
</div>

<div class="alert alert-info">
    <i class="fas fa-info-circle"></i> Monthly subscriptions offer better rates and guaranteed spots for your child. Contact centers directly from their profile to start a subscription.
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: var(--space-lg); margin-top: var(--space-xl);">
    <?php if (count($subscriptions) > 0): ?>
        <?php foreach ($subscriptions as $sub): ?>
            <div class="card <?= $sub['status'] == 'active' ? 'subscription-card active' : 'subscription-card' ?>" style="margin: 0;">
                
                <?php if ($sub['status'] == 'active'): ?>
                    <div style="position: absolute; top: -12px; right: 24px; background: var(--success); color: white; padding: 4px 12px; border-radius: var(--radius-full); font-size: 12px; font-weight: 600; box-shadow: var(--shadow-sm);">
                        Active
                    </div>
                <?php elseif ($sub['status'] == 'cancelled'): ?>
                    <div style="position: absolute; top: -12px; right: 24px; background: var(--danger); color: white; padding: 4px 12px; border-radius: var(--radius-full); font-size: 12px; font-weight: 600; box-shadow: var(--shadow-sm);">
                        Cancelled
                    </div>
                <?php endif; ?>
                
                <h3 style="color: var(--dark-gray); margin-bottom: 4px;"><a href="center-details.php?id=<?= $sub['center_id'] ?>" style="color: inherit;"><?= htmlspecialchars($sub['center_name']) ?></a></h3>
                <p style="color: var(--medium-gray); font-size: 13px; margin-bottom: var(--space-md);">
                    <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($sub['area'] . ', ' . $sub['city']) ?>
                </p>
                
                <div style="background: var(--light-gray); border-radius: var(--radius-md); padding: var(--space-md); margin-bottom: var(--space-md); display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-sm); text-align: left;">
                    <div>
                        <span style="font-size: 12px; color: var(--medium-gray);">Plan Type</span><br>
                        <strong style="font-size: 14px;"><?= ucfirst($sub['plan_type']) ?></strong>
                    </div>
                    <div>
                        <span style="font-size: 12px; color: var(--medium-gray);">Child</span><br>
                        <strong style="font-size: 14px;"><?= htmlspecialchars($sub['child_name']) ?></strong>
                    </div>
                    <div>
                        <span style="font-size: 12px; color: var(--medium-gray);">Start Date</span><br>
                        <strong style="font-size: 14px;"><?= date('d M Y', strtotime($sub['start_date'])) ?></strong>
                    </div>
                    <div>
                        <span style="font-size: 12px; color: var(--medium-gray);">Renews / Ends</span><br>
                        <strong style="font-size: 14px;"><?= date('d M Y', strtotime($sub['end_date'])) ?></strong>
                    </div>
                </div>
                
                <div class="subscription-price" style="<?= $sub['status'] != 'active' ? 'color: var(--medium-gray);' : '' ?>">
                    <?= formatCurrency($sub['monthly_amount']) ?><span style="font-size: 14px; font-weight: normal; color: var(--medium-gray);">/mo</span>
                </div>
                
                <?php if ($sub['status'] == 'active'): ?>
                    <div style="display: flex; gap: var(--space-sm);">
                        <button class="btn btn-outline" style="flex: 1;" onclick="alert('Feature coming soon: Change Payment Method')">Update Payment</button>
                        <button class="btn btn-outline" style="flex: 1; color: var(--danger); border-color: var(--danger);" onclick="alert('Please contact the center directly to cancel a subscription.')">Cancel</button>
                    </div>
                <?php else: ?>
                    <button class="btn btn-primary" style="width: 100%;" onclick="location.href='center-details.php?id=<?= $sub['center_id'] ?>'">Reactivate</button>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div style="grid-column: 1 / -1;" class="card text-center">
            <div style="padding: var(--space-2xl) 0;">
                <div style="font-size: 48px; color: var(--light-pink); margin-bottom: var(--space-md);">
                    <i class="fas fa-credit-card"></i>
                </div>
                <h3>No Subscriptions Found</h3>
                <p style="color: var(--medium-gray); margin-bottom: var(--space-md);">You don't have any active or past subscriptions.</p>
                <a href="centers.php" class="btn btn-primary">Find a Center to Subscribe</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
