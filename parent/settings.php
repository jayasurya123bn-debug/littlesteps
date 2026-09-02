<?php
/**
 * Parent Settings Page
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
requireRole('parent');

$pageTitleHeader = 'Account Settings';
$pageTitle = 'Settings';

$conn = getDBConnection();
$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        setFlashMessage('error', "Invalid request token.");
    } else {
        if (isset($_POST['action'])) {
            if ($_POST['action'] === 'password') {
                $currentPassword = $_POST['current_password'];
                $newPassword = $_POST['new_password'];
                $confirmPassword = $_POST['confirm_password'];
                
                if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                    setFlashMessage('error', 'All password fields are required.');
                } elseif (strlen($newPassword) < 8) {
                    setFlashMessage('error', 'New password must be at least 8 characters long.');
                } elseif ($newPassword !== $confirmPassword) {
                    setFlashMessage('error', 'New passwords do not match.');
                } else {
                    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
                    $stmt->bind_param("i", $userId);
                    $stmt->execute();
                    $user = $stmt->get_result()->fetch_assoc();
                    
                    if (password_verify($currentPassword, $user['password'])) {
                        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                        $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                        $updateStmt->bind_param("si", $hashedPassword, $userId);
                        
                        if ($updateStmt->execute()) {
                            setFlashMessage('success', 'Password updated successfully.');
                        } else {
                            setFlashMessage('error', 'Failed to update password.');
                        }
                    } else {
                        setFlashMessage('error', 'Incorrect current password.');
                    }
                }
            } elseif ($_POST['action'] === 'notifications') {
                // In a real app, update notification preferences in DB
                setFlashMessage('success', 'Notification preferences saved.');
            }
        }
    }
    redirect('/parent/settings.php');
}
$conn->close();

require_once __DIR__ . '/includes/header.php';
?>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: var(--space-xl);">
    
    <!-- Settings Menu -->
    <div>
        <div class="card" style="padding: 0; overflow: hidden;">
            <a href="#security" class="nav-item" style="color: var(--dark-gray); padding: var(--space-md); border-bottom: 1px solid var(--light-pink); border-left: 4px solid var(--main-pink); background: var(--baby-pink); display: block; text-decoration: none; font-weight: 600;">
                <i class="fas fa-lock" style="width: 24px;"></i> Security & Password
            </a>
            <a href="#notifications" class="nav-item" style="color: var(--dark-gray); padding: var(--space-md); border-bottom: 1px solid var(--light-pink); display: block; text-decoration: none;">
                <i class="fas fa-bell" style="width: 24px;"></i> Notification Preferences
            </a>
            <a href="#danger" class="nav-item" style="color: var(--danger); padding: var(--space-md); display: block; text-decoration: none;">
                <i class="fas fa-exclamation-triangle" style="width: 24px;"></i> Danger Zone
            </a>
        </div>
    </div>
    
    <!-- Settings Forms -->
    <div>
        <!-- Security -->
        <div id="security" class="card" style="margin-bottom: var(--space-xl);">
            <h3 style="color: var(--dark-pink); margin-bottom: var(--space-md); border-bottom: 1px solid var(--light-pink); padding-bottom: 8px;">Change Password</h3>
            
            <form method="POST" action="settings.php" class="needs-validation">
                <?php csrfField(); ?>
                <input type="hidden" name="action" value="password">
                
                <div class="form-group">
                    <label class="form-label">Current Password</label>
                    <input type="password" name="current_password" class="form-control" required>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-md);">
                    <div class="form-group">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" required minlength="8">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required minlength="8">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary" style="margin-top: var(--space-sm);">Update Password</button>
            </form>
        </div>
        
        <!-- Notification Preferences -->
        <div id="notifications" class="card" style="margin-bottom: var(--space-xl);">
            <h3 style="color: var(--dark-pink); margin-bottom: var(--space-md); border-bottom: 1px solid var(--light-pink); padding-bottom: 8px;">Notification Preferences</h3>
            
            <form method="POST" action="settings.php">
                <?php csrfField(); ?>
                <input type="hidden" name="action" value="notifications">
                
                <div style="margin-bottom: var(--space-md);">
                    <label style="display: flex; align-items: center; gap: 12px; cursor: pointer; margin-bottom: 12px;">
                        <input type="checkbox" name="notif_email" checked style="accent-color: var(--main-pink); width: 18px; height: 18px;">
                        <div>
                            <span style="font-weight: 500; display: block; font-size: 14px;">Email Notifications</span>
                            <span style="font-size: 12px; color: var(--medium-gray);">Receive booking updates and marketing emails.</span>
                        </div>
                    </label>
                    
                    <label style="display: flex; align-items: center; gap: 12px; cursor: pointer;">
                        <input type="checkbox" name="notif_sms" checked style="accent-color: var(--main-pink); width: 18px; height: 18px;">
                        <div>
                            <span style="font-weight: 500; display: block; font-size: 14px;">SMS Notifications</span>
                            <span style="font-size: 12px; color: var(--medium-gray);">Receive critical updates (like booking confirmations) via SMS.</span>
                        </div>
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary">Save Preferences</button>
            </form>
        </div>
        
        <!-- Danger Zone -->
        <div id="danger" class="card" style="border-color: var(--danger);">
            <h3 style="color: var(--danger); margin-bottom: var(--space-md); border-bottom: 1px solid var(--danger-bg); padding-bottom: 8px;">Danger Zone</h3>
            
            <p style="color: var(--dark-gray); font-size: 14px; margin-bottom: var(--space-md);">
                Once you delete your account, there is no going back. Please be certain.
            </p>
            
            <button class="btn btn-danger" onclick="alert('Account deletion requires admin contact in this demo version.')">Delete Account</button>
        </div>
    </div>
</div>

<style>
    @media (max-width: 767px) {
        div[style*="grid-template-columns: 1fr 2fr"] { grid-template-columns: 1fr !important; }
    }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
