<?php
/**
 * Provider Reviews Management
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
requireRole('provider');

$pageTitleHeader = 'Parent Reviews';
$pageTitle = 'Reviews';

$conn = getDBConnection();
$providerId = $_SESSION['user_id'];

// Get center ID
$stmt = $conn->prepare("SELECT id, rating, total_reviews FROM daycare_centers WHERE provider_id = ? LIMIT 1");
$stmt->bind_param("i", $providerId);
$stmt->execute();
$centerResult = $stmt->get_result();
$center = $centerResult->num_rows > 0 ? $centerResult->fetch_assoc() : null;
$centerId = $center['id'] ?? 0;

$reviews = [];
if ($centerId > 0) {
    $revStmt = $conn->prepare("
        SELECT r.*, u.first_name, u.last_name, u.profile_image
        FROM reviews r
        JOIN users u ON r.user_id = u.id
        WHERE r.center_id = ?
        ORDER BY r.created_at DESC
    ");
    $revStmt->bind_param("i", $centerId);
    $revStmt->execute();
    $reviews = $revStmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
$conn->close();

require_once __DIR__ . '/includes/header.php';
?>

<?php if ($centerId == 0): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i> You must <a href="center_profile.php" style="font-weight: 600;">create a center profile</a> to view reviews.
    </div>
<?php else: ?>

    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: var(--space-xl);">
        
        <!-- Summary -->
        <div>
            <div class="card text-center" style="position: sticky; top: 100px;">
                <h3 style="color: var(--dark-gray); margin-bottom: var(--space-md);">Center Rating</h3>
                <div style="font-size: 64px; font-weight: 700; color: var(--near-black); line-height: 1;">
                    <?= $center['rating'] > 0 ? number_format($center['rating'], 1) : '0.0' ?>
                </div>
                <div style="color: #FFC107; font-size: 24px; margin: var(--space-sm) 0;">
                    <?php 
                    $rating = round($center['rating']);
                    for($i = 1; $i <= 5; $i++): 
                        echo '<i class="fas fa-star" style="' . ($i <= $rating ? '' : 'color: var(--light-gray);') . '"></i> ';
                    endfor; 
                    ?>
                </div>
                <p style="color: var(--medium-gray); margin: 0;">Based on <?= $center['total_reviews'] ?> reviews</p>
                
                <hr style="border: 0; border-top: 1px solid var(--light-pink); margin: var(--space-lg) 0;">
                
                <p style="font-size: 14px; color: var(--medium-gray);">
                    Provide great service to improve your rating! Higher ratings increase your visibility in search results.
                </p>
            </div>
        </div>
        
        <!-- Review List -->
        <div>
            <div class="card" style="padding: 0; overflow: hidden;">
                <div style="padding: var(--space-md) var(--space-lg); border-bottom: 1px solid var(--light-pink); background: var(--white);">
                    <h3 style="margin: 0; color: var(--dark-gray); font-size: 18px;">All Reviews</h3>
                </div>
                
                <?php if (count($reviews) > 0): ?>
                    <?php foreach ($reviews as $review): ?>
                        <div style="padding: var(--space-lg); border-bottom: 1px solid var(--light-gray);">
                            <div style="display: flex; gap: var(--space-md); margin-bottom: var(--space-sm);">
                                <img src="<?= $review['profile_image'] ? SITE_URL . '/' . $review['profile_image'] : 'https://ui-avatars.com/api/?name=' . urlencode($review['first_name'].'+'.$review['last_name']) . '&background=FCE4EC&color=E91E63' ?>" alt="Parent" style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover;">
                                
                                <div style="flex-grow: 1;">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                        <h4 style="margin: 0; font-size: 16px;"><?= htmlspecialchars($review['first_name'] . ' ' . $review['last_name']) ?></h4>
                                        <span style="font-size: 12px; color: var(--medium-gray);"><?= date('d M Y', strtotime($review['created_at'])) ?></span>
                                    </div>
                                    <div style="color: #FFC107; font-size: 12px; margin-top: 4px;">
                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star" style="<?= $i <= $review['rating'] ? '' : 'color: var(--light-gray);' ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <p style="margin-bottom: 0; font-size: 14px; color: var(--near-black); padding-left: 64px;">
                                "<?= nl2br(htmlspecialchars($review['review_text'] ?? '')) ?>"
                            </p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: var(--space-3xl) var(--space-md);">
                        <p style="color: var(--medium-gray);">No reviews received yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
    </div>

<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
