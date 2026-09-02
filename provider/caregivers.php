<?php
/**
 * Caregivers / Staff Management
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        setFlashMessage('error', "Invalid request token.");
    } else {
        if (isset($_POST['action']) && $_POST['action'] === 'add') {
            $firstName = sanitizeInput($conn, $_POST['first_name']);
            $lastName = sanitizeInput($conn, $_POST['last_name']);
            $qualification = sanitizeInput($conn, $_POST['qualification']);
            
            $insStmt = $conn->prepare("INSERT INTO caregivers (provider_id, first_name, last_name, qualification, status, background_verified) VALUES (?, ?, ?, ?, 'active', 0)");
            $insStmt->bind_param("isss", $providerId, $firstName, $lastName, $qualification);
            
            if ($insStmt->execute()) {
                setFlashMessage('success', 'Caregiver added successfully.');
            } else {
                setFlashMessage('error', 'Failed to add caregiver.');
            }
        } elseif (isset($_POST['action']) && $_POST['action'] === 'delete') {
            $cgId = (int)$_POST['caregiver_id'];
            $delStmt = $conn->prepare("UPDATE caregivers SET status = 'inactive' WHERE id = ? AND provider_id = ?");
            $delStmt->bind_param("ii", $cgId, $providerId);
            $delStmt->execute();
            setFlashMessage('success', 'Caregiver removed.');
        }
    }
    redirect('/provider/caregivers.php');
}

// Get caregivers
$stmt = $conn->prepare("SELECT * FROM caregivers WHERE provider_id = ? AND status = 'active'");
$stmt->bind_param("i", $providerId);
$stmt->execute();
$caregivers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$conn->close();

require_once __DIR__ . '/includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-lg);">
    <h2 style="margin: 0; color: var(--dark-pink);">Your Staff</h2>
    <button class="btn btn-primary" data-toggle="modal" data-target="#addCaregiverModal"><i class="fas fa-plus"></i> Add Caregiver</button>
</div>

<div class="caregiver-grid">
    <?php if (count($caregivers) > 0): ?>
        <?php foreach ($caregivers as $cg): ?>
            <div class="caregiver-card">
                <div class="caregiver-photo">
                    <i class="fas fa-user" style="font-size: 64px; color: var(--main-pink); opacity: 0.5;"></i>
                    <?php if ($cg['background_verified']): ?>
                        <div style="position: absolute; bottom: 8px; right: 8px; background: var(--success); color: white; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; border: 2px solid white;" title="Background Verified">
                            <i class="fas fa-check"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div style="padding: var(--space-md);">
                    <h4 style="margin-bottom: 4px; font-size: 16px;"><?= htmlspecialchars($cg['first_name'] . ' ' . $cg['last_name']) ?></h4>
                    <p style="color: var(--medium-gray); font-size: 13px; margin-bottom: var(--space-md);">
                        <i class="fas fa-graduation-cap"></i> <?= htmlspecialchars($cg['qualification'] ?: 'Not specified') ?>
                    </p>
                    
                    <div style="display: flex; gap: var(--space-sm);">
                        <button class="btn btn-sm btn-outline" style="flex: 1;" onclick="alert('Edit functionality would open a modal here')">Edit</button>
                        <form method="POST" action="caregivers.php" style="flex: 1;" onsubmit="return confirm('Remove this caregiver?');">
                            <?php csrfField(); ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="caregiver_id" value="<?= $cg['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline" style="width: 100%; color: var(--danger); border-color: var(--danger);">Remove</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div style="grid-column: 1 / -1;" class="card text-center">
            <div style="padding: var(--space-2xl) 0;">
                <div style="font-size: 48px; color: var(--light-pink); margin-bottom: var(--space-md);">
                    <i class="fas fa-users-slash"></i>
                </div>
                <h3>No Caregivers Added</h3>
                <p style="color: var(--medium-gray); margin-bottom: var(--space-md);">Add your staff to build trust with parents.</p>
                <button class="btn btn-primary" data-toggle="modal" data-target="#addCaregiverModal">Add First Caregiver</button>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Add Caregiver Modal -->
<div class="modal-overlay" id="addCaregiverModal">
    <div class="modal-content">
        <div class="modal-header">
            <span>Add New Caregiver</span>
            <button class="modal-close" data-dismiss="modal"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" action="caregivers.php" class="needs-validation">
            <?php csrfField(); ?>
            <input type="hidden" name="action" value="add">
            
            <div class="modal-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-md);">
                    <div class="form-group">
                        <label class="form-label">First Name</label>
                        <input type="text" name="first_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Last Name</label>
                        <input type="text" name="last_name" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Qualifications / Certifications</label>
                    <input type="text" name="qualification" class="form-control" placeholder="e.g. Early Childhood Education, CPR Certified" required>
                </div>
                
                <div class="alert alert-info" style="font-size: 13px; margin-top: var(--space-md);">
                    <i class="fas fa-info-circle"></i> Caregivers must pass a background check to receive the verified badge.
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Caregiver</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
