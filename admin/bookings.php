<?php
/**
 * Admin Bookings Log
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
requireRole('admin');

$pageTitleHeader = 'Platform Bookings';
$pageTitle = 'Bookings';

$conn = getDBConnection();

// Pagination setup
$page = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Get total count
$countStmt = $conn->query("SELECT COUNT(*) as cnt FROM bookings");
$totalCount = $countStmt->fetch_assoc()['cnt'];
$totalPages = ceil($totalCount / $limit);

// Get bookings
$stmt = $conn->prepare("
    SELECT b.*, c.name as center_name, u.first_name, u.last_name
    FROM bookings b
    JOIN daycare_centers c ON b.center_id = c.id
    JOIN users u ON b.user_id = u.id
    ORDER BY b.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$conn->close();

require_once __DIR__ . '/includes/header.php';
?>

<div class="card" style="padding: 0; overflow: hidden;">
    <div style="padding: var(--space-md) var(--space-lg); border-bottom: 1px solid var(--light-gray); display: flex; justify-content: space-between; align-items: center; background: #F8F9FA;">
        <h3 style="margin: 0; color: var(--dark-gray); font-size: 18px;">All Booking Records</h3>
    </div>
    
    <div class="table-container" style="box-shadow: none;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Date Created</th>
                    <th>Center</th>
                    <th>Parent</th>
                    <th>Child (Age)</th>
                    <th>Schedule</th>
                    <th>Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($bookings) > 0): ?>
                    <?php foreach ($bookings as $b): ?>
                        <tr>
                            <td>#<?= $b['id'] ?></td>
                            <td style="font-size: 12px; color: var(--medium-gray);"><?= date('d M Y, h:i A', strtotime($b['created_at'])) ?></td>
                            <td><strong><?= htmlspecialchars($b['center_name']) ?></strong></td>
                            <td><?= htmlspecialchars($b['first_name'] . ' ' . $b['last_name']) ?></td>
                            <td><?= htmlspecialchars($b['child_name']) ?> (<?= $b['child_age_months'] ?> mo)</td>
                            <td style="font-size: 12px;">
                                <?= date('d M', strtotime($b['start_datetime'])) ?><br>
                                <span style="color: var(--medium-gray);">
                                    <?= date('h:i A', strtotime($b['start_datetime'])) ?> - <?= date('h:i A', strtotime($b['end_datetime'])) ?>
                                </span>
                            </td>
                            <td><strong><?= formatCurrency($b['final_amount']) ?></strong></td>
                            <td>
                                <?php if ($b['status'] == 'completed'): ?>
                                    <span class="badge badge-success">Completed</span>
                                <?php elseif ($b['status'] == 'in_progress'): ?>
                                    <span class="badge badge-info">In Progress</span>
                                <?php elseif ($b['status'] == 'pending'): ?>
                                    <span class="badge badge-warning">Pending</span>
                                <?php elseif ($b['status'] == 'confirmed'): ?>
                                    <span class="badge badge-primary" style="background: var(--main-pink); color: white;">Confirmed</span>
                                <?php else: ?>
                                    <span class="badge badge-default"><?= ucfirst($b['status']) ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8" class="text-center" style="padding: 30px;">No bookings found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div style="padding: var(--space-md) var(--space-lg); border-top: 1px solid var(--light-gray); display: flex; justify-content: center; gap: 8px;">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?p=<?= $i ?>" class="btn btn-sm <?= $i === $page ? 'btn-primary' : 'btn-outline' ?>" style="padding: 4px 12px;"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
