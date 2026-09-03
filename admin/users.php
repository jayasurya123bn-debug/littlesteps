<?php
/**
 * Admin Parent Users Management
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
requireRole('admin');

$pageTitleHeader = 'Parent Users';
$pageTitle = 'Manage Parents';

$conn = getDBConnection();

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="littlesteps_parents_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'First Name', 'Last Name', 'Email', 'Phone', 'City', 'Status', 'Registered At']);
    
    $exportQuery = $conn->query("SELECT id, first_name, last_name, email, phone, city, is_active, created_at FROM users WHERE role = 'parent' ORDER BY id ASC");
    while($row = $exportQuery->fetch_assoc()) {
        fputcsv($output, [
            $row['id'],
            $row['first_name'],
            $row['last_name'],
            $row['email'],
            $row['phone'] ?? 'N/A',
            $row['city'] ?? 'N/A',
            $row['is_active'] ? 'Active' : 'Inactive',
            $row['created_at']
        ]);
    }
    fclose($output);
    exit();
}

// Handle Status Toggle or Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['user_id'])) {
    if (verifyCSRFToken($_POST['csrf_token'])) {
        $userId = (int)$_POST['user_id'];
        $action = $_POST['action'];

        if ($action === 'toggle_status') {
            $newStatus = (int)$_POST['new_status'];
            $stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE id = ? AND role = 'parent'");
            $stmt->bind_param("ii", $newStatus, $userId);
            $stmt->execute();
            setFlashMessage('success', 'Parent account status updated successfully.');
        } elseif ($action === 'delete') {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'parent'");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            setFlashMessage('success', 'Parent account deleted successfully.');
        }
    }
    redirect('/admin/users.php');
}

// Search and Filter logic
$search = sanitizeInput($conn, $_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? 'all';
$cityFilter = sanitizeInput($conn, $_GET['city'] ?? '');

$sql = "SELECT u.id, u.first_name, u.last_name, u.email, u.phone, u.city, u.is_active, u.created_at,
               COUNT(b.id) as bookings_count
        FROM users u
        LEFT JOIN bookings b ON u.id = b.user_id
        WHERE u.role = 'parent'";

if (!empty($search)) {
    $sql .= " AND (u.first_name LIKE '%$search%' OR u.last_name LIKE '%$search%' OR u.email LIKE '%$search%' OR u.phone LIKE '%$search%')";
}

if ($statusFilter === 'active') {
    $sql .= " AND u.is_active = 1";
} elseif ($statusFilter === 'inactive') {
    $sql .= " AND u.is_active = 0";
}

if (!empty($cityFilter)) {
    $sql .= " AND u.city = '$cityFilter'";
}

$sql .= " GROUP BY u.id ORDER BY u.created_at DESC";
$users = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

// Get distinct cities for filter
$cities = [];
$cRes = $conn->query("SELECT DISTINCT city FROM users WHERE role = 'parent' AND city IS NOT NULL AND city != ''");
if ($cRes) {
    while($cRow = $cRes->fetch_assoc()) $cities[] = $cRow['city'];
}

$conn->close();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Filter Card -->
<div class="filter-card">
    <form method="GET" class="filter-form">
        <div style="flex: 2; min-width: 200px;">
            <input type="text" name="search" class="filter-input" style="width: 100%;" placeholder="Search by name, email or phone..." value="<?= htmlspecialchars($search) ?>">
        </div>
        
        <div>
            <select name="status" class="filter-input">
                <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All Statuses</option>
                <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
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
            <a href="users.php" class="btn btn-secondary" style="padding: 9px 14px;">Reset</a>
        <?php endif; ?>
        
        <a href="users.php?export=csv" class="btn btn-secondary" style="margin-left: auto; padding: 9px 16px;">
            <i class="fas fa-file-csv"></i> Export CSV
        </a>
    </form>
</div>

<!-- Parents Table -->
<div class="table-container">
    <div class="table-header-bar">
        <h3 class="table-header-title">
            <i class="fas fa-users"></i> Registered Parents (<?= count($users) ?>)
        </h3>
    </div>
    
    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Parent Name</th>
                    <th>Email & Contact</th>
                    <th>Location</th>
                    <th>Bookings</th>
                    <th>Registered</th>
                    <th>Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 36px; color: #9E9E9E;">
                            <i class="fas fa-user-slash" style="font-size: 32px; color: var(--admin-pastel-pink); margin-bottom: 8px; display: block;"></i>
                            No parent users found matching criteria.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td>#<?= $u['id'] ?></td>
                            <td>
                                <strong><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?></strong>
                            </td>
                            <td>
                                <div><i class="fas fa-envelope" style="color: var(--admin-pink); font-size: 11px; margin-right: 4px;"></i> <?= htmlspecialchars($u['email']) ?></div>
                                <?php if($u['phone']): ?>
                                    <div style="font-size: 12px; color: #757575;"><i class="fas fa-phone" style="font-size: 10px; margin-right: 4px;"></i> <?= htmlspecialchars($u['phone']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($u['city'] ?? 'Not set') ?></td>
                            <td>
                                <span class="badge badge-pink" style="font-weight: 600;">
                                    <?= $u['bookings_count'] ?> Bookings
                                </span>
                            </td>
                            <td><?= formatDate($u['created_at']) ?></td>
                            <td>
                                <span class="badge badge-<?= $u['is_active'] ? 'approved' : 'rejected' ?>">
                                    <?= $u['is_active'] ? 'Active' : 'Deactivated' ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <div class="btn-group-action">
                                    <a href="user_edit.php?id=<?= $u['id'] ?>" class="btn-icon btn-icon-edit" title="Edit & View Details">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Change status for this parent account?');">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <input type="hidden" name="new_status" value="<?= $u['is_active'] ? 0 : 1 ?>">
                                        <button type="submit" class="btn-icon <?= $u['is_active'] ? 'btn-icon-delete' : 'btn-icon-approve' ?>" title="<?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>">
                                            <i class="fas <?= $u['is_active'] ? 'fa-ban' : 'fa-check' ?>"></i>
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
