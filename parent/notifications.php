<?php
/**
 * Parent Notifications Center
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
requireRole('parent');

$pageTitleHeader = 'Notifications';
$pageTitle = 'Notifications';

$conn = getDBConnection();
$userId = $_SESSION['user_id'];

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verifyCSRFToken($_POST['csrf_token'])) {
        $action = $_POST['action'] ?? '';

        if ($action === 'mark_all_read') {
            $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            setFlashMessage('success', 'All notifications marked as read.');
            
        } elseif ($action === 'delete') {
            $notifId = (int)$_POST['notif_id'];
            $stmt = $conn->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $notifId, $userId);
            $stmt->execute();
            setFlashMessage('success', 'Notification removed.');
        }
    }
    redirect('/parent/notifications.php');
}

// Filter
$filter = sanitizeInput($conn, $_GET['filter'] ?? 'all');
$sql = "SELECT * FROM notifications WHERE user_id = $userId";

if ($filter === 'unread') {
    $sql .= " AND is_read = 0";
} elseif ($filter === 'booking') {
    $sql .= " AND type = 'booking'";
} elseif ($filter === 'system') {
    $sql .= " AND type = 'system'";
}

$sql .= " ORDER BY created_at DESC";
$notifications = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

$conn->close();

require_once __DIR__ . '/includes/header.php';
?>

<div style="max-width: 800px; margin: 0 auto;">
    <!-- Filter Tabs & Actions -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
        <div style="display: flex; gap: 8px;">
            <a href="notifications.php" class="btn <?= $filter === 'all' ? 'btn-primary' : 'btn-secondary' ?>" style="padding: 7px 14px; font-size: 13px;">All</a>
            <a href="notifications.php?filter=unread" class="btn <?= $filter === 'unread' ? 'btn-primary' : 'btn-secondary' ?>" style="padding: 7px 14px; font-size: 13px;">Unread</a>
            <a href="notifications.php?filter=booking" class="btn <?= $filter === 'booking' ? 'btn-primary' : 'btn-secondary' ?>" style="padding: 7px 14px; font-size: 13px;">Bookings</a>
            <a href="notifications.php?filter=system" class="btn <?= $filter === 'system' ? 'btn-primary' : 'btn-secondary' ?>" style="padding: 7px 14px; font-size: 13px;">System</a>
        </div>
        
        <?php if (!empty($notifications)): ?>
            <form method="POST" style="display: inline;">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="mark_all_read">
                <button type="submit" class="btn btn-secondary" style="padding: 7px 14px; font-size: 13px;">
                    <i class="fas fa-check-double"></i> Mark All as Read
                </button>
            </form>
        <?php endif; ?>
    </div>
    
    <!-- Notification List Card -->
    <div class="detail-card" style="padding: 0; overflow: hidden;">
        <div class="table-header-bar" style="border-radius: 0;">
            <h3 class="table-header-title">
                <i class="fas fa-bell"></i> Notifications (<?= count($notifications) ?>)
            </h3>
        </div>
        
        <?php if (empty($notifications)): ?>
            <div style="text-align: center; padding: 48px 20px; color: #9E9E9E;">
                <i class="fas fa-bell-slash" style="font-size: 40px; color: var(--parent-pastel-pink); margin-bottom: 12px; display: block;"></i>
                <h4 style="margin: 0 0 6px 0; color: #212121;">No Notifications</h4>
                <p style="margin: 0; font-size: 14px;">You have no unread notifications or system alerts at this moment.</p>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column;">
                <?php foreach($notifications as $n): ?>
                    <div class="notif-item <?= !$n['is_read'] ? 'unread' : '' ?>">
                        <div class="notif-icon">
                            <?php if ($n['type'] === 'booking'): ?>
                                <i class="fas fa-calendar-check"></i>
                            <?php elseif ($n['type'] === 'review'): ?>
                                <i class="fas fa-star"></i>
                            <?php elseif ($n['type'] === 'payment'): ?>
                                <i class="fas fa-receipt"></i>
                            <?php else: ?>
                                <i class="fas fa-bell"></i>
                            <?php endif; ?>
                        </div>
                        
                        <div class="notif-body">
                            <h4 class="notif-title">
                                <?= htmlspecialchars($n['title']) ?>
                                <?php if (!$n['is_read']): ?>
                                    <span style="display: inline-block; width: 8px; height: 8px; background: var(--parent-pink); border-radius: 50%; margin-left: 6px;"></span>
                                <?php endif; ?>
                            </h4>
                            <div class="notif-desc"><?= htmlspecialchars($n['message']) ?></div>
                            <div class="notif-time"><i class="far fa-clock"></i> <?= formatDate($n['created_at'], 'd M Y, h:i A') ?></div>
                        </div>
                        
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <?php if ($n['link']): ?>
                                <a href="<?= htmlspecialchars($n['link']) ?>" class="btn-icon btn-icon-view" title="Open Link">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                            <?php endif; ?>
                            
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Delete notification?');">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="notif_id" value="<?= $n['id'] ?>">
                                <button type="submit" class="btn-icon btn-icon-delete" title="Delete">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
