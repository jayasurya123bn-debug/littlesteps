<?php
/**
 * Provider Settings Page
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

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
            }
        }
    }
    redirect('/provider/settings.php');
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
            <a href="#documents" class="nav-item" style="color: var(--dark-gray); padding: var(--space-md); border-bottom: 1px solid var(--light-pink); display: block; text-decoration: none;">
                <i class="fas fa-file-alt" style="width: 24px;"></i> Legal Documents
            </a>
            <a href="#billing" class="nav-item" style="color: var(--dark-gray); padding: var(--space-md); border-bottom: 1px solid var(--light-pink); display: block; text-decoration: none;">
                <i class="fas fa-credit-card" style="width: 24px;"></i> Billing & Payouts
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
        
        <!-- Documents -->
        <div id="documents" class="card" style="margin-bottom: var(--space-xl);">
            <h3 style="color: var(--dark-pink); margin-bottom: var(--space-md); border-bottom: 1px solid var(--light-pink); padding-bottom: 8px;">Verification Documents</h3>
            
            <div class="alert alert-warning" style="font-size: 13px;">
                <i class="fas fa-exclamation-triangle"></i> We require business registration and safety certificates to verify your center.
            </div>
            
            <div class="document-upload-zone" onclick="alert('Document upload functionality goes here.')">
                <i class="fas fa-cloud-upload-alt"></i>
                <h4>Upload Registration/License</h4>
                <p style="color: var(--medium-gray); font-size: 13px;">PDF, JPG, or PNG (Max 5MB)</p>
            </div>
        </div>
        
        <!-- Billing -->
        <div id="billing" class="card">
            <h3 style="color: var(--dark-pink); margin-bottom: var(--space-md); border-bottom: 1px solid var(--light-pink); padding-bottom: 8px;">Payout Settings</h3>
            
            <div style="display: flex; align-items: center; justify-content: space-between; padding: var(--space-md); background: var(--light-gray); border-radius: var(--radius-md);">
                <div>
                    <strong style="display: block;">No Bank Account Linked</strong>
                    <span style="font-size: 13px; color: var(--medium-gray);">Add a bank account to receive payouts.</span>
                </div>
                <button class="btn btn-primary" onclick="alert('Bank setup integration goes here.')">Add Account</button>
            </div>
        </div>
    </div>
</div>

<style>
    @media (max-width: 767px) {
        div[style*="grid-template-columns: 1fr 2fr"] { grid-template-columns: 1fr !important; }
    }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
