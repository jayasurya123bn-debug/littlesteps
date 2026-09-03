<?php
/**
 * Provider Add/Edit Caregiver Form
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
requireRole('provider');

$conn = getDBConnection();
$providerId = $_SESSION['user_id'];
$cgId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = ($cgId > 0);

$caregiver = [
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'phone' => '',
    'date_of_birth' => '',
    'gender' => 'Female',
    'qualification' => 'Early Childhood Care & Education',
    'experience_years' => 3,
    'specialization' => 'Toddler Care & Nutrition',
    'background_verified' => 1,
    'verification_date' => date('Y-m-d'),
    'bio' => '',
    'status' => 'active'
];

if ($isEdit) {
    $stmt = $conn->prepare("SELECT * FROM caregivers WHERE id = ? AND provider_id = ?");
    $stmt->bind_param("ii", $cgId, $providerId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 1) {
        $caregiver = array_merge($caregiver, $res->fetch_assoc());
    } else {
        $conn->close();
        setFlashMessage('error', 'Caregiver record not found.');
        redirect('/provider/caregivers.php');
    }
}

// Handle Save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verifyCSRFToken($_POST['csrf_token'])) {
        $firstName     = sanitizeInput($conn, $_POST['first_name']);
        $lastName      = sanitizeInput($conn, $_POST['last_name']);
        $email         = sanitizeInput($conn, $_POST['email']);
        $phone         = sanitizeInput($conn, $_POST['phone']);
        $dob           = $_POST['date_of_birth'] ?: null;
        $gender        = sanitizeInput($conn, $_POST['gender'] ?? 'Female');
        $qualification = sanitizeInput($conn, $_POST['qualification']);
        $experience    = (int)$_POST['experience_years'];
        $specialization= sanitizeInput($conn, $_POST['specialization']);
        $bio           = sanitizeInput($conn, $_POST['bio']);
        $verified      = isset($_POST['background_verified']) ? 1 : 0;
        $vDate         = $verified ? ($_POST['verification_date'] ?: date('Y-m-d')) : null;
        $status        = sanitizeInput($conn, $_POST['status'] ?? 'active');

        if ($isEdit) {
            $upd = $conn->prepare("
                UPDATE caregivers SET 
                    first_name=?, last_name=?, email=?, phone=?, date_of_birth=?,
                    gender=?, qualification=?, experience_years=?, specialization=?,
                    bio=?, background_verified=?, verification_date=?, status=?
                WHERE id=? AND provider_id=?
            ");
            $upd->bind_param(
                "sssssssisssssii",
                $firstName, $lastName, $email, $phone, $dob,
                $gender, $qualification, $experience, $specialization,
                $bio, $verified, $vDate, $status,
                $cgId, $providerId
            );
            $upd->execute();
            setFlashMessage('success', 'Caregiver details updated successfully.');
        } else {
            $ins = $conn->prepare("
                INSERT INTO caregivers (
                    provider_id, first_name, last_name, email, phone, date_of_birth,
                    gender, qualification, experience_years, specialization,
                    bio, background_verified, verification_date, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $ins->bind_param(
                "issssssisssiss",
                $providerId, $firstName, $lastName, $email, $phone, $dob,
                $gender, $qualification, $experience, $specialization,
                $bio, $verified, $vDate, $status
            );
            $ins->execute();
            setFlashMessage('success', 'New caregiver added to staff!');
        }
        redirect('/provider/caregivers.php');
    }
}

$conn->close();

$pageTitleHeader = $isEdit ? 'Edit Caregiver' : 'Add Caregiver';
$pageTitle = $isEdit ? 'Edit Caregiver' : 'Add Caregiver';
require_once __DIR__ . '/includes/header.php';
?>

<div style="max-width: 800px; margin: 0 auto;">
    <div class="detail-card">
        <h3 style="margin: 0 0 20px 0; color: var(--provider-dark-pink); font-size: 18px;">
            <i class="fas fa-user-nurse" style="margin-right: 8px;"></i> <?= $isEdit ? 'Edit Caregiver Profile' : 'Register New Caregiver' ?>
        </h3>
        
        <form method="POST">
            <?= csrfField() ?>
            
            <h4 style="color: var(--provider-pink); font-size: 15px; margin: 0 0 14px 0; border-bottom: 1px solid var(--provider-light-pink); padding-bottom: 6px;">
                1. Personal Information
            </h4>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div class="form-group">
                    <label class="form-label">First Name *</label>
                    <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($caregiver['first_name']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Last Name *</label>
                    <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($caregiver['last_name']) ?>" required>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($caregiver['email']) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Phone Number *</label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($caregiver['phone']) ?>" required>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
                <div class="form-group">
                    <label class="form-label">Date of Birth</label>
                    <input type="date" name="date_of_birth" class="form-control" value="<?= htmlspecialchars($caregiver['date_of_birth'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Gender</label>
                    <div style="display: flex; gap: 20px; align-items: center; padding-top: 8px;">
                        <label><input type="radio" name="gender" value="Female" <?= $caregiver['gender'] === 'Female' ? 'checked' : '' ?> style="accent-color: var(--provider-pink);"> Female</label>
                        <label><input type="radio" name="gender" value="Male" <?= $caregiver['gender'] === 'Male' ? 'checked' : '' ?> style="accent-color: var(--provider-pink);"> Male</label>
                    </div>
                </div>
            </div>
            
            <h4 style="color: var(--provider-pink); font-size: 15px; margin: 0 0 14px 0; border-bottom: 1px solid var(--provider-light-pink); padding-bottom: 6px;">
                2. Professional Qualifications & Bio
            </h4>
            
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div class="form-group">
                    <label class="form-label">Highest Qualification *</label>
                    <input type="text" name="qualification" class="form-control" value="<?= htmlspecialchars($caregiver['qualification']) ?>" placeholder="e.g. Diploma in Montessori, Pediatric Nurse" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Experience (Years)</label>
                    <input type="number" name="experience_years" class="form-control" value="<?= $caregiver['experience_years'] ?>">
                </div>
            </div>
            
            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label">Childcare Specialization</label>
                <input type="text" name="specialization" class="form-control" value="<?= htmlspecialchars($caregiver['specialization']) ?>" placeholder="e.g. Newborn Care, Sensory Development, First Aid">
            </div>
            
            <div class="form-group" style="margin-bottom: 24px;">
                <label class="form-label">Professional Bio / About Staff</label>
                <textarea name="bio" class="form-control" rows="3" placeholder="Brief background, certifications, and teaching philosophy..."><?= htmlspecialchars($caregiver['bio']) ?></textarea>
            </div>
            
            <h4 style="color: var(--provider-pink); font-size: 15px; margin: 0 0 14px 0; border-bottom: 1px solid var(--provider-light-pink); padding-bottom: 6px;">
                3. Safety Verification & Status
            </h4>
            
            <div style="background: #FFF0F5; padding: 16px; border-radius: 10px; border: 1px solid var(--provider-pastel-pink); margin-bottom: 24px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; align-items: center;">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                        <input type="checkbox" name="background_verified" value="1" <?= $caregiver['background_verified'] ? 'checked' : '' ?> style="accent-color: var(--provider-pink); width: 20px; height: 20px;">
                        <div>
                            <strong>Criminal & Police Verification Cleared</strong>
                            <div style="font-size: 12px; color: #757575;">Confirms background check on file</div>
                        </div>
                    </label>
                    
                    <div class="form-group">
                        <label class="form-label" style="font-size: 12px;">Verification Date</label>
                        <input type="date" name="verification_date" class="form-control" value="<?= htmlspecialchars($caregiver['verification_date'] ?? date('Y-m-d')) ?>">
                    </div>
                </div>
            </div>
            
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <a href="caregivers.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary" style="padding: 10px 24px;">
                    <i class="fas fa-save" style="margin-right: 6px;"></i> Save Caregiver Record
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
