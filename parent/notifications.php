<?php
/**
 * Parent Notifications Page
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

$pageTitleHeader = 'Notifications';
$pageTitle = 'Notifications';

$conn = getDBConnection();
$userId = $_SESSION['user_id'];

// Mark all as read if requested
if (isset($_GET['mark_read'])) {
    $updateStmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND user_type = 'parent'");
    $updateStmt->bind_param("i", $userId);
    $updateStmt->execute();
    redirect('/parent/notifications.php');
}

// Get Notifications
$stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? AND user_type = 'parent' ORDER BY created_at DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$conn->close();

require_once __DIR__ . '/includes/header.php';
?>

<div class="card" style="padding: 0; overflow: hidden; max-width: 800px; margin: 0 auto;">
    <div style="padding: var(--space-md) var(--space-lg); border-bottom: 1px solid var(--light-pink); display: flex; justify-content: space-between; align-items: center; background: var(--baby-pink);">
        <h3 style="margin: 0; color: var(--dark-pink); font-size: 18px;">Recent Notifications</h3>
        <?php if (count($notifications) > 0): ?>
            <a href="?mark_read=1" class="btn btn-sm btn-outline"><i class="fas fa-check-double"></i> Mark all as read</a>
        <?php endif; ?>
    </div>
    
    <div>
        <?php if (count($notifications) > 0): ?>
            <?php foreach ($notifications as $notif): ?>
                <div class="notification-item <?= $notif['is_read'] ? '' : 'unread' ?>">
                    <div class="notification-icon" style="background: <?= $notif['type'] == 'booking' ? 'var(--info-bg)' : ($notif['type'] == 'system' ? 'var(--warning-bg)' : 'var(--success-bg)') ?>; color: <?= $notif['type'] == 'booking' ? 'var(--info)' : ($notif['type'] == 'system' ? 'var(--warning)' : 'var(--success)') ?>;">
                        <?php if ($notif['type'] == 'booking'): ?>
                            <i class="fas fa-calendar-check"></i>
                        <?php elseif ($notif['type'] == 'system'): ?>
                            <i class="fas fa-exclamation-circle"></i>
                        <?php else: ?>
                            <i class="fas fa-bell"></i>
                        <?php endif; ?>
                    </div>
                    <div style="flex-grow: 1;">
                        <h4 style="font-size: 15px; margin-bottom: 4px; color: var(--dark-gray);"><?= htmlspecialchars($notif['title']) ?></h4>
                        <p style="font-size: 14px; color: var(--medium-gray); margin-bottom: 4px;"><?= htmlspecialchars($notif['message']) ?></p>
                        <span style="font-size: 11px; color: var(--medium-gray);"><i class="far fa-clock"></i> <?= date('d M Y, h:i A', strtotime($notif['created_at'])) ?></span>
                    </div>
                    <?php if (!$notif['is_read']): ?>
                        <div style="width: 8px; height: 8px; border-radius: 50%; background: var(--main-pink); margin-top: 8px;"></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="text-align: center; padding: var(--space-3xl) var(--space-md);">
                <div style="font-size: 48px; color: var(--light-pink); margin-bottom: var(--space-md);">
                    <i class="fas fa-bell-slash"></i>
                </div>
                <p style="color: var(--medium-gray);">You have no notifications yet.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
