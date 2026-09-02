<?php
/**
 * Center Details Page
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/functions.php';

$centerId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($centerId <= 0) {
    redirect('/search.php');
}

$conn = getDBConnection();

// Fetch Center Details
$stmt = $conn->prepare("
    SELECT c.*, p.business_name, p.pricing_hourly, p.pricing_daily, p.pricing_monthly, p.facilities, p.safety_measures 
    FROM daycare_centers c 
    JOIN providers p ON c.provider_id = p.id 
    WHERE c.id = ? AND c.status = 'active'
");
$stmt->bind_param("i", $centerId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $conn->close();
    setFlashMessage('error', 'Center not found.');
    redirect('/search.php');
}

$center = $result->fetch_assoc();

// Fetch Caregivers
$caregiverStmt = $conn->prepare("SELECT * FROM caregivers WHERE provider_id = ? AND status = 'active'");
$caregiverStmt->bind_param("i", $center['provider_id']);
$caregiverStmt->execute();
$caregivers = $caregiverStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$conn->close();

$pageTitle = htmlspecialchars($center['name']);
require_once __DIR__ . '/includes/header.php';
?>

<!-- Header/Hero for Center -->
<div style="background-color: var(--white); border-bottom: 1px solid var(--light-pink);">
    <!-- Cover Image -->
    <div style="height: 300px; width: 100%; position: relative;">
        <div style="position: absolute; inset: 0; background: url('<?= SITE_URL ?>/assets/images/center_<?= ($center['id'] % 2) + 1 ?>.jpg') center/cover;"></div>
        <div style="position: absolute; inset: 0; background: linear-gradient(0deg, rgba(0,0,0,0.6) 0%, rgba(255,255,255,0) 100%);"></div>
        
        <div class="container" style="position: relative; height: 100%; display: flex; align-items: flex-end; padding-bottom: var(--space-xl);">
            <div style="color: white; text-shadow: 0 2px 4px rgba(0,0,0,0.3);">
                <?php if ($center['is_24x7']): ?>
                    <span class="badge badge-pink" style="background: rgba(233,30,99,0.9); color: white; border: none; margin-bottom: 12px; font-size: 14px; padding: 6px 16px;">
                        <i class="fas fa-moon"></i> &nbsp;24x7 Care Available
                    </span>
                <?php endif; ?>
                <h1 style="color: white; margin-bottom: 8px;"><?= htmlspecialchars($center['name']) ?></h1>
                <p style="font-size: 1.125rem; opacity: 0.9; margin-bottom: 0;">
                    <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($center['address'] . ', ' . $center['area'] . ', ' . $center['city']) ?>
                </p>
            </div>
        </div>
    </div>
</div>

<div class="container" style="padding: var(--space-2xl) 0;">
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: var(--space-xl);">
        
        <!-- Main Content (Left) -->
        <div>
            <!-- Overview -->
            <div class="card" style="margin-bottom: var(--space-xl);">
                <h3 style="color: var(--dark-pink); margin-bottom: var(--space-md); border-bottom: 1px solid var(--light-pink); padding-bottom: 8px;">Overview</h3>
                <p style="white-space: pre-line;"><?= htmlspecialchars($center['description'] ?? 'No description provided.') ?></p>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-md); margin-top: var(--space-lg);">
                    <div>
                        <span style="color: var(--medium-gray); font-size: 14px; display: block;">Operating Hours</span>
                        <strong><?= $center['operating_hours_start'] ? date('h:i A', strtotime($center['operating_hours_start'])) . ' to ' . date('h:i A', strtotime($center['operating_hours_end'])) : 'Not specified' ?></strong>
                    </div>
                    <div>
                        <span style="color: var(--medium-gray); font-size: 14px; display: block;">Age Group</span>
                        <strong><?= $center['min_age_months'] ?> months to <?= $center['max_age_years'] ?> years</strong>
                    </div>
                    <div>
                        <span style="color: var(--medium-gray); font-size: 14px; display: block;">Curriculum</span>
                        <strong><?= htmlspecialchars($center['curriculum'] ?? 'Standard Play-based') ?></strong>
                    </div>
                    <div>
                        <span style="color: var(--medium-gray); font-size: 14px; display: block;">Current Occupancy</span>
                        <strong><?= $center['current_occupancy'] ?> / <?= $center['capacity'] ?> Children</strong>
                    </div>
                </div>
            </div>
            
            <!-- Amenities & Safety -->
            <div class="card" style="margin-bottom: var(--space-xl);">
                <h3 style="color: var(--dark-pink); margin-bottom: var(--space-md); border-bottom: 1px solid var(--light-pink); padding-bottom: 8px;">Amenities & Safety</h3>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-md);">
                    <ul style="list-style: none;">
                        <li style="margin-bottom: 8px; display: flex; align-items: center; gap: 8px;">
                            <i class="fas <?= $center['meals_included'] ? 'fa-check text-success' : 'fa-times text-danger' ?>" style="width: 20px;"></i>
                            Meals Included
                        </li>
                        <li style="margin-bottom: 8px; display: flex; align-items: center; gap: 8px;">
                            <i class="fas <?= $center['transport_available'] ? 'fa-check text-success' : 'fa-times text-danger' ?>" style="width: 20px;"></i>
                            Transport Available
                        </li>
                        <li style="margin-bottom: 8px; display: flex; align-items: center; gap: 8px;">
                            <i class="fas <?= $center['cctv_enabled'] ? 'fa-check text-success' : 'fa-times text-danger' ?>" style="width: 20px;"></i>
                            CCTV Monitoring
                        </li>
                    </ul>
                    
                    <?php 
                    $safety = $center['safety_measures'] ? json_decode($center['safety_measures'], true) : []; 
                    if (!empty($safety)):
                    ?>
                    <ul style="list-style: none;">
                        <?php foreach ($safety as $measure): ?>
                        <li style="margin-bottom: 8px; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-shield-alt text-success" style="width: 20px; color: var(--success);"></i>
                            <?= htmlspecialchars($measure) ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Caregivers -->
            <?php if (count($caregivers) > 0): ?>
            <div class="card">
                <h3 style="color: var(--dark-pink); margin-bottom: var(--space-md); border-bottom: 1px solid var(--light-pink); padding-bottom: 8px;">Caregivers & Staff</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: var(--space-md);">
                    <?php foreach ($caregivers as $staff): ?>
                    <div style="border: 1px solid var(--light-pink); border-radius: var(--radius-md); padding: var(--space-md); text-align: center;">
                        <div style="width: 80px; height: 80px; border-radius: 50%; background: var(--baby-pink); margin: 0 auto var(--space-sm); display: flex; align-items: center; justify-content: center; overflow: hidden;">
                            <?php if ($staff['photo']): ?>
                                <!-- Actual photo logic here -->
                            <?php else: ?>
                                <i class="fas fa-user text-main-pink" style="font-size: 32px; color: var(--main-pink);"></i>
                            <?php endif; ?>
                        </div>
                        <h4 style="font-size: 16px; margin-bottom: 4px;"><?= htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']) ?></h4>
                        <p style="font-size: 12px; color: var(--medium-gray); margin-bottom: 8px;"><?= htmlspecialchars($staff['qualification']) ?></p>
                        <?php if ($staff['background_verified']): ?>
                            <span class="badge badge-success" style="font-size: 10px;"><i class="fas fa-check-circle"></i> Verified</span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
        </div>
        
        <!-- Sidebar (Right) -->
        <div>
            <!-- Booking Card -->
            <div class="card" style="position: sticky; top: 100px; box-shadow: var(--shadow-md);">
                <div style="text-align: center; margin-bottom: var(--space-md);">
                    <div style="display: inline-flex; align-items: center; gap: 4px; background: var(--success-bg); color: var(--success); padding: 4px 12px; border-radius: var(--radius-full); font-size: 16px; font-weight: 600; margin-bottom: 8px;">
                        <?= $center['rating'] ?> <i class="fas fa-star" style="font-size: 14px;"></i>
                    </div>
                    <p style="color: var(--medium-gray); font-size: 14px; margin: 0;">Based on <?= $center['total_reviews'] ?> reviews</p>
                </div>
                
                <hr style="border: 0; border-top: 1px solid var(--light-pink); margin: var(--space-md) 0;">
                
                <h4 style="font-size: 16px; margin-bottom: var(--space-sm);">Pricing</h4>
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <span style="color: var(--medium-gray);">Hourly</span>
                    <strong style="color: var(--dark-gray);"><?= $center['pricing_hourly'] ? formatCurrency($center['pricing_hourly']) : 'N/A' ?></strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <span style="color: var(--medium-gray);">Daily</span>
                    <strong style="color: var(--dark-gray);"><?= $center['pricing_daily'] ? formatCurrency($center['pricing_daily']) : 'N/A' ?></strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: var(--space-md);">
                    <span style="color: var(--medium-gray);">Monthly</span>
                    <strong style="color: var(--dark-gray);"><?= $center['pricing_monthly'] ? formatCurrency($center['pricing_monthly']) : 'N/A' ?></strong>
                </div>
                
                <?php if (isLoggedIn() && $_SESSION['user_role'] === 'parent'): ?>
                    <a href="<?= SITE_URL ?>/parent/booking_create.php?center_id=<?= $center['id'] ?>" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 16px;">
                        Book Now
                    </a>
                <?php elseif (!isLoggedIn()): ?>
                    <a href="<?= SITE_URL ?>/login.php" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 16px;">
                        Log in to Book
                    </a>
                <?php else: ?>
                    <button disabled class="btn btn-outline" style="width: 100%; padding: 12px; font-size: 16px; opacity: 0.5; cursor: not-allowed;">
                        Parents only
                    </button>
                <?php endif; ?>
                
                <div style="text-align: center; margin-top: var(--space-sm);">
                    <a href="contact.php?center=<?= $center['id'] ?>" style="font-size: 14px; text-decoration: underline;">Contact Center</a>
                </div>
            </div>
        </div>
        
    </div>
</div>

<style>
    @media (max-width: 991px) {
        .container > div[style*="grid-template-columns"] { grid-template-columns: 1fr !important; }
    }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
