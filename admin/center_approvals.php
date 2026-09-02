<?php
/**
 * Admin Center Approvals
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
requireRole('admin');

$pageTitleHeader = 'Center Approvals';
$pageTitle = 'Approvals';

$conn = getDBConnection();

// Handle Approval / Rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['center_id'])) {
    if (verifyCSRFToken($_POST['csrf_token'])) {
        $centerId = (int)$_POST['center_id'];
        $action = $_POST['action'];
        
        if ($action === 'approve') {
            // Activate the center
            $updCenter = $conn->prepare("UPDATE daycare_centers SET status = 'active' WHERE id = ?");
            $updCenter->bind_param("i", $centerId);
            $updCenter->execute();
            // Activate the provider who owns this center
            $updProvider = $conn->prepare("UPDATE providers p JOIN daycare_centers c ON c.provider_id = p.id SET p.status = 'approved', p.is_active = 1 WHERE c.id = ?");
            $updProvider->bind_param("i", $centerId);
            $updProvider->execute();
            setFlashMessage('success', 'Center approved and provider activated successfully.');
        } else {
            $updCenter = $conn->prepare("UPDATE daycare_centers SET status = 'inactive' WHERE id = ?");
            $updCenter->bind_param("i", $centerId);
            $updCenter->execute();
            // Update provider status to rejected
            $updProvider = $conn->prepare("UPDATE providers p JOIN daycare_centers c ON c.provider_id = p.id SET p.status = 'rejected' WHERE c.id = ?");
            $updProvider->bind_param("i", $centerId);
            $updProvider->execute();
            setFlashMessage('success', 'Center rejected.');
        }
    }
    redirect('/admin/center_approvals.php');
}

// Get pending centers
$stmt = $conn->query("
    SELECT c.*, p.owner_name as first_name, '' as last_name, p.email, p.phone
    FROM daycare_centers c
    JOIN providers p ON c.provider_id = p.id
    WHERE c.status = 'pending'
    ORDER BY c.created_at ASC
");
$pendingCenters = $stmt->fetch_all(MYSQLI_ASSOC);

$conn->close();

require_once __DIR__ . '/includes/header.php';
?>

<?php if (count($pendingCenters) > 0): ?>
    <div style="display: grid; gap: var(--space-lg);">
        <?php foreach ($pendingCenters as $c): ?>
            <div class="card" style="border-left: 4px solid var(--warning);">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: var(--space-md);">
                    <div>
                        <h2 style="margin: 0; color: var(--admin-primary);"><?= htmlspecialchars($c['name']) ?></h2>
                        <span style="color: var(--medium-gray); font-size: 14px;">Submitted on <?= date('d M Y, h:i A', strtotime($c['created_at'])) ?></span>
                    </div>
                    <span class="badge badge-warning" style="font-size: 14px; padding: 6px 12px;">Pending Review</span>
                </div>
                
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: var(--space-lg);">
                    <div>
                        <h4 style="color: var(--dark-gray); border-bottom: 1px solid var(--light-gray); padding-bottom: 8px; margin-bottom: 8px;">Center Details</h4>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 14px; margin-bottom: var(--space-md);">
                            <div><strong>Address:</strong> <?= htmlspecialchars($c['address']) ?>, <?= htmlspecialchars($c['city']) ?>, <?= htmlspecialchars($c['state']) ?> - <?= htmlspecialchars($c['pincode']) ?></div>
                            <div><strong>Capacity:</strong> <?= htmlspecialchars($c['capacity']) ?> children</div>
                            <div><strong>Age Group:</strong> <?= htmlspecialchars($c['min_age_months']) ?>mo - <?= htmlspecialchars($c['max_age_years']) ?>yrs</div>
                            <div><strong>Hours:</strong> <?= date('h:i A', strtotime($c['operating_hours_start'])) ?> - <?= date('h:i A', strtotime($c['operating_hours_end'])) ?></div>
                            <div><strong>24x7 Care:</strong> <?= $c['is_24x7'] ? 'Yes' : 'No' ?></div>
                            <div><strong>CCTV:</strong> <?= $c['cctv_enabled'] ? 'Yes' : 'No' ?></div>
                        </div>
                        
                        <h4 style="color: var(--dark-gray); border-bottom: 1px solid var(--light-gray); padding-bottom: 8px; margin-bottom: 8px;">Description</h4>
                        <p style="font-size: 14px; color: var(--near-black);"><?= nl2br(htmlspecialchars($c['description'])) ?></p>
                    </div>
                    
                    <div>
                        <div style="background: var(--light-gray); padding: var(--space-md); border-radius: var(--radius-md);">
                            <h4 style="color: var(--dark-gray); margin-bottom: 12px;">Provider Info</h4>
                            <p style="margin: 0 0 8px; font-size: 14px;"><strong>Name:</strong> <?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name']) ?></p>
                            <p style="margin: 0 0 8px; font-size: 14px;"><strong>Email:</strong> <a href="mailto:<?= htmlspecialchars($c['email']) ?>"><?= htmlspecialchars($c['email']) ?></a></p>
                            <p style="margin: 0 0 8px; font-size: 14px;"><strong>Phone:</strong> <a href="tel:<?= htmlspecialchars($c['phone']) ?>"><?= htmlspecialchars($c['phone']) ?></a></p>
                            
                            <div style="margin-top: var(--space-md); text-align: center;">
                                <button class="btn btn-sm btn-outline" style="width: 100%;" onclick="alert('Document viewing logic goes here.')"><i class="fas fa-file-pdf"></i> View Documents</button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div style="display: flex; gap: var(--space-md); margin-top: var(--space-lg); border-top: 1px solid var(--light-gray); padding-top: var(--space-md); justify-content: flex-end;">
                    <form method="POST" action="center_approvals.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to REJECT this center?');">
                        <?php csrfField(); ?>
                        <input type="hidden" name="center_id" value="<?= $c['id'] ?>">
                        <input type="hidden" name="action" value="reject">
                        <button type="submit" class="btn btn-outline" style="color: var(--danger); border-color: var(--danger);"><i class="fas fa-times"></i> Reject Center</button>
                    </form>
                    
                    <form method="POST" action="center_approvals.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to APPROVE this center? It will become visible to parents.');">
                        <?php csrfField(); ?>
                        <input type="hidden" name="center_id" value="<?= $c['id'] ?>">
                        <input type="hidden" name="action" value="approve">
                        <button type="submit" class="btn btn-success" style="background: var(--success); border-color: var(--success);"><i class="fas fa-check"></i> Approve Center</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="card text-center" style="padding: var(--space-3xl) 0;">
        <i class="fas fa-check-circle text-success" style="font-size: 64px; margin-bottom: var(--space-md);"></i>
        <h2 style="color: var(--dark-gray); margin-bottom: 8px;">All Caught Up!</h2>
        <p style="color: var(--medium-gray);">There are no pending center approvals at this time.</p>
        <a href="centers.php" class="btn btn-primary" style="margin-top: var(--space-md);">View Active Centers</a>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
