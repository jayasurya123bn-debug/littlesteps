<?php
/**
 * Provider Documents Upload & Verification
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
requireRole('provider');

$pageTitleHeader = 'Documents & Verification';
$pageTitle = 'Documents';

$conn = getDBConnection();
$providerId = $_SESSION['user_id'];

// Handle Document Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verifyCSRFToken($_POST['csrf_token'])) {
        $action = $_POST['action'] ?? '';

        if ($action === 'upload') {
            $docType = sanitizeInput($conn, $_POST['document_type']);
            $docName = sanitizeInput($conn, $_POST['document_name']);
            $expiry  = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;

            // Handle file upload if provided
            $filePath = 'uploads/documents/doc_' . time() . '.pdf';
            if (isset($_FILES['doc_file']) && $_FILES['doc_file']['error'] === UPLOAD_ERR_OK) {
                $uploaded = uploadFile($_FILES['doc_file'], 'documents', ['application/pdf', 'image/jpeg', 'image/png']);
                if ($uploaded) {
                    $filePath = $uploaded;
                }
            }

            $stmt = $conn->prepare("
                INSERT INTO documents (provider_id, document_type, document_name, file_path, expiry_date, status)
                VALUES (?, ?, ?, ?, ?, 'pending')
            ");
            $stmt->bind_param("issss", $providerId, $docType, $docName, $filePath, $expiry);
            $stmt->execute();
            setFlashMessage('success', 'Document submitted for verification review.');

        } elseif ($action === 'delete') {
            $docId = (int)$_POST['doc_id'];
            $stmt = $conn->prepare("DELETE FROM documents WHERE id = ? AND provider_id = ?");
            $stmt->bind_param("ii", $docId, $providerId);
            $stmt->execute();
            setFlashMessage('success', 'Document removed.');
        }
    }
    redirect('/provider/documents.php');
}

// Fetch Uploaded Documents
$stmt = $conn->prepare("SELECT * FROM documents WHERE provider_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $providerId);
$stmt->execute();
$documents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Map uploaded types
$uploadedTypes = [];
foreach($documents as $d) {
    $uploadedTypes[$d['document_type']] = $d['status'];
}

$requiredDocs = [
    'business_license'   => 'Business License / Childcare Registration',
    'identity_proof'     => 'Identity Proof (Aadhaar / PAN / Passport)',
    'address_proof'      => 'Proof of Premises Address / Lease Deed',
    'tax_registration'   => 'Tax / GST Registration Certificate',
    'safety_certificate' => 'Fire & Child Safety NOC',
    'insurance'          => 'Child Commercial Liability Insurance'
];

$conn->close();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Required Checklist Grid -->
<div class="detail-card" style="margin-bottom: 24px;">
    <h3 style="margin: 0 0 16px 0; color: var(--provider-dark-pink); font-size: 18px;">
        <i class="fas fa-tasks" style="margin-right: 8px;"></i> Verification Checklist
    </h3>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 14px;">
        <?php foreach($requiredDocs as $key => $title): ?>
            <?php 
            $status = $uploadedTypes[$key] ?? 'missing'; 
            ?>
            <div style="padding: 14px 18px; border-radius: 10px; background: #FFF0F5; border: 1px solid var(--provider-pastel-pink); display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <strong style="font-size: 14px; color: #212121;"><?= htmlspecialchars($title) ?></strong>
                </div>
                <?php if ($status === 'approved'): ?>
                    <span class="badge badge-approved">✓ Verified</span>
                <?php elseif ($status === 'pending'): ?>
                    <span class="badge badge-pending">Under Review</span>
                <?php elseif ($status === 'rejected'): ?>
                    <span class="badge badge-rejected">Rejected</span>
                <?php else: ?>
                    <span class="badge badge-pink" style="color: #AD1457;">Pending Upload</span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Upload New Document Section -->
<div class="detail-card" style="margin-bottom: 24px;">
    <h3 style="margin: 0 0 16px 0; color: var(--provider-dark-pink); font-size: 18px;">
        <i class="fas fa-upload" style="margin-right: 8px;"></i> Upload Compliance Document
    </h3>
    
    <form method="POST" enctype="multipart/form-data">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="upload">
        
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div class="form-group">
                <label class="form-label">Document Category *</label>
                <select name="document_type" class="form-control" required>
                    <?php foreach($requiredDocs as $key => $title): ?>
                        <option value="<?= $key ?>"><?= htmlspecialchars($title) ?></option>
                    <?php endforeach; ?>
                    <option value="other">Other Supporting Document</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Document Title / File Name *</label>
                <input type="text" name="document_name" class="form-control" placeholder="e.g. Fire_NOC_Certificate.pdf" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Expiry Date (if applicable)</label>
                <input type="date" name="expiry_date" class="form-control">
            </div>
        </div>
        
        <div class="form-group" style="margin-bottom: 20px;">
            <label class="form-label">Select File (PDF, PNG, JPG - max 5MB) *</label>
            <input type="file" name="doc_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
        </div>
        
        <div style="display: flex; justify-content: flex-end;">
            <button type="submit" class="btn btn-primary" style="padding: 10px 24px;">
                <i class="fas fa-cloud-upload-alt" style="margin-right: 6px;"></i> Submit for Verification
            </button>
        </div>
    </form>
</div>

<!-- Uploaded Documents Table -->
<div class="provider-table-card">
    <div class="provider-table-header">
        <h3><i class="fas fa-file-alt" style="margin-right: 6px;"></i> Uploaded Documents Log (<?= count($documents) ?>)</h3>
    </div>
    
    <div style="overflow-x: auto;">
        <table class="provider-table">
            <thead>
                <tr>
                    <th>Document Name</th>
                    <th>Category</th>
                    <th>Uploaded Date</th>
                    <th>Expiry</th>
                    <th>Status</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($documents)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 24px; color: #9E9E9E;">No documents uploaded yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach($documents as $doc): ?>
                        <tr>
                            <td>
                                <i class="fas fa-file-pdf" style="color: #E91E63; margin-right: 6px;"></i>
                                <strong><?= htmlspecialchars($doc['document_name']) ?></strong>
                            </td>
                            <td><?= htmlspecialchars(ucwords(str_replace('_', ' ', $doc['document_type']))) ?></td>
                            <td><?= formatDate($doc['created_at']) ?></td>
                            <td><?= $doc['expiry_date'] ? formatDate($doc['expiry_date']) : 'Lifetime / None' ?></td>
                            <td>
                                <span class="badge badge-<?= htmlspecialchars($doc['status']) ?>">
                                    <?= htmlspecialchars($doc['status']) ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this document?');">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="doc_id" value="<?= $doc['id'] ?>">
                                    <button type="submit" class="btn-icon btn-icon-delete" title="Delete Document">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
