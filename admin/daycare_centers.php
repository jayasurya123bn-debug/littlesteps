<?php
/**
 * Admin Daycare Centers Management
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
requireRole('admin');

$pageTitleHeader = 'Daycare Centers';
$pageTitle = 'Manage Daycare Centers';

$conn = getDBConnection();

// Handle Status Toggle or Delete Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['center_id'])) {
    if (verifyCSRFToken($_POST['csrf_token'])) {
        $centerId = (int)$_POST['center_id'];
        $action   = $_POST['action'];

        if ($action === 'toggle_status') {
            $newStatus = sanitizeInput($conn, $_POST['new_status']);
            $stmt = $conn->prepare("UPDATE daycare_centers SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $newStatus, $centerId);
            $stmt->execute();
            setFlashMessage('success', 'Center status updated to ' . $newStatus);
        } elseif ($action === 'delete') {
            $stmt = $conn->prepare("DELETE FROM daycare_centers WHERE id = ?");
            $stmt->bind_param("i", $centerId);
            $stmt->execute();
            setFlashMessage('success', 'Daycare center removed successfully.');
        }
    }
    redirect('/admin/daycare_centers.php');
}

// Search and Filters
$search       = sanitizeInput($conn, $_GET['search'] ?? '');
$typeFilter   = sanitizeInput($conn, $_GET['type'] ?? '');
$cityFilter   = sanitizeInput($conn, $_GET['city'] ?? '');
$is24x7Filter = $_GET['is_24x7'] ?? '';

$sql = "SELECT c.*, p.business_name, p.owner_name
        FROM daycare_centers c
        JOIN providers p ON c.provider_id = p.id
        WHERE 1=1";

if (!empty($search)) {
    $sql .= " AND (c.name LIKE '%$search%' OR c.area LIKE '%$search%' OR p.business_name LIKE '%$search%')";
}
if (!empty($typeFilter)) {
    $sql .= " AND c.type = '$typeFilter'";
}
if (!empty($cityFilter)) {
    $sql .= " AND c.city = '$cityFilter'";
}
if ($is24x7Filter !== '') {
    $sql .= " AND c.is_24x7 = " . (int)$is24x7Filter;
}

$sql .= " ORDER BY c.created_at DESC";
$centers = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

// Distinct Cities
$cities = [];
$cRes = $conn->query("SELECT DISTINCT city FROM daycare_centers WHERE city IS NOT NULL AND city != ''");
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
            <input type="text" name="search" class="filter-input" style="width: 100%;" placeholder="Search center name, area, provider..." value="<?= htmlspecialchars($search) ?>">
        </div>
        
        <div>
            <select name="type" class="filter-input">
                <option value="">All Types</option>
                <option value="daycare" <?= $typeFilter === 'daycare' ? 'selected' : '' ?>>Daycare</option>
                <option value="creche" <?= $typeFilter === 'creche' ? 'selected' : '' ?>>Creche</option>
                <option value="preschool" <?= $typeFilter === 'preschool' ? 'selected' : '' ?>>Preschool</option>
                <option value="babysitting" <?= $typeFilter === 'babysitting' ? 'selected' : '' ?>>Babysitting</option>
            </select>
        </div>
        
        <?php if(!empty($cities)): ?>
            <div>
                <select name="city" class="filter-input">
                    <option value="">All Cities</option>
                    <?php foreach($cities as $c): ?>
                        <option value="<?= htmlspecialchars($c) ?>" <?= $cityFilter === $c ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
        
        <div>
            <select name="is_24x7" class="filter-input">
                <option value="">All Hours</option>
                <option value="1" <?= $is24x7Filter === '1' ? 'selected' : '' ?>>24x7 Only</option>
                <option value="0" <?= $is24x7Filter === '0' ? 'selected' : '' ?>>Daytime Only</option>
            </select>
        </div>
        
        <button type="submit" class="btn btn-primary" style="padding: 9px 18px;">
            <i class="fas fa-filter"></i> Filter
        </button>
        
        <?php if(!empty($search) || !empty($typeFilter) || !empty($cityFilter) || $is24x7Filter !== ''): ?>
            <a href="daycare_centers.php" class="btn btn-secondary" style="padding: 9px 14px;">Reset</a>
        <?php endif; ?>
    </form>
</div>

<!-- Master Centers Table -->
<div class="table-container">
    <div class="table-header-bar">
        <h3 class="table-header-title">
            <i class="fas fa-school"></i> Daycare Centers Across Platform (<?= count($centers) ?>)
        </h3>
    </div>
    
    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Center Name</th>
                    <th>Provider Organization</th>
                    <th>Location</th>
                    <th>Capacity / Occ.</th>
                    <th>24x7</th>
                    <th>Daily Rate</th>
                    <th>Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($centers)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 36px; color: #9E9E9E;">
                            No daycare centers match criteria.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach($centers as $c): ?>
                        <tr>
                            <td>#<?= $c['id'] ?></td>
                            <td>
                                <strong><?= htmlspecialchars($c['name']) ?></strong>
                                <div style="font-size: 11px; color: #757575; text-transform: uppercase;">
                                    <?= htmlspecialchars($c['type']) ?> • Rating: <?= $c['rating'] ?> ★
                                </div>
                            </td>
                            <td>
                                <a href="provider_view.php?id=<?= $c['provider_id'] ?>" style="color: var(--admin-pink); text-decoration: none; font-weight: 500;">
                                    <?= htmlspecialchars($c['business_name']) ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars($c['area'] . ', ' . $c['city']) ?></td>
                            <td>
                                <strong><?= $c['current_occupancy'] ?></strong> / <?= $c['capacity'] ?>
                            </td>
                            <td>
                                <?= $c['is_24x7'] ? '<span class="badge badge-approved" style="font-size:11px;">24x7</span>' : '<span style="font-size:12px; color:#757575;">Daytime</span>' ?>
                            </td>
                            <td><strong><?= formatCurrency($c['pricing_daily']) ?></strong></td>
                            <td>
                                <span class="badge badge-<?= htmlspecialchars($c['status']) ?>">
                                    <?= htmlspecialchars($c['status']) ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <div class="btn-group-action">
                                    <a href="<?= SITE_URL ?>/center-details.php?id=<?= $c['id'] ?>" target="_blank" class="btn-icon btn-icon-view" title="Preview Public Page">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                    
                                    <!-- Toggle Active/Inactive -->
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Toggle status for this center?');">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="center_id" value="<?= $c['id'] ?>">
                                        <input type="hidden" name="new_status" value="<?= $c['status'] === 'active' ? 'inactive' : 'active' ?>">
                                        <button type="submit" class="btn-icon <?= $c['status'] === 'active' ? 'btn-icon-delete' : 'btn-icon-approve' ?>" title="<?= $c['status'] === 'active' ? 'Deactivate' : 'Activate' ?>">
                                            <i class="fas <?= $c['status'] === 'active' ? 'fa-ban' : 'fa-check' ?>"></i>
                                        </button>
                                    </form>
                                    
                                    <!-- Delete Center -->
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to permanently delete this center?');">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="center_id" value="<?= $c['id'] ?>">
                                        <button type="submit" class="btn-icon btn-icon-delete" title="Delete Center">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
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
