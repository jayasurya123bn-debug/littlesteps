<?php
/**
 * Forgot Password Page
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/functions.php';

if (isLoggedIn()) {
    redirect('/' . $_SESSION['user_role'] . '/dashboard.php');
}

$pageTitle = 'Reset Password';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $error = "Invalid request token. Please try again.";
    } else {
        $conn = getDBConnection();
        $email = sanitizeInput($conn, $_POST['email']);
        $newPassword = $_POST['new_password'];
        
        if (empty($email) || empty($newPassword)) {
            $error = "Please enter both email and new password.";
        } elseif (strlen($newPassword) < 8) {
            $error = "Password must be at least 8 characters long.";
        } else {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Check users table
            $stmtUser = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmtUser->bind_param("s", $email);
            $stmtUser->execute();
            $resultUser = $stmtUser->get_result();
            
            // Check providers table
            $stmtProvider = $conn->prepare("SELECT id FROM providers WHERE email = ?");
            $stmtProvider->bind_param("s", $email);
            $stmtProvider->execute();
            $resultProvider = $stmtProvider->get_result();
            
            if ($resultUser->num_rows > 0) {
                // Update user password
                $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
                $updateStmt->bind_param("ss", $hashedPassword, $email);
                $updateStmt->execute();
                $success = "Password successfully reset! You can now log in.";
            } elseif ($resultProvider->num_rows > 0) {
                // Update provider password
                $updateStmt = $conn->prepare("UPDATE providers SET password = ? WHERE email = ?");
                $updateStmt->bind_param("ss", $hashedPassword, $email);
                $updateStmt->execute();
                $success = "Password successfully reset! You can now log in.";
            } else {
                // To prevent email enumeration, we show the same message even if email not found, 
                // but since this is a direct reset demo, we'll just say email not found.
                $error = "No account found with that email address.";
            }
        }
        $conn->close();
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<style>
    .bg-blobs { position: relative; background: linear-gradient(135deg, #FFF0F5 0%, #FCE4EC 50%, #FFF0F5 100%); min-height: calc(100vh - 80px); display: flex; align-items: center; justify-content: center; padding: var(--space-2xl) var(--space-md); overflow: hidden; }
    .blob { position: absolute; border-radius: 50%; filter: blur(70px); opacity: 0.55; animation: blob-float 8s ease-in-out infinite; }
    .blob-1 { width: 320px; height: 320px; background: radial-gradient(circle, #FF6B9D, #E91E63); top: -80px; left: -80px; animation-delay: 0s; }
    .blob-2 { width: 280px; height: 280px; background: radial-gradient(circle, #FFD36B, #FF9E6B); top: 10%; right: -60px; animation-delay: 2s; }
    .blob-3 { width: 260px; height: 260px; background: radial-gradient(circle, #6BB2FF, #6B6BFF); bottom: -60px; left: 10%; animation-delay: 4s; }
    .blob-4 { width: 200px; height: 200px; background: radial-gradient(circle, #C96BFF, #FF6BF5); bottom: 5%; right: 5%; animation-delay: 1s; }
    .blob-5 { width: 150px; height: 150px; background: radial-gradient(circle, #6BFFB2, #6BF5D4); top: 50%; left: 40%; animation-delay: 3s; }
    @keyframes blob-float {
        0%, 100% { transform: translateY(0px) scale(1); }
        33% { transform: translateY(-20px) scale(1.05); }
        66% { transform: translateY(10px) scale(0.95); }
    }
</style>

<div class="bg-blobs">
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>
    <div class="blob blob-4"></div>
    <div class="blob blob-5"></div>

    <div class="card" style="width: 100%; max-width: 450px; margin: 0; box-shadow: 0 20px 60px rgba(233,30,99,0.2); backdrop-filter: blur(2px); position: relative; z-index: 2;">
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
                <div class="icon-circle-inner"><i class="fas fa-key"></i></div>
            </div>
            <h2 style="font-size: 2rem; font-weight: 700; color: var(--near-black); margin-bottom: 6px;">Reset Password</h2>
            <p style="color: var(--medium-gray);">Enter your email to set a new password</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-error" style="animation: shake 0.5s;">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
            </div>
            <a href="login.php" class="btn btn-primary" style="width: 100%; text-align: center; margin-top: var(--space-md);">Go to Login</a>
        <?php else: ?>
            <form method="POST" action="forgot-password.php" class="needs-validation">
                <?php csrfField(); ?>
                
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <div style="position: relative;">
                        <i class="fas fa-envelope" style="position: absolute; left: 14px; top: 12px; color: var(--medium-gray);"></i>
                        <input type="email" name="email" class="form-control" style="padding-left: 40px;" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <div style="position: relative;">
                        <i class="fas fa-lock" style="position: absolute; left: 14px; top: 12px; color: var(--medium-gray);"></i>
                        <input type="password" name="new_password" class="form-control" style="padding-left: 40px;" required minlength="8">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-lg mt-3" style="width: 100%;">
                    Reset Password
                </button>
                
                <div style="margin-top: var(--space-xl); text-align: center;">
                    <a href="login.php" style="color: var(--medium-gray); font-size: 14px; text-decoration: none;">
                        <i class="fas fa-arrow-left"></i> Back to Login
                    </a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
