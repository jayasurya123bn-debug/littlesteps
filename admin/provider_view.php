<?php
/**
 * Admin Provider Detailed Inspection & Verification View
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
requireRole('admin');

$providerId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($providerId <= 0) {
    setFlashMessage('error', 'Invalid Provider ID.');
    redirect('/admin/providers.php');
}

$conn = getDBConnection();

// Handle Status or Admin Notes Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verifyCSRFToken($_POST['csrf_token'])) {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'update_status') {
            $newStatus = sanitizeInput($conn, $_POST['status']);
            $isActive = ($newStatus === 'approved') ? 1 : 0;
            $adminNotes = sanitizeInput($conn, $_POST['admin_notes'] ?? '');
            
            $stmt = $conn->prepare("UPDATE providers SET status = ?, is_active = ?, admin_notes = ? WHERE id = ?");
            $stmt->bind_param("sisi", $newStatus, $isActive, $adminNotes, $providerId);
            $stmt->execute();
            
            // Notify Provider
            $msg = "Your provider status has been updated to: " . strtoupper($newStatus);
            $nStmt = $conn->prepare("INSERT INTO notifications (provider_id, title, message, type, link) VALUES (?, 'Provider Status Update', ?, 'info', 'dashboard.php')");
            $nStmt->bind_param("is", $providerId, $msg);
            $nStmt->execute();
            
            setFlashMessage('success', 'Provider status and notes updated.');
            redirect('/admin/provider_view.php?id=' . $providerId);
            
        } elseif ($action === 'verify_doc') {
            $docId = (int)$_POST['doc_id'];
            $docStatus = sanitizeInput($conn, $_POST['doc_status']);
            $adminId = $_SESSION['user_id'];
            
            $stmt = $conn->prepare("UPDATE documents SET status = ?, verified_by = ?, verified_at = NOW() WHERE id = ?");
            $stmt->bind_param("sii", $docStatus, $adminId, $docId);
            $stmt->execute();
            setFlashMessage('success', 'Document verification status updated to ' . $docStatus);
            redirect('/admin/provider_view.php?id=' . $providerId . '&tab=documents');
        }
    }
}

// 1. Fetch Provider Details
$stmt = $conn->prepare("SELECT * FROM providers WHERE id = ?");
$stmt->bind_param("i", $providerId);
$stmt->execute();
$provider = $stmt->get_result()->fetch_assoc();

if (!$provider) {
    $conn->close();
    setFlashMessage('error', 'Provider not found.');
    redirect('/admin/providers.php');
}

// 2. Fetch Centers
$cStmt = $conn->prepare("SELECT * FROM daycare_centers WHERE provider_id = ? ORDER BY created_at DESC");
$cStmt->bind_param("i", $providerId);
$cStmt->execute();
$centers = $cStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// 3. Fetch Caregivers
$cgStmt = $conn->prepare("SELECT * FROM caregivers WHERE provider_id = ? ORDER BY created_at DESC");
$cgStmt->bind_param("i", $providerId);
$cgStmt->execute();
$caregivers = $cgStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// 4. Fetch Documents
$dStmt = $conn->prepare("SELECT * FROM documents WHERE provider_id = ? ORDER BY created_at DESC");
$dStmt->bind_param("i", $providerId);
$dStmt->execute();
$documents = $dStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// 5. Fetch Recent Bookings
$bStmt = $conn->prepare("
    SELECT b.*, u.first_name, u.last_name, c.name as center_name 
    FROM bookings b
    JOIN daycare_centers c ON b.center_id = c.id
    JOIN users u ON b.user_id = u.id
    WHERE c.provider_id = ?
    ORDER BY b.created_at DESC LIMIT 10
");
$bStmt->bind_param("i", $providerId);
$bStmt->execute();
$bookings = $bStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// 6. Fetch Reviews
$rStmt = $conn->prepare("
    SELECT r.*, u.first_name, u.last_name, c.name as center_name
    FROM reviews r
    JOIN daycare_centers c ON r.center_id = c.id
    JOIN users u ON r.user_id = u.id
    WHERE c.provider_id = ?
    ORDER BY r.created_at DESC
");
$rStmt->bind_param("i", $providerId);
$rStmt->execute();
$reviews = $rStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$conn->close();

$activeTab = $_GET['tab'] ?? 'overview';

$pageTitleHeader = 'Provider Details';
$pageTitle = htmlspecialchars($provider['business_name']);
require_once __DIR__ . '/includes/header.php';
?>

<!-- Provider Profile Header Card -->
<div class="detail-card" style="margin-bottom: 24px;">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
        <div style="display: flex; align-items: center; gap: 20px;">
            <div style="width: 72px; height: 72px; border-radius: 12px; background: var(--admin-light-pink); display: flex; align-items: center; justify-content: center; font-size: 32px; color: var(--admin-pink); border: 2px solid var(--admin-pastel-pink);">
                🏢
            </div>
            <div>
                <h2 style="margin: 0 0 6px 0; color: #212121; font-size: 24px;"><?= htmlspecialchars($provider['business_name']) ?></h2>
                <div style="color: #616161; font-size: 14px;">
                    <i class="fas fa-user-tie" style="color: var(--admin-pink);"></i> <?= htmlspecialchars($provider['owner_name']) ?> &nbsp;•&nbsp; 
                    <i class="fas fa-map-marker-alt" style="color: var(--admin-pink);"></i> <?= htmlspecialchars($provider['city']) ?>, <?= htmlspecialchars($provider['state']) ?> &nbsp;•&nbsp;
                    <i class="fas fa-envelope" style="color: var(--admin-pink);"></i> <?= htmlspecialchars($provider['email']) ?>
                </div>
            </div>
        </div>
        
        <div style="display: flex; align-items: center; gap: 12px;">
            <span class="badge badge-<?= htmlspecialchars($provider['status']) ?>" style="font-size: 14px; padding: 6px 16px;">
                Status: <?= strtoupper(htmlspecialchars($provider['status'])) ?>
            </span>
        </div>
    </div>
</div>

<!-- Tabs Navigation -->
<div class="admin-tabs">
    <a href="provider_view.php?id=<?= $providerId ?>&tab=overview" class="admin-tab <?= $activeTab === 'overview' ? 'active' : '' ?>">Overview</a>
    <a href="provider_view.php?id=<?= $providerId ?>&tab=centers" class="admin-tab <?= $activeTab === 'centers' ? 'active' : '' ?>">Centers (<?= count($centers) ?>)</a>
    <a href="provider_view.php?id=<?= $providerId ?>&tab=caregivers" class="admin-tab <?= $activeTab === 'caregivers' ? 'active' : '' ?>">Staff / Caregivers (<?= count($caregivers) ?>)</a>
    <a href="provider_view.php?id=<?= $providerId ?>&tab=documents" class="admin-tab <?= $activeTab === 'documents' ? 'active' : '' ?>">Documents (<?= count($documents) ?>)</a>
    <a href="provider_view.php?id=<?= $providerId ?>&tab=bookings" class="admin-tab <?= $activeTab === 'bookings' ? 'active' : '' ?>">Bookings (<?= count($bookings) ?>)</a>
    <a href="provider_view.php?id=<?= $providerId ?>&tab=reviews" class="admin-tab <?= $activeTab === 'reviews' ? 'active' : '' ?>">Reviews (<?= count($reviews) ?>)</a>
</div>

<!-- Tab Content Area -->
<?php if ($activeTab === 'overview'): ?>
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
        <div class="detail-card">
            <h3 style="margin: 0 0 16px 0; color: var(--admin-dark-pink); font-size: 18px;">About Daycare Provider</h3>
            <p style="line-height: 1.6; color: #424242;"><?= nl2br(htmlspecialchars($provider['description'] ?? 'No description provided.')) ?></p>
            
            <hr style="border: none; border-top: 1px solid var(--admin-light-pink); margin: 20px 0;">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; font-size: 14px;">
                <div><strong>License Number:</strong> <?= htmlspecialchars($provider['license_number'] ?? 'Not provided') ?></div>
                <div><strong>Established:</strong> <?= htmlspecialchars($provider['established_year'] ?? 'N/A') ?></div>
                <div><strong>Primary Phone:</strong> <?= htmlspecialchars($provider['phone']) ?></div>
                <div><strong>Alternate Phone:</strong> <?= htmlspecialchars($provider['alternate_phone'] ?? 'None') ?></div>
                <div><strong>Full Address:</strong> <?= htmlspecialchars($provider['address']) ?>, <?= htmlspecialchars($provider['pincode']) ?></div>
                <div><strong>24x7 Facility:</strong> <?= $provider['is_24x7'] ? 'Yes, Round-the-clock' : 'Standard Daytime' ?></div>
            </div>
        </div>
        
        <!-- Status & Internal Notes -->
        <div class="detail-card">
            <h3 style="margin: 0 0 16px 0; color: var(--admin-dark-pink); font-size: 18px;">Admin Approval & Notes</h3>
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update_status">
                
                <div class="form-group" style="margin-bottom: 14px;">
                    <label class="form-label">Provider Status</label>
                    <select name="status" class="filter-input" style="width: 100%;">
                        <option value="pending" <?= $provider['status'] === 'pending' ? 'selected' : '' ?>>🟡 Pending Review</option>
                        <option value="approved" <?= $provider['status'] === 'approved' ? 'selected' : '' ?>>🟢 Approved & Active</option>
                        <option value="rejected" <?= $provider['status'] === 'rejected' ? 'selected' : '' ?>>🔴 Rejected</option>
                        <option value="suspended" <?= $provider['status'] === 'suspended' ? 'selected' : '' ?>>⚫ Suspended</option>
                    </select>
                </div>
                
                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label">Internal Admin Notes</label>
                    <textarea name="admin_notes" class="form-control" rows="4" placeholder="Notes on licenses, physical inspection, verification history..."><?= htmlspecialchars($provider['admin_notes'] ?? '') ?></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">Save Status & Notes</button>
            </form>
        </div>
    </div>

<?php elseif ($activeTab === 'centers'): ?>
    <div class="table-container">
        <div class="table-header-bar">
            <h3 class="table-header-title">Registered Centers</h3>
        </div>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Center Name</th>
                    <th>Area & City</th>
                    <th>Capacity</th>
                    <th>24x7</th>
                    <th>Pricing (Day)</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($centers)): ?>
                    <tr><td colspan="6" style="text-align: center; padding: 24px; color: #9E9E9E;">No centers added by this provider.</td></tr>
                <?php else: ?>
                    <?php foreach($centers as $c): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($c['name']) ?></strong></td>
                            <td><?= htmlspecialchars($c['area'] . ', ' . $c['city']) ?></td>
                            <td><?= $c['capacity'] ?> Children</td>
                            <td><?= $c['is_24x7'] ? '<span class="badge badge-approved">24x7</span>' : 'Daytime' ?></td>
                            <td><?= formatCurrency($c['pricing_daily']) ?></td>
                            <td><span class="badge badge-<?= htmlspecialchars($c['status']) ?>"><?= htmlspecialchars($c['status']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

<?php elseif ($activeTab === 'caregivers'): ?>
    <div class="table-container">
        <div class="table-header-bar">
            <h3 class="table-header-title">Staff / Caregivers</h3>
        </div>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Qualification</th>
                    <th>Experience</th>
                    <th>Background Verified</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($caregivers)): ?>
                    <tr><td colspan="6" style="text-align: center; padding: 24px; color: #9E9E9E;">No caregivers listed.</td></tr>
                <?php else: ?>
                    <?php foreach($caregivers as $cg): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($cg['first_name'] . ' ' . $cg['last_name']) ?></strong></td>
                            <td><?= htmlspecialchars($cg['phone'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($cg['qualification'] ?? 'N/A') ?></td>
                            <td><?= $cg['experience_years'] ?> Years</td>
                            <td>
                                <span class="badge badge-<?= $cg['background_verified'] ? 'approved' : 'pending' ?>">
                                    <?= $cg['background_verified'] ? 'Verified' : 'Pending' ?>
                                </span>
                            </td>
                            <td><span class="badge badge-<?= htmlspecialchars($cg['status']) ?>"><?= htmlspecialchars($cg['status']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

<?php elseif ($activeTab === 'documents'): ?>
    <div class="table-container">
        <div class="table-header-bar">
            <h3 class="table-header-title">Uploaded Verification Documents</h3>
        </div>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Document Name</th>
                    <th>Type</th>
                    <th>Uploaded</th>
                    <th>Status</th>
                    <th style="text-align: right;">Review Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($documents)): ?>
                    <tr><td colspan="5" style="text-align: center; padding: 24px; color: #9E9E9E;">No documents uploaded yet.</td></tr>
                <?php else: ?>
                    <?php foreach($documents as $doc): ?>
                        <tr>
                            <td>
                                <i class="fas fa-file-pdf" style="color: #D32F2F; margin-right: 6px;"></i>
                                <strong><?= htmlspecialchars($doc['document_name']) ?></strong>
                            </td>
                            <td><?= htmlspecialchars(ucwords(str_replace('_', ' ', $doc['document_type']))) ?></td>
                            <td><?= formatDate($doc['created_at']) ?></td>
                            <td>
                                <span class="badge badge-<?= htmlspecialchars($doc['status']) ?>">
                                    <?= htmlspecialchars($doc['status']) ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <div class="btn-group-action">
                                    <form method="POST" style="display: inline;">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="verify_doc">
                                        <input type="hidden" name="doc_id" value="<?= $doc['id'] ?>">
                                        <input type="hidden" name="doc_status" value="approved">
                                        <button type="submit" class="btn btn-success" style="padding: 4px 10px; font-size: 12px;" title="Approve Document">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                    </form>
                                    <form method="POST" style="display: inline;">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="verify_doc">
                                        <input type="hidden" name="doc_id" value="<?= $doc['id'] ?>">
                                        <input type="hidden" name="doc_status" value="rejected">
                                        <button type="submit" class="btn btn-danger" style="padding: 4px 10px; font-size: 12px;" title="Reject Document">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

<?php elseif ($activeTab === 'bookings'): ?>
    <div class="table-container">
        <div class="table-header-bar">
            <h3 class="table-header-title">Recent Bookings Under This Provider</h3>
        </div>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Parent</th>
                    <th>Child</th>
                    <th>Center</th>
                    <th>Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($bookings)): ?>
                    <tr><td colspan="6" style="text-align: center; padding: 24px; color: #9E9E9E;">No bookings recorded.</td></tr>
                <?php else: ?>
                    <?php foreach($bookings as $b): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($b['booking_code']) ?></strong></td>
                            <td><?= htmlspecialchars($b['first_name'] . ' ' . $b['last_name']) ?></td>
                            <td><?= htmlspecialchars($b['child_name']) ?></td>
                            <td><?= htmlspecialchars($b['center_name']) ?></td>
                            <td><?= formatCurrency($b['final_amount']) ?></td>
                            <td><span class="badge badge-<?= htmlspecialchars($b['status']) ?>"><?= htmlspecialchars($b['status']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

<?php elseif ($activeTab === 'reviews'): ?>
    <div class="detail-card">
        <h3 style="margin: 0 0 16px 0; color: var(--admin-dark-pink);">Parent Feedback & Reviews</h3>
        <?php if(empty($reviews)): ?>
            <p style="color: #9E9E9E; text-align: center; padding: 20px;">No reviews submitted yet.</p>
        <?php else: ?>
            <?php foreach($reviews as $rev): ?>
                <div style="background: #FFF0F5; border-radius: 10px; padding: 16px; margin-bottom: 14px; border: 1px solid var(--admin-pastel-pink);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <strong><?= htmlspecialchars($rev['title'] ?? 'Review') ?></strong>
                        <div style="color: #FFB300;">
                            <?= str_repeat('★', $rev['rating']) . str_repeat('☆', 5 - $rev['rating']) ?>
                        </div>
                    </div>
                    <p style="margin: 0 0 8px 0; font-size: 14px; color: #424242;"><?= htmlspecialchars($rev['review_text'] ?? '') ?></p>
                    <div style="font-size: 12px; color: #757575;">
                        By <?= htmlspecialchars($rev['first_name'] . ' ' . $rev['last_name']) ?> • Center: <?= htmlspecialchars($rev['center_name']) ?> • <?= formatDate($rev['created_at']) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
