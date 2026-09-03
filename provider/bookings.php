<?php
/**
 * Provider Bookings Management
 * Little Steps Childcare Platform
 * Status-Based Action Modals & Full Oversight
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
requireRole('provider');

$pageTitleHeader = 'Booking Requests';
$pageTitle = 'Bookings';

$conn = getDBConnection();
$providerId = $_SESSION['user_id'];

// Handle Actions (Accept, Reject, Complete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['booking_id'])) {
    if (verifyCSRFToken($_POST['csrf_token'])) {
        $bookingId = (int)$_POST['booking_id'];
        $action    = $_POST['action'];

        if ($action === 'accept') {
            $stmt = $conn->prepare("
                UPDATE bookings b
                JOIN daycare_centers c ON b.center_id = c.id
                SET b.status = 'confirmed'
                WHERE b.id = ? AND c.provider_id = ?
            ");
            $stmt->bind_param("ii", $bookingId, $providerId);
            $stmt->execute();

            // Send notification to parent
            $notif = $conn->prepare("
                INSERT INTO notifications (user_id, title, message, type, link)
                SELECT b.user_id, 'Booking Accepted! 🌟', 'Your childcare booking has been accepted by the provider.', 'booking', 'bookings.php'
                FROM bookings b WHERE b.id = ?
            ");
            $notif->bind_param("i", $bookingId);
            $notif->execute();

            setFlashMessage('success', 'Booking accepted and confirmed.');

        } elseif ($action === 'reject') {
            $reason = sanitizeInput($conn, $_POST['rejection_reason'] ?? 'Capacity limit reached');
            $stmt = $conn->prepare("
                UPDATE bookings b
                JOIN daycare_centers c ON b.center_id = c.id
                SET b.status = 'cancelled', b.cancellation_reason = ?, b.cancelled_by = 'provider', b.cancelled_at = NOW()
                WHERE b.id = ? AND c.provider_id = ?
            ");
            $stmt->bind_param("sii", $reason, $bookingId, $providerId);
            $stmt->execute();
            setFlashMessage('warning', 'Booking request rejected.');

        } elseif ($action === 'complete') {
            $stmt = $conn->prepare("
                UPDATE bookings b
                JOIN daycare_centers c ON b.center_id = c.id
                SET b.status = 'completed'
                WHERE b.id = ? AND c.provider_id = ?
            ");
            $stmt->bind_param("ii", $bookingId, $providerId);
            $stmt->execute();
            setFlashMessage('success', 'Childcare session marked as completed.');
        }
    }
    redirect('/provider/bookings.php');
}

// Filters
$statusFilter = sanitizeInput($conn, $_GET['status'] ?? 'all');
$search       = sanitizeInput($conn, $_GET['search'] ?? '');

$sql = "
    SELECT b.*, u.first_name, u.last_name, u.phone as parent_phone, u.email as parent_email,
           c.name as center_name
    FROM bookings b
    JOIN daycare_centers c ON b.center_id = c.id
    JOIN users u ON b.user_id = u.id
    WHERE c.provider_id = $providerId
";

if ($statusFilter !== 'all' && !empty($statusFilter)) {
    $sql .= " AND b.status = '$statusFilter'";
}
if (!empty($search)) {
    $sql .= " AND (b.booking_code LIKE '%$search%' OR b.child_name LIKE '%$search%' OR u.first_name LIKE '%$search%' OR u.last_name LIKE '%$search%')";
}

$sql .= " ORDER BY (CASE WHEN b.status = 'pending' THEN 0 ELSE 1 END), b.created_at DESC";
$bookings = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

$conn->close();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Filter Toolbar -->
<div class="filter-card">
    <form method="GET" class="filter-form">
        <div style="flex: 2; min-width: 200px;">
            <input type="text" name="search" class="filter-input" style="width: 100%;" placeholder="Search code, child name, or parent..." value="<?= htmlspecialchars($search) ?>">
        </div>
        
        <div>
            <select name="status" class="filter-input">
                <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All Requests</option>
                <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>🟡 Pending (Action Required)</option>
                <option value="confirmed" <?= $statusFilter === 'confirmed' ? 'selected' : '' ?>>🟢 Confirmed</option>
                <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>✓ Completed</option>
                <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>✕ Cancelled</option>
            </select>
        </div>
        
        <button type="submit" class="btn btn-primary" style="padding: 9px 18px;">
            <i class="fas fa-filter"></i> Filter
        </button>
        
        <?php if($statusFilter !== 'all' || !empty($search)): ?>
            <a href="bookings.php" class="btn btn-secondary" style="padding: 9px 14px;">Reset</a>
        <?php endif; ?>
    </form>
</div>

<!-- Bookings Table -->
<div class="provider-table-card">
    <div class="provider-table-header">
        <h3><i class="fas fa-calendar-check" style="margin-right: 6px;"></i> Incoming & Scheduled Bookings (<?= count($bookings) ?>)</h3>
    </div>
    
    <div style="overflow-x: auto;">
        <table class="provider-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Parent</th>
                    <th>Child (Age)</th>
                    <th>Center</th>
                    <th>Date & Time</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($bookings)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 36px; color: #9E9E9E;">
                            No booking requests found matching criteria.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach($bookings as $b): ?>
                        <tr style="<?= $b['status'] === 'pending' ? 'background: #FFFDE7;' : '' ?>">
                            <td>
                                <strong style="color: var(--provider-pink);"><?= htmlspecialchars($b['booking_code']) ?></strong>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($b['first_name'] . ' ' . $b['last_name']) ?></strong>
                                <div style="font-size: 11px; color: #757575;"><?= htmlspecialchars($b['parent_phone'] ?? 'N/A') ?></div>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($b['child_name']) ?></strong>
                                <div style="font-size: 11px; color: #757575;"><?= $b['child_age_months'] ?> mo • <?= htmlspecialchars($b['child_gender']) ?></div>
                            </td>
                            <td><?= htmlspecialchars($b['center_name']) ?></td>
                            <td>
                                <div><strong><?= formatDate($b['start_datetime'], 'd M Y') ?></strong></div>
                                <div style="font-size: 11px; color: #757575;">
                                    <?= date('h:i A', strtotime($b['start_datetime'])) ?> - <?= date('h:i A', strtotime($b['end_datetime'])) ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-pink" style="font-size: 11px; text-transform: uppercase;">
                                    <?= htmlspecialchars($b['booking_type']) ?>
                                </span>
                            </td>
                            <td><strong><?= formatCurrency($b['final_amount']) ?></strong></td>
                            <td>
                                <span class="badge badge-<?= htmlspecialchars($b['status']) ?>">
                                    <?= htmlspecialchars($b['status']) ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <div class="btn-group-action">
                                    <!-- Pending Actions: Accept or Reject -->
                                    <?php if ($b['status'] === 'pending'): ?>
                                        <form method="POST" style="display: inline;">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="accept">
                                            <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                            <button type="submit" class="btn btn-success" style="padding: 5px 12px; font-size: 12px;" title="Accept Booking">
                                                <i class="fas fa-check"></i> Accept
                                            </button>
                                        </form>
                                        
                                        <button type="button" class="btn btn-danger" style="padding: 5px 12px; font-size: 12px;" onclick="openRejectModal('<?= $b['id'] ?>', '<?= htmlspecialchars($b['booking_code']) ?>')">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    
                                    <!-- Confirmed Action: Mark Complete -->
                                    <?php elseif ($b['status'] === 'confirmed' || $b['status'] === 'in_progress'): ?>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Mark this childcare session as completed?');">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="complete">
                                            <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                            <button type="submit" class="btn btn-primary" style="padding: 5px 12px; font-size: 12px;" title="Complete Session">
                                                <i class="fas fa-check-double"></i> Complete
                                            </button>
                                        </form>
                                    
                                    <?php else: ?>
                                        <span style="font-size: 12px; color: #9E9E9E;">Closed</span>
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

<!-- Reject Modal -->
<div class="modal-overlay" id="rejectModal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3>Reject Booking Request</h3>
            <button type="button" class="modal-close-btn" onclick="closeRejectModal()">&times;</button>
        </div>
        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="reject">
            <input type="hidden" name="booking_id" id="rejectBookingId">
            
            <div class="modal-body">
                <p style="color: #424242; font-size: 14px;">
                    Are you sure you want to reject booking <strong id="rejectCodeLabel"></strong>?
                </p>
                
                <div class="form-group">
                    <label class="form-label">Reason for Rejection (Visible to Parent)</label>
                    <textarea name="rejection_reason" class="form-control" rows="3" placeholder="e.g. Center capacity fully booked for this time slot..." required></textarea>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeRejectModal()">Cancel</button>
                <button type="submit" class="btn btn-danger">Confirm Rejection</button>
            </div>
        </form>
    </div>
</div>

<script>
function openRejectModal(id, code) {
    document.getElementById('rejectBookingId').value = id;
    document.getElementById('rejectCodeLabel').innerText = code;
    document.getElementById('rejectModal').style.display = 'flex';
}
function closeRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
