<?php
/**
 * Search/Browse Centers
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/functions.php';

$pageTitle = 'Find Childcare Centers';
require_once __DIR__ . '/includes/header.php';

// Prepare base query
$conn = getDBConnection();
$query = "SELECT c.*, p.business_name FROM daycare_centers c JOIN providers p ON c.provider_id = p.id WHERE c.status = 'active'";
$params = [];
$types = "";

// Handle search and filters
$search = isset($_GET['q']) ? sanitizeInput($conn, $_GET['q']) : '';
$city = isset($_GET['city']) ? sanitizeInput($conn, $_GET['city']) : '';
$is24x7 = isset($_GET['is_24x7']) ? 1 : 0;

if (!empty($search)) {
    $query .= " AND (c.name LIKE ? OR c.area LIKE ?)";
    $searchParam = "%{$search}%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "ss";
}

if (!empty($city)) {
    $query .= " AND c.city = ?";
    $params[] = $city;
    $types .= "s";
}

if ($is24x7) {
    $query .= " AND c.is_24x7 = 1";
}

$query .= " ORDER BY c.rating DESC, c.name ASC LIMIT 50"; // Limit for basic implementation

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$centers = $result->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>

<div style="background-color: var(--baby-pink); padding: var(--space-xl) 0; border-bottom: 1px solid var(--light-pink);">
    <div class="container">
        <h1 style="color: var(--dark-pink); font-size: 2rem; margin-bottom: var(--space-md);">Find the Perfect Childcare</h1>
        
        <!-- Search Bar -->
        <div class="card" style="margin: 0; padding: var(--space-md); border-radius: var(--radius-full);">
            <form method="GET" action="search.php" style="display: flex; gap: var(--space-sm); align-items: center; flex-wrap: wrap;">
                
                <div style="flex-grow: 1; min-width: 200px; position: relative;">
                    <i class="fas fa-search" style="position: absolute; left: 16px; top: 12px; color: var(--medium-gray);"></i>
                    <input type="text" name="q" placeholder="Search by name or area..." class="form-control" style="border-radius: var(--radius-full); padding-left: 44px; border-color: transparent; background: var(--light-gray);" value="<?= htmlspecialchars($search) ?>">
                </div>
                
                <div style="flex-grow: 0.5; min-width: 150px; position: relative;">
                    <i class="fas fa-map-marker-alt" style="position: absolute; left: 16px; top: 12px; color: var(--medium-gray);"></i>
                    <input type="text" name="city" placeholder="City..." class="form-control" style="border-radius: var(--radius-full); padding-left: 44px; border-color: transparent; background: var(--light-gray);" value="<?= htmlspecialchars($city) ?>">
                </div>
                
                <div style="display: flex; align-items: center; gap: 8px; padding: 0 var(--space-sm);">
                    <input type="checkbox" id="is_24x7" name="is_24x7" value="1" <?= $is24x7 ? 'checked' : '' ?> style="accent-color: var(--main-pink); width: 18px; height: 18px;">
                    <label for="is_24x7" style="font-size: 14px; font-weight: 500; cursor: pointer;">24x7 Care</label>
                </div>
                
                <button type="submit" class="btn btn-primary" style="border-radius: var(--radius-full);">Search</button>
            </form>
        </div>
    </div>
</div>

<div class="container" style="padding: var(--space-2xl) 0;">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-lg);">
        <h3 style="margin: 0;"><?= count($centers) ?> Centers Found</h3>
        <!-- Filter Toggle for mobile could go here -->
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: var(--space-lg);">
        <?php if (count($centers) > 0): ?>
            <?php foreach ($centers as $center): ?>
                <div class="card" style="margin: 0; padding: 0; overflow: hidden; display: flex; flex-direction: column; height: 100%;">
                    
                    <div style="height: 180px; background: var(--light-pink); position: relative;">
                        <!-- Local Image -->
                        <div style="position: absolute; inset: 0; background: url('<?= SITE_URL ?>/assets/images/center_<?= ($center['id'] % 2) + 1 ?>.jpg') center/cover;"></div>
                        
                        <?php if ($center['is_24x7']): ?>
                            <span class="badge badge-pink" style="position: absolute; top: 12px; right: 12px; background: rgba(255,255,255,0.9); box-shadow: var(--shadow-sm);"><i class="fas fa-moon"></i> &nbsp;24x7 Care</span>
                        <?php endif; ?>
                    </div>
                    
                    <div style="padding: var(--space-md); flex-grow: 1; display: flex; flex-direction: column;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                            <h3 style="margin: 0; font-size: 1.125rem;">
                                <a href="center-details.php?id=<?= $center['id'] ?>" style="color: var(--near-black);"><?= htmlspecialchars($center['name']) ?></a>
                            </h3>
                            <div style="display: flex; align-items: center; gap: 4px; background: var(--success-bg); color: var(--success); padding: 2px 6px; border-radius: var(--radius-sm); font-size: 12px; font-weight: 600;">
                                <?= $center['rating'] ?> <i class="fas fa-star" style="font-size: 10px;"></i>
                            </div>
                        </div>
                        
                        <p style="color: var(--medium-gray); font-size: 13px; margin-bottom: var(--space-sm);">
                            <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($center['area'] . ', ' . $center['city']) ?>
                        </p>
                        
                        <p style="font-size: 14px; margin-bottom: var(--space-md); flex-grow: 1;">
                            <?= htmlspecialchars(substr($center['description'] ?? 'No description provided for this center.', 0, 100)) ?>...
                        </p>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--light-gray); padding-top: var(--space-sm); margin-top: auto;">
                            <div>
                                <span style="font-size: 12px; color: var(--medium-gray);">Capacity</span><br>
                                <span style="font-weight: 600; font-size: 14px;"><?= $center['current_occupancy'] ?>/<?= $center['capacity'] ?></span>
                            </div>
                            <a href="center-details.php?id=<?= $center['id'] ?>" class="btn btn-primary btn-sm">View Details</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: var(--space-3xl) 0;">
                <div style="font-size: 64px; color: var(--light-pink); margin-bottom: var(--space-md);">
                    <i class="fas fa-search"></i>
                </div>
                <h3>No centers found</h3>
                <p style="color: var(--medium-gray);">Try adjusting your search criteria or clearing filters.</p>
                <a href="search.php" class="btn btn-outline mt-2">Clear Filters</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
