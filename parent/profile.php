<?php
/**
 * Parent Profile Page
 * Little Steps Childcare Platform
 * Profile Edit & Password Change
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
requireRole('parent');

$pageTitleHeader = 'My Profile';
$pageTitle = 'Profile';

$conn = getDBConnection();
$userId = $_SESSION['user_id'];

// Handle Profile Update or Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        setFlashMessage('error', "Invalid request token.");
    } else {
        $action = $_POST['action'] ?? 'update_profile';

        if ($action === 'update_profile') {
            $firstName = sanitizeInput($conn, $_POST['first_name']);
            $lastName  = sanitizeInput($conn, $_POST['last_name']);
            $phone     = sanitizeInput($conn, $_POST['phone']);
            $address   = sanitizeInput($conn, $_POST['address']);
            $city      = sanitizeInput($conn, $_POST['city']);
            $state     = sanitizeInput($conn, $_POST['state']);
            $pincode   = sanitizeInput($conn, $_POST['pincode']);
            
            $stmt = $conn->prepare("UPDATE users SET first_name=?, last_name=?, phone=?, address=?, city=?, state=?, pincode=? WHERE id=?");
            $stmt->bind_param("sssssssi", $firstName, $lastName, $phone, $address, $city, $state, $pincode, $userId);
            
            if ($stmt->execute()) {
                $_SESSION['user_name'] = $firstName . ' ' . $lastName;
                
                // Handle Profile Photo Upload
                if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                    $uploaded = uploadFile($_FILES['profile_image'], 'profiles');
                    if ($uploaded) {
                        $pStmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                        $pStmt->bind_param("si", $uploaded, $userId);
                        $pStmt->execute();
                    }
                }
                setFlashMessage('success', 'Profile updated successfully.');
            } else {
                setFlashMessage('error', 'Failed to update profile: ' . $conn->error);
            }
            redirect('/parent/profile.php');

        } elseif ($action === 'change_password') {
            $currentPass = $_POST['current_password'];
            $newPass     = $_POST['new_password'];
            $confirmPass = $_POST['confirm_password'];

            if (empty($currentPass) || empty($newPass) || empty($confirmPass)) {
                setFlashMessage('error', 'All password fields are required.');
            } elseif (strlen($newPass) < 8) {
                setFlashMessage('error', 'New password must be at least 8 characters.');
            } elseif ($newPass !== $confirmPass) {
                setFlashMessage('error', 'New passwords do not match.');
            } else {
                $chk = $conn->prepare("SELECT password FROM users WHERE id = ?");
                $chk->bind_param("i", $userId);
                $chk->execute();
                $stored = $chk->get_result()->fetch_assoc();

                if ($stored && password_verify($currentPass, $stored['password'])) {
                    $hashed = password_hash($newPass, PASSWORD_DEFAULT);
                    $upd = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $upd->bind_param("si", $hashed, $userId);
                    $upd->execute();
                    setFlashMessage('success', 'Password changed successfully.');
                } else {
                    setFlashMessage('error', 'Current password is incorrect.');
                }
            }
            redirect('/parent/profile.php');
        }
    }
}

// Fetch user details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$conn->close();

require_once __DIR__ . '/includes/header.php';
?>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px;">
    <!-- Profile Card & Avatar -->
    <div>
        <div class="detail-card" style="text-align: center;">
            <div style="position: relative; display: inline-block; margin-bottom: 16px;">
                <?php if (!empty($user['profile_image'])): ?>
                    <img src="<?= SITE_URL . '/' . htmlspecialchars($user['profile_image']) ?>" 
                         alt="Avatar" style="width: 110px; height: 110px; border-radius: 50%; object-fit: cover; border: 4px solid var(--parent-pastel-pink);">
                <?php else: ?>
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($user['first_name'] . ' ' . $user['last_name']) ?>&size=120&background=FCE4EC&color=E91E63" 
                         alt="Avatar" style="width: 110px; height: 110px; border-radius: 50%; border: 4px solid var(--parent-pastel-pink);">
                <?php endif; ?>
            </div>
            
            <h3 style="margin: 0 0 4px 0; color: #212121;"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h3>
            <div style="font-size: 14px; color: #757575; margin-bottom: 12px;"><?= htmlspecialchars($user['email']) ?></div>
            
            <div style="margin-bottom: 20px;">
                <span class="badge badge-pink" style="font-size: 12px;">Verified Parent</span>
            </div>
            
            <div style="text-align: left; background: #FFF0F5; padding: 14px; border-radius: 10px; font-size: 13px; line-height: 1.8;">
                <div><strong>Phone:</strong> <?= htmlspecialchars($user['phone'] ?? 'Not set') ?></div>
                <div><strong>City:</strong> <?= htmlspecialchars($user['city'] ?? 'Not set') ?></div>
                <div><strong>Member Since:</strong> <?= formatDate($user['created_at']) ?></div>
            </div>
        </div>
        
        <!-- Change Password Card -->
        <div class="detail-card">
            <h3 style="margin: 0 0 16px 0; color: var(--parent-dark-pink); font-size: 17px;">
                <i class="fas fa-key" style="margin-right: 6px;"></i> Change Password
            </h3>
            
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="change_password">
                
                <div class="form-group" style="margin-bottom: 12px;">
                    <label class="form-label" style="font-size: 13px;">Current Password</label>
                    <input type="password" name="current_password" class="form-control" required>
                </div>
                <div class="form-group" style="margin-bottom: 12px;">
                    <label class="form-label" style="font-size: 13px;">New Password (min 8 chars)</label>
                    <input type="password" name="new_password" class="form-control" minlength="8" required>
                </div>
                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label" style="font-size: 13px;">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" minlength="8" required>
                </div>
                
                <button type="submit" class="btn btn-secondary" style="width: 100%; justify-content: center;">
                    Update Password
                </button>
            </form>
        </div>
    </div>
    
    <!-- Edit Personal Information -->
    <div class="detail-card">
        <h3 style="margin: 0 0 20px 0; color: var(--parent-dark-pink); font-size: 18px;">
            <i class="fas fa-user-edit" style="margin-right: 8px;"></i> Personal Information
        </h3>
        
        <form method="POST" enctype="multipart/form-data">
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
                    <label class="form-label">Email (Account ID)</label>
                    <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" readonly style="background: #F5F5F5;">
                </div>
                <div class="form-group">
                    <label class="form-label">Phone Number *</label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" required>
                </div>
            </div>
            
            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label">Residential Address</label>
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
            
            <div class="form-group" style="margin-bottom: 24px;">
                <label class="form-label">Upload Profile Photo</label>
                <input type="file" name="profile_image" class="form-control" accept="image/*">
            </div>
            
            <div style="display: flex; justify-content: flex-end;">
                <button type="submit" class="btn btn-primary" style="padding: 11px 28px; font-size: 15px;">
                    <i class="fas fa-save" style="margin-right: 6px;"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
