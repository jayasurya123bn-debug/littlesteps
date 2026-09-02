<?php
/**
 * Admin Dashboard
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

$pageTitleHeader = 'System Overview';
$pageTitle = 'Dashboard';

$conn = getDBConnection();

// Get platform stats
$stats = [
    'total_users' => 0,
    'total_providers' => 0,
    'active_centers' => 0,
    'pending_centers' => 0,
    'total_bookings' => 0,
    'total_revenue' => 0
];

// Users & Providers
$q1 = $conn->query("SELECT role, COUNT(*) as cnt FROM users GROUP BY role");
while($row = $q1->fetch_assoc()) {
    if ($row['role'] == 'parent') $stats['total_users'] = $row['cnt'];
    if ($row['role'] == 'provider') $stats['total_providers'] = $row['cnt'];
}

// Centers
$q2 = $conn->query("SELECT status, COUNT(*) as cnt FROM daycare_centers GROUP BY status");
while($row = $q2->fetch_assoc()) {
    if ($row['status'] == 'active') $stats['active_centers'] = $row['cnt'];
    if ($row['status'] == 'pending') $stats['pending_centers'] = $row['cnt'];
}

// Bookings & Revenue
$q3 = $conn->query("SELECT COUNT(*) as cnt, SUM(total_price) as rev FROM bookings WHERE status = 'completed'");
$bStats = $q3->fetch_assoc();
$stats['total_bookings'] = $bStats['cnt'] ?? 0;
// Assuming 10% platform fee
$stats['total_revenue'] = ($bStats['rev'] ?? 0) * 0.10;

// Recent signups
$recentUsers = $conn->query("SELECT first_name, last_name, role, created_at FROM users ORDER BY created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// Pending approvals
$pendingCenters = $conn->query("
    SELECT c.id, c.name, u.first_name, u.last_name, c.created_at 
    FROM daycare_centers c 
    JOIN users u ON c.provider_id = u.id 
    WHERE c.status = 'pending' 
    ORDER BY c.created_at ASC LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

$conn->close();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Stat Cards -->
<div class="stat-card-row">
    <div class="admin-stat-card" style="flex: 1;">
        <div class="admin-stat-icon">
            <i class="fas fa-users"></i>
        </div>
        <div class="admin-stat-details">
            <h3><?= $stats['total_users'] ?></h3>
            <p>Parents</p>
        </div>
    </div>
    
    <div class="admin-stat-card" style="flex: 1;">
        <div class="admin-stat-icon">
            <i class="fas fa-store"></i>
        </div>
        <div class="admin-stat-details">
            <h3><?= $stats['active_centers'] ?></h3>
            <p>Active Centers</p>
        </div>
    </div>
    
    <div class="admin-stat-card" style="flex: 1;">
        <div class="admin-stat-icon">
            <i class="fas fa-calendar-check"></i>
        </div>
        <div class="admin-stat-details">
            <h3><?= $stats['total_bookings'] ?></h3>
            <p>Completed Bookings</p>
        </div>
    </div>
    
    <div class="admin-stat-card" style="flex: 1;">
        <div class="admin-stat-icon">
            <i class="fas fa-rupee-sign"></i>
        </div>
        <div class="admin-stat-details">
            <h3><?= formatCurrency($stats['total_revenue']) ?></h3>
            <p>Platform Revenue</p>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-lg); margin-top: var(--space-xl);">
    
    <!-- Action Required -->
    <div class="card" style="border-top: 4px solid var(--warning);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-md);">
            <h3 style="margin: 0; color: var(--dark-gray);">Action Required: Center Approvals</h3>
            <a href="center_approvals.php" class="btn btn-sm btn-outline">View All</a>
        </div>
        
        <?php if (count($pendingCenters) > 0): ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Center Name</th>
                        <th>Provider</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingCenters as $c): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($c['name']) ?></strong></td>
                            <td><?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name']) ?></td>
                            <td><?= date('d M Y', strtotime($c['created_at'])) ?></td>
                            <td>
                                <a href="center_approvals.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-primary">Review</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div style="text-align: center; padding: var(--space-xl) 0;">
                <i class="fas fa-check-circle text-success" style="font-size: 32px; margin-bottom: 8px;"></i>
                <p style="color: var(--medium-gray);">All caught up! No pending center approvals.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Recent Signups -->
    <div class="card" style="border-top: 4px solid var(--admin-primary);">
        <h3 style="margin: 0; margin-bottom: var(--space-md); color: var(--dark-gray);">Recent Signups</h3>
        
        <table class="admin-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Joined</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentUsers as $u): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?></strong>
                        </td>
                        <td>
                            <span class="badge <?= $u['role'] == 'provider' ? 'badge-info' : 'badge-pink' ?>">
                                <?= ucfirst($u['role']) ?>
                            </span>
                        </td>
                        <td><?= date('d M Y, h:i A', strtotime($u['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div style="text-align: center; margin-top: var(--space-md);">
            <a href="users.php" style="color: var(--admin-primary); font-size: 14px; font-weight: 500;">View All Users &rarr;</a>
        </div>
    </div>
    
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
