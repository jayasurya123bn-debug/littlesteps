<?php
/**
 * Create New Booking
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

$pageTitleHeader = 'New Booking';
$pageTitle = 'New Booking';
$error = '';
$success = false;

$centerId = isset($_GET['center_id']) ? (int)$_GET['center_id'] : 0;
$conn = getDBConnection();
$userId = $_SESSION['user_id'];

// Get center details if center_id is provided
$center = null;
if ($centerId > 0) {
    $stmt = $conn->prepare("SELECT * FROM daycare_centers WHERE id = ? AND status = 'active'");
    $stmt->bind_param("i", $centerId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $center = $result->fetch_assoc();
    }
}

// Get all active centers for dropdown if not selected
$centers = [];
if (!$center) {
    $stmt = $conn->prepare("SELECT id, name, area, city FROM daycare_centers WHERE status = 'active' ORDER BY name ASC");
    $stmt->execute();
    $centers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $error = "Invalid request token.";
    } else {
        $selectedCenterId = (int)$_POST['center_id'];
        $childName = sanitizeInput($conn, $_POST['child_name']);
        $childAge = (int)$_POST['child_age'];
        $specialNeeds = sanitizeInput($conn, $_POST['special_needs']);
        $startDate = $_POST['start_date'];
        $startTime = $_POST['start_time'];
        $endDate = $_POST['end_date'];
        $endTime = $_POST['end_time'];
        
        if (empty($selectedCenterId) || empty($childName) || empty($childAge) || empty($startDate) || empty($startTime) || empty($endDate) || empty($endTime)) {
            $error = "Please fill in all required fields.";
        } else {
            $startDateTime = date('Y-m-d H:i:s', strtotime("$startDate $startTime"));
            $endDateTime = date('Y-m-d H:i:s', strtotime("$endDate $endTime"));
            
            if (strtotime($startDateTime) >= strtotime($endDateTime)) {
                $error = "End time must be after start time.";
            } elseif (strtotime($startDateTime) < time()) {
                $error = "Booking cannot be in the past.";
            } else {
                // Calculate estimated price (simplified logic)
                $hours = ceil((strtotime($endDateTime) - strtotime($startDateTime)) / 3600);
                // Fetch hourly rate
                $rateStmt = $conn->prepare("SELECT p.pricing_hourly FROM providers p JOIN daycare_centers c ON c.provider_id = p.id WHERE c.id = ?");
                $rateStmt->bind_param("i", $selectedCenterId);
                $rateStmt->execute();
                $rateResult = $rateStmt->get_result();
                $hourlyRate = $rateResult->num_rows > 0 ? $rateResult->fetch_assoc()['pricing_hourly'] : 50; // Default 50
                
                $totalPrice = $hours * $hourlyRate;
                $status = 'pending';
                
                $insertStmt = $conn->prepare("INSERT INTO bookings (parent_id, center_id, child_name, child_age, special_needs, start_time, end_time, total_price, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $insertStmt->bind_param("iisssssds", $userId, $selectedCenterId, $childName, $childAge, $specialNeeds, $startDateTime, $endDateTime, $totalPrice, $status);
                
                if ($insertStmt->execute()) {
                    setFlashMessage('success', 'Booking request submitted successfully! Waiting for center confirmation.');
                    redirect('/parent/bookings.php');
                } else {
                    $error = "Database error. Please try again.";
                }
            }
        }
    }
}

$conn->close();
require_once __DIR__ . '/includes/header.php';
?>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <h2 style="color: var(--dark-pink); margin-bottom: var(--space-md); border-bottom: 1px solid var(--light-pink); padding-bottom: 8px;">Book Childcare</h2>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <div class="booking-steps">
        <div class="step active">1<span class="step-label">Details</span></div>
        <div class="step">2<span class="step-label">Review</span></div>
        <div class="step">3<span class="step-label">Payment</span></div>
    </div>
    
    <form method="POST" action="booking_create.php<?= $centerId ? '?center_id='.$centerId : '' ?>" class="needs-validation" style="margin-top: var(--space-xl);">
        <?php csrfField(); ?>
        
        <?php if ($center): ?>
            <div class="alert alert-info" style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <strong>Selected Center:</strong> <?= htmlspecialchars($center['name']) ?>
                </div>
                <input type="hidden" name="center_id" value="<?= $center['id'] ?>">
                <a href="centers.php" class="btn btn-sm btn-outline">Change</a>
            </div>
        <?php else: ?>
            <div class="form-group">
                <label class="form-label">Select Center</label>
                <select name="center_id" class="form-control" required>
                    <option value="">Choose a center...</option>
                    <?php foreach ($centers as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name'] . ' (' . $c['area'] . ', ' . $c['city'] . ')') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-md);">
            <div class="form-group">
                <label class="form-label">Child's Name</label>
                <input type="text" name="child_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Child's Age (Years)</label>
                <input type="number" name="child_age" class="form-control" min="0" max="15" step="0.5" required>
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">Special Needs / Allergies / Notes</label>
            <textarea name="special_needs" class="form-control" rows="2" placeholder="Optional"></textarea>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-md); margin-top: var(--space-lg);">
            <div class="form-group">
                <label class="form-label">Drop-off Date</label>
                <input type="date" name="start_date" class="form-control" required min="<?= date('Y-m-d') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Drop-off Time</label>
                <input type="time" name="start_time" class="form-control" required>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-md);">
            <div class="form-group">
                <label class="form-label">Pick-up Date</label>
                <input type="date" name="end_date" class="form-control" required min="<?= date('Y-m-d') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Pick-up Time</label>
                <input type="time" name="end_time" class="form-control" required>
            </div>
        </div>
        
        <div style="display: flex; justify-content: flex-end; gap: var(--space-md); margin-top: var(--space-xl); border-top: 1px solid var(--light-pink); padding-top: var(--space-lg);">
            <a href="dashboard.php" class="btn btn-outline">Cancel</a>
            <button type="submit" class="btn btn-primary">Proceed to Review</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
