<?php
/**
 * Parent Profile Page
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

$pageTitleHeader = 'My Profile';
$pageTitle = 'Profile';

$conn = getDBConnection();
$userId = $_SESSION['user_id'];

// Get user details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        setFlashMessage('error', "Invalid request token.");
    } else {
        $firstName = sanitizeInput($conn, $_POST['first_name']);
        $lastName = sanitizeInput($conn, $_POST['last_name']);
        $phone = sanitizeInput($conn, $_POST['phone']);
        $address = sanitizeInput($conn, $_POST['address']);
        
        $updateStmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, phone = ?, address = ? WHERE id = ?");
        $updateStmt->bind_param("ssssi", $firstName, $lastName, $phone, $address, $userId);
        
        if ($updateStmt->execute()) {
            $_SESSION['user_name'] = $firstName . ' ' . $lastName;
            setFlashMessage('success', 'Profile updated successfully.');
            
            // Handle profile photo upload if exists
            if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
                $uploadPath = uploadFile($_FILES['profile_photo'], 'profiles');
                if ($uploadPath) {
                    $photoStmt = $conn->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
                    $photoStmt->bind_param("si", $uploadPath, $userId);
                    $photoStmt->execute();
                    setFlashMessage('success', 'Profile updated with new photo.');
                }
            }
            redirect('/parent/profile.php');
        } else {
            setFlashMessage('error', 'Failed to update profile.');
        }
    }
}
$conn->close();

require_once __DIR__ . '/includes/header.php';
?>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: var(--space-xl);">
    
    <!-- Profile Photo & Summary -->
    <div>
        <div class="card text-center">
            <form id="photoForm" method="POST" action="profile.php" enctype="multipart/form-data">
                <?php csrfField(); ?>
                <div class="profile-photo-upload" onclick="document.getElementById('photoInput').click()">
                    <img src="<?= $user['profile_photo'] ? SITE_URL . '/' . $user['profile_photo'] : 'https://ui-avatars.com/api/?name=' . urlencode($user['first_name'].'+'.$user['last_name']) . '&background=FCE4EC&color=E91E63' ?>" alt="Profile Photo">
                    <div class="upload-overlay">
                        <i class="fas fa-camera"></i>
                        <span style="font-size: 12px; margin-top: 4px;">Change</span>
                    </div>
                </div>
                <input type="file" id="photoInput" name="profile_photo" accept="image/*" style="display: none;" onchange="document.getElementById('photoForm').submit()">
            </form>
            
            <h3 style="margin: var(--space-md) 0 4px;"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h3>
            <p style="color: var(--medium-gray); font-size: 14px; margin-bottom: var(--space-md);"><?= htmlspecialchars($user['email']) ?></p>
            
            <div style="background: var(--baby-pink); border-radius: var(--radius-md); padding: var(--space-sm) var(--space-md); display: inline-block;">
                <span style="font-size: 12px; color: var(--dark-pink); font-weight: 600;">Parent Member</span>
            </div>
            
            <hr style="border: 0; border-top: 1px solid var(--light-pink); margin: var(--space-lg) 0;">
            
            <div style="text-align: left;">
                <div style="margin-bottom: var(--space-sm);">
                    <span style="color: var(--medium-gray); font-size: 12px; display: block;">Joined</span>
                    <strong style="font-size: 14px;"><?= date('F Y', strtotime($user['created_at'])) ?></strong>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Profile Form -->
    <div>
        <div class="card">
            <h3 style="color: var(--dark-pink); margin-bottom: var(--space-lg); border-bottom: 1px solid var(--light-pink); padding-bottom: 8px;">Personal Information</h3>
            
            <form method="POST" action="profile.php" class="needs-validation">
                <?php csrfField(); ?>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-md);">
                    <div class="form-group">
                        <label class="form-label">First Name</label>
                        <input type="text" name="first_name" class="form-control" required value="<?= htmlspecialchars($user['first_name']) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Last Name</label>
                        <input type="text" name="last_name" class="form-control" required value="<?= htmlspecialchars($user['last_name']) ?>">
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-md);">
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" class="form-control" disabled value="<?= htmlspecialchars($user['email']) ?>">
                        <small style="color: var(--medium-gray); font-size: 12px;">Email cannot be changed.</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <input type="tel" name="phone" class="form-control" required value="<?= htmlspecialchars($user['phone']) ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Home Address</label>
                    <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary" style="margin-top: var(--space-sm);">Save Changes</button>
            </form>
        </div>
    </div>
</div>

<style>
    @media (max-width: 767px) {
        div[style*="grid-template-columns: 1fr 2fr"] { grid-template-columns: 1fr !important; }
    }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
