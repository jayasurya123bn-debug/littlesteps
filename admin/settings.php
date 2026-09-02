<?php
/**
 * Admin System Settings
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

$pageTitleHeader = 'System Settings';
$pageTitle = 'Settings';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verifyCSRFToken($_POST['csrf_token'])) {
        setFlashMessage('success', 'System settings updated successfully.');
    } else {
        setFlashMessage('error', 'Invalid request token.');
    }
    redirect('/admin/settings.php');
}

require_once __DIR__ . '/includes/header.php';
?>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: var(--space-xl);">
    
    <div>
        <div class="card" style="padding: 0; overflow: hidden;">
            <a href="#general" class="nav-item" style="color: var(--dark-gray); padding: var(--space-md); border-bottom: 1px solid var(--light-gray); border-left: 4px solid var(--admin-primary); background: #F8F9FA; display: block; text-decoration: none; font-weight: 600;">
                <i class="fas fa-sliders-h" style="width: 24px;"></i> General Platform Settings
            </a>
            <a href="#commission" class="nav-item" style="color: var(--dark-gray); padding: var(--space-md); border-bottom: 1px solid var(--light-gray); display: block; text-decoration: none;">
                <i class="fas fa-percentage" style="width: 24px;"></i> Commission & Fees
            </a>
            <a href="#security" class="nav-item" style="color: var(--dark-gray); padding: var(--space-md); display: block; text-decoration: none;">
                <i class="fas fa-shield-alt" style="width: 24px;"></i> Security & Backups
            </a>
        </div>
    </div>
    
    <div>
        <form method="POST" action="settings.php">
            <?php csrfField(); ?>
            
            <div id="general" class="card" style="margin-bottom: var(--space-xl);">
                <h3 style="color: var(--admin-primary); margin-bottom: var(--space-md); border-bottom: 1px solid var(--light-gray); padding-bottom: 8px;">General Settings</h3>
                
                <div class="form-group">
                    <label class="form-label">Platform Name</label>
                    <input type="text" class="form-control" value="Little Steps Childcare Platform">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Support Email</label>
                    <input type="email" class="form-control" value="support@littlesteps.com">
                </div>
                
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" checked style="width: 18px; height: 18px;">
                        <span>Enable new provider registrations</span>
                    </label>
                </div>
            </div>
            
            <div id="commission" class="card" style="margin-bottom: var(--space-xl);">
                <h3 style="color: var(--admin-primary); margin-bottom: var(--space-md); border-bottom: 1px solid var(--light-gray); padding-bottom: 8px;">Commission & Fees</h3>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-md);">
                    <div class="form-group">
                        <label class="form-label">Platform Fee (%)</label>
                        <input type="number" class="form-control" value="10" min="0" max="100">
                        <small style="color: var(--medium-gray);">Percentage taken from every successful booking.</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tax Rate (%)</label>
                        <input type="number" class="form-control" value="18" min="0" max="100">
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary" style="background: var(--admin-primary); border-color: var(--admin-primary);">Save All Settings</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
