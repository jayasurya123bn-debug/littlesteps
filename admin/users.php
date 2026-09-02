<?php
/**
 * Admin Users Management (Parents + Providers)
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
requireRole('admin');

$pageTitleHeader = 'User Management';
$pageTitle = 'Users';

$conn = getDBConnection();

// Handle Block/Unblock actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['user_id'])) {
    if (verifyCSRFToken($_POST['csrf_token'])) {
        $targetId  = (int)$_POST['user_id'];
        $userType  = $_POST['user_type'] ?? 'user'; // 'user' or 'provider'
        $action    = $_POST['action'];
        $newActive = ($action === 'block') ? 0 : 1;

        if ($userType === 'provider') {
            $upd = $conn->prepare("UPDATE providers SET is_active = ? WHERE id = ?");
        } else {
            $upd = $conn->prepare("UPDATE users SET is_active = ? WHERE id = ?");
        }
        $upd->bind_param("ii", $newActive, $targetId);
        $upd->execute();
        $msg = ($action === 'block') ? 'User blocked.' : 'User unblocked.';
        setFlashMessage('success', $msg);
    }
    redirect('/admin/users.php');
}

// Fetch parents from users table
$parents = $conn->query("
    SELECT id, first_name AS name, '' AS business_name, email, phone, role,
           is_active, created_at, 'user' AS source
    FROM users
    WHERE role != 'admin'
    ORDER BY created_at DESC
")->fetch_all(MYSQLI_ASSOC);

// Fetch providers from providers table
$providers = $conn->query("
    SELECT id, owner_name AS name, business_name, email, phone, 'provider' AS role,
           is_active, created_at, 'provider' AS source
    FROM providers
    ORDER BY created_at DESC
")->fetch_all(MYSQLI_ASSOC);

// Merge all users
$allUsers = array_merge($parents, $providers);

// Sort by created_at DESC
usort($allUsers, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));

$conn->close();
require_once __DIR__ . '/includes/header.php';
?>

<div class="card" style="padding: 0; overflow: hidden;">
    <div style="padding: var(--space-md) var(--space-lg); border-bottom: 1px solid var(--light-gray); display: flex; justify-content: space-between; align-items: center; background: #F8F9FA;">
        <h3 style="margin: 0; color: var(--dark-gray); font-size: 18px;">All Registered Users</h3>
        <div style="display: flex; gap: var(--space-sm);">
            <select id="roleFilter" class="form-control" style="width: 150px; padding: 4px 8px;" onchange="filterTable()">
                <option value="all">All Roles</option>
                <option value="parent">Parents</option>
                <option value="provider">Providers</option>
            </select>
            <input type="text" id="userSearch" class="form-control" placeholder="Search users..." style="width: 220px; padding: 4px 8px;" oninput="filterTable()">
        </div>
    </div>

    <div class="table-container" style="box-shadow: none;">
        <table class="admin-table" id="usersTable">
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
                <?php if (count($allUsers) > 0): ?>
                    <?php foreach ($allUsers as $u): ?>
                        <tr data-role="<?= htmlspecialchars($u['role']) ?>">
                            <td>#<?= $u['id'] ?></td>
                            <td>
                                <strong><?= htmlspecialchars($u['name']) ?></strong>
                                <?php if (!empty($u['business_name'])): ?>
                                    <br><span style="font-size: 12px; color: var(--medium-gray);"><?= htmlspecialchars($u['business_name']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="mailto:<?= htmlspecialchars($u['email']) ?>"><?= htmlspecialchars($u['email']) ?></a><br>
                                <span style="font-size: 12px; color: var(--medium-gray);"><?= htmlspecialchars($u['phone'] ?? '') ?></span>
                            </td>
                            <td>
                                <span class="badge <?= $u['role'] == 'provider' ? 'badge-info' : 'badge-pink' ?>">
                                    <?= ucfirst($u['role']) ?>
                                </span>
                            </td>
                            <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                            <td>
                                <?php if ($u['is_active']): ?>
                                    <span class="status-dot active"></span> Active
                                <?php else: ?>
                                    <span class="status-dot inactive"></span> Blocked
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 4px;">
                                    <?php if ($u['is_active']): ?>
                                        <form method="POST" action="users.php" style="display:inline;" onsubmit="return confirm('Block this user?');">
                                            <?php csrfField(); ?>
                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                            <input type="hidden" name="user_type" value="<?= $u['source'] ?>">
                                            <input type="hidden" name="action" value="block">
                                            <button type="submit" class="btn btn-sm btn-danger" title="Block User"><i class="fas fa-ban"></i></button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" action="users.php" style="display:inline;" onsubmit="return confirm('Unblock this user?');">
                                            <?php csrfField(); ?>
                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                            <input type="hidden" name="user_type" value="<?= $u['source'] ?>">
                                            <input type="hidden" name="action" value="unblock">
                                            <button type="submit" class="btn btn-sm btn-success" title="Unblock User"><i class="fas fa-check-circle"></i></button>
                                        </form>
                                    <?php endif; ?>
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

<script>
function filterTable() {
    const roleFilter = document.getElementById('roleFilter').value;
    const search = document.getElementById('userSearch').value.toLowerCase();
    document.querySelectorAll('#usersTable tbody tr').forEach(row => {
        const role = row.dataset.role || '';
        const text = row.textContent.toLowerCase();
        const matchRole = roleFilter === 'all' || role === roleFilter;
        const matchSearch = text.includes(search);
        row.style.display = (matchRole && matchSearch) ? '' : 'none';
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
