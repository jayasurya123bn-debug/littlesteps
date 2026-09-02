<?php
/**
 * Parent Bookings (Active & Upcoming)
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

$pageTitleHeader = 'My Bookings';
$pageTitle = 'My Bookings';

$conn = getDBConnection();
$userId = $_SESSION['user_id'];

// Get Upcoming and Active Bookings
$stmt = $conn->prepare("
    SELECT b.*, c.name as center_name, c.area, c.city 
    FROM bookings b
    JOIN daycare_centers c ON b.center_id = c.id
    WHERE b.parent_id = ? AND b.status IN ('pending', 'confirmed', 'in_progress')
    ORDER BY b.start_time ASC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$conn->close();

require_once __DIR__ . '/includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-lg);">
    <h2 style="margin: 0; color: var(--dark-pink);">Active & Upcoming Bookings</h2>
    <a href="booking_create.php" class="btn btn-primary"><i class="fas fa-plus"></i> New Booking</a>
</div>

<div class="card" style="padding: 0; overflow: hidden;">
    <div class="table-container" style="box-shadow: none;">
        <table class="table">
            <thead>
                <tr>
                    <th>Center</th>
                    <th>Child</th>
                    <th>Date & Time</th>
                    <th>Total Price</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($bookings) > 0): ?>
                    <?php foreach ($bookings as $booking): ?>
                        <tr>
                            <td>
                                <strong style="color: var(--dark-gray);"><?= htmlspecialchars($booking['center_name']) ?></strong><br>
                                <span style="font-size: 12px; color: var(--medium-gray);"><?= htmlspecialchars($booking['area']) ?></span>
                            </td>
                            <td>
                                <?= htmlspecialchars($booking['child_name']) ?><br>
                                <span style="font-size: 12px; color: var(--medium-gray);"><?= $booking['child_age'] ?> yrs</span>
                            </td>
                            <td>
                                <?= date('d M Y', strtotime($booking['start_time'])) ?><br>
                                <span style="font-size: 12px; color: var(--medium-gray);">
                                    <?= date('h:i A', strtotime($booking['start_time'])) ?> - <?= date('h:i A', strtotime($booking['end_time'])) ?>
                                </span>
                            </td>
                            <td>
                                <strong><?= formatCurrency($booking['total_price']) ?></strong>
                            </td>
                            <td>
                                <?php if ($booking['status'] == 'confirmed'): ?>
                                    <span class="badge badge-success">Confirmed</span>
                                <?php elseif ($booking['status'] == 'in_progress'): ?>
                                    <span class="badge badge-info">In Progress</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a href="center-details.php?id=<?= $booking['center_id'] ?>" class="btn btn-sm btn-info" title="View Center"><i class="fas fa-eye"></i></a>
                                    <?php if ($booking['status'] == 'pending'): ?>
                                        <button class="btn btn-sm btn-danger" onclick="cancelBooking(<?= $booking['id'] ?>)" title="Cancel Booking"><i class="fas fa-times"></i></button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: var(--space-2xl) 0;">
                            <div style="font-size: 48px; color: var(--light-pink); margin-bottom: var(--space-md);">
                                <i class="fas fa-calendar"></i>
                            </div>
                            <p style="color: var(--medium-gray);">You have no active or upcoming bookings.</p>
                            <a href="centers.php" class="btn btn-primary mt-2">Find a Center</a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function cancelBooking(id) {
    if (confirm('Are you sure you want to cancel this booking?')) {
        // Implement cancellation logic via AJAX or form submission
        alert('Cancel functionality would be implemented here.');
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
