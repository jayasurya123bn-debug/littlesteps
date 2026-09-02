<?php
/**
 * Unified Login Page
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('/' . $_SESSION['user_role'] . '/dashboard.php');
}

$pageTitle = 'Login';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $error = "Invalid request token. Please try again.";
    } else {
        $conn = getDBConnection();
        $email = sanitizeInput($conn, $_POST['email']);
        $password = $_POST['password'];
        
        if (empty($email) || empty($password)) {
            $error = "Please enter both email and password.";
        } else {
            // First check users table (Parents/Admins)
            $stmt = $conn->prepare("SELECT id, email, password, first_name, last_name, role, is_active FROM users WHERE email = ? LIMIT 1");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    if ($user['is_active'] == 1) {
                        loginUser($user, $user['role']);
                        
                        // Update last login
                        $updateStmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                        $updateStmt->bind_param("i", $user['id']);
                        $updateStmt->execute();
                        
                        setFlashMessage('success', 'Welcome back, ' . $user['first_name'] . '!');
                        redirect('/' . $user['role'] . '/dashboard.php');
                    } else {
                        $error = "Your account has been deactivated. Please contact support.";
                    }
                } else {
                    $error = "Invalid email or password.";
                }
            } else {
                // Not found in users, check providers table
                $stmt = $conn->prepare("SELECT id, email, password, business_name, owner_name, status, is_active FROM providers WHERE email = ? LIMIT 1");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 1) {
                    $provider = $result->fetch_assoc();
                    if (password_verify($password, $provider['password'])) {
                        if ($provider['status'] === 'approved' && $provider['is_active'] == 1) {
                            loginUser($provider, 'provider');
                            
                            // Update last login
                            $updateStmt = $conn->prepare("UPDATE providers SET last_login = NOW() WHERE id = ?");
                            $updateStmt->bind_param("i", $provider['id']);
                            $updateStmt->execute();
                            
                            setFlashMessage('success', 'Welcome back, ' . $provider['owner_name'] . '!');
                            redirect('/provider/dashboard.php');
                        } elseif ($provider['status'] === 'pending') {
                            $error = "Your provider account is still pending approval.";
                        } else {
                            $error = "Your account is not active. Status: " . ucfirst($provider['status']);
                        }
                    } else {
                        $error = "Invalid email or password.";
                    }
                } else {
                    $error = "Invalid email or password.";
                }
            }
        }
        $conn->close();
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div style="background-color: var(--baby-pink); min-height: calc(100vh - 80px); display: flex; align-items: center; justify-content: center; padding: var(--space-2xl) var(--space-md);">
    <div class="card" style="width: 100%; max-width: 450px; margin: 0; box-shadow: var(--shadow-xl);">
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
                <div class="icon-circle-inner"><i class="fas fa-lock"></i></div>
            </div>
            <h2 style="font-size: 2rem; font-weight: 700; color: var(--near-black); margin-bottom: 6px;">Welcome Back</h2>
            <p style="color: var(--medium-gray);">Login to your Little Steps account</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-error" style="animation: shake 0.5s;">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="login.php" class="needs-validation">
            <?php csrfField(); ?>
            
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <div style="position: relative;">
                    <i class="fas fa-envelope" style="position: absolute; left: 14px; top: 12px; color: var(--medium-gray);"></i>
                    <input type="email" name="email" class="form-control" style="padding-left: 40px;" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label" style="display: flex; justify-content: space-between;">
                    Password
                    <a href="#" style="font-size: 12px; font-weight: normal;">Forgot Password?</a>
                </label>
                <div style="position: relative;">
                    <i class="fas fa-key" style="position: absolute; left: 14px; top: 12px; color: var(--medium-gray);"></i>
                    <input type="password" name="password" class="form-control" style="padding-left: 40px;" required>
                </div>
            </div>
            
            <div class="form-group" style="display: flex; align-items: center; gap: 8px;">
                <input type="checkbox" id="remember" name="remember" style="accent-color: var(--main-pink); width: 16px; height: 16px;">
                <label for="remember" style="font-size: 14px; color: var(--dark-gray); cursor: pointer;">Remember me for 30 days</label>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: var(--space-sm); padding: 12px; font-size: 16px;">
                Log In
            </button>
        </form>
        
        <div style="margin-top: var(--space-xl); text-align: center; border-top: 1px solid var(--light-pink); padding-top: var(--space-lg);">
            <p style="font-size: 14px;">Don't have an account?</p>
            <div style="display: flex; justify-content: center; gap: var(--space-md); margin-top: var(--space-sm);">
                <a href="register.php" class="btn btn-outline btn-sm">I'm a Parent</a>
                <a href="provider-register.php" class="btn btn-outline btn-sm">I'm a Provider</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
