<?php
/**
 * Admin Platform Configuration
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
requireRole('admin');

$pageTitleHeader = 'Platform Settings';
$pageTitle = 'Settings';

$conn = getDBConnection();

// Handle Settings Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verifyCSRFToken($_POST['csrf_token'])) {
        $settingsToSave = [
            'site_name'                 => sanitizeInput($conn, $_POST['site_name'] ?? 'Little Steps'),
            'site_tagline'              => sanitizeInput($conn, $_POST['site_tagline'] ?? 'Trusted 24x7 Childcare Platform'),
            'contact_email'             => sanitizeInput($conn, $_POST['contact_email'] ?? 'support@littlesteps.com'),
            'contact_phone'             => sanitizeInput($conn, $_POST['contact_phone'] ?? '+91 80 4912 3456'),
            'platform_fee_percent'      => sanitizeInput($conn, $_POST['platform_fee_percent'] ?? '10.0'),
            'cancellation_cutoff_hours' => (int)($_POST['cancellation_cutoff_hours'] ?? 4),
            'enable_sms_alerts'         => isset($_POST['enable_sms_alerts']) ? '1' : '0',
            'require_id_verification'   => isset($_POST['require_id_verification']) ? '1' : '0'
        ];

        foreach ($settingsToSave as $key => $val) {
            $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->bind_param("sss", $key, $val, $val);
            $stmt->execute();
        }

        setFlashMessage('success', 'Platform settings saved successfully.');
    }
    redirect('/admin/settings.php');
}

// Fetch Current Settings
$currentSettings = [];
$res = $conn->query("SELECT setting_key, setting_value FROM settings");
if ($res) {
    while($row = $res->fetch_assoc()) {
        $currentSettings[$row['setting_key']] = $row['setting_value'];
    }
}

$conn->close();

require_once __DIR__ . '/includes/header.php';
?>

<div style="display: grid; grid-template-columns: 280px 1fr; gap: 24px;">
    <!-- Settings Navigation Sidebar -->
    <div>
        <div class="detail-card" style="padding: 0; overflow: hidden;">
            <a href="#general" class="admin-tab active" style="display: block; padding: 14px 20px; border-bottom: 1px solid var(--admin-light-pink); text-decoration: none;">
                <i class="fas fa-sliders-h" style="width: 22px; color: var(--admin-pink);"></i> General Platform
            </a>
            <a href="#bookings" class="admin-tab" style="display: block; padding: 14px 20px; border-bottom: 1px solid var(--admin-light-pink); text-decoration: none;">
                <i class="fas fa-calendar-alt" style="width: 22px; color: var(--admin-pink);"></i> Booking Rules
            </a>
            <a href="#notifications" class="admin-tab" style="display: block; padding: 14px 20px; border-bottom: 1px solid var(--admin-light-pink); text-decoration: none;">
                <i class="fas fa-bell" style="width: 22px; color: var(--admin-pink);"></i> Notifications
            </a>
            <a href="#compliance" class="admin-tab" style="display: block; padding: 14px 20px; text-decoration: none;">
                <i class="fas fa-shield-alt" style="width: 22px; color: var(--admin-pink);"></i> Safety & Compliance
            </a>
        </div>
    </div>
    
    <!-- Settings Form Panels -->
    <div>
        <form method="POST">
            <?= csrfField() ?>
            
            <!-- General Settings -->
            <div class="detail-card" id="general" style="margin-bottom: 24px;">
                <h3 style="margin: 0 0 16px 0; color: var(--admin-dark-pink); font-size: 18px;">
                    <i class="fas fa-sliders-h" style="margin-right: 8px;"></i> General Settings
                </h3>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <div class="form-group">
                        <label class="form-label">Platform Name</label>
                        <input type="text" name="site_name" class="form-control" value="<?= htmlspecialchars($currentSettings['site_name'] ?? 'Little Steps') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Platform Tagline</label>
                        <input type="text" name="site_tagline" class="form-control" value="<?= htmlspecialchars($currentSettings['site_tagline'] ?? 'Trusted 24x7 Childcare Platform') ?>" required>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <div class="form-group">
                        <label class="form-label">Official Support Email</label>
                        <input type="email" name="contact_email" class="form-control" value="<?= htmlspecialchars($currentSettings['contact_email'] ?? 'support@littlesteps.com') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Helpline Phone Number</label>
                        <input type="text" name="contact_phone" class="form-control" value="<?= htmlspecialchars($currentSettings['contact_phone'] ?? '+91 80 4912 3456') ?>" required>
                    </div>
                </div>
            </div>
            
            <!-- Booking Settings -->
            <div class="detail-card" id="bookings" style="margin-bottom: 24px;">
                <h3 style="margin: 0 0 16px 0; color: var(--admin-dark-pink); font-size: 18px;">
                    <i class="fas fa-calendar-check" style="margin-right: 8px;"></i> Booking & Financial Rules
                </h3>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <div class="form-group">
                        <label class="form-label">Platform Service Commission (%)</label>
                        <input type="number" step="0.1" name="platform_fee_percent" class="form-control" value="<?= htmlspecialchars($currentSettings['platform_fee_percent'] ?? '10.0') ?>" required>
                        <small style="color: #757575;">Commission retained by Little Steps on each session</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Cancellation Cutoff Window (Hours)</label>
                        <input type="number" name="cancellation_cutoff_hours" class="form-control" value="<?= htmlspecialchars($currentSettings['cancellation_cutoff_hours'] ?? '4') ?>" required>
                        <small style="color: #757575;">Minimum hours prior to booking start for full refund</small>
                    </div>
                </div>
            </div>
            
            <!-- Notifications & Compliance -->
            <div class="detail-card" id="notifications" style="margin-bottom: 24px;">
                <h3 style="margin: 0 0 16px 0; color: var(--admin-dark-pink); font-size: 18px;">
                    <i class="fas fa-shield-alt" style="margin-right: 8px;"></i> Alerts & Compliance
                </h3>
                
                <div style="display: flex; flex-direction: column; gap: 14px;">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                        <input type="checkbox" name="enable_sms_alerts" value="1" <?= ($currentSettings['enable_sms_alerts'] ?? '1') === '1' ? 'checked' : '' ?> style="accent-color: var(--admin-pink); width: 18px; height: 18px;">
                        <span><strong>Enable Automated SMS & WhatsApp Alerts</strong> (Booking confirmations & caregiver check-in)</span>
                    </label>
                    
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                        <input type="checkbox" name="require_id_verification" value="1" <?= ($currentSettings['require_id_verification'] ?? '1') === '1' ? 'checked' : '' ?> style="accent-color: var(--admin-pink); width: 18px; height: 18px;">
                        <span><strong>Strict KYC & Background Verification</strong> (Requires document check prior to activating provider)</span>
                    </label>
                </div>
            </div>
            
            <div style="display: flex; justify-content: flex-end;">
                <button type="submit" class="btn btn-primary" style="padding: 12px 28px; font-size: 15px;">
                    <i class="fas fa-save" style="margin-right: 6px;"></i> Save Platform Settings
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
