<?php
/**
 * Provider Business Profile Management
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
requireRole('provider');

$pageTitleHeader = 'Business Profile';
$pageTitle = 'Profile';

$conn = getDBConnection();
$providerId = $_SESSION['user_id'];

// Fetch Provider Profile
$stmt = $conn->prepare("SELECT * FROM providers WHERE id = ?");
$stmt->bind_param("i", $providerId);
$stmt->execute();
$provider = $stmt->get_result()->fetch_assoc();

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verifyCSRFToken($_POST['csrf_token'])) {
        $businessName   = sanitizeInput($conn, $_POST['business_name']);
        $ownerName      = sanitizeInput($conn, $_POST['owner_name']);
        $phone          = sanitizeInput($conn, $_POST['phone']);
        $altPhone       = sanitizeInput($conn, $_POST['alternate_phone']);
        $address        = sanitizeInput($conn, $_POST['address']);
        $city           = sanitizeInput($conn, $_POST['city']);
        $state          = sanitizeInput($conn, $_POST['state']);
        $pincode        = sanitizeInput($conn, $_POST['pincode']);
        $estYear        = (int)$_POST['established_year'];
        $license        = sanitizeInput($conn, $_POST['license_number']);
        $description    = sanitizeInput($conn, $_POST['description']);

        $upd = $conn->prepare("
            UPDATE providers SET 
                business_name=?, owner_name=?, phone=?, alternate_phone=?,
                address=?, city=?, state=?, pincode=?, established_year=?,
                license_number=?, description=?
            WHERE id=?
        ");
        $upd->bind_param(
            "ssssssssissi",
            $businessName, $ownerName, $phone, $altPhone,
            $address, $city, $state, $pincode, $estYear,
            $license, $description, $providerId
        );
        
        if ($upd->execute()) {
            $_SESSION['business_name'] = $businessName;
            $_SESSION['user_name'] = $ownerName;
            setFlashMessage('success', 'Business profile details updated successfully.');
        } else {
            setFlashMessage('error', 'Failed to update profile: ' . $conn->error);
        }
        redirect('/provider/profile.php');
    }
}

$conn->close();

require_once __DIR__ . '/includes/header.php';
?>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px;">
    <!-- Profile Status Summary -->
    <div>
        <div class="detail-card" style="text-align: center;">
            <div style="width: 90px; height: 90px; border-radius: 16px; background: #FFF0F5; border: 3px solid var(--provider-pastel-pink); display: flex; align-items: center; justify-content: center; font-size: 40px; margin: 0 auto 16px;">
                🏢
            </div>
            
            <h3 style="margin: 0 0 4px 0; color: #212121;"><?= htmlspecialchars($provider['business_name']) ?></h3>
            <div style="font-size: 14px; color: #757575; margin-bottom: 12px;"><?= htmlspecialchars($provider['owner_name']) ?> (Owner)</div>
            
            <div style="margin-bottom: 18px;">
                <span class="badge badge-<?= htmlspecialchars($provider['status']) ?>" style="font-size: 13px; padding: 6px 16px;">
                    <?= strtoupper(htmlspecialchars($provider['status'])) ?>
                </span>
            </div>
            
            <div style="text-align: left; background: #FFF0F5; padding: 14px; border-radius: 8px; font-size: 13px; color: #424242;">
                <div style="margin-bottom: 6px;"><strong>License:</strong> <?= htmlspecialchars($provider['license_number'] ?? 'N/A') ?></div>
                <div style="margin-bottom: 6px;"><strong>Email:</strong> <?= htmlspecialchars($provider['email']) ?></div>
                <div><strong>Joined:</strong> <?= formatDate($provider['created_at']) ?></div>
            </div>
        </div>
    </div>
    
    <!-- Edit Business Details Form -->
    <div class="detail-card">
        <h3 style="margin: 0 0 20px 0; color: var(--provider-dark-pink); font-size: 18px;">
            <i class="fas fa-building" style="margin-right: 8px;"></i> Business & Legal Information
        </h3>
        
        <form method="POST">
            <?= csrfField() ?>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div class="form-group">
                    <label class="form-label">Business Name *</label>
                    <input type="text" name="business_name" class="form-control" value="<?= htmlspecialchars($provider['business_name']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Owner / Director Name *</label>
                    <input type="text" name="owner_name" class="form-control" value="<?= htmlspecialchars($provider['owner_name']) ?>" required>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div class="form-group">
                    <label class="form-label">Email (Read Only)</label>
                    <input type="email" class="form-control" value="<?= htmlspecialchars($provider['email']) ?>" readonly style="background: #F5F5F5;">
                </div>
                <div class="form-group">
                    <label class="form-label">Primary Phone *</label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($provider['phone']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Alternate Phone</label>
                    <input type="text" name="alternate_phone" class="form-control" value="<?= htmlspecialchars($provider['alternate_phone'] ?? '') ?>">
                </div>
            </div>
            
            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label">Head Office Address *</label>
                <textarea name="address" class="form-control" rows="2" required><?= htmlspecialchars($provider['address']) ?></textarea>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div class="form-group">
                    <label class="form-label">City *</label>
                    <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($provider['city']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">State *</label>
                    <input type="text" name="state" class="form-control" value="<?= htmlspecialchars($provider['state']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Pincode *</label>
                    <input type="text" name="pincode" class="form-control" value="<?= htmlspecialchars($provider['pincode']) ?>" required>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div class="form-group">
                    <label class="form-label">Trade / Childcare License No.</label>
                    <input type="text" name="license_number" class="form-control" value="<?= htmlspecialchars($provider['license_number'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Established Year</label>
                    <input type="number" name="established_year" class="form-control" value="<?= htmlspecialchars($provider['established_year'] ?? '2020') ?>">
                </div>
            </div>
            
            <div class="form-group" style="margin-bottom: 24px;">
                <label class="form-label">Company Overview / Description</label>
                <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($provider['description'] ?? '') ?></textarea>
            </div>
            
            <div style="display: flex; justify-content: flex-end;">
                <button type="submit" class="btn btn-primary" style="padding: 10px 24px;">
                    <i class="fas fa-save" style="margin-right: 6px;"></i> Save Profile Changes
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
