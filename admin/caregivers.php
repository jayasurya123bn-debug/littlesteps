<?php
/**
 * Admin Caregivers Master Management
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
requireRole('admin');

$pageTitleHeader = 'Caregivers & Staff';
$pageTitle = 'Manage Caregivers';

$conn = getDBConnection();

// Handle Background Verification or Status Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['caregiver_id'])) {
    if (verifyCSRFToken($_POST['csrf_token'])) {
        $cgId   = (int)$_POST['caregiver_id'];
        $action = $_POST['action'];

        if ($action === 'toggle_verify') {
            $newVerify = (int)$_POST['new_verify'];
            $vDate = $newVerify ? date('Y-m-d') : null;
            $stmt = $conn->prepare("UPDATE caregivers SET background_verified = ?, verification_date = ? WHERE id = ?");
            $stmt->bind_param("isi", $newVerify, $vDate, $cgId);
            $stmt->execute();
            setFlashMessage('success', 'Caregiver verification status updated.');
            
        } elseif ($action === 'delete') {
            $stmt = $conn->prepare("DELETE FROM caregivers WHERE id = ?");
            $stmt->bind_param("i", $cgId);
            $stmt->execute();
            setFlashMessage('success', 'Caregiver record deleted.');
        }
    }
    redirect('/admin/caregivers.php');
}

// Search & Filters
$search = sanitizeInput($conn, $_GET['search'] ?? '');
$verifyFilter = $_GET['verified'] ?? 'all';
$providerFilter = isset($_GET['provider_id']) ? (int)$_GET['provider_id'] : 0;

$sql = "SELECT cg.*, p.business_name, p.city as provider_city
        FROM caregivers cg
        JOIN providers p ON cg.provider_id = p.id
        WHERE 1=1";

if (!empty($search)) {
    $sql .= " AND (cg.first_name LIKE '%$search%' OR cg.last_name LIKE '%$search%' OR cg.qualification LIKE '%$search%' OR p.business_name LIKE '%$search%')";
}

if ($verifyFilter === '1') {
    $sql .= " AND cg.background_verified = 1";
} elseif ($verifyFilter === '0') {
    $sql .= " AND cg.background_verified = 0";
}

if ($providerFilter > 0) {
    $sql .= " AND cg.provider_id = $providerFilter";
}

$sql .= " ORDER BY cg.created_at DESC";
$caregivers = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

// Providers list for filter
$providersList = $conn->query("SELECT id, business_name FROM providers ORDER BY business_name ASC")->fetch_all(MYSQLI_ASSOC);

$conn->close();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Filter Card -->
<div class="filter-card">
    <form method="GET" class="filter-form">
        <div style="flex: 2; min-width: 200px;">
            <input type="text" name="search" class="filter-input" style="width: 100%;" placeholder="Search caregiver name, qualification, provider..." value="<?= htmlspecialchars($search) ?>">
        </div>
        
        <div>
            <select name="verified" class="filter-input">
                <option value="all" <?= $verifyFilter === 'all' ? 'selected' : '' ?>>All Verification</option>
                <option value="1" <?= $verifyFilter === '1' ? 'selected' : '' ?>>✓ Background Verified</option>
                <option value="0" <?= $verifyFilter === '0' ? 'selected' : '' ?>>Pending Verification</option>
            </select>
        </div>
        
        <?php if(!empty($providersList)): ?>
            <div>
                <select name="provider_id" class="filter-input">
                    <option value="0">All Providers</option>
                    <?php foreach($providersList as $prov): ?>
                        <option value="<?= $prov['id'] ?>" <?= $providerFilter === (int)$prov['id'] ? 'selected' : '' ?>><?= htmlspecialchars($prov['business_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
        
        <button type="submit" class="btn btn-primary" style="padding: 9px 18px;">
            <i class="fas fa-filter"></i> Filter
        </button>
        
        <?php if(!empty($search) || $verifyFilter !== 'all' || $providerFilter > 0): ?>
            <a href="caregivers.php" class="btn btn-secondary" style="padding: 9px 14px;">Reset</a>
        <?php endif; ?>
    </form>
</div>

<!-- Caregivers Master Table -->
<div class="table-container">
    <div class="table-header-bar">
        <h3 class="table-header-title">
            <i class="fas fa-user-nurse"></i> Certified Caregivers & Staff (<?= count($caregivers) ?>)
        </h3>
    </div>
    
    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Caregiver</th>
                    <th>Provider / Employer</th>
                    <th>Contact</th>
                    <th>Qualification & Experience</th>
                    <th>Specialization</th>
                    <th>Background Verified</th>
                    <th>Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($caregivers)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 36px; color: #9E9E9E;">
                            No caregivers found matching criteria.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach($caregivers as $cg): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($cg['first_name'] . ' ' . $cg['last_name']) ?>&size=64&background=FFF0F5&color=E91E63" 
                                         style="width: 40px; height: 40px; border-radius: 50%; border: 2px solid var(--admin-pastel-pink);" alt="Staff">
                                    <div>
                                        <strong><?= htmlspecialchars($cg['first_name'] . ' ' . $cg['last_name']) ?></strong>
                                        <div style="font-size: 11px; color: #757575;"><?= htmlspecialchars($cg['gender']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <a href="provider_view.php?id=<?= $cg['provider_id'] ?>" style="color: var(--admin-pink); text-decoration: none; font-weight: 500;">
                                    <?= htmlspecialchars($cg['business_name']) ?>
                                </a>
                                <div style="font-size: 11px; color: #757575;"><?= htmlspecialchars($cg['provider_city']) ?></div>
                            </td>
                            <td>
                                <div><i class="fas fa-phone" style="color: var(--admin-pink); font-size: 11px;"></i> <?= htmlspecialchars($cg['phone'] ?? 'N/A') ?></div>
                                <div style="font-size: 12px; color: #757575;"><?= htmlspecialchars($cg['email'] ?? '') ?></div>
                            </td>
                            <td>
                                <div><strong><?= htmlspecialchars($cg['qualification']) ?></strong></div>
                                <div style="font-size: 12px; color: #757575;"><?= $cg['experience_years'] ?> Years Experience</div>
                            </td>
                            <td>
                                <span class="badge badge-pink" style="font-size: 11px;">
                                    <?= htmlspecialchars($cg['specialization'] ?? 'Childcare') ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-<?= $cg['background_verified'] ? 'approved' : 'pending' ?>">
                                    <?= $cg['background_verified'] ? '✓ Verified' : 'Pending Check' ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-<?= htmlspecialchars($cg['status']) ?>">
                                    <?= htmlspecialchars($cg['status']) ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <div class="btn-group-action">
                                    <!-- Toggle Verification -->
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Toggle background verification status for <?= htmlspecialchars($cg['first_name']) ?>?');">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="toggle_verify">
                                        <input type="hidden" name="caregiver_id" value="<?= $cg['id'] ?>">
                                        <input type="hidden" name="new_verify" value="<?= $cg['background_verified'] ? 0 : 1 ?>">
                                        <button type="submit" class="btn-icon <?= $cg['background_verified'] ? 'btn-icon-delete' : 'btn-icon-approve' ?>" title="<?= $cg['background_verified'] ? 'Unmark Verified' : 'Mark Verified' ?>">
                                            <i class="fas <?= $cg['background_verified'] ? 'fa-user-times' : 'fa-user-check' ?>"></i>
                                        </button>
                                    </form>
                                    
                                    <!-- Delete Record -->
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Permanently remove this caregiver record?');">
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
