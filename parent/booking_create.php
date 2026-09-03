<?php
/**
 * Create New Booking - 4-Step Interactive Booking Wizard
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
requireRole('parent');

$pageTitleHeader = 'Book Childcare';
$pageTitle = 'New Booking';
$error = '';

$centerId = isset($_GET['center_id']) ? (int)$_GET['center_id'] : 0;
$conn = getDBConnection();
$userId = $_SESSION['user_id'];

// Get center details if center_id is provided
$center = null;
if ($centerId > 0) {
    $stmt = $conn->prepare("SELECT * FROM daycare_centers WHERE id = ? AND status = 'active'");
    $stmt->bind_param("i", $centerId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $center = $res->fetch_assoc();
    }
}

// Get all active centers for dropdown
$centers = $conn->query("SELECT id, name, area, city, pricing_hourly, pricing_daily, is_24x7 FROM daycare_centers WHERE status = 'active' ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $error = "Invalid request token.";
    } else {
        $selectedCenterId = (int)$_POST['center_id'];
        $childName        = sanitizeInput($conn, $_POST['child_name']);
        $childAgeMonths   = (int)$_POST['child_age_months'];
        $childGender      = sanitizeInput($conn, $_POST['child_gender'] ?? 'Not specified');
        $specialNeeds     = sanitizeInput($conn, $_POST['special_requirements'] ?? '');
        $pickupPerson     = sanitizeInput($conn, $_POST['pickup_person'] ?? '');
        $pickupPhone      = sanitizeInput($conn, $_POST['pickup_phone'] ?? '');
        $emergencyContact = sanitizeInput($conn, $_POST['emergency_contact'] ?? '');
        $bookingType      = sanitizeInput($conn, $_POST['booking_type'] ?? 'daily');
        $startDate        = $_POST['start_date'];
        $startTime        = $_POST['start_time'];
        $endDate          = !empty($_POST['end_date']) ? $_POST['end_date'] : $startDate;
        $endTime          = $_POST['end_time'];

        if (empty($selectedCenterId) || empty($childName) || empty($childAgeMonths) || empty($startDate) || empty($startTime) || empty($endTime)) {
            $error = "Please fill in all required fields.";
        } else {
            $startDateTime = date('Y-m-d H:i:s', strtotime("$startDate $startTime"));
            $endDateTime   = date('Y-m-d H:i:s', strtotime("$endDate $endTime"));

            if (strtotime($startDateTime) >= strtotime($endDateTime)) {
                $error = "End time must be after start time.";
            } else {
                // Fetch center pricing
                $cRateStmt = $conn->prepare("SELECT pricing_hourly, pricing_daily, pricing_monthly FROM daycare_centers WHERE id = ?");
                $cRateStmt->bind_param("i", $selectedCenterId);
                $cRateStmt->execute();
                $cRate = $cRateStmt->get_result()->fetch_assoc();

                $hours = max(1, ceil((strtotime($endDateTime) - strtotime($startDateTime)) / 3600));
                
                if ($bookingType === 'daily') {
                    $unitPrice = (float)($cRate['pricing_daily'] ?? 850.00);
                    $totalAmount = $unitPrice;
                } elseif ($bookingType === 'hourly') {
                    $unitPrice = (float)($cRate['pricing_hourly'] ?? 150.00);
                    $totalAmount = $hours * $unitPrice;
                } else {
                    $unitPrice = (float)($cRate['pricing_daily'] ?? 1000.00);
                    $totalAmount = $unitPrice * 1.25; // emergency rate
                }

                $bookingCode = 'LS' . strtoupper(substr(uniqid(), -6));

                $ins = $conn->prepare("
                    INSERT INTO bookings (
                        booking_code, user_id, center_id, child_name, child_age_months,
                        child_gender, booking_type, start_datetime, end_datetime,
                        hours_count, unit_price, total_amount, final_amount,
                        special_requirements, pickup_person, pickup_phone, emergency_contact,
                        status, payment_status
                    ) VALUES (
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, ?,
                        ?, ?, ?, ?,
                        ?, ?, ?, ?,
                        'pending', 'paid'
                    )
                ");
                $ins->bind_param(
                    "siisissssddddssss",
                    $bookingCode, $userId, $selectedCenterId, $childName, $childAgeMonths,
                    $childGender, $bookingType, $startDateTime, $endDateTime,
                    $hours, $unitPrice, $totalAmount, $totalAmount,
                    $specialNeeds, $pickupPerson, $pickupPhone, $emergencyContact
                );

                if ($ins->execute()) {
                    // Send notification to Provider
                    $provStmt = $conn->prepare("SELECT provider_id FROM daycare_centers WHERE id = ?");
                    $provStmt->bind_param("i", $selectedCenterId);
                    $provStmt->execute();
                    $providerId = $provStmt->get_result()->fetch_assoc()['provider_id'];

                    $nMsg = "New booking request received for " . $childName . " (Booking #" . $bookingCode . ").";
                    $notif = $conn->prepare("INSERT INTO notifications (provider_id, title, message, type, link) VALUES (?, 'New Booking Received', ?, 'booking', 'bookings.php')");
                    $notif->bind_param("is", $providerId, $nMsg);
                    $notif->execute();

                    setFlashMessage('success', 'Booking confirmed successfully! Booking Code: ' . $bookingCode);
                    redirect('/parent/bookings.php');
                } else {
                    $error = "Database error saving booking: " . $conn->error;
                }
            }
        }
    }
}

$conn->close();
require_once __DIR__ . '/includes/header.php';
?>

<div style="max-width: 820px; margin: 0 auto;">
    <!-- Wizard Progress Indicator -->
    <div class="wizard-steps">
        <div class="wizard-step active">
            <div class="wizard-step-circle">1</div>
            <div class="wizard-step-label">Center & Time</div>
        </div>
        <div class="wizard-step active">
            <div class="wizard-step-circle">2</div>
            <div class="wizard-step-label">Child Details</div>
        </div>
        <div class="wizard-step active">
            <div class="wizard-step-circle">3</div>
            <div class="wizard-step-label">Pickup & Safety</div>
        </div>
        <div class="wizard-step active">
            <div class="wizard-step-circle">4</div>
            <div class="wizard-step-label">Confirmation</div>
        </div>
    </div>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <div class="detail-card">
        <h2 style="color: var(--parent-dark-pink); font-size: 20px; margin: 0 0 20px 0; border-bottom: 1px solid var(--parent-light-pink); padding-bottom: 10px;">
            <i class="fas fa-baby" style="margin-right: 8px;"></i> Book Safe & Verified Childcare
        </h2>
        
        <form method="POST">
            <?= csrfField() ?>
            
            <!-- Step 1: Center Selection -->
            <div style="background: #FFF0F5; padding: 18px; border-radius: 12px; border: 1px solid var(--parent-pastel-pink); margin-bottom: 20px;">
                <h4 style="margin: 0 0 12px 0; color: var(--parent-pink);">Step 1: Select Daycare Facility</h4>
                
                <?php if ($center): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong style="font-size: 16px; color: #212121;"><?= htmlspecialchars($center['name']) ?></strong>
                            <div style="font-size: 13px; color: #757575;">
                                <?= htmlspecialchars($center['area'] . ', ' . $center['city']) ?> • Daily Rate: <?= formatCurrency($center['pricing_daily']) ?>
                            </div>
                        </div>
                        <input type="hidden" name="center_id" value="<?= $center['id'] ?>">
                        <a href="centers.php" class="btn btn-secondary" style="padding: 6px 14px; font-size: 12px;">Change Center</a>
                    </div>
                <?php else: ?>
                    <div class="form-group">
                        <label class="form-label">Choose Center *</label>
                        <select name="center_id" class="form-control" required id="centerSelector">
                            <option value="">Select a childcare center...</option>
                            <?php foreach ($centers as $c): ?>
                                <option value="<?= $c['id'] ?>">
                                    <?= htmlspecialchars($c['name'] . ' (' . $c['area'] . ', ' . $c['city'] . ')') ?> - <?= formatCurrency($c['pricing_daily']) ?>/day
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Step 2: Schedule & Care Type -->
            <div style="margin-bottom: 20px;">
                <h4 style="margin: 0 0 12px 0; color: var(--parent-pink);">Step 2: Date, Time & Care Tier</h4>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <div class="form-group">
                        <label class="form-label">Care Tier *</label>
                        <select name="booking_type" class="form-control">
                            <option value="daily">Full Day Care</option>
                            <option value="hourly">Hourly Care</option>
                            <option value="emergency">24×7 Emergency Care</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Start Date *</label>
                        <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Drop-off Time *</label>
                        <input type="time" name="start_time" class="form-control" value="08:30" required>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label">Pickup Date</label>
                        <input type="date" name="end_date" class="form-control" value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Pickup Time *</label>
                        <input type="time" name="end_time" class="form-control" value="17:30" required>
                    </div>
                </div>
            </div>
            
            <!-- Step 3: Child Details -->
            <div style="margin-bottom: 20px;">
                <h4 style="margin: 0 0 12px 0; color: var(--parent-pink);">Step 3: Child Information</h4>
                
                <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <div class="form-group">
                        <label class="form-label">Child's Full Name *</label>
                        <input type="text" name="child_name" class="form-control" placeholder="e.g. Aarav Sharma" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Age (Months) *</label>
                        <input type="number" name="child_age_months" class="form-control" placeholder="e.g. 24" min="1" max="144" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Gender</label>
                        <select name="child_gender" class="form-control">
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Allergies, Dietary Needs or Medical Instructions</label>
                    <textarea name="special_requirements" class="form-control" rows="2" placeholder="e.g. Peanut allergy, nap time routine, warm milk at 3 PM..."></textarea>
                </div>
            </div>
            
            <!-- Step 4: Pickup Person & Safety Authorization -->
            <div style="margin-bottom: 24px;">
                <h4 style="margin: 0 0 12px 0; color: var(--parent-pink);">Step 4: Authorized Pickup Person & Contact</h4>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label">Authorized Pickup Person</label>
                        <input type="text" name="pickup_person" class="form-control" placeholder="Name of parent or guardian">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Pickup Contact Phone</label>
                        <input type="text" name="pickup_phone" class="form-control" placeholder="+91 98765 43210">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Emergency Phone</label>
                        <input type="text" name="emergency_contact" class="form-control" placeholder="Doctor or relative phone">
                    </div>
                </div>
            </div>
            
            <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--parent-light-pink); padding-top: 20px;">
                <a href="bookings.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary" style="padding: 12px 32px; font-size: 15px;">
                    <i class="fas fa-lock" style="margin-right: 6px;"></i> Confirm & Book Session
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
