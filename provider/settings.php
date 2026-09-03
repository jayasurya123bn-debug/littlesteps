<?php
/**
 * Provider Settings Page
 * Little Steps Childcare Platform
 * Policies, Notifications & Password Management
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
requireRole('provider');

$pageTitleHeader = 'Account & Center Settings';
$pageTitle = 'Settings';

$conn = getDBConnection();
$providerId = $_SESSION['user_id'];

// Handle Settings or Password Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        setFlashMessage('error', 'Invalid request token.');
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'password') {
            $currentPassword = $_POST['current_password'];
            $newPassword     = $_POST['new_password'];
            $confirmPassword = $_POST['confirm_password'];

            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                setFlashMessage('error', 'All password fields are required.');
            } elseif (strlen($newPassword) < 8) {
                setFlashMessage('error', 'New password must be at least 8 characters long.');
            } elseif ($newPassword !== $confirmPassword) {
                setFlashMessage('error', 'New passwords do not match.');
            } else {
                // Check providers table
                $stmt = $conn->prepare("SELECT password FROM providers WHERE id = ?");
                $stmt->bind_param("i", $providerId);
                $stmt->execute();
                $prov = $stmt->get_result()->fetch_assoc();

                if ($prov && password_verify($currentPassword, $prov['password'])) {
                    $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
                    $upd = $conn->prepare("UPDATE providers SET password = ? WHERE id = ?");
                    $upd->bind_param("si", $hashed, $providerId);
                    $upd->execute();
                    setFlashMessage('success', 'Your password has been changed successfully.');
                } else {
                    setFlashMessage('error', 'Current password was incorrect.');
                }
            }

        } elseif ($action === 'preferences') {
            setFlashMessage('success', 'Operational preferences and notification settings saved.');
        }
    }
    redirect('/provider/settings.php');
}

$conn->close();

require_once __DIR__ . '/includes/header.php';
?>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
    <!-- Change Password Card -->
    <div class="detail-card">
        <h3 style="margin: 0 0 16px 0; color: var(--provider-dark-pink); font-size: 18px;">
            <i class="fas fa-lock" style="margin-right: 8px;"></i> Change Password
        </h3>
        
        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="password">
            
            <div class="form-group" style="margin-bottom: 14px;">
                <label class="form-label">Current Password *</label>
                <input type="password" name="current_password" class="form-control" required>
            </div>
            
            <div class="form-group" style="margin-bottom: 14px;">
                <label class="form-label">New Password (min 8 characters) *</label>
                <input type="password" name="new_password" class="form-control" minlength="8" required>
            </div>
            
            <div class="form-group" style="margin-bottom: 20px;">
                <label class="form-label">Confirm New Password *</label>
                <input type="password" name="confirm_password" class="form-control" minlength="8" required>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-key" style="margin-right: 6px;"></i> Update Password
            </button>
        </form>
    </div>
    
    <!-- Operational Preferences & Policies -->
    <div class="detail-card">
        <h3 style="margin: 0 0 16px 0; color: var(--provider-dark-pink); font-size: 18px;">
            <i class="fas fa-sliders-h" style="margin-right: 8px;"></i> Booking & Alert Preferences
        </h3>
        
        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="preferences">
            
            <div style="display: flex; flex-direction: column; gap: 16px; margin-bottom: 24px;">
                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                    <input type="checkbox" name="auto_accept" value="1" style="accent-color: var(--provider-pink); width: 18px; height: 18px;">
                    <div>
                        <strong>Auto-Accept Available Slots</strong>
                        <div style="font-size: 12px; color: #757575;">Instantly confirm bookings if slot capacity has not been exceeded</div>
                    </div>
                </label>
                
                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                    <input type="checkbox" name="email_notifs" value="1" checked style="accent-color: var(--provider-pink); width: 18px; height: 18px;">
                    <div>
                        <strong>Instant Email Alerts</strong>
                        <div style="font-size: 12px; color: #757575;">Receive notifications for new parent booking requests and inquiries</div>
                    </div>
                </label>
                
                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                    <input type="checkbox" name="sms_notifs" value="1" checked style="accent-color: var(--provider-pink); width: 18px; height: 18px;">
                    <div>
                        <strong>SMS & WhatsApp Broadcasts</strong>
                        <div style="font-size: 12px; color: #757575;">Receive emergency parent pickup and check-in updates via SMS</div>
                    </div>
                </label>
            </div>
            
            <button type="submit" class="btn btn-secondary">
                <i class="fas fa-save" style="margin-right: 6px;"></i> Save Preferences
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
