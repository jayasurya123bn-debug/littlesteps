<?php
/**
 * Parent Reviews Page
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
requireRole('parent');

$pageTitleHeader = 'My Reviews';
$pageTitle = 'Reviews';

$conn = getDBConnection();
$userId = $_SESSION['user_id'];

// Handle new review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    if (verifyCSRFToken($_POST['csrf_token'])) {
        $bookingId = (int)$_POST['booking_id'];
        $centerId = (int)$_POST['center_id'];
        $rating = (int)$_POST['rating'];
        $comment = sanitizeInput($conn, $_POST['comment']);
        
        // Check if booking belongs to user
        $checkStmt = $conn->prepare("SELECT id FROM bookings WHERE id = ? AND parent_id = ? AND center_id = ?");
        $checkStmt->bind_param("iii", $bookingId, $userId, $centerId);
        $checkStmt->execute();
        
        if ($checkStmt->get_result()->num_rows > 0 && $rating >= 1 && $rating <= 5) {
            $insertStmt = $conn->prepare("INSERT INTO reviews (parent_id, center_id, booking_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
            $insertStmt->bind_param("iiiis", $userId, $centerId, $bookingId, $rating, $comment);
            
            if ($insertStmt->execute()) {
                // Update center's average rating
                $updateRatingStmt = $conn->prepare("
                    UPDATE daycare_centers 
                    SET rating = (SELECT AVG(rating) FROM reviews WHERE center_id = ?),
                        total_reviews = (SELECT COUNT(*) FROM reviews WHERE center_id = ?)
                    WHERE id = ?
                ");
                $updateRatingStmt->bind_param("iii", $centerId, $centerId, $centerId);
                $updateRatingStmt->execute();
                
                setFlashMessage('success', 'Your review has been submitted successfully.');
            } else {
                setFlashMessage('error', 'Failed to submit review.');
            }
        } else {
            setFlashMessage('error', 'Invalid booking details.');
        }
    }
    redirect('/parent/reviews.php');
}

// Get User's Reviews
$stmt = $conn->prepare("
    SELECT r.*, c.name as center_name, b.start_time as booking_date
    FROM reviews r
    JOIN daycare_centers c ON r.center_id = c.id
    LEFT JOIN bookings b ON r.booking_id = b.id
    WHERE r.parent_id = ?
    ORDER BY r.created_at DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$conn->close();

require_once __DIR__ . '/includes/header.php';
?>

<div class="card" style="padding: 0; overflow: hidden; max-width: 900px; margin: 0 auto;">
    <div style="padding: var(--space-md) var(--space-lg); border-bottom: 1px solid var(--light-pink); background: var(--baby-pink);">
        <h3 style="margin: 0; color: var(--dark-pink); font-size: 18px;">Reviews You've Written</h3>
    </div>
    
    <div>
        <?php if (count($reviews) > 0): ?>
            <?php foreach ($reviews as $review): ?>
                <div style="padding: var(--space-lg); border-bottom: 1px solid var(--light-gray);">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                        <h4 style="font-size: 16px; margin: 0;">
                            <a href="center-details.php?id=<?= $review['center_id'] ?>" style="color: var(--dark-gray);"><?= htmlspecialchars($review['center_name']) ?></a>
                        </h4>
                        <div style="color: #FFC107; font-size: 14px;">
                            <?php for($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star" style="<?= $i <= $review['rating'] ? '' : 'color: var(--light-gray);' ?>"></i>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <p style="font-size: 14px; margin-bottom: var(--space-sm); color: var(--near-black);">
                        "<?= nl2br(htmlspecialchars($review['comment'])) ?>"
                    </p>
                    
                    <div style="display: flex; gap: var(--space-md); font-size: 12px; color: var(--medium-gray);">
                        <span><i class="fas fa-calendar-check"></i> Care date: <?= $review['booking_date'] ? date('d M Y', strtotime($review['booking_date'])) : 'N/A' ?></span>
                        <span><i class="far fa-clock"></i> Reviewed on: <?= date('d M Y', strtotime($review['created_at'])) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="text-align: center; padding: var(--space-3xl) var(--space-md);">
                <div style="font-size: 48px; color: var(--light-pink); margin-bottom: var(--space-md);">
                    <i class="fas fa-star-half-alt"></i>
                </div>
                <p style="color: var(--medium-gray); margin-bottom: var(--space-md);">You haven't written any reviews yet.</p>
                <a href="booking_history.php" class="btn btn-outline btn-sm">Review Past Bookings</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
