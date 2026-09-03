<?php
/**
 * Parent Bookings (Active & Upcoming)
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
requireRole('parent');

$pageTitleHeader = 'My Bookings';
$pageTitle = 'My Bookings';

$conn = getDBConnection();
$userId = $_SESSION['user_id'];

// Handle Booking Cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['booking_id'])) {
    if (verifyCSRFToken($_POST['csrf_token'])) {
        $bookingId = (int)$_POST['booking_id'];
        $reason = sanitizeInput($conn, $_POST['cancellation_reason'] ?? 'Cancelled by parent');

        $stmt = $conn->prepare("
            UPDATE bookings SET 
                status = 'cancelled', 
                cancellation_reason = ?, 
                cancelled_by = 'parent', 
                cancelled_at = NOW() 
            WHERE id = ? AND user_id = ? AND status IN ('pending', 'confirmed')
        ");
        $stmt->bind_param("sii", $reason, $bookingId, $userId);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            setFlashMessage('success', 'Booking cancelled successfully.');
        } else {
            setFlashMessage('error', 'Unable to cancel this booking.');
        }
    }
    redirect('/parent/bookings.php');
}

// Get Upcoming and Active Bookings
$stmt = $conn->prepare("
    SELECT b.*, c.name as center_name, c.area, c.city 
    FROM bookings b
    JOIN daycare_centers c ON b.center_id = c.id
    WHERE b.user_id = ? AND b.status IN ('pending', 'confirmed', 'in_progress')
    ORDER BY b.start_datetime ASC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$conn->close();

require_once __DIR__ . '/includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
    <div>
        <h2 style="margin: 0 0 4px 0; color: var(--parent-dark-pink); font-size: 22px;">Active & Upcoming Bookings</h2>
        <p style="margin: 0; color: #757575; font-size: 14px;">Review your child's confirmed schedule and check-in times</p>
    </div>
    <a href="booking_create.php" class="btn btn-primary" style="padding: 10px 22px;">
        <i class="fas fa-plus-circle" style="margin-right: 6px;"></i> Book New Session
    </a>
</div>

<div class="table-container">
    <div class="table-header-bar">
        <h3 class="table-header-title">
            <i class="fas fa-calendar-alt"></i> Scheduled Sessions (<?= count($bookings) ?>)
        </h3>
        <a href="booking_history.php" class="btn btn-secondary" style="padding: 6px 14px; font-size: 13px;">View Past History</a>
    </div>
    
    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Booking Code</th>
                    <th>Daycare Center</th>
                    <th>Child (Age)</th>
                    <th>Schedule Date & Time</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($bookings) > 0): ?>
                    <?php foreach ($bookings as $booking): ?>
                        <tr>
                            <td>
                                <strong style="color: var(--parent-pink);"><?= htmlspecialchars($booking['booking_code']) ?></strong>
                            </td>
                            <td>
                                <strong style="color: #212121;"><?= htmlspecialchars($booking['center_name']) ?></strong><br>
                                <span style="font-size: 12px; color: #757575;"><i class="fas fa-map-marker-alt" style="color: var(--parent-pink);"></i> <?= htmlspecialchars($booking['area'] . ', ' . $booking['city']) ?></span>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($booking['child_name']) ?></strong><br>
                                <span style="font-size: 12px; color: #757575;"><?= $booking['child_age_months'] ?> months • <?= htmlspecialchars($booking['child_gender']) ?></span>
                            </td>
                            <td>
                                <strong><?= formatDate($booking['start_datetime'], 'd M Y') ?></strong><br>
                                <span style="font-size: 12px; color: #757575;">
                                    <?= date('h:i A', strtotime($booking['start_datetime'])) ?> - <?= date('h:i A', strtotime($booking['end_datetime'])) ?>
                                </span>
                            </td>
                            <td>
                                <strong><?= formatCurrency($booking['final_amount']) ?></strong>
                            </td>
                            <td>
                                <span class="badge badge-<?= htmlspecialchars($booking['status']) ?>">
                                    <?= htmlspecialchars($booking['status']) ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <div class="btn-group-action">
                                    <a href="<?= SITE_URL ?>/center-details.php?id=<?= $booking['center_id'] ?>" target="_blank" class="btn-icon btn-icon-view" title="View Daycare Details">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                    <?php if ($booking['status'] === 'pending' || $booking['status'] === 'confirmed'): ?>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Cancel this childcare booking?');">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="cancel">
                                            <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                                            <button type="submit" class="btn-icon btn-icon-delete" title="Cancel Booking">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 48px 0; color: #9E9E9E;">
                            <div style="font-size: 48px; color: var(--parent-pastel-pink); margin-bottom: 12px;">
                                <i class="fas fa-calendar-heart"></i>
                            </div>
                            <h4 style="margin: 0 0 6px 0; color: #212121;">No Active or Upcoming Bookings</h4>
                            <p style="color: #757575; font-size: 14px; margin: 0 0 16px 0;">Find a verified 24x7 daycare or creche for your child in seconds.</p>
                            <a href="booking_create.php" class="btn btn-primary">Book Your First Session</a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
