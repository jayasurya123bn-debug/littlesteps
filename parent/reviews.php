<?php
/**
 * Parent Reviews Page
 * Little Steps Childcare Platform
 * Review Submission with Star Rating Picker & Provider Responses
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
requireRole('parent');

$pageTitleHeader = 'Reviews & Ratings';
$pageTitle = 'My Reviews';

$conn = getDBConnection();
$userId = $_SESSION['user_id'];

// Handle Actions (Submit, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verifyCSRFToken($_POST['csrf_token'])) {
        $action = $_POST['action'] ?? '';

        if ($action === 'create') {
            $centerId   = (int)$_POST['center_id'];
            $bookingId  = !empty($_POST['booking_id']) ? (int)$_POST['booking_id'] : null;
            $rating     = (int)$_POST['rating'];
            $title      = sanitizeInput($conn, $_POST['title'] ?? 'Parent Review');
            $reviewText = sanitizeInput($conn, $_POST['review_text']);

            if ($centerId > 0 && $rating >= 1 && $rating <= 5 && !empty($reviewText)) {
                $stmt = $conn->prepare("
                    INSERT INTO reviews (user_id, center_id, booking_id, rating, title, review_text, status)
                    VALUES (?, ?, ?, ?, ?, ?, 'approved')
                ");
                $stmt->bind_param("iiiiss", $userId, $centerId, $bookingId, $rating, $title, $reviewText);
                
                if ($stmt->execute()) {
                    // Recalculate center rating
                    $updRating = $conn->prepare("
                        UPDATE daycare_centers 
                        SET rating = (SELECT AVG(rating) FROM reviews WHERE center_id = ? AND status = 'approved'),
                            total_reviews = (SELECT COUNT(*) FROM reviews WHERE center_id = ? AND status = 'approved')
                        WHERE id = ?
                    ");
                    $updRating->bind_param("iii", $centerId, $centerId, $centerId);
                    $updRating->execute();

                    setFlashMessage('success', 'Your review has been published!');
                } else {
                    setFlashMessage('error', 'Failed to submit review.');
                }
            } else {
                setFlashMessage('error', 'Please fill in all rating and review fields.');
            }

        } elseif ($action === 'delete') {
            $revId = (int)$_POST['review_id'];
            $stmt = $conn->prepare("DELETE FROM reviews WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $revId, $userId);
            $stmt->execute();
            setFlashMessage('success', 'Review deleted.');
        }
    }
    redirect('/parent/reviews.php');
}

// Fetch Reviews submitted by this parent
$stmt = $conn->prepare("
    SELECT r.*, c.name as center_name, c.area, c.city
    FROM reviews r
    JOIN daycare_centers c ON r.center_id = c.id
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch centers completed by this parent for the review modal dropdown
$completedCenters = $conn->query("
    SELECT DISTINCT c.id, c.name, b.id as booking_id
    FROM bookings b
    JOIN daycare_centers c ON b.center_id = c.id
    WHERE b.user_id = $userId AND b.status = 'completed'
")->fetch_all(MYSQLI_ASSOC);

$conn->close();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Header Toolbar -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
    <div>
        <h2 style="margin: 0 0 4px 0; color: var(--parent-dark-pink); font-size: 22px;">My Childcare Experience Reviews</h2>
        <p style="margin: 0; color: #757575; font-size: 14px;">Your honest feedback helps fellow parents find safe, caring environments</p>
    </div>
    
    <button type="button" class="btn btn-primary" onclick="document.getElementById('addReviewModal').style.display='flex'">
        <i class="fas fa-star" style="margin-right: 6px;"></i> Write a Review
    </button>
</div>

<!-- Reviews Listing -->
<?php if (empty($reviews)): ?>
    <div class="detail-card" style="text-align: center; padding: 48px 24px;">
        <div style="font-size: 48px; color: var(--parent-pastel-pink); margin-bottom: 12px;">
            <i class="fas fa-comment-dots"></i>
        </div>
        <h3 style="margin: 0 0 6px 0; color: #212121;">No Reviews Shared Yet</h3>
        <p style="color: #757575; max-width: 480px; margin: 0 auto 20px; font-size: 14px;">
            After completing a session at any daycare or creche, you can rate the staff, hygiene, safety, and activities.
        </p>
        <button type="button" class="btn btn-primary" onclick="document.getElementById('addReviewModal').style.display='flex'">
            Write Your First Review
        </button>
    </div>
<?php else: ?>
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <?php foreach ($reviews as $rev): ?>
            <div class="detail-card" style="margin-bottom: 0;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                    <div>
                        <div style="color: #FFC107; font-size: 16px; margin-bottom: 4px;">
                            <?php for($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star" style="color: <?= $i <= $rev['rating'] ? '#FFC107' : '#E0E0E0' ?>;"></i>
                            <?php endfor; ?>
                            <strong style="color: #212121; margin-left: 6px; font-size: 14px;"><?= $rev['rating'] ?>.0</strong>
                        </div>
                        <h3 style="margin: 0 0 4px 0; font-size: 17px; color: #212121;"><?= htmlspecialchars($rev['title']) ?></h3>
                        <div style="font-size: 13px; color: #757575;">
                            Center: <strong><?= htmlspecialchars($rev['center_name']) ?></strong> (<?= htmlspecialchars($rev['area'] . ', ' . $rev['city']) ?>) • <?= formatDate($rev['created_at']) ?>
                        </div>
                    </div>
                    
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this review?');">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="review_id" value="<?= $rev['id'] ?>">
                        <button type="submit" class="btn-icon btn-icon-delete" title="Delete Review">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </form>
                </div>
                
                <p style="color: #424242; font-size: 14px; line-height: 1.6; margin: 0 0 16px 0;">
                    <?= nl2br(htmlspecialchars($rev['review_text'])) ?>
                </p>
                
                <!-- Provider Official Response -->
                <?php if (!empty($rev['provider_reply'])): ?>
                    <div style="background: #FFF0F5; border-left: 4px solid var(--parent-pink); padding: 12px 16px; border-radius: 0 8px 8px 0; font-size: 13px;">
                        <div style="font-weight: 600; color: var(--parent-dark-pink); margin-bottom: 4px;">
                            <i class="fas fa-reply"></i> Official Response from <?= htmlspecialchars($rev['center_name']) ?>:
                        </div>
                        <div style="color: #616161; line-height: 1.5;">
                            <?= nl2br(htmlspecialchars($rev['provider_reply'])) ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Write Review Modal -->
<div class="modal-overlay" id="addReviewModal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3>Write Childcare Experience Review</h3>
            <button type="button" class="modal-close-btn" onclick="document.getElementById('addReviewModal').style.display='none'">&times;</button>
        </div>
        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="create">
            
            <div class="modal-body">
                <div class="form-group" style="margin-bottom: 14px;">
                    <label class="form-label">Select Daycare Center *</label>
                    <select name="center_id" class="form-control" required>
                        <option value="">Choose center...</option>
                        <?php if(!empty($completedCenters)): ?>
                            <?php foreach($completedCenters as $cc): ?>
                                <option value="<?= $cc['id'] ?>"><?= htmlspecialchars($cc['name']) ?></option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="1">Bloom & Blossom Daycare - Indiranagar</option>
                            <option value="2">Little Wonders 24x7 Creche</option>
                        <?php endif; ?>
                    </select>
                </div>
                
                <!-- Star Rating Radio Group -->
                <div class="form-group" style="margin-bottom: 14px;">
                    <label class="form-label">Star Rating *</label>
                    <div style="display: flex; gap: 16px; align-items: center; padding-top: 4px;">
                        <label><input type="radio" name="rating" value="5" checked style="accent-color: var(--parent-pink);"> 5 ★ Excellent</label>
                        <label><input type="radio" name="rating" value="4" style="accent-color: var(--parent-pink);"> 4 ★ Great</label>
                        <label><input type="radio" name="rating" value="3" style="accent-color: var(--parent-pink);"> 3 ★ Average</label>
                        <label><input type="radio" name="rating" value="2" style="accent-color: var(--parent-pink);"> 2 ★ Poor</label>
                    </div>
                </div>
                
                <div class="form-group" style="margin-bottom: 14px;">
                    <label class="form-label">Review Headline / Title *</label>
                    <input type="text" name="title" class="form-control" placeholder="e.g. Wonderful caregivers and spotless play area!" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Detailed Review *</label>
                    <textarea name="review_text" class="form-control" rows="4" placeholder="Describe the atmosphere, caregiver attentiveness, meals, and safety measures..." required></textarea>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('addReviewModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Submit Review</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
