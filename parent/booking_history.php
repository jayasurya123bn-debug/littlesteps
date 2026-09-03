<?php
/**
 * Parent Booking History
 * Little Steps Childcare Platform
 * Filterable Completed & Cancelled Bookings with Re-book Action
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
requireRole('parent');

$pageTitleHeader = 'Booking History';
$pageTitle = 'Booking History';

$conn = getDBConnection();
$userId = $_SESSION['user_id'];

// Filters
$statusFilter = sanitizeInput($conn, $_GET['status'] ?? 'all');
$search       = sanitizeInput($conn, $_GET['search'] ?? '');

$sql = "
    SELECT b.*, c.name as center_name, c.area, c.city,
           (SELECT COUNT(*) FROM reviews r WHERE r.booking_id = b.id) as has_review
    FROM bookings b
    JOIN daycare_centers c ON b.center_id = c.id
    WHERE b.user_id = ? AND b.status IN ('completed', 'cancelled', 'no_show')
";

if ($statusFilter !== 'all' && !empty($statusFilter)) {
    $sql .= " AND b.status = '$statusFilter'";
}
if (!empty($search)) {
    $sql .= " AND (b.booking_code LIKE '%$search%' OR b.child_name LIKE '%$search%' OR c.name LIKE '%$search%')";
}

$sql .= " ORDER BY b.start_datetime DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$conn->close();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Filter Toolbar -->
<div class="filter-card">
    <form method="GET" class="filter-form">
        <div style="flex: 2; min-width: 200px;">
            <input type="text" name="search" class="filter-input" style="width: 100%;" placeholder="Search by booking code, center, or child..." value="<?= htmlspecialchars($search) ?>">
        </div>
        
        <div>
            <select name="status" class="filter-input">
                <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All Historical Statuses</option>
                <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>✓ Completed Sessions</option>
                <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>✕ Cancelled</option>
            </select>
        </div>
        
        <button type="submit" class="btn btn-primary" style="padding: 9px 18px;">
            <i class="fas fa-filter"></i> Filter
        </button>
        
        <?php if($statusFilter !== 'all' || !empty($search)): ?>
            <a href="booking_history.php" class="btn btn-secondary" style="padding: 9px 14px;">Reset</a>
        <?php endif; ?>
        
        <button type="button" onclick="window.print()" class="btn btn-secondary" style="margin-left: auto; padding: 9px 16px;">
            <i class="fas fa-print"></i> Print Statement
        </button>
    </form>
</div>

<!-- History Table -->
<div class="table-container">
    <div class="table-header-bar">
        <h3 class="table-header-title">
            <i class="fas fa-history"></i> Past Childcare Sessions (<?= count($bookings) ?>)
        </h3>
    </div>
    
    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Daycare Center</th>
                    <th>Child</th>
                    <th>Session Date</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($bookings)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 48px 0; color: #9E9E9E;">
                            <i class="fas fa-history" style="font-size: 36px; color: var(--parent-pastel-pink); margin-bottom: 10px; display: block;"></i>
                            No past bookings found matching your search.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($bookings as $b): ?>
                        <tr>
                            <td>
                                <strong style="color: var(--parent-pink);"><?= htmlspecialchars($b['booking_code']) ?></strong>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($b['center_name']) ?></strong><br>
                                <span style="font-size: 12px; color: #757575;"><?= htmlspecialchars($b['area'] . ', ' . $b['city']) ?></span>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($b['child_name']) ?></strong><br>
                                <span style="font-size: 11px; color: #757575;"><?= $b['child_age_months'] ?> months</span>
                            </td>
                            <td>
                                <div><strong><?= formatDate($b['start_datetime'], 'd M Y') ?></strong></div>
                                <div style="font-size: 11px; color: #757575;">
                                    <?= date('h:i A', strtotime($b['start_datetime'])) ?> - <?= date('h:i A', strtotime($b['end_datetime'])) ?>
                                </div>
                            </td>
                            <td>
                                <strong><?= formatCurrency($b['final_amount']) ?></strong>
                            </td>
                            <td>
                                <span class="badge badge-<?= htmlspecialchars($b['status']) ?>">
                                    <?= htmlspecialchars($b['status']) ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <div class="btn-group-action">
                                    <!-- Re-book CTA -->
                                    <a href="booking_create.php?center_id=<?= $b['center_id'] ?>" class="btn btn-primary" style="padding: 5px 12px; font-size: 12px;" title="Book Again">
                                        <i class="fas fa-redo"></i> Re-book
                                    </a>
                                    
                                    <!-- Review CTA if completed and not reviewed yet -->
                                    <?php if ($b['status'] === 'completed' && $b['has_review'] == 0): ?>
                                        <a href="reviews.php?booking_id=<?= $b['id'] ?>&center_id=<?= $b['center_id'] ?>" class="btn btn-secondary" style="padding: 5px 12px; font-size: 12px;" title="Write Review">
                                            <i class="fas fa-star"></i> Review
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
