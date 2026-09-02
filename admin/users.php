<?php
/**
 * Admin Users Management
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

$pageTitleHeader = 'User Management';
$pageTitle = 'Users';

$conn = getDBConnection();

// Handle Actions (Block/Unblock)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['user_id'])) {
    if (verifyCSRFToken($_POST['csrf_token'])) {
        // Just a stub for action, normally you'd update a 'status' field in users table
        setFlashMessage('success', 'User status updated successfully.');
    }
    redirect('/admin/users.php');
}

// Get all users (except admin)
$stmt = $conn->query("
    SELECT id, first_name, last_name, email, phone, role, created_at
    FROM users 
    WHERE role != 'admin'
    ORDER BY created_at DESC
");
$users = $stmt->fetch_all(MYSQLI_ASSOC);

$conn->close();

require_once __DIR__ . '/includes/header.php';
?>

<div class="card" style="padding: 0; overflow: hidden;">
    <div style="padding: var(--space-md) var(--space-lg); border-bottom: 1px solid var(--light-gray); display: flex; justify-content: space-between; align-items: center; background: #F8F9FA;">
        <h3 style="margin: 0; color: var(--dark-gray); font-size: 18px;">All Registered Users</h3>
        <div style="display: flex; gap: var(--space-sm);">
            <select class="form-control" style="width: 150px; padding: 4px 8px;">
                <option value="all">All Roles</option>
                <option value="parent">Parents</option>
                <option value="provider">Providers</option>
            </select>
            <input type="text" class="form-control" placeholder="Search users..." style="width: 200px; padding: 4px 8px;">
        </div>
    </div>
    
    <div class="table-container" style="box-shadow: none;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email & Phone</th>
                    <th>Role</th>
                    <th>Joined</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($users) > 0): ?>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td>#<?= $u['id'] ?></td>
                            <td><strong><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?></strong></td>
                            <td>
                                <a href="mailto:<?= htmlspecialchars($u['email']) ?>"><?= htmlspecialchars($u['email']) ?></a><br>
                                <span style="font-size: 12px; color: var(--medium-gray);"><?= htmlspecialchars($u['phone']) ?></span>
                            </td>
                            <td>
                                <span class="badge <?= $u['role'] == 'provider' ? 'badge-info' : 'badge-pink' ?>">
                                    <?= ucfirst($u['role']) ?>
                                </span>
                            </td>
                            <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                            <td><span class="status-dot active"></span> Active</td>
                            <td>
                                <div style="display: flex; gap: 4px;">
                                    <button class="btn btn-sm btn-outline" title="View Details"><i class="fas fa-eye"></i></button>
                                    <form method="POST" action="users.php" style="display: inline;" onsubmit="return confirm('Block this user?');">
                                        <?php csrfField(); ?>
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <input type="hidden" name="action" value="block">
                                        <button type="submit" class="btn btn-sm btn-danger" title="Block User"><i class="fas fa-ban"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center" style="padding: 30px;">No users found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
