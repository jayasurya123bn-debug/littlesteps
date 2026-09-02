<?php
/**
 * Provider Dashboard
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
requireRole('provider');

$pageTitleHeader = 'Dashboard Overview';
$pageTitle = 'Dashboard';

$conn = getDBConnection();
$providerId = $_SESSION['user_id'];

// Get primary center for this provider
$stmt = $conn->prepare("SELECT id FROM daycare_centers WHERE provider_id = ? LIMIT 1");
$stmt->bind_param("i", $providerId);
$stmt->execute();
$centerResult = $stmt->get_result();
$centerId = $centerResult->num_rows > 0 ? $centerResult->fetch_assoc()['id'] : 0;

// Get stats
$stats = [
    'pending_bookings' => 0,
    'active_children' => 0,
    'monthly_revenue' => 0,
    'total_reviews' => 0,
    'avg_rating' => 0
];

if ($centerId > 0) {
    // Pending Bookings
    $q1 = $conn->prepare("SELECT COUNT(*) as cnt FROM bookings WHERE center_id = ? AND status = 'pending'");
    $q1->bind_param("i", $centerId);
    $q1->execute();
    $stats['pending_bookings'] = $q1->get_result()->fetch_assoc()['cnt'];
    
    // Active Children (Confirmed bookings for today + active subscriptions)
    $q2 = $conn->prepare("
        SELECT COUNT(DISTINCT child_name) as cnt FROM (
            SELECT child_name FROM bookings WHERE center_id = ? AND status IN ('confirmed', 'in_progress') AND DATE(start_time) = CURDATE()
            UNION
            SELECT child_name FROM subscriptions WHERE center_id = ? AND status = 'active'
        ) as active_kids
    ");
    $q2->bind_param("ii", $centerId, $centerId);
    $q2->execute();
    $stats['active_children'] = $q2->get_result()->fetch_assoc()['cnt'];
    
    // Monthly Revenue (Bookings completed this month + active subs)
    $q3 = $conn->prepare("
        SELECT SUM(amount) as total FROM (
            SELECT total_price as amount FROM bookings WHERE center_id = ? AND status = 'completed' AND MONTH(start_time) = MONTH(CURDATE()) AND YEAR(start_time) = YEAR(CURDATE())
            UNION ALL
            SELECT price as amount FROM subscriptions WHERE center_id = ? AND status = 'active'
        ) as rev
    ");
    $q3->bind_param("ii", $centerId, $centerId);
    $q3->execute();
    $stats['monthly_revenue'] = $q3->get_result()->fetch_assoc()['total'] ?? 0;
    
    // Reviews
    $q4 = $conn->prepare("SELECT rating, total_reviews FROM daycare_centers WHERE id = ?");
    $q4->bind_param("i", $centerId);
    $q4->execute();
    $revStats = $q4->get_result()->fetch_assoc();
    $stats['avg_rating'] = $revStats['rating'];
    $stats['total_reviews'] = $revStats['total_reviews'];
}

// Get recent pending bookings
$pendingStmt = $conn->prepare("
    SELECT b.*, u.first_name, u.last_name, u.phone 
    FROM bookings b
    JOIN users u ON b.parent_id = u.id
    WHERE b.center_id = ? AND b.status = 'pending'
    ORDER BY b.created_at ASC LIMIT 5
");
if ($centerId > 0) {
    $pendingStmt->bind_param("i", $centerId);
    $pendingStmt->execute();
    $pendingRequests = $pendingStmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $pendingRequests = [];
}

$conn->close();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Statistics -->
<div class="stat-card-row">
    <div class="card stat-card" style="margin-bottom: 0;">
        <div class="stat-icon" style="background: var(--warning-bg); color: var(--warning);">
            <i class="fas fa-bell"></i>
        </div>
        <div class="stat-details" style="flex-grow: 1;">
            <div class="stat-value"><?= $stats['pending_bookings'] ?></div>
            <div class="stat-label">Pending Requests</div>
        </div>
    </div>
    
    <div class="card stat-card" style="margin-bottom: 0;">
        <div class="stat-icon" style="background: var(--info-bg); color: var(--info);">
            <i class="fas fa-child"></i>
        </div>
        <div class="stat-details" style="flex-grow: 1;">
            <div class="stat-value"><?= $stats['active_children'] ?></div>
            <div class="stat-label">Expected Today</div>
        </div>
    </div>
    
    <div class="card stat-card" style="margin-bottom: 0;">
        <div class="stat-icon" style="background: var(--success-bg); color: var(--success);">
            <i class="fas fa-rupee-sign"></i>
        </div>
        <div class="stat-details" style="flex-grow: 1;">
            <div class="stat-value"><?= formatCurrency($stats['monthly_revenue']) ?></div>
            <div class="stat-label">Revenue (This Month)</div>
        </div>
    </div>
    
    <div class="card stat-card" style="margin-bottom: 0;">
        <div class="stat-icon" style="background: rgba(255, 193, 7, 0.2); color: #FFC107;">
            <i class="fas fa-star"></i>
        </div>
        <div class="stat-details" style="flex-grow: 1;">
            <div class="stat-value"><?= $stats['avg_rating'] > 0 ? number_format($stats['avg_rating'], 1) : 'N/A' ?></div>
            <div class="stat-label"><?= $stats['total_reviews'] ?> Reviews</div>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: var(--space-lg); margin-top: var(--space-xl);">
    
    <!-- Pending Requests -->
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-md);">
            <h3 style="color: var(--dark-gray); margin: 0;">New Booking Requests</h3>
            <a href="bookings.php" class="btn btn-sm btn-outline">View All</a>
        </div>
        
        <?php if (count($pendingRequests) > 0): ?>
            <div class="table-container" style="box-shadow: none; border-radius: 0;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Parent</th>
                            <th>Child Details</th>
                            <th>Date & Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingRequests as $req): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($req['first_name'] . ' ' . $req['last_name']) ?></strong><br>
                                    <span style="font-size: 12px; color: var(--medium-gray);"><?= htmlspecialchars($req['phone']) ?></span>
                                </td>
                                <td>
                                    <?= htmlspecialchars($req['child_name']) ?> (<?= $req['child_age'] ?> yrs)<br>
                                    <?php if ($req['special_needs']): ?>
                                        <span class="badge badge-warning" style="font-size: 10px;" title="<?= htmlspecialchars($req['special_needs']) ?>"><i class="fas fa-exclamation-triangle"></i> Notes</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= date('d M', strtotime($req['start_time'])) ?><br>
                                    <span style="font-size: 12px; color: var(--medium-gray);">
                                        <?= date('h:i A', strtotime($req['start_time'])) ?> - <?= date('h:i A', strtotime($req['end_time'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 4px;">
                                        <form method="POST" action="bookings.php" style="display: inline;">
                                            <?php csrfField(); ?>
                                            <input type="hidden" name="booking_id" value="<?= $req['id'] ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-sm btn-success" title="Approve"><i class="fas fa-check"></i></button>
                                        </form>
                                        <form method="POST" action="bookings.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to reject this request?');">
                                            <?php csrfField(); ?>
                                            <input type="hidden" name="booking_id" value="<?= $req['id'] ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="btn btn-sm btn-danger" title="Reject"><i class="fas fa-times"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: var(--space-xl) 0;">
                <p style="color: var(--medium-gray);">No pending requests at the moment.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Quick Actions / Setup -->
    <div>
        <div class="card">
            <h3 style="color: var(--dark-gray); margin-bottom: var(--space-md);">Setup Checklist</h3>
            
            <div style="display: flex; flex-direction: column; gap: var(--space-sm);">
                <div style="display: flex; align-items: center; justify-content: space-between; padding: var(--space-sm); background: <?= $centerId > 0 ? 'var(--success-bg)' : 'var(--warning-bg)' ?>; border-radius: var(--radius-sm);">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <i class="fas <?= $centerId > 0 ? 'fa-check-circle text-success' : 'fa-circle text-warning' ?>"></i>
                        <span style="font-size: 14px; font-weight: 500;">Center Profile</span>
                    </div>
                    <?php if ($centerId == 0): ?>
                        <a href="center_profile.php" class="btn btn-sm btn-primary">Complete</a>
                    <?php endif; ?>
                </div>
                
                <!-- Additional checklist items can go here -->
                <div style="display: flex; align-items: center; justify-content: space-between; padding: var(--space-sm); background: var(--success-bg); border-radius: var(--radius-sm);">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-check-circle text-success"></i>
                        <span style="font-size: 14px; font-weight: 500;">Add Caregivers</span>
                    </div>
                </div>
                
                <div style="display: flex; align-items: center; justify-content: space-between; padding: var(--space-sm); background: var(--warning-bg); border-radius: var(--radius-sm);">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-circle text-warning"></i>
                        <span style="font-size: 14px; font-weight: 500;">Verify Documents</span>
                    </div>
                    <a href="settings.php#documents" class="btn btn-sm btn-primary">Upload</a>
                </div>
            </div>
        </div>
        
        <div class="card" style="background: linear-gradient(135deg, var(--main-pink), #C2185B); color: white; text-align: center;">
            <i class="fas fa-headset" style="font-size: 32px; margin-bottom: var(--space-md);"></i>
            <h4 style="color: white; margin-bottom: 8px;">Need Help?</h4>
            <p style="font-size: 13px; opacity: 0.9; margin-bottom: var(--space-md);">Contact our provider support team for assistance.</p>
            <button class="btn btn-outline" style="color: white; border-color: white; width: 100%;">Contact Support</button>
        </div>
    </div>
</div>

<style>
    @media (max-width: 991px) {
        div[style*="grid-template-columns: 2fr 1fr"] { grid-template-columns: 1fr !important; }
    }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
