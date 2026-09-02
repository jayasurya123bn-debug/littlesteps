<?php
/**
 * Parent Booking History
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

$pageTitleHeader = 'Booking History';
$pageTitle = 'Booking History';

$conn = getDBConnection();
$userId = $_SESSION['user_id'];

// Get Past Bookings (Completed or Cancelled)
$stmt = $conn->prepare("
    SELECT b.*, c.name as center_name, c.area, c.city,
           (SELECT COUNT(*) FROM reviews r WHERE r.booking_id = b.id) as has_review
    FROM bookings b
    JOIN daycare_centers c ON b.center_id = c.id
    WHERE b.parent_id = ? AND (b.status IN ('completed', 'cancelled', 'rejected') OR (b.status = 'confirmed' AND b.end_time < NOW()))
    ORDER BY b.start_time DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$conn->close();

require_once __DIR__ . '/includes/header.php';
?>

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
                        <?php 
                        $actualStatus = $booking['status'];
                        if ($actualStatus == 'confirmed' && strtotime($booking['end_time']) < time()) {
                            $actualStatus = 'completed'; // Auto-complete in view
                        }
                        ?>
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
                                <?php if ($actualStatus == 'completed'): ?>
                                    <span class="badge badge-success" style="background: #E8F5E9; color: #2E7D32;">Completed</span>
                                <?php elseif ($actualStatus == 'cancelled'): ?>
                                    <span class="badge badge-default">Cancelled</span>
                                <?php elseif ($actualStatus == 'rejected'): ?>
                                    <span class="badge badge-danger">Rejected</span>
                                <?php else: ?>
                                    <span class="badge badge-info"><?= ucfirst($actualStatus) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a href="booking_create.php?center_id=<?= $booking['center_id'] ?>" class="btn btn-sm btn-outline" title="Book Again"><i class="fas fa-redo"></i> Rebook</a>
                                    
                                    <?php if ($actualStatus == 'completed' && !$booking['has_review']): ?>
                                        <button class="btn btn-sm btn-primary" onclick="openReviewModal(<?= $booking['id'] ?>, <?= $booking['center_id'] ?>, '<?= htmlspecialchars(addslashes($booking['center_name'])) ?>')" title="Leave Review"><i class="fas fa-star"></i> Review</button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: var(--space-2xl) 0;">
                            <p style="color: var(--medium-gray);">You have no past bookings.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Review Modal -->
<div class="modal-overlay" id="reviewModal">
    <div class="modal-content">
        <div class="modal-header">
            <span>Write a Review</span>
            <button class="modal-close" data-dismiss="modal"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" action="reviews.php">
            <?php csrfField(); ?>
            <input type="hidden" name="booking_id" id="review_booking_id">
            <input type="hidden" name="center_id" id="review_center_id">
            <input type="hidden" name="action" value="create">
            
            <div class="modal-body">
                <p style="margin-bottom: var(--space-sm);">How was your experience at <strong id="review_center_name"></strong>?</p>
                
                <div class="form-group text-center">
                    <div class="star-rating-input" style="justify-content: center; margin-bottom: var(--space-sm);">
                        <input type="radio" id="star5" name="rating" value="5" required />
                        <label for="star5"></label>
                        <input type="radio" id="star4" name="rating" value="4" />
                        <label for="star4"></label>
                        <input type="radio" id="star3" name="rating" value="3" />
                        <label for="star3"></label>
                        <input type="radio" id="star2" name="rating" value="2" />
                        <label for="star2"></label>
                        <input type="radio" id="star1" name="rating" value="1" />
                        <label for="star1"></label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Your Review</label>
                    <textarea name="comment" class="form-control" rows="4" placeholder="Tell us about the care, facilities, and staff..." required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Submit Review</button>
            </div>
        </form>
    </div>
</div>

<script>
function openReviewModal(bookingId, centerId, centerName) {
    document.getElementById('review_booking_id').value = bookingId;
    document.getElementById('review_center_id').value = centerId;
    document.getElementById('review_center_name').textContent = centerName;
    document.getElementById('reviewModal').classList.add('active');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
