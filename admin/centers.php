<?php
/**
 * Admin Active Centers
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
requireRole('admin');

$pageTitleHeader = 'Daycare Centers';
$pageTitle = 'Centers';

$conn = getDBConnection();

// Handle Suspend / Activate
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['center_id'])) {
    if (verifyCSRFToken($_POST['csrf_token'])) {
        $centerId = (int)$_POST['center_id'];
        $action = $_POST['action'];
        
        if ($action === 'suspend') {
            $updStmt = $conn->prepare("UPDATE daycare_centers SET status = 'suspended' WHERE id = ?");
            $updStmt->bind_param("i", $centerId);
            $updStmt->execute();
            setFlashMessage('success', 'Center suspended successfully.');
        } elseif ($action === 'activate') {
            $updStmt = $conn->prepare("UPDATE daycare_centers SET status = 'active' WHERE id = ?");
            $updStmt->bind_param("i", $centerId);
            $updStmt->execute();
            setFlashMessage('success', 'Center re-activated successfully.');
        }
    }
    redirect('/admin/centers.php');
}

// Get all non-pending centers
$stmt = $conn->query("
    SELECT c.*, p.owner_name as first_name, '' as last_name, p.phone
    FROM daycare_centers c
    JOIN providers p ON c.provider_id = p.id
    WHERE c.status != 'pending'
    ORDER BY c.created_at DESC
");
$centers = $stmt->fetch_all(MYSQLI_ASSOC);

$conn->close();

require_once __DIR__ . '/includes/header.php';
?>

<div class="card" style="padding: 0; overflow: hidden;">
    <div style="padding: var(--space-md) var(--space-lg); border-bottom: 1px solid var(--light-gray); display: flex; justify-content: space-between; align-items: center; background: #F8F9FA;">
        <h3 style="margin: 0; color: var(--dark-gray); font-size: 18px;">Registered Centers</h3>
        <input type="text" class="form-control" placeholder="Search centers..." style="width: 250px; padding: 4px 8px;">
    </div>
    
    <div class="table-container" style="box-shadow: none;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Center Name</th>
                    <th>Provider</th>
                    <th>Location</th>
                    <th>Capacity</th>
                    <th>Rating</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($centers) > 0): ?>
                    <?php foreach ($centers as $c): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($c['name']) ?></strong><br>
                                <span style="font-size: 11px; color: var(--medium-gray);">Added: <?= date('d M Y', strtotime($c['created_at'])) ?></span>
                            </td>
                            <td>
                                <?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name']) ?><br>
                                <span style="font-size: 12px; color: var(--medium-gray);"><i class="fas fa-phone-alt"></i> <?= htmlspecialchars($c['phone']) ?></span>
                            </td>
                            <td><?= htmlspecialchars($c['area']) ?>, <?= htmlspecialchars($c['city']) ?></td>
                            <td><?= htmlspecialchars($c['capacity']) ?> slots</td>
                            <td>
                                <i class="fas fa-star text-warning" style="color: #FFC107;"></i> 
                                <?= $c['rating'] > 0 ? number_format($c['rating'], 1) : 'N/A' ?> 
                                <span style="font-size: 11px; color: var(--medium-gray);">((<?= $c['total_reviews'] ?>))</span>
                            </td>
                            <td>
                                <?php if ($c['status'] == 'active'): ?>
                                    <span class="badge badge-success">Active</span>
                                <?php elseif ($c['status'] == 'suspended'): ?>
                                    <span class="badge badge-danger">Suspended</span>
                                <?php else: ?>
                                    <span class="badge badge-default"><?= ucfirst($c['status']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 4px;">
                                    <a href="<?= SITE_URL ?>/center-details.php?id=<?= $c['id'] ?>" target="_blank" class="btn btn-sm btn-outline" title="View Public Profile"><i class="fas fa-external-link-alt"></i></a>
                                    
                                    <?php if ($c['status'] == 'active'): ?>
                                        <form method="POST" action="centers.php" style="display: inline;" onsubmit="return confirm('Suspend this center? It will be hidden from parents.');">
                                            <?php csrfField(); ?>
                                            <input type="hidden" name="center_id" value="<?= $c['id'] ?>">
                                            <input type="hidden" name="action" value="suspend">
                                            <button type="submit" class="btn btn-sm btn-danger" title="Suspend Center"><i class="fas fa-pause"></i></button>
                                        </form>
                                    <?php elseif ($c['status'] == 'suspended'): ?>
                                        <form method="POST" action="centers.php" style="display: inline;" onsubmit="return confirm('Re-activate this center?');">
                                            <?php csrfField(); ?>
                                            <input type="hidden" name="center_id" value="<?= $c['id'] ?>">
                                            <input type="hidden" name="action" value="activate">
                                            <button type="submit" class="btn btn-sm btn-success" title="Activate Center"><i class="fas fa-play"></i></button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center" style="padding: 30px;">No centers found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
