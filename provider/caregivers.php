<?php
/**
 * Provider Caregivers & Staff Management
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
requireRole('provider');

$pageTitleHeader = 'Staff & Caregivers';
$pageTitle = 'Caregivers';

$conn = getDBConnection();
$providerId = $_SESSION['user_id'];

// Handle Actions (Delete / Toggle Status)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['caregiver_id'])) {
    if (verifyCSRFToken($_POST['csrf_token'])) {
        $cgId   = (int)$_POST['caregiver_id'];
        $action = $_POST['action'];

        if ($action === 'delete') {
            $stmt = $conn->prepare("DELETE FROM caregivers WHERE id = ? AND provider_id = ?");
            $stmt->bind_param("ii", $cgId, $providerId);
            $stmt->execute();
            setFlashMessage('success', 'Caregiver record removed.');
        } elseif ($action === 'toggle_status') {
            $newStatus = sanitizeInput($conn, $_POST['new_status']);
            $stmt = $conn->prepare("UPDATE caregivers SET status = ? WHERE id = ? AND provider_id = ?");
            $stmt->bind_param("sii", $newStatus, $cgId, $providerId);
            $stmt->execute();
            setFlashMessage('success', 'Caregiver status updated to ' . $newStatus);
        }
    }
    redirect('/provider/caregivers.php');
}

// Search and Filter
$search       = sanitizeInput($conn, $_GET['search'] ?? '');
$statusFilter = sanitizeInput($conn, $_GET['status'] ?? '');
$verifyFilter = $_GET['verified'] ?? '';

$sql = "SELECT * FROM caregivers WHERE provider_id = $providerId";
if (!empty($search)) {
    $sql .= " AND (first_name LIKE '%$search%' OR last_name LIKE '%$search%' OR phone LIKE '%$search%' OR qualification LIKE '%$search%')";
}
if (!empty($statusFilter)) {
    $sql .= " AND status = '$statusFilter'";
}
if ($verifyFilter !== '') {
    $sql .= " AND background_verified = " . (int)$verifyFilter;
}
$sql .= " ORDER BY created_at DESC";
$caregivers = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

$conn->close();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Header Action Toolbar -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
    <div style="display: flex; gap: 10px; align-items: center;">
        <a href="caregivers.php" class="btn <?= empty($statusFilter) ? 'btn-primary' : 'btn-secondary' ?>" style="padding: 8px 16px;">All Staff</a>
        <a href="caregivers.php?status=active" class="btn <?= $statusFilter === 'active' ? 'btn-primary' : 'btn-secondary' ?>" style="padding: 8px 16px;">Active Only</a>
    </div>
    
    <a href="caregiver_add.php" class="btn btn-primary" style="padding: 10px 22px; font-size: 14px;">
        <i class="fas fa-user-plus" style="margin-right: 6px;"></i> Add New Caregiver
    </a>
</div>

<!-- Filter Card -->
<div class="filter-card">
    <form method="GET" class="filter-form">
        <div style="flex: 2; min-width: 200px;">
            <input type="text" name="search" class="filter-input" style="width: 100%;" placeholder="Search by name, phone, or degree..." value="<?= htmlspecialchars($search) ?>">
        </div>
        
        <div>
            <select name="verified" class="filter-input">
                <option value="">All Verification</option>
                <option value="1" <?= $verifyFilter === '1' ? 'selected' : '' ?>>✓ Background Verified</option>
                <option value="0" <?= $verifyFilter === '0' ? 'selected' : '' ?>>Pending Verification</option>
            </select>
        </div>
        
        <button type="submit" class="btn btn-primary" style="padding: 9px 18px;">
            <i class="fas fa-filter"></i> Filter
        </button>
        
        <?php if(!empty($search) || $verifyFilter !== '' || !empty($statusFilter)): ?>
            <a href="caregivers.php" class="btn btn-secondary" style="padding: 9px 14px;">Reset</a>
        <?php endif; ?>
    </form>
</div>

<!-- Staff Table -->
<div class="provider-table-card">
    <div class="provider-table-header">
        <h3><i class="fas fa-user-nurse" style="margin-right: 6px;"></i> Daycare Staff & Babysitters (<?= count($caregivers) ?>)</h3>
    </div>
    
    <div style="overflow-x: auto;">
        <table class="provider-table">
            <thead>
                <tr>
                    <th>Caregiver</th>
                    <th>Contact</th>
                    <th>Qualification</th>
                    <th>Experience</th>
                    <th>Specialization</th>
                    <th>Verification</th>
                    <th>Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($caregivers)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 36px; color: #9E9E9E;">
                            No caregivers added yet. Click "Add New Caregiver" to list staff.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach($caregivers as $cg): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($cg['first_name'] . ' ' . $cg['last_name']) ?>&size=64&background=FFF0F5&color=E91E63" 
                                         style="width: 42px; height: 42px; border-radius: 50%; border: 2px solid var(--provider-pastel-pink);" alt="Staff">
                                    <div>
                                        <strong><?= htmlspecialchars($cg['first_name'] . ' ' . $cg['last_name']) ?></strong>
                                        <div style="font-size: 11px; color: #757575;"><?= htmlspecialchars($cg['gender'] ?? 'Caregiver') ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div><i class="fas fa-phone" style="color: var(--provider-pink); font-size: 11px;"></i> <?= htmlspecialchars($cg['phone'] ?? 'N/A') ?></div>
                                <div style="font-size: 12px; color: #757575;"><?= htmlspecialchars($cg['email'] ?? '') ?></div>
                            </td>
                            <td><strong><?= htmlspecialchars($cg['qualification']) ?></strong></td>
                            <td><?= $cg['experience_years'] ?> Years</td>
                            <td>
                                <span class="badge badge-pink" style="font-size: 11px;">
                                    <?= htmlspecialchars($cg['specialization'] ?? 'Childcare') ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-<?= $cg['background_verified'] ? 'approved' : 'pending' ?>">
                                    <?= $cg['background_verified'] ? '✓ Verified' : 'Pending' ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-<?= htmlspecialchars($cg['status']) ?>">
                                    <?= htmlspecialchars($cg['status']) ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <div class="btn-group-action">
                                    <a href="caregiver_add.php?id=<?= $cg['id'] ?>" class="btn-icon btn-icon-edit" title="Edit Details">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Remove caregiver record?');">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="caregiver_id" value="<?= $cg['id'] ?>">
                                        <button type="submit" class="btn-icon btn-icon-delete" title="Delete Caregiver">
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
