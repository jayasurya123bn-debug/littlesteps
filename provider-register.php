<?php
/**
 * Provider Registration Page
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/functions.php';

if (isLoggedIn()) {
    redirect('/' . $_SESSION['user_role'] . '/dashboard.php');
}

$pageTitle = 'Provider Registration';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $error = "Invalid request token. Please try again.";
    } else {
        $conn = getDBConnection();
        
        $businessName = sanitizeInput($conn, $_POST['business_name']);
        $ownerName = sanitizeInput($conn, $_POST['owner_name']);
        $email = sanitizeInput($conn, $_POST['email']);
        $phone = sanitizeInput($conn, $_POST['phone']);
        $password = $_POST['password'];
        $confirmPassword = $_POST['confirm_password'];
        $address = sanitizeInput($conn, $_POST['address']);
        $city = sanitizeInput($conn, $_POST['city']);
        $state = sanitizeInput($conn, $_POST['state']);
        $pincode = sanitizeInput($conn, $_POST['pincode']);
        
        if (empty($businessName) || empty($ownerName) || empty($email) || empty($phone) || empty($password) || empty($address) || empty($city) || empty($state) || empty($pincode)) {
            $error = "All fields are required.";
        } elseif (!isValidEmail($email)) {
            $error = "Please enter a valid email address.";
        } elseif (strlen($password) < 8) {
            $error = "Password must be at least 8 characters long.";
        } elseif ($password !== $confirmPassword) {
            $error = "Passwords do not match.";
        } else {
            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? UNION SELECT id FROM providers WHERE email = ?");
            $stmt->bind_param("ss", $email, $email);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $error = "An account with this email already exists.";
            } else {
                // Hash password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $status = 'pending'; // Requires admin approval
                
                $insertStmt = $conn->prepare("INSERT INTO providers (business_name, owner_name, email, password, phone, address, city, state, pincode, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $insertStmt->bind_param("ssssssssss", $businessName, $ownerName, $email, $hashedPassword, $phone, $address, $city, $state, $pincode, $status);
                
                if ($insertStmt->execute()) {
                    setFlashMessage('success', 'Registration successful! Your account is pending admin approval. You will be notified once approved.');
                    redirect('/login.php');
                } else {
                    $error = "Something went wrong. Please try again later.";
                }
            }
        }
        $conn->close();
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div style="background-color: var(--baby-pink); min-height: calc(100vh - 80px); display: flex; align-items: center; justify-content: center; padding: var(--space-2xl) var(--space-md);">
    <div class="card" style="width: 100%; max-width: 800px; margin: 0; box-shadow: var(--shadow-xl);">
        <style>
    .icon-circle-wrap {
        position: relative;
        width: 90px;
        height: 90px;
        margin: 0 auto var(--space-md);
    }
    .icon-circle-glow {
        position: absolute;
        inset: -8px;
        border-radius: 50%;
        background: conic-gradient(from 0deg, #FF6B9D, #FF9E6B, #FFD36B, #6BFFB2, #6BB2FF, #C96BFF, #FF6B9D);
        animation: spin-glow 4s linear infinite;
        filter: blur(4px);
        opacity: 0.7;
    }
    .icon-circle-inner {
        position: relative;
        width: 90px;
        height: 90px;
        background: linear-gradient(135deg, #FF6B9D 0%, #E91E63 40%, #AD1457 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 36px;
        color: white;
        box-shadow: 0 8px 32px rgba(233,30,99,0.4);
        overflow: hidden;
    }
    .icon-circle-inner::before {
        content: '';
        position: absolute;
        top: -20%;
        left: -20%;
        width: 60%;
        height: 60%;
        background: rgba(255,255,255,0.25);
        border-radius: 50%;
        filter: blur(6px);
    }
    @keyframes spin-glow {
        to { transform: rotate(360deg); }
    }
    .section-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        font-weight: 700;
        font-size: 15px;
        color: white;
        margin-right: 10px;
        flex-shrink: 0;
        vertical-align: middle;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    }
    .section-heading {
        display: flex;
        align-items: center;
        color: var(--dark-pink);
        border-bottom: 2px solid;
        border-image: linear-gradient(90deg, var(--main-pink), transparent) 1;
        padding-bottom: var(--space-sm);
        margin-bottom: var(--space-md);
        margin-top: var(--space-lg);
    }
</style>

<div style="text-align: center; margin-bottom: var(--space-xl);">
    <div class="icon-circle-wrap">
        <div class="icon-circle-glow"></div>
        <div class="icon-circle-inner">
            <i class="fas fa-store"></i>
        </div>
    </div>
    <h2 style="font-size: 2rem; font-weight: 700; color: var(--near-black); margin-bottom: 6px;">Partner with Us</h2>
    <p style="color: var(--medium-gray);">Register your daycare center on Little Steps</p>
</div>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-error" style="animation: shake 0.5s;">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="provider-register.php" class="needs-validation">
            <?php csrfField(); ?>
            
            <h4 class="section-heading">
                <span class="section-badge" style="background: linear-gradient(135deg, #FF6B9D, #E91E63);">1</span>
                Business Information
            </h4>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: var(--space-md);">
                <div class="form-group">
                    <label class="form-label">Business Name</label>
                    <input type="text" name="business_name" class="form-control" required value="<?= isset($_POST['business_name']) ? htmlspecialchars($_POST['business_name']) : '' ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Owner Name</label>
                    <input type="text" name="owner_name" class="form-control" required value="<?= isset($_POST['owner_name']) ? htmlspecialchars($_POST['owner_name']) : '' ?>">
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: var(--space-md);">
                <div class="form-group">
                    <label class="form-label">Email Address (for login)</label>
                    <input type="email" name="email" class="form-control" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <input type="tel" name="phone" class="form-control" required value="<?= isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '' ?>">
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: var(--space-md);">
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required minlength="8">
                </div>
                <div class="form-group">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" required minlength="8">
                </div>
            </div>
            
            <h4 class="section-heading" style="margin-top: var(--space-lg);">
                <span class="section-badge" style="background: linear-gradient(135deg, #FF9E6B, #FF6B2B);">2</span>
                Location Details
            </h4>
            
            <div class="form-group">
                <label class="form-label">Full Address</label>
                <textarea name="address" class="form-control" rows="2" required><?= isset($_POST['address']) ? htmlspecialchars($_POST['address']) : '' ?></textarea>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--space-md);">
                <div class="form-group">
                    <label class="form-label">City</label>
                    <input type="text" name="city" class="form-control" required value="<?= isset($_POST['city']) ? htmlspecialchars($_POST['city']) : '' ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">State</label>
                    <input type="text" name="state" class="form-control" required value="<?= isset($_POST['state']) ? htmlspecialchars($_POST['state']) : '' ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Pincode</label>
                    <input type="text" name="pincode" class="form-control" required value="<?= isset($_POST['pincode']) ? htmlspecialchars($_POST['pincode']) : '' ?>">
                </div>
            </div>
            
            <div class="alert alert-info mt-3" style="font-size: 14px;">
                <i class="fas fa-info-circle" style="font-size: 18px;"></i>
                After registration, an administrator will review your application. You will be asked to provide business documents upon approval.
            </div>
            
            <button type="submit" class="btn btn-primary btn-lg mt-3" style="width: 100%;">
                Submit Registration
            </button>
        </form>
        
        <div style="margin-top: var(--space-xl); text-align: center; border-top: 1px solid var(--light-pink); padding-top: var(--space-lg);">
            <p style="font-size: 14px;">Already partnered with us? <a href="login.php" style="font-weight: 600;">Log In</a></p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
