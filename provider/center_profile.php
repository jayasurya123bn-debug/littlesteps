<?php
/**
 * Center Profile Setup & Management
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

$pageTitleHeader = 'Center Profile';
$pageTitle = 'Center Profile';

$conn = getDBConnection();
$providerId = $_SESSION['user_id'];

// Get existing center
$stmt = $conn->prepare("SELECT * FROM daycare_centers WHERE provider_id = ? LIMIT 1");
$stmt->bind_param("i", $providerId);
$stmt->execute();
$result = $stmt->get_result();
$center = $result->num_rows > 0 ? $result->fetch_assoc() : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        setFlashMessage('error', "Invalid request token.");
    } else {
        $name = sanitizeInput($conn, $_POST['name']);
        $description = sanitizeInput($conn, $_POST['description']);
        $address = sanitizeInput($conn, $_POST['address']);
        $area = sanitizeInput($conn, $_POST['area']);
        $city = sanitizeInput($conn, $_POST['city']);
        $state = sanitizeInput($conn, $_POST['state']);
        $pincode = sanitizeInput($conn, $_POST['pincode']);
        $capacity = (int)$_POST['capacity'];
        $minAge = (int)$_POST['min_age_months'];
        $maxAge = (int)$_POST['max_age_years'];
        $curriculum = sanitizeInput($conn, $_POST['curriculum']);
        $is24x7 = isset($_POST['is_24x7']) ? 1 : 0;
        $operatingStart = $_POST['operating_hours_start'];
        $operatingEnd = $_POST['operating_hours_end'];
        $mealsIncluded = isset($_POST['meals_included']) ? 1 : 0;
        $cctvEnabled = isset($_POST['cctv_enabled']) ? 1 : 0;
        $transportAvailable = isset($_POST['transport_available']) ? 1 : 0;
        
        if ($center) {
            // Update
            $updStmt = $conn->prepare("
                UPDATE daycare_centers 
                SET name=?, description=?, address=?, area=?, city=?, state=?, pincode=?, capacity=?, min_age_months=?, max_age_years=?, curriculum=?, is_24x7=?, operating_hours_start=?, operating_hours_end=?, meals_included=?, cctv_enabled=?, transport_available=?
                WHERE provider_id=?
            ");
            $updStmt->bind_param("sssssssiississiiii", $name, $description, $address, $area, $city, $state, $pincode, $capacity, $minAge, $maxAge, $curriculum, $is24x7, $operatingStart, $operatingEnd, $mealsIncluded, $cctvEnabled, $transportAvailable, $providerId);
            
            if ($updStmt->execute()) {
                setFlashMessage('success', 'Center profile updated successfully.');
            } else {
                setFlashMessage('error', 'Failed to update center profile.');
            }
        } else {
            // Insert
            $insStmt = $conn->prepare("
                INSERT INTO daycare_centers (provider_id, name, description, address, area, city, state, pincode, capacity, current_occupancy, min_age_months, max_age_years, curriculum, is_24x7, operating_hours_start, operating_hours_end, meals_included, cctv_enabled, transport_available, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
            ");
            $insStmt->bind_param("isssssssiiisisiiii", $providerId, $name, $description, $address, $area, $city, $state, $pincode, $capacity, $minAge, $maxAge, $curriculum, $is24x7, $operatingStart, $operatingEnd, $mealsIncluded, $cctvEnabled, $transportAvailable);
            
            if ($insStmt->execute()) {
                setFlashMessage('success', 'Center profile created successfully.');
            } else {
                setFlashMessage('error', 'Failed to create center profile.');
            }
        }
    }
    redirect('/provider/center_profile.php');
}

$conn->close();

require_once __DIR__ . '/includes/header.php';
?>

<div class="card" style="max-width: 900px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-lg); border-bottom: 1px solid var(--light-pink); padding-bottom: var(--space-md);">
        <h2 style="color: var(--dark-pink); margin: 0;">Center Information</h2>
        <?php if ($center): ?>
            <span class="badge badge-success">Profile Active</span>
        <?php else: ?>
            <span class="badge badge-warning">Setup Required</span>
        <?php endif; ?>
    </div>
    
    <form method="POST" action="center_profile.php" class="needs-validation">
        <?php csrfField(); ?>
        
        <h4 style="color: var(--dark-gray); margin-bottom: var(--space-md);">Basic Details</h4>
        <div class="form-group">
            <label class="form-label">Center Name</label>
            <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($center['name'] ?? $_SESSION['business_name']) ?>">
        </div>
        
        <div class="form-group">
            <label class="form-label">Description / About</label>
            <textarea name="description" class="form-control" rows="4" required placeholder="Tell parents what makes your center special..."><?= htmlspecialchars($center['description'] ?? '') ?></textarea>
        </div>
        
        <h4 style="color: var(--dark-gray); margin-top: var(--space-lg); margin-bottom: var(--space-md); border-top: 1px solid var(--light-gray); padding-top: var(--space-md);">Location</h4>
        <div class="form-group">
            <label class="form-label">Full Address</label>
            <input type="text" name="address" class="form-control" required value="<?= htmlspecialchars($center['address'] ?? '') ?>">
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: var(--space-md);">
            <div class="form-group">
                <label class="form-label">Area / Locality</label>
                <input type="text" name="area" class="form-control" required value="<?= htmlspecialchars($center['area'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">City</label>
                <input type="text" name="city" class="form-control" required value="<?= htmlspecialchars($center['city'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">State</label>
                <input type="text" name="state" class="form-control" required value="<?= htmlspecialchars($center['state'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Pincode</label>
                <input type="text" name="pincode" class="form-control" required value="<?= htmlspecialchars($center['pincode'] ?? '') ?>">
            </div>
        </div>
        
        <h4 style="color: var(--dark-gray); margin-top: var(--space-lg); margin-bottom: var(--space-md); border-top: 1px solid var(--light-gray); padding-top: var(--space-md);">Operations & Rules</h4>
        
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: var(--space-md);">
            <div class="form-group">
                <label class="form-label">Total Capacity (Children)</label>
                <input type="number" name="capacity" class="form-control" required min="1" value="<?= htmlspecialchars($center['capacity'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Min Age (Months)</label>
                <input type="number" name="min_age_months" class="form-control" required min="0" value="<?= htmlspecialchars($center['min_age_months'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Max Age (Years)</label>
                <input type="number" name="max_age_years" class="form-control" required min="0" value="<?= htmlspecialchars($center['max_age_years'] ?? '') ?>">
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">Curriculum / Approach</label>
            <input type="text" name="curriculum" class="form-control" placeholder="e.g. Montessori, Play-based, Waldorf" required value="<?= htmlspecialchars($center['curriculum'] ?? '') ?>">
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: var(--space-md);">
            <div class="form-group">
                <label class="form-label">Operating Hours Start</label>
                <input type="time" name="operating_hours_start" class="form-control" required value="<?= htmlspecialchars($center['operating_hours_start'] ?? '08:00:00') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Operating Hours End</label>
                <input type="time" name="operating_hours_end" class="form-control" required value="<?= htmlspecialchars($center['operating_hours_end'] ?? '18:00:00') ?>">
            </div>
        </div>
        
        <div class="form-group" style="margin-top: var(--space-md);">
            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 12px; background: var(--baby-pink); border-radius: var(--radius-sm); border: 1px solid var(--main-pink);">
                <input type="checkbox" name="is_24x7" <?= isset($center['is_24x7']) && $center['is_24x7'] ? 'checked' : '' ?> style="accent-color: var(--main-pink); width: 18px; height: 18px;">
                <span style="font-weight: 600; color: var(--dark-pink);">We offer 24x7 Care (Night Shifts included)</span>
            </label>
        </div>
        
        <h4 style="color: var(--dark-gray); margin-top: var(--space-lg); margin-bottom: var(--space-md); border-top: 1px solid var(--light-gray); padding-top: var(--space-md);">Amenities & Facilities</h4>
        <div class="facility-grid">
            <label class="facility-item <?= isset($center['meals_included']) && $center['meals_included'] ? 'selected' : '' ?>">
                <input type="checkbox" name="meals_included" <?= isset($center['meals_included']) && $center['meals_included'] ? 'checked' : '' ?> onchange="this.parentElement.classList.toggle('selected')"> Meals Included
            </label>
            <label class="facility-item <?= isset($center['cctv_enabled']) && $center['cctv_enabled'] ? 'selected' : '' ?>">
                <input type="checkbox" name="cctv_enabled" <?= isset($center['cctv_enabled']) && $center['cctv_enabled'] ? 'checked' : '' ?> onchange="this.parentElement.classList.toggle('selected')"> CCTV Access
            </label>
            <label class="facility-item <?= isset($center['transport_available']) && $center['transport_available'] ? 'selected' : '' ?>">
                <input type="checkbox" name="transport_available" <?= isset($center['transport_available']) && $center['transport_available'] ? 'checked' : '' ?> onchange="this.parentElement.classList.toggle('selected')"> Transport
            </label>
        </div>
        
        <div style="margin-top: var(--space-xl); display: flex; justify-content: flex-end; border-top: 1px solid var(--light-pink); padding-top: var(--space-lg);">
            <button type="submit" class="btn btn-primary btn-lg">Save Center Profile</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
