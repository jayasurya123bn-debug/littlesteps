<?php
/**
 * Provider Add/Edit Center Form
 * Little Steps Childcare Platform
 * 4-Tab Interactive Form
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
requireRole('provider');

$conn = getDBConnection();
$providerId = $_SESSION['user_id'];
$centerId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = ($centerId > 0);

$center = [
    'name' => '',
    'type' => 'daycare',
    'address' => '',
    'area' => '',
    'city' => '',
    'state' => 'Karnataka',
    'pincode' => '',
    'contact_phone' => '',
    'contact_email' => '',
    'description' => '',
    'curriculum' => 'Montessori & Playway',
    'min_age_months' => 3,
    'max_age_years' => 10,
    'capacity' => 30,
    'operating_hours_start' => '08:00:00',
    'operating_hours_end' => '19:00:00',
    'is_24x7' => 0,
    'meals_included' => 1,
    'transport_available' => 1,
    'cctv_enabled' => 1,
    'pricing_hourly' => 150.00,
    'pricing_daily' => 850.00,
    'pricing_monthly' => 10500.00,
    'status' => 'active'
];

if ($isEdit) {
    $stmt = $conn->prepare("SELECT * FROM daycare_centers WHERE id = ? AND provider_id = ?");
    $stmt->bind_param("ii", $centerId, $providerId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 1) {
        $center = array_merge($center, $res->fetch_assoc());
    } else {
        $conn->close();
        setFlashMessage('error', 'Center not found.');
        redirect('/provider/center.php');
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verifyCSRFToken($_POST['csrf_token'])) {
        $name           = sanitizeInput($conn, $_POST['name']);
        $type           = sanitizeInput($conn, $_POST['type']);
        $address        = sanitizeInput($conn, $_POST['address']);
        $area           = sanitizeInput($conn, $_POST['area']);
        $city           = sanitizeInput($conn, $_POST['city']);
        $state          = sanitizeInput($conn, $_POST['state']);
        $pincode        = sanitizeInput($conn, $_POST['pincode']);
        $phone          = sanitizeInput($conn, $_POST['contact_phone']);
        $email          = sanitizeInput($conn, $_POST['contact_email']);
        $description    = sanitizeInput($conn, $_POST['description']);
        $curriculum     = sanitizeInput($conn, $_POST['curriculum']);
        $minAge         = (int)$_POST['min_age_months'];
        $maxAge         = (int)$_POST['max_age_years'];
        $capacity       = (int)$_POST['capacity'];
        $startTime      = $_POST['operating_hours_start'] ?? '08:00:00';
        $endTime        = $_POST['operating_hours_end'] ?? '19:00:00';
        $is24x7         = isset($_POST['is_24x7']) ? 1 : 0;
        $meals          = isset($_POST['meals_included']) ? 1 : 0;
        $transport      = isset($_POST['transport_available']) ? 1 : 0;
        $cctv           = isset($_POST['cctv_enabled']) ? 1 : 0;
        $priceHourly    = (float)$_POST['pricing_hourly'];
        $priceDaily     = (float)$_POST['pricing_daily'];
        $priceMonthly   = (float)$_POST['pricing_monthly'];
        $status         = isset($_POST['save_draft']) ? 'inactive' : 'active';

        if ($isEdit) {
            $upd = $conn->prepare("
                UPDATE daycare_centers SET
                    name=?, type=?, address=?, area=?, city=?, state=?, pincode=?,
                    contact_phone=?, contact_email=?, description=?, curriculum=?,
                    min_age_months=?, max_age_years=?, capacity=?, operating_hours_start=?,
                    operating_hours_end=?, is_24x7=?, meals_included=?, transport_available=?,
                    cctv_enabled=?, pricing_hourly=?, pricing_daily=?, pricing_monthly=?, status=?
                WHERE id=? AND provider_id=?
            ");
            $upd->bind_param(
                "sssssssssssiiissiiiidddsii",
                $name, $type, $address, $area, $city, $state, $pincode,
                $phone, $email, $description, $curriculum,
                $minAge, $maxAge, $capacity, $startTime,
                $endTime, $is24x7, $meals, $transport,
                $cctv, $priceHourly, $priceDaily, $priceMonthly, $status,
                $centerId, $providerId
            );
            $upd->execute();
            setFlashMessage('success', 'Daycare center updated successfully.');
        } else {
            $ins = $conn->prepare("
                INSERT INTO daycare_centers (
                    provider_id, name, type, address, area, city, state, pincode,
                    contact_phone, contact_email, description, curriculum,
                    min_age_months, max_age_years, capacity, operating_hours_start,
                    operating_hours_end, is_24x7, meals_included, transport_available,
                    cctv_enabled, pricing_hourly, pricing_daily, pricing_monthly, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $ins->bind_param(
                "isssssssssssiiissiiiiddds",
                $providerId, $name, $type, $address, $area, $city, $state, $pincode,
                $phone, $email, $description, $curriculum,
                $minAge, $maxAge, $capacity, $startTime,
                $endTime, $is24x7, $meals, $transport,
                $cctv, $priceHourly, $priceDaily, $priceMonthly, $status
            );
            $ins->execute();
            $centerId = $ins->insert_id;
            setFlashMessage('success', 'New daycare center added successfully!');
        }
        redirect('/provider/center.php');
    }
}

$conn->close();

$pageTitleHeader = $isEdit ? 'Edit Daycare Center' : 'Add New Center';
$pageTitle = $isEdit ? 'Edit Center' : 'New Center';
require_once __DIR__ . '/includes/header.php';
?>

<form method="POST">
    <?= csrfField() ?>
    
    <!-- Tabbed Navigation -->
    <div class="form-tabs">
        <button type="button" class="form-tab-btn active" onclick="switchTab('basic')">
            <i class="fas fa-info-circle"></i> 1. Basic Information
        </button>
        <button type="button" class="form-tab-btn" onclick="switchTab('operations')">
            <i class="fas fa-clock"></i> 2. Operations & Timings
        </button>
        <button type="button" class="form-tab-btn" onclick="switchTab('amenities')">
            <i class="fas fa-tags"></i> 3. Amenities & Pricing
        </button>
        <button type="button" class="form-tab-btn" onclick="switchTab('media')">
            <i class="fas fa-camera"></i> 4. Media & Photos
        </button>
    </div>
    
    <!-- TAB 1: Basic Info -->
    <div class="tab-pane active" id="pane-basic">
        <div class="detail-card">
            <h3 style="margin: 0 0 16px 0; color: var(--provider-dark-pink); font-size: 18px;">Basic Facility Information</h3>
            
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div class="form-group">
                    <label class="form-label">Center Name *</label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($center['name']) ?>" placeholder="e.g., Bloom & Blossom Daycare - Indiranagar Branch" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Facility Type</label>
                    <select name="type" class="form-control">
                        <option value="daycare" <?= $center['type'] === 'daycare' ? 'selected' : '' ?>>Daycare</option>
                        <option value="creche" <?= $center['type'] === 'creche' ? 'selected' : '' ?>>Creche (Infants)</option>
                        <option value="preschool" <?= $center['type'] === 'preschool' ? 'selected' : '' ?>>Preschool / Montessori</option>
                        <option value="babysitting" <?= $center['type'] === 'babysitting' ? 'selected' : '' ?>>Babysitting Hub</option>
                        <option value="after_school" <?= $center['type'] === 'after_school' ? 'selected' : '' ?>>After School Care</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label">Detailed Description</label>
                <textarea name="description" class="form-control" rows="3" placeholder="Describe the facility highlights, safety standards, and atmosphere..."><?= htmlspecialchars($center['description']) ?></textarea>
            </div>
            
            <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div class="form-group">
                    <label class="form-label">Street Address *</label>
                    <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($center['address']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Area / Locality *</label>
                    <input type="text" name="area" class="form-control" value="<?= htmlspecialchars($center['area']) ?>" placeholder="e.g. Indiranagar" required>
                </div>
                <div class="form-group">
                    <label class="form-label">City *</label>
                    <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($center['city']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Pincode *</label>
                    <input type="text" name="pincode" class="form-control" value="<?= htmlspecialchars($center['pincode']) ?>" required>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label class="form-label">State</label>
                    <input type="text" name="state" class="form-control" value="<?= htmlspecialchars($center['state']) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Contact Phone</label>
                    <input type="text" name="contact_phone" class="form-control" value="<?= htmlspecialchars($center['contact_phone']) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Contact Email</label>
                    <input type="email" name="contact_email" class="form-control" value="<?= htmlspecialchars($center['contact_email']) ?>">
                </div>
            </div>
        </div>
    </div>
    
    <!-- TAB 2: Operations -->
    <div class="tab-pane" id="pane-operations">
        <div class="detail-card">
            <h3 style="margin: 0 0 16px 0; color: var(--provider-dark-pink); font-size: 18px;">Operations & Timings</h3>
            
            <div style="margin-bottom: 20px; background: #FFF0F5; padding: 16px; border-radius: 10px; border: 1px solid var(--provider-pastel-pink);">
                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                    <input type="checkbox" name="is_24x7" value="1" <?= $center['is_24x7'] ? 'checked' : '' ?> style="accent-color: var(--provider-pink); width: 20px; height: 20px;">
                    <div>
                        <strong style="color: #212121; font-size: 15px;">24×7 Round-The-Clock Operation</strong>
                        <div style="font-size: 12px; color: #757575;">Enable this if this center accepts overnight, late-night shift, or emergency childcare.</div>
                    </div>
                </label>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                <div class="form-group">
                    <label class="form-label">Opening Time</label>
                    <input type="time" name="operating_hours_start" class="form-control" value="<?= $center['operating_hours_start'] ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Closing Time</label>
                    <input type="time" name="operating_hours_end" class="form-control" value="<?= $center['operating_hours_end'] ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Max Children Capacity *</label>
                    <input type="number" name="capacity" class="form-control" value="<?= $center['capacity'] ?>" required>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr 2fr; gap: 16px;">
                <div class="form-group">
                    <label class="form-label">Min Age (Months)</label>
                    <input type="number" name="min_age_months" class="form-control" value="<?= $center['min_age_months'] ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Max Age (Years)</label>
                    <input type="number" name="max_age_years" class="form-control" value="<?= $center['max_age_years'] ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Curriculum / Philosophy</label>
                    <input type="text" name="curriculum" class="form-control" value="<?= htmlspecialchars($center['curriculum']) ?>" placeholder="e.g. Montessori, Reggio Emilia, Playway">
                </div>
            </div>
        </div>
    </div>
    
    <!-- TAB 3: Amenities & Pricing -->
    <div class="tab-pane" id="pane-amenities">
        <div class="detail-card">
            <h3 style="margin: 0 0 16px 0; color: var(--provider-dark-pink); font-size: 18px;">Key Amenities Included</h3>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="meals_included" value="1" <?= $center['meals_included'] ? 'checked' : '' ?> style="accent-color: var(--provider-pink); width: 18px; height: 18px;">
                    <span>🥗 Nutritious Meals Included</span>
                </label>
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="transport_available" value="1" <?= $center['transport_available'] ? 'checked' : '' ?> style="accent-color: var(--provider-pink); width: 18px; height: 18px;">
                    <span>🚐 Van Transport Available</span>
                </label>
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="cctv_enabled" value="1" <?= $center['cctv_enabled'] ? 'checked' : '' ?> style="accent-color: var(--provider-pink); width: 18px; height: 18px;">
                    <span>📹 Live CCTV Mobile Access</span>
                </label>
            </div>
            
            <h3 style="margin: 0 0 16px 0; color: var(--provider-dark-pink); font-size: 18px;">Pricing Tiers (₹ INR)</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label class="form-label">Hourly Rate (₹)</label>
                    <input type="number" step="0.5" name="pricing_hourly" class="form-control" value="<?= $center['pricing_hourly'] ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Daily Full-Day Rate (₹)</label>
                    <input type="number" step="1" name="pricing_daily" class="form-control" value="<?= $center['pricing_daily'] ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Monthly Plan Rate (₹)</label>
                    <input type="number" step="1" name="pricing_monthly" class="form-control" value="<?= $center['pricing_monthly'] ?>" required>
                </div>
            </div>
        </div>
    </div>
    
    <!-- TAB 4: Media -->
    <div class="tab-pane" id="pane-media">
        <div class="detail-card">
            <h3 style="margin: 0 0 16px 0; color: var(--provider-dark-pink); font-size: 18px;">Center Photos & Gallery</h3>
            <div class="doc-upload-zone">
                <div class="doc-upload-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                <h4 style="margin: 0 0 6px 0; color: #212121;">Drag & Drop Photos Here</h4>
                <p style="color: #757575; font-size: 13px; margin: 0 0 12px 0;">Upload high-res JPG, PNG, or WEBP photos of playrooms, cribs, and outdoor grounds.</p>
                <button type="button" class="btn btn-secondary" style="padding: 8px 18px;">Select Files</button>
            </div>
        </div>
    </div>
    
    <!-- Bottom Save Controls -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 24px;">
        <a href="center.php" class="btn btn-secondary">Cancel & Back</a>
        <div style="display: flex; gap: 12px;">
            <button type="submit" name="save_draft" value="1" class="btn btn-secondary">Save as Draft</button>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save" style="margin-right: 6px;"></i> Publish Center
            </button>
        </div>
    </div>
</form>

<script>
function switchTab(tabName) {
    document.querySelectorAll('.form-tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));
    
    event.currentTarget.classList.add('active');
    document.getElementById('pane-' + tabName).classList.add('active');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
