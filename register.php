<?php
/**
 * Parent Registration Page
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/functions.php';

if (isLoggedIn()) {
    redirect('/' . $_SESSION['user_role'] . '/dashboard.php');
}

$pageTitle = 'Parent Registration';
$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $error = "Invalid request token. Please try again.";
    } else {
        $conn = getDBConnection();
        
        $firstName = sanitizeInput($conn, $_POST['first_name']);
        $lastName = sanitizeInput($conn, $_POST['last_name']);
        $email = sanitizeInput($conn, $_POST['email']);
        $password = $_POST['password'];
        $confirmPassword = $_POST['confirm_password'];
        $phone = sanitizeInput($conn, $_POST['phone']);
        
        if (empty($firstName) || empty($lastName) || empty($email) || empty($password) || empty($phone)) {
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
                $role = 'parent';
                
                // Insert into users
                $insertStmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, phone, role) VALUES (?, ?, ?, ?, ?, ?)");
                $insertStmt->bind_param("ssssss", $firstName, $lastName, $email, $hashedPassword, $phone, $role);
                
                if ($insertStmt->execute()) {
                    $success = true;
                    setFlashMessage('success', 'Registration successful! You can now log in.');
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
    <div class="card" style="width: 100%; max-width: 600px; margin: 0; box-shadow: var(--shadow-xl);">
        <style>
    .icon-circle-wrap { position: relative; width: 90px; height: 90px; margin: 0 auto var(--space-md); }
    .icon-circle-glow { position: absolute; inset: -8px; border-radius: 50%; background: conic-gradient(from 0deg, #FF6B9D, #FF9E6B, #FFD36B, #6BFFB2, #6BB2FF, #C96BFF, #FF6B9D); animation: spin-glow 4s linear infinite; filter: blur(4px); opacity: 0.7; }
    .icon-circle-inner { position: relative; width: 90px; height: 90px; background: linear-gradient(135deg, #FF6B9D 0%, #E91E63 40%, #AD1457 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 36px; color: white; box-shadow: 0 8px 32px rgba(233,30,99,0.4); overflow: hidden; }
    .icon-circle-inner::before { content: ''; position: absolute; top: -20%; left: -20%; width: 60%; height: 60%; background: rgba(255,255,255,0.25); border-radius: 50%; filter: blur(6px); }
    @keyframes spin-glow { to { transform: rotate(360deg); } }
</style>

        <div style="text-align: center; margin-bottom: var(--space-xl);">
            <div class="icon-circle-wrap">
                <div class="icon-circle-glow"></div>
                <div class="icon-circle-inner"><i class="fas fa-user-plus"></i></div>
            </div>
            <h2 style="font-size: 2rem; font-weight: 700; color: var(--near-black); margin-bottom: 6px;">Create an Account</h2>
            <p style="color: var(--medium-gray);">Join Little Steps to find the best childcare</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-error" style="animation: shake 0.5s;">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="register.php" class="needs-validation">
            <?php csrfField(); ?>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-md);">
                <div class="form-group">
                    <label class="form-label">First Name</label>
                    <input type="text" name="first_name" class="form-control" required value="<?= isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : '' ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Last Name</label>
                    <input type="text" name="last_name" class="form-control" required value="<?= isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : '' ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label">Phone Number</label>
                <input type="tel" name="phone" class="form-control" required value="<?= isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '' ?>">
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-md);">
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required minlength="8">
                    <small style="color: var(--medium-gray); font-size: 12px;">Min. 8 characters</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" required minlength="8">
                </div>
            </div>
            
            <div class="form-group" style="display: flex; align-items: flex-start; gap: 8px; margin-top: var(--space-sm);">
                <input type="checkbox" id="terms" name="terms" required style="accent-color: var(--main-pink); width: 16px; height: 16px; margin-top: 4px;">
                <label for="terms" style="font-size: 14px; color: var(--dark-gray); cursor: pointer;">
                    I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>
                </label>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: var(--space-md); padding: 12px; font-size: 16px;">
                Sign Up
            </button>
        </form>
        
        <div style="margin-top: var(--space-xl); text-align: center; border-top: 1px solid var(--light-pink); padding-top: var(--space-lg);">
            <p style="font-size: 14px;">Already have an account? <a href="login.php" style="font-weight: 600;">Log In</a></p>
            <p style="font-size: 14px; margin-top: var(--space-sm);">Are you a daycare provider? <a href="provider-register.php" style="font-weight: 600;">Register here</a></p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
