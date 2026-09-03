<?php
/**
 * Provider Center Management
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
requireRole('provider');

$pageTitleHeader = 'Daycare Centers';
$pageTitle = 'My Centers';

$conn = getDBConnection();
$providerId = $_SESSION['user_id'];

// Handle Delete or Status Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['center_id'])) {
    if (verifyCSRFToken($_POST['csrf_token'])) {
        $centerId = (int)$_POST['center_id'];
        $action   = $_POST['action'];

        if ($action === 'delete') {
            $stmt = $conn->prepare("DELETE FROM daycare_centers WHERE id = ? AND provider_id = ?");
            $stmt->bind_param("ii", $centerId, $providerId);
            $stmt->execute();
            setFlashMessage('success', 'Daycare center deleted.');
        } elseif ($action === 'toggle_status') {
            $newStatus = sanitizeInput($conn, $_POST['new_status']);
            $stmt = $conn->prepare("UPDATE daycare_centers SET status = ? WHERE id = ? AND provider_id = ?");
            $stmt->bind_param("sii", $newStatus, $centerId, $providerId);
            $stmt->execute();
            setFlashMessage('success', 'Center status updated to ' . $newStatus);
        }
    }
    redirect('/provider/center.php');
}

// Fetch Centers
$statusFilter = sanitizeInput($conn, $_GET['status'] ?? '');
$sql = "SELECT c.*, COUNT(b.id) as total_bookings
        FROM daycare_centers c
        LEFT JOIN bookings b ON c.id = b.center_id
        WHERE c.provider_id = $providerId";

if (!empty($statusFilter)) {
    $sql .= " AND c.status = '$statusFilter'";
}
$sql .= " GROUP BY c.id ORDER BY c.created_at DESC";
$centers = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

$conn->close();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Action Bar -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
    <div style="display: flex; gap: 10px; align-items: center;">
        <a href="center.php" class="btn <?= empty($statusFilter) ? 'btn-primary' : 'btn-secondary' ?>" style="padding: 8px 16px;">All Centers</a>
        <a href="center.php?status=active" class="btn <?= $statusFilter === 'active' ? 'btn-primary' : 'btn-secondary' ?>" style="padding: 8px 16px;">Active Only</a>
        <a href="center.php?status=inactive" class="btn <?= $statusFilter === 'inactive' ? 'btn-primary' : 'btn-secondary' ?>" style="padding: 8px 16px;">Inactive</a>
    </div>
    
    <a href="center_edit.php" class="btn btn-primary" style="padding: 10px 22px; font-size: 14px;">
        <i class="fas fa-plus-circle" style="margin-right: 6px;"></i> Add New Daycare Center
    </a>
</div>

<!-- Centers Grid / Cards -->
<?php if (empty($centers)): ?>
    <div class="detail-card" style="text-align: center; padding: 48px 24px;">
        <i class="fas fa-school" style="font-size: 48px; color: var(--provider-pastel-pink); margin-bottom: 14px;"></i>
        <h3 style="color: #212121; margin: 0 0 8px 0;">No Daycare Centers Added Yet</h3>
        <p style="color: #757575; max-width: 480px; margin: 0 auto 20px;">List your first daycare facility or creche to start receiving bookings from parents in your area.</p>
        <a href="center_edit.php" class="btn btn-primary">
            <i class="fas fa-plus-circle"></i> Create Center Profile
        </a>
    </div>
<?php else: ?>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 24px;">
        <?php foreach ($centers as $c): ?>
            <div class="provider-table-card" style="margin-bottom: 0; display: flex; flex-direction: column;">
                <div style="height: 160px; background: #FFF0F5; position: relative; display: flex; align-items: center; justify-content: center;">
                    <span style="font-size: 48px;">🏫</span>
                    <?php if ($c['is_24x7']): ?>
                        <span style="position: absolute; top: 12px; left: 12px; background: var(--provider-pink); color: white; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700;">
                            24x7 Active
                        </span>
                    <?php endif; ?>
                    <span class="badge badge-<?= htmlspecialchars($c['status']) ?>" style="position: absolute; top: 12px; right: 12px;">
                        <?= htmlspecialchars($c['status']) ?>
                    </span>
                </div>
                
                <div style="padding: 20px; flex-grow: 1; display: flex; flex-direction: column;">
                    <h3 style="margin: 0 0 6px 0; font-size: 18px; color: #212121;"><?= htmlspecialchars($c['name']) ?></h3>
                    <div style="font-size: 13px; color: #757575; margin-bottom: 12px;">
                        <i class="fas fa-map-marker-alt" style="color: var(--provider-pink);"></i> <?= htmlspecialchars($c['area'] . ', ' . $c['city']) ?>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; background: #FFF0F5; padding: 12px; border-radius: 8px; font-size: 12px; color: #424242; margin-bottom: 16px;">
                        <div><strong>Capacity:</strong> <?= $c['capacity'] ?> children</div>
                        <div><strong>Current Occ:</strong> <?= $c['current_occupancy'] ?> booked</div>
                        <div><strong>Daily Rate:</strong> <?= formatCurrency($c['pricing_daily']) ?></div>
                        <div><strong>Rating:</strong> <?= $c['rating'] ?> ★</div>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--provider-light-pink); padding-top: 14px; margin-top: auto;">
                        <a href="<?= SITE_URL ?>/center-details.php?id=<?= $c['id'] ?>" target="_blank" class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px;">
                            <i class="fas fa-external-link-alt"></i> Preview
                        </a>
                        
                        <div class="btn-group-action">
                            <a href="center_edit.php?id=<?= $c['id'] ?>" class="btn-icon btn-icon-edit" title="Edit Center">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="availability.php?center_id=<?= $c['id'] ?>" class="btn-icon btn-icon-view" title="Manage Slots">
                                <i class="fas fa-calendar-alt"></i>
                            </a>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Permanently delete this center?');">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="center_id" value="<?= $c['id'] ?>">
                                <button type="submit" class="btn-icon btn-icon-delete" title="Delete Center">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
