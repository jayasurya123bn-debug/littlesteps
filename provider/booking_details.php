<?php
/**
 * Booking Details (Provider)
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

$bookingId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($bookingId <= 0) redirect('/provider/bookings.php');

$conn = getDBConnection();
$providerId = $_SESSION['user_id'];

// Get center ID
$stmt = $conn->prepare("SELECT id FROM daycare_centers WHERE provider_id = ? LIMIT 1");
$stmt->bind_param("i", $providerId);
$stmt->execute();
$centerResult = $stmt->get_result();
$centerId = $centerResult->num_rows > 0 ? $centerResult->fetch_assoc()['id'] : 0;

if ($centerId == 0) redirect('/provider/dashboard.php');

// Get Booking Details
$bookStmt = $conn->prepare("
    SELECT b.*, u.first_name, u.last_name, u.email, u.phone, u.address
    FROM bookings b
    JOIN users u ON b.parent_id = u.id
    WHERE b.id = ? AND b.center_id = ?
");
$bookStmt->bind_param("ii", $bookingId, $centerId);
$bookStmt->execute();
$result = $bookStmt->get_result();

if ($result->num_rows === 0) {
    setFlashMessage('error', 'Booking not found or access denied.');
    redirect('/provider/bookings.php');
}

$booking = $result->fetch_assoc();
$conn->close();

$pageTitleHeader = 'Booking #' . $booking['id'];
$pageTitle = 'Booking Details';
require_once __DIR__ . '/includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-md);">
    <a href="bookings.php" class="btn btn-sm btn-outline"><i class="fas fa-arrow-left"></i> Back to Bookings</a>
    <div>
        <?php if ($booking['status'] == 'pending'): ?>
            <form method="POST" action="bookings.php" style="display: inline;">
                <?php csrfField(); ?>
                <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                <input type="hidden" name="action" value="approve">
                <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> Approve</button>
            </form>
            <form method="POST" action="bookings.php" style="display: inline;" onsubmit="return confirm('Reject this request?');">
                <?php csrfField(); ?>
                <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                <input type="hidden" name="action" value="reject">
                <button type="submit" class="btn btn-danger"><i class="fas fa-times"></i> Reject</button>
            </form>
        <?php elseif ($booking['status'] == 'confirmed'): ?>
            <form method="POST" action="bookings.php" style="display: inline;">
                <?php csrfField(); ?>
                <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                <input type="hidden" name="action" value="start">
                <button type="submit" class="btn btn-info"><i class="fas fa-sign-in-alt"></i> Check-in Child</button>
            </form>
        <?php elseif ($booking['status'] == 'in_progress'): ?>
            <form method="POST" action="bookings.php" style="display: inline;">
                <?php csrfField(); ?>
                <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                <input type="hidden" name="action" value="complete">
                <button type="submit" class="btn btn-primary"><i class="fas fa-sign-out-alt"></i> Check-out Child</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: var(--space-lg);">
    
    <!-- Details -->
    <div>
        <div class="card" style="margin-bottom: var(--space-md);">
            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--light-pink); padding-bottom: var(--space-md); margin-bottom: var(--space-md);">
                <div>
                    <h3 style="color: var(--dark-pink); margin-bottom: 4px;">Child Information</h3>
                    <span style="font-size: 24px; font-weight: 700; color: var(--dark-gray);"><?= htmlspecialchars($booking['child_name']) ?></span>
                    <span style="color: var(--medium-gray); margin-left: 8px;">(<?= $booking['child_age'] ?> years old)</span>
                </div>
                <div style="text-align: right;">
                    <span style="font-size: 12px; color: var(--medium-gray); display: block;">Status</span>
                    <span class="badge badge-<?= $booking['status'] == 'confirmed' ? 'success' : ($booking['status'] == 'in_progress' ? 'info' : ($booking['status'] == 'pending' ? 'warning' : 'default')) ?>" style="font-size: 14px; padding: 6px 12px;">
                        <?= ucfirst(str_replace('_', ' ', $booking['status'])) ?>
                    </span>
                </div>
            </div>
            
            <?php if (!empty($booking['special_needs'])): ?>
                <div class="alert alert-warning">
                    <strong><i class="fas fa-exclamation-triangle"></i> Special Needs / Notes:</strong><br>
                    <?= nl2br(htmlspecialchars($booking['special_needs'])) ?>
                </div>
            <?php endif; ?>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-md); margin-top: var(--space-lg);">
                <div style="background: var(--light-gray); padding: var(--space-md); border-radius: var(--radius-md);">
                    <span style="color: var(--medium-gray); font-size: 12px; display: block;">Drop-off (Start)</span>
                    <strong style="font-size: 16px; display: block; color: var(--near-black);"><?= date('d M Y, h:i A', strtotime($booking['start_time'])) ?></strong>
                </div>
                <div style="background: var(--light-gray); padding: var(--space-md); border-radius: var(--radius-md);">
                    <span style="color: var(--medium-gray); font-size: 12px; display: block;">Pick-up (End)</span>
                    <strong style="font-size: 16px; display: block; color: var(--near-black);"><?= date('d M Y, h:i A', strtotime($booking['end_time'])) ?></strong>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Parent & Payment Info -->
    <div>
        <div class="card" style="margin-bottom: var(--space-md);">
            <h3 style="color: var(--dark-pink); margin-bottom: var(--space-md); border-bottom: 1px solid var(--light-pink); padding-bottom: 8px;">Parent Information</h3>
            
            <div style="margin-bottom: var(--space-sm);">
                <span style="color: var(--medium-gray); font-size: 12px; display: block;">Name</span>
                <strong><?= htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']) ?></strong>
            </div>
            <div style="margin-bottom: var(--space-sm);">
                <span style="color: var(--medium-gray); font-size: 12px; display: block;">Phone</span>
                <strong><a href="tel:<?= htmlspecialchars($booking['phone']) ?>" style="color: var(--info);"><i class="fas fa-phone-alt"></i> <?= htmlspecialchars($booking['phone']) ?></a></strong>
            </div>
            <div style="margin-bottom: var(--space-sm);">
                <span style="color: var(--medium-gray); font-size: 12px; display: block;">Email</span>
                <strong><a href="mailto:<?= htmlspecialchars($booking['email']) ?>" style="color: var(--info);"><i class="fas fa-envelope"></i> <?= htmlspecialchars($booking['email']) ?></a></strong>
            </div>
        </div>
        
        <div class="card">
            <h3 style="color: var(--dark-pink); margin-bottom: var(--space-md); border-bottom: 1px solid var(--light-pink); padding-bottom: 8px;">Payment Details</h3>
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-sm);">
                <span style="color: var(--medium-gray);">Total Amount</span>
                <strong style="font-size: 18px; color: var(--near-black);"><?= formatCurrency($booking['total_price']) ?></strong>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="color: var(--medium-gray);">Payment Status</span>
                <?php if ($booking['status'] == 'completed'): ?>
                    <span class="badge badge-success">Paid</span>
                <?php else: ?>
                    <span class="badge badge-warning">Pending</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
