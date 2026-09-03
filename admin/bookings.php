<?php
/**
 * Admin Comprehensive Bookings Management
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
requireRole('admin');

$pageTitleHeader = 'Bookings Oversight';
$pageTitle = 'Manage Bookings';

$conn = getDBConnection();

// Handle Status / Refund / Cancel action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['booking_id'])) {
    if (verifyCSRFToken($_POST['csrf_token'])) {
        $bookingId = (int)$_POST['booking_id'];
        $action    = $_POST['action'];

        if ($action === 'refund') {
            $stmt = $conn->prepare("UPDATE bookings SET payment_status = 'refunded', status = 'cancelled', cancelled_by = 'admin', cancelled_at = NOW() WHERE id = ?");
            $stmt->bind_param("i", $bookingId);
            $stmt->execute();
            setFlashMessage('success', 'Booking marked as refunded and cancelled.');
            
        } elseif ($action === 'cancel') {
            $reason = sanitizeInput($conn, $_POST['cancellation_reason'] ?? 'Administrative cancellation');
            $stmt = $conn->prepare("UPDATE bookings SET status = 'cancelled', cancellation_reason = ?, cancelled_by = 'admin', cancelled_at = NOW() WHERE id = ?");
            $stmt->bind_param("si", $reason, $bookingId);
            $stmt->execute();
            setFlashMessage('warning', 'Booking cancelled by administrator.');
            
        } elseif ($action === 'update_status') {
            $newStatus = sanitizeInput($conn, $_POST['new_status']);
            $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $newStatus, $bookingId);
            $stmt->execute();
            setFlashMessage('success', 'Booking status updated to ' . $newStatus);
        }
    }
    redirect('/admin/bookings.php');
}

// Search and Filter variables
$search        = sanitizeInput($conn, $_GET['search'] ?? '');
$statusFilter  = sanitizeInput($conn, $_GET['status'] ?? '');
$paymentFilter = sanitizeInput($conn, $_GET['payment_status'] ?? '');
$typeFilter    = sanitizeInput($conn, $_GET['type'] ?? '');

$sql = "SELECT b.*, u.first_name, u.last_name, u.email as parent_email, u.phone as parent_phone,
               c.name as center_name, p.business_name
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN daycare_centers c ON b.center_id = c.id
        JOIN providers p ON c.provider_id = p.id
        WHERE 1=1";

if (!empty($search)) {
    $sql .= " AND (b.booking_code LIKE '%$search%' OR b.child_name LIKE '%$search%' OR u.first_name LIKE '%$search%' OR u.last_name LIKE '%$search%' OR c.name LIKE '%$search%')";
}
if (!empty($statusFilter)) {
    $sql .= " AND b.status = '$statusFilter'";
}
if (!empty($paymentFilter)) {
    $sql .= " AND b.payment_status = '$paymentFilter'";
}
if (!empty($typeFilter)) {
    $sql .= " AND b.booking_type = '$typeFilter'";
}

$sql .= " ORDER BY b.created_at DESC";
$bookings = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

$conn->close();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Filter Card -->
<div class="filter-card">
    <form method="GET" class="filter-form">
        <div style="flex: 2; min-width: 200px;">
            <input type="text" name="search" class="filter-input" style="width: 100%;" placeholder="Search code, child, parent, or center..." value="<?= htmlspecialchars($search) ?>">
        </div>
        
        <div>
            <select name="status" class="filter-input">
                <option value="">All Statuses</option>
                <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="confirmed" <?= $statusFilter === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                <option value="in_progress" <?= $statusFilter === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>Completed</option>
                <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
        </div>
        
        <div>
            <select name="payment_status" class="filter-input">
                <option value="">All Payments</option>
                <option value="paid" <?= $paymentFilter === 'paid' ? 'selected' : '' ?>>Paid</option>
                <option value="pending" <?= $paymentFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="refunded" <?= $paymentFilter === 'refunded' ? 'selected' : '' ?>>Refunded</option>
            </select>
        </div>
        
        <div>
            <select name="type" class="filter-input">
                <option value="">All Types</option>
                <option value="hourly" <?= $typeFilter === 'hourly' ? 'selected' : '' ?>>Hourly</option>
                <option value="daily" <?= $typeFilter === 'daily' ? 'selected' : '' ?>>Daily</option>
                <option value="monthly" <?= $typeFilter === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                <option value="emergency" <?= $typeFilter === 'emergency' ? 'selected' : '' ?>>Emergency</option>
            </select>
        </div>
        
        <button type="submit" class="btn btn-primary" style="padding: 9px 18px;">
            <i class="fas fa-filter"></i> Filter
        </button>
        
        <?php if(!empty($search) || !empty($statusFilter) || !empty($paymentFilter) || !empty($typeFilter)): ?>
            <a href="bookings.php" class="btn btn-secondary" style="padding: 9px 14px;">Reset</a>
        <?php endif; ?>
    </form>
</div>

<!-- Bookings Master Table -->
<div class="table-container">
    <div class="table-header-bar">
        <h3 class="table-header-title">
            <i class="fas fa-calendar-check"></i> Platform Bookings Master Log (<?= count($bookings) ?>)
        </h3>
    </div>
    
    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Parent & Child</th>
                    <th>Center / Provider</th>
                    <th>Type</th>
                    <th>Schedule</th>
                    <th>Amount</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($bookings)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 36px; color: #9E9E9E;">
                            No bookings found matching filters.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($bookings as $b): ?>
                        <tr>
                            <td>
                                <strong style="color: var(--admin-pink);"><?= htmlspecialchars($b['booking_code']) ?></strong>
                                <div style="font-size: 11px; color: #757575;"><?= formatDate($b['created_at']) ?></div>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($b['first_name'] . ' ' . $b['last_name']) ?></strong>
                                <div style="font-size: 12px; color: #757575;">Child: <?= htmlspecialchars($b['child_name']) ?></div>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($b['center_name']) ?></strong>
                                <div style="font-size: 12px; color: #757575;"><?= htmlspecialchars($b['business_name']) ?></div>
                            </td>
                            <td>
                                <span class="badge badge-pink" style="font-size: 11px; text-transform: uppercase;">
                                    <?= htmlspecialchars($b['booking_type']) ?>
                                </span>
                            </td>
                            <td>
                                <div style="font-size: 13px; font-weight: 500;"><?= formatDate($b['start_datetime'], 'd M Y') ?></div>
                                <div style="font-size: 11px; color: #757575;">
                                    <?= date('h:i A', strtotime($b['start_datetime'])) ?> - <?= date('h:i A', strtotime($b['end_datetime'])) ?>
                                </div>
                            </td>
                            <td>
                                <strong><?= formatCurrency($b['final_amount']) ?></strong>
                            </td>
                            <td>
                                <span class="badge badge-<?= htmlspecialchars($b['payment_status']) ?>">
                                    <?= htmlspecialchars($b['payment_status']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-<?= htmlspecialchars($b['status']) ?>">
                                    <?= htmlspecialchars($b['status']) ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <div class="btn-group-action">
                                    <!-- Print invoice link -->
                                    <a href="javascript:void(0)" onclick="printInvoice('<?= htmlspecialchars($b['booking_code']) ?>', '<?= htmlspecialchars($b['first_name'] . ' ' . $b['last_name']) ?>', '<?= htmlspecialchars($b['child_name']) ?>', '<?= htmlspecialchars($b['center_name']) ?>', '<?= $b['final_amount'] ?>', '<?= $b['status'] ?>')" class="btn-icon btn-icon-view" title="Print Invoice">
                                        <i class="fas fa-file-invoice"></i>
                                    </a>
                                    
                                    <!-- Refund action if paid and not already refunded -->
                                    <?php if ($b['payment_status'] === 'paid'): ?>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Process full refund for booking <?= $b['booking_code'] ?>?');">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="refund">
                                            <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                            <button type="submit" class="btn-icon btn-icon-delete" title="Process Refund & Cancel">
                                                <i class="fas fa-undo"></i>
                                            </button>
                                        </form>
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

<!-- Invoice Printable Modal/Script -->
<script>
function printInvoice(code, parent, child, center, amount, status) {
    const printWindow = window.open('', '', 'width=700,height=600');
    printWindow.document.write(`
        <html>
        <head>
            <title>Invoice - ${code}</title>
            <style>
                body { font-family: 'Poppins', sans-serif; padding: 40px; color: #212121; }
                .header { text-align: center; border-bottom: 2px solid #E91E63; padding-bottom: 20px; margin-bottom: 30px; }
                .logo { font-size: 24px; font-weight: 700; color: #E91E63; }
                .invoice-details { display: flex; justify-content: space-between; margin-bottom: 30px; font-size: 14px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
                th { background: #FFF0F5; color: #AD1457; text-align: left; padding: 12px; border-bottom: 1px solid #F8BBD9; }
                td { padding: 12px; border-bottom: 1px solid #EEEEEE; font-size: 14px; }
                .total-row { font-size: 18px; font-weight: 700; color: #E91E63; text-align: right; }
                .footer { text-align: center; font-size: 12px; color: #9E9E9E; margin-top: 50px; }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="logo">🌸 LITTLE STEPS CHILDCARE PLATFORM</div>
                <p style="margin: 4px 0; color: #757575;">Official Booking Receipt & Invoice</p>
            </div>
            <div class="invoice-details">
                <div>
                    <strong>Billed To:</strong><br>
                    Parent: ${parent}<br>
                    Child: ${child}
                </div>
                <div style="text-align: right;">
                    <strong>Invoice Code:</strong> ${code}<br>
                    <strong>Date:</strong> ${new Date().toLocaleDateString()}<br>
                    <strong>Status:</strong> ${status.toUpperCase()}
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Daycare Center</th>
                        <th style="text-align: right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Childcare Session Booking Fee</td>
                        <td>${center}</td>
                        <td style="text-align: right;">₹${parseFloat(amount).toFixed(2)}</td>
                    </tr>
                </tbody>
            </table>
            <div class="total-row">
                Total Paid: ₹${parseFloat(amount).toFixed(2)}
            </div>
            <div class="footer">
                Thank you for trusting Little Steps with your child's care!<br>
                www.littlesteps.com • 24x7 Support: support@littlesteps.com
            </div>
            <script>window.print();<\/script>
        </body>
        </html>
    `);
    printWindow.document.close();
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
