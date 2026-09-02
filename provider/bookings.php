<?php
/**
 * Provider Bookings Management (Kanban Board)
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
requireRole('provider');

$pageTitleHeader = 'Bookings & Requests';
$pageTitle = 'Bookings';

$conn = getDBConnection();
$providerId = $_SESSION['user_id'];

// Get center ID
$stmt = $conn->prepare("SELECT id FROM daycare_centers WHERE provider_id = ? LIMIT 1");
$stmt->bind_param("i", $providerId);
$stmt->execute();
$centerResult = $stmt->get_result();
$centerId = $centerResult->num_rows > 0 ? $centerResult->fetch_assoc()['id'] : 0;

// Handle Actions (Approve/Reject/Complete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['booking_id'])) {
    if (verifyCSRFToken($_POST['csrf_token'])) {
        $bookingId = (int)$_POST['booking_id'];
        $action = $_POST['action'];
        $newStatus = '';
        
        switch ($action) {
            case 'approve': $newStatus = 'confirmed'; break;
            case 'reject': $newStatus = 'rejected'; break;
            case 'start': $newStatus = 'in_progress'; break;
            case 'complete': $newStatus = 'completed'; break;
        }
        
        if ($newStatus) {
            $updStmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ? AND center_id = ?");
            $updStmt->bind_param("sii", $newStatus, $bookingId, $centerId);
            if ($updStmt->execute()) {
                setFlashMessage('success', "Booking marked as $newStatus.");
            } else {
                setFlashMessage('error', 'Failed to update booking status.');
            }
        }
    }
    redirect('/provider/bookings.php');
}

// Fetch all bookings for the center
$bookings = ['pending' => [], 'confirmed' => [], 'in_progress' => []];

if ($centerId > 0) {
    $bookStmt = $conn->prepare("
        SELECT b.*, u.first_name, u.last_name, u.phone 
        FROM bookings b
        JOIN users u ON b.parent_id = u.id
        WHERE b.center_id = ? AND b.status IN ('pending', 'confirmed', 'in_progress')
        ORDER BY b.start_time ASC
    ");
    $bookStmt->bind_param("i", $centerId);
    $bookStmt->execute();
    $result = $bookStmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $bookings[$row['status']][] = $row;
    }
}
$conn->close();

require_once __DIR__ . '/includes/header.php';
?>

<?php if ($centerId == 0): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i> You must <a href="center_profile.php" style="font-weight: 600;">create a center profile</a> before managing bookings.
    </div>
<?php else: ?>

    <div class="kanban-board">
        <!-- New Requests -->
        <div class="kanban-column">
            <div class="kanban-column-header">
                New Requests
                <span class="kanban-count"><?= count($bookings['pending']) ?></span>
            </div>
            
            <?php foreach ($bookings['pending'] as $b): ?>
                <div class="booking-card">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                        <h4 style="margin: 0; font-size: 15px;">
                            <a href="booking_details.php?id=<?= $b['id'] ?>" style="color: var(--dark-gray);"><?= htmlspecialchars($b['child_name']) ?> (<?= $b['child_age'] ?>y)</a>
                        </h4>
                        <span style="font-size: 11px; color: var(--medium-gray);"><?= date('d M', strtotime($b['start_time'])) ?></span>
                    </div>
                    
                    <p style="font-size: 12px; color: var(--medium-gray); margin-bottom: 8px;">
                        <i class="fas fa-user-circle"></i> <?= htmlspecialchars($b['first_name'] . ' ' . $b['last_name']) ?>
                    </p>
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--light-pink); padding-top: 8px; margin-top: 8px;">
                        <div style="font-size: 12px; font-weight: 600; color: var(--dark-pink);">
                            <?= date('h:i A', strtotime($b['start_time'])) ?>
                        </div>
                        <div style="display: flex; gap: 4px;">
                            <form method="POST" action="bookings.php" style="display: inline;">
                                <?php csrfField(); ?>
                                <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" class="btn btn-sm btn-success" title="Approve"><i class="fas fa-check"></i></button>
                            </form>
                            <form method="POST" action="bookings.php" style="display: inline;" onsubmit="return confirm('Reject this request?');">
                                <?php csrfField(); ?>
                                <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                <input type="hidden" name="action" value="reject">
                                <button type="submit" class="btn btn-sm btn-danger" title="Reject"><i class="fas fa-times"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Confirmed / Upcoming -->
        <div class="kanban-column">
            <div class="kanban-column-header">
                Confirmed / Upcoming
                <span class="kanban-count"><?= count($bookings['confirmed']) ?></span>
            </div>
            
            <?php foreach ($bookings['confirmed'] as $b): 
                $isToday = date('Y-m-d', strtotime($b['start_time'])) === date('Y-m-d');
            ?>
                <div class="booking-card <?= $isToday ? 'priority-high' : '' ?>">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                        <h4 style="margin: 0; font-size: 15px;">
                            <a href="booking_details.php?id=<?= $b['id'] ?>" style="color: var(--dark-gray);"><?= htmlspecialchars($b['child_name']) ?></a>
                        </h4>
                        <?php if ($isToday): ?>
                            <span class="badge badge-warning" style="font-size: 10px;">Today</span>
                        <?php else: ?>
                            <span style="font-size: 11px; color: var(--medium-gray);"><?= date('d M', strtotime($b['start_time'])) ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <p style="font-size: 12px; color: var(--medium-gray); margin-bottom: 8px;">
                        <i class="fas fa-clock"></i> <?= date('h:i A', strtotime($b['start_time'])) ?> to <?= date('h:i A', strtotime($b['end_time'])) ?>
                    </p>
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--light-pink); padding-top: 8px; margin-top: 8px;">
                        <div style="font-size: 12px;">
                            <?= formatCurrency($b['total_price']) ?>
                        </div>
                        <?php if ($isToday): ?>
                            <form method="POST" action="bookings.php" style="display: inline;">
                                <?php csrfField(); ?>
                                <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                <input type="hidden" name="action" value="start">
                                <button type="submit" class="btn btn-sm btn-info" style="font-size: 11px;">Check-in</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Active / In Progress -->
        <div class="kanban-column">
            <div class="kanban-column-header">
                In Progress (Active)
                <span class="kanban-count"><?= count($bookings['in_progress']) ?></span>
            </div>
            
            <?php foreach ($bookings['in_progress'] as $b): ?>
                <div class="booking-card" style="border-left-color: var(--info);">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                        <h4 style="margin: 0; font-size: 15px;">
                            <a href="booking_details.php?id=<?= $b['id'] ?>" style="color: var(--dark-gray);"><?= htmlspecialchars($b['child_name']) ?></a>
                        </h4>
                        <span class="badge badge-info" style="font-size: 10px; background: rgba(33,150,243,0.1); color: var(--info);">Active</span>
                    </div>
                    
                    <p style="font-size: 12px; color: var(--medium-gray); margin-bottom: 8px;">
                        Parent: <?= htmlspecialchars($b['first_name'] . ' ' . $b['last_name']) ?>
                    </p>
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--light-pink); padding-top: 8px; margin-top: 8px;">
                        <div style="font-size: 11px; color: var(--medium-gray);">
                            Due: <?= date('h:i A', strtotime($b['end_time'])) ?>
                        </div>
                        <form method="POST" action="bookings.php" style="display: inline;">
                            <?php csrfField(); ?>
                            <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                            <input type="hidden" name="action" value="complete">
                            <button type="submit" class="btn btn-sm btn-primary" style="font-size: 11px;">Check-out</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
