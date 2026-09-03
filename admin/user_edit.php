<?php
/**
 * Admin User Edit & Detail View
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
requireRole('admin');

$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($userId <= 0) {
    setFlashMessage('error', 'Invalid User ID.');
    redirect('/admin/users.php');
}

$conn = getDBConnection();

// Fetch Parent details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role = 'parent'");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    $conn->close();
    setFlashMessage('error', 'Parent user not found.');
    redirect('/admin/users.php');
}

// Handle Form Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verifyCSRFToken($_POST['csrf_token'])) {
        $action = $_POST['action'] ?? 'update_profile';
        
        if ($action === 'update_profile') {
            $firstName = sanitizeInput($conn, $_POST['first_name']);
            $lastName  = sanitizeInput($conn, $_POST['last_name']);
            $phone     = sanitizeInput($conn, $_POST['phone']);
            $address   = sanitizeInput($conn, $_POST['address']);
            $city      = sanitizeInput($conn, $_POST['city']);
            $state     = sanitizeInput($conn, $_POST['state']);
            $pincode   = sanitizeInput($conn, $_POST['pincode']);
            $isActive  = isset($_POST['is_active']) ? 1 : 0;
            
            $upd = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, phone = ?, address = ?, city = ?, state = ?, pincode = ?, is_active = ? WHERE id = ?");
            $upd->bind_param("sssssssii", $firstName, $lastName, $phone, $address, $city, $state, $pincode, $isActive, $userId);
            
            if ($upd->execute()) {
                setFlashMessage('success', 'User profile updated successfully.');
            } else {
                setFlashMessage('error', 'Failed to update user profile: ' . $conn->error);
            }
            redirect('/admin/user_edit.php?id=' . $userId);
            
        } elseif ($action === 'reset_password') {
            $newPassword = 'Password@123';
            $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
            $upd = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $upd->bind_param("si", $hashed, $userId);
            $upd->execute();
            setFlashMessage('success', 'Password reset successfully to temporary password: <strong>' . $newPassword . '</strong>');
            redirect('/admin/user_edit.php?id=' . $userId);
        }
    }
}

// Fetch Parent's Bookings
$bookingsStmt = $conn->prepare("
    SELECT b.booking_code, b.child_name, b.final_amount, b.status, b.created_at,
           c.name as center_name
    FROM bookings b
    JOIN daycare_centers c ON b.center_id = c.id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
");
$bookingsStmt->bind_param("i", $userId);
$bookingsStmt->execute();
$bookings = $bookingsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch Parent's Subscriptions
$subsStmt = $conn->prepare("
    SELECT s.subscription_code, s.plan_name, s.monthly_amount, s.status, s.start_date, s.end_date,
           c.name as center_name
    FROM subscriptions s
    JOIN daycare_centers c ON s.center_id = c.id
    WHERE s.user_id = ?
    ORDER BY s.created_at DESC
");
$subsStmt->bind_param("i", $userId);
$subsStmt->execute();
$subscriptions = $subsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$conn->close();

$pageTitleHeader = 'Edit Parent User';
$pageTitle = 'Edit User #' . $userId;
require_once __DIR__ . '/includes/header.php';
?>

<div style="display: flex; gap: 24px; flex-wrap: wrap;">
    <!-- Left Column: Summary Card & Password Reset -->
    <div style="flex: 1; min-width: 300px;">
        <div class="detail-card" style="text-align: center;">
            <img src="https://ui-avatars.com/api/?name=<?= urlencode($user['first_name'] . ' ' . $user['last_name']) ?>&size=120&background=FCE4EC&color=E91E63" 
                 alt="<?= htmlspecialchars($user['first_name']) ?>" 
                 style="width: 100px; height: 100px; border-radius: 50%; border: 4px solid var(--admin-pastel-pink); margin-bottom: 14px;">
            
            <h3 style="margin: 0 0 4px 0; color: #212121;"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h3>
            <div style="color: #757575; font-size: 14px; margin-bottom: 12px;"><?= htmlspecialchars($user['email']) ?></div>
            
            <div style="margin-bottom: 18px;">
                <span class="badge badge-<?= $user['is_active'] ? 'approved' : 'rejected' ?>">
                    <?= $user['is_active'] ? 'Active Account' : 'Deactivated' ?>
                </span>
            </div>
            
            <div style="text-align: left; background: #FFF0F5; padding: 14px; border-radius: 8px; font-size: 13px; color: #424242; margin-bottom: 20px;">
                <div style="margin-bottom: 6px;"><strong>Member Since:</strong> <?= formatDate($user['created_at']) ?></div>
                <div style="margin-bottom: 6px;"><strong>Total Bookings:</strong> <?= count($bookings) ?></div>
                <div><strong>Active Subscriptions:</strong> <?= count($subscriptions) ?></div>
            </div>
            
            <!-- Password Reset Form -->
            <form method="POST" onsubmit="return confirm('Reset password for this user to temporary password (Password@123)?');">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="reset_password">
                <button type="submit" class="btn btn-warning" style="width: 100%; justify-content: center; gap: 8px;">
                    <i class="fas fa-key"></i> Reset Password
                </button>
            </form>
        </div>
    </div>
    
    <!-- Right Column: Edit Profile Form -->
    <div style="flex: 2; min-width: 400px;">
        <div class="detail-card">
            <h3 style="margin: 0 0 20px 0; font-size: 18px; color: var(--admin-dark-pink);">
                <i class="fas fa-user-edit" style="margin-right: 8px;"></i> Edit Parent Information
            </h3>
            
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update_profile">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <div class="form-group">
                        <label class="form-label">First Name *</label>
                        <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($user['first_name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Last Name *</label>
                        <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($user['last_name']) ?>" required>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <div class="form-group">
                        <label class="form-label">Email (Read Only)</label>
                        <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" readonly style="background: #F5F5F5;">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                    </div>
                </div>
                
                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label">Street Address</label>
                    <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                    <div class="form-group">
                        <label class="form-label">City</label>
                        <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($user['city'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">State</label>
                        <input type="text" name="state" class="form-control" value="<?= htmlspecialchars($user['state'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Pincode</label>
                        <input type="text" name="pincode" class="form-control" value="<?= htmlspecialchars($user['pincode'] ?? '') ?>">
                    </div>
                </div>
                
                <div style="margin-bottom: 24px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="is_active" value="1" <?= $user['is_active'] ? 'checked' : '' ?> style="accent-color: var(--admin-pink); width: 18px; height: 18px;">
                        <span style="font-weight: 500; color: #212121;">Account Active & Allowed to Book</span>
                    </label>
                </div>
                
                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <a href="users.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
        
        <!-- Bookings History -->
        <div class="table-container">
            <div class="table-header-bar">
                <h3 class="table-header-title" style="font-size: 16px;">
                    <i class="fas fa-calendar-alt"></i> Parent's Booking History
                </h3>
            </div>
            <div style="overflow-x: auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Child</th>
                            <th>Center</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($bookings)): ?>
                            <tr><td colspan="6" style="text-align: center; color: #9E9E9E; padding: 20px;">No bookings made yet.</td></tr>
                        <?php else: ?>
                            <?php foreach($bookings as $b): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($b['booking_code']) ?></strong></td>
                                    <td><?= htmlspecialchars($b['child_name']) ?></td>
                                    <td><?= htmlspecialchars($b['center_name']) ?></td>
                                    <td><?= formatCurrency($b['final_amount']) ?></td>
                                    <td><span class="badge badge-<?= htmlspecialchars($b['status']) ?>"><?= htmlspecialchars($b['status']) ?></span></td>
                                    <td><?= formatDate($b['created_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
