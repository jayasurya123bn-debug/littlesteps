<?php
/**
 * Admin Service Providers Management
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
requireRole('admin');

$pageTitleHeader = 'Service Providers';
$pageTitle = 'Manage Providers';

$conn = getDBConnection();

// Handle Direct Quick Actions (Approve, Reject, Suspend, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['provider_id'])) {
    if (verifyCSRFToken($_POST['csrf_token'])) {
        $providerId = (int)$_POST['provider_id'];
        $action     = $_POST['action'];

        if ($action === 'approve') {
            $stmt = $conn->prepare("UPDATE providers SET status = 'approved', is_active = 1 WHERE id = ?");
            $stmt->bind_param("i", $providerId);
            $stmt->execute();
            
            // Add notification for provider
            $notifMsg = "Congratulations! Your provider profile has been approved by the Little Steps Administration.";
            $nStmt = $conn->prepare("INSERT INTO notifications (provider_id, title, message, type, link) VALUES (?, 'Profile Approved! 🎉', ?, 'success', 'center.php')");
            $nStmt->bind_param("is", $providerId, $notifMsg);
            $nStmt->execute();
            
            setFlashMessage('success', 'Provider application approved successfully.');
            
        } elseif ($action === 'reject') {
            $reason = sanitizeInput($conn, $_POST['reject_reason'] ?? 'Application does not meet requirements');
            $stmt = $conn->prepare("UPDATE providers SET status = 'rejected', is_active = 0, admin_notes = ? WHERE id = ?");
            $stmt->bind_param("si", $reason, $providerId);
            $stmt->execute();
            setFlashMessage('warning', 'Provider application marked as rejected.');
            
        } elseif ($action === 'suspend') {
            $stmt = $conn->prepare("UPDATE providers SET status = 'suspended', is_active = 0 WHERE id = ?");
            $stmt->bind_param("i", $providerId);
            $stmt->execute();
            setFlashMessage('error', 'Provider has been suspended.');
            
        } elseif ($action === 'reactivate') {
            $stmt = $conn->prepare("UPDATE providers SET status = 'approved', is_active = 1 WHERE id = ?");
            $stmt->bind_param("i", $providerId);
            $stmt->execute();
            setFlashMessage('success', 'Provider reactivated.');
            
        } elseif ($action === 'delete') {
            $stmt = $conn->prepare("DELETE FROM providers WHERE id = ?");
            $stmt->bind_param("i", $providerId);
            $stmt->execute();
            setFlashMessage('success', 'Provider permanently removed.');
        }
    }
    redirect('/admin/providers.php');
}

// Search & Filter
$search = sanitizeInput($conn, $_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? 'all';
$cityFilter = sanitizeInput($conn, $_GET['city'] ?? '');

$sql = "SELECT p.id, p.business_name, p.owner_name, p.email, p.phone, p.city, p.status, p.created_at,
               COUNT(c.id) as centers_count
        FROM providers p
        LEFT JOIN daycare_centers c ON p.id = c.provider_id
        WHERE 1=1";

if (!empty($search)) {
    $sql .= " AND (p.business_name LIKE '%$search%' OR p.owner_name LIKE '%$search%' OR p.email LIKE '%$search%' OR p.phone LIKE '%$search%')";
}

if ($statusFilter !== 'all') {
    $sql .= " AND p.status = '$statusFilter'";
}

if (!empty($cityFilter)) {
    $sql .= " AND p.city = '$cityFilter'";
}

// Pin pending providers at the top, then newest
$sql .= " GROUP BY p.id ORDER BY (CASE WHEN p.status = 'pending' THEN 0 ELSE 1 END), p.created_at DESC";
$providers = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

// Distinct cities
$cities = [];
$cRes = $conn->query("SELECT DISTINCT city FROM providers WHERE city IS NOT NULL AND city != ''");
if ($cRes) {
    while($r = $cRes->fetch_assoc()) $cities[] = $r['city'];
}

$conn->close();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Filter Toolbar -->
<div class="filter-card">
    <form method="GET" class="filter-form">
        <div style="flex: 2; min-width: 200px;">
            <input type="text" name="search" class="filter-input" style="width: 100%;" placeholder="Search by business, owner, email..." value="<?= htmlspecialchars($search) ?>">
        </div>
        
        <div>
            <select name="status" class="filter-input">
                <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All Statuses</option>
                <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>🟡 Pending Approval</option>
                <option value="approved" <?= $statusFilter === 'approved' ? 'selected' : '' ?>>🟢 Approved</option>
                <option value="rejected" <?= $statusFilter === 'rejected' ? 'selected' : '' ?>>🔴 Rejected</option>
                <option value="suspended" <?= $statusFilter === 'suspended' ? 'selected' : '' ?>>⚫ Suspended</option>
            </select>
        </div>
        
        <?php if(!empty($cities)): ?>
            <div>
                <select name="city" class="filter-input">
                    <option value="">All Cities</option>
                    <?php foreach($cities as $city): ?>
                        <option value="<?= htmlspecialchars($city) ?>" <?= $cityFilter === $city ? 'selected' : '' ?>><?= htmlspecialchars($city) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
        
        <button type="submit" class="btn btn-primary" style="padding: 9px 18px;">
            <i class="fas fa-filter"></i> Filter
        </button>
        
        <?php if(!empty($search) || $statusFilter !== 'all' || !empty($cityFilter)): ?>
            <a href="providers.php" class="btn btn-secondary" style="padding: 9px 14px;">Reset</a>
        <?php endif; ?>
    </form>
</div>

<!-- Providers Master Table -->
<div class="table-container">
    <div class="table-header-bar">
        <h3 class="table-header-title">
            <i class="fas fa-building"></i> Daycare Organizations & Providers (<?= count($providers) ?>)
        </h3>
    </div>
    
    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Business & Owner</th>
                    <th>Contact Info</th>
                    <th>City</th>
                    <th>Centers</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($providers)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 36px; color: #9E9E9E;">
                            No daycare providers found matching your filter.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($providers as $p): ?>
                        <tr style="<?= $p['status'] === 'pending' ? 'background: #FFFDE7;' : '' ?>">
                            <td>#<?= $p['id'] ?></td>
                            <td>
                                <strong style="color: var(--admin-heading); font-size: 15px;"><?= htmlspecialchars($p['business_name']) ?></strong>
                                <div style="font-size: 12px; color: #757575;">Owner: <?= htmlspecialchars($p['owner_name']) ?></div>
                            </td>
                            <td>
                                <div><i class="fas fa-envelope" style="color: var(--admin-pink); font-size: 11px;"></i> <?= htmlspecialchars($p['email']) ?></div>
                                <div style="font-size: 12px; color: #757575;"><i class="fas fa-phone" style="font-size: 10px;"></i> <?= htmlspecialchars($p['phone']) ?></div>
                            </td>
                            <td><?= htmlspecialchars($p['city']) ?></td>
                            <td>
                                <span class="badge badge-pink" style="font-weight: 600;">
                                    <?= $p['centers_count'] ?> Centers
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-<?= htmlspecialchars($p['status']) ?>">
                                    <?= htmlspecialchars($p['status']) ?>
                                </span>
                            </td>
                            <td><?= formatDate($p['created_at']) ?></td>
                            <td style="text-align: right;">
                                <div class="btn-group-action">
                                    <!-- View Details -->
                                    <a href="provider_view.php?id=<?= $p['id'] ?>" class="btn-icon btn-icon-view" title="View Detailed Profile">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    <!-- Approve Quick Action (if pending or suspended) -->
                                    <?php if ($p['status'] !== 'approved'): ?>
                                        <form method="POST" style="display: inline;">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="approve">
                                            <input type="hidden" name="provider_id" value="<?= $p['id'] ?>">
                                            <button type="submit" class="btn-icon btn-icon-approve" title="Approve Provider">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <!-- Suspend Action (if approved) -->
                                    <?php if ($p['status'] === 'approved'): ?>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Suspend this provider?');">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="suspend">
                                            <input type="hidden" name="provider_id" value="<?= $p['id'] ?>">
                                            <button type="submit" class="btn-icon btn-icon-delete" title="Suspend Provider">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <!-- Reject Action (if pending) -->
                                    <?php if ($p['status'] === 'pending'): ?>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Reject this provider application?');">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="reject">
                                            <input type="hidden" name="provider_id" value="<?= $p['id'] ?>">
                                            <button type="submit" class="btn-icon btn-icon-delete" title="Reject Application">
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
