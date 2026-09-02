<?php
/**
 * Parent - Browse Childcare Centers
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
requireRole('parent');

$pageTitleHeader = 'Find Centers';
$pageTitle = 'Centers';

$conn = getDBConnection();

// Search & filter from GET
$search   = isset($_GET['q'])    ? sanitizeInput($conn, $_GET['q'])    : '';
$cityFilter = isset($_GET['city']) ? sanitizeInput($conn, $_GET['city']) : '';
$typeFilter = isset($_GET['type']) ? sanitizeInput($conn, $_GET['type']) : '';

// Build query
$where = ["c.status = 'active'"];
$params = [];
$types  = '';

if (!empty($search)) {
    $where[] = "(c.name LIKE ? OR c.area LIKE ? OR c.city LIKE ?)";
    $s = "%$search%";
    $params[] = $s; $params[] = $s; $params[] = $s;
    $types .= 'sss';
}
if (!empty($cityFilter)) {
    $where[] = "c.city = ?";
    $params[] = $cityFilter;
    $types .= 's';
}
if (!empty($typeFilter)) {
    $where[] = "c.type = ?";
    $params[] = $typeFilter;
    $types .= 's';
}

$sql = "
    SELECT c.*, p.pricing_hourly, p.pricing_daily, p.pricing_monthly
    FROM daycare_centers c
    JOIN providers p ON c.provider_id = p.id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY c.rating DESC, c.name ASC
";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$centers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get unique cities for filter
$cities = $conn->query("SELECT DISTINCT city FROM daycare_centers WHERE status = 'active' ORDER BY city")->fetch_all(MYSQLI_ASSOC);

$conn->close();
require_once __DIR__ . '/includes/header.php';
?>

<style>
    .center-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 24px; }
    .center-card { background: var(--white); border-radius: 20px; overflow: hidden; box-shadow: 0 4px 20px rgba(233,30,99,0.08); transition: transform 0.3s, box-shadow 0.3s; }
    .center-card:hover { transform: translateY(-4px); box-shadow: 0 12px 32px rgba(233,30,99,0.15); }
    .center-img { width: 100%; height: 180px; object-fit: cover; background: var(--light-pink); }
    .center-img-placeholder { width: 100%; height: 180px; background: linear-gradient(135deg, var(--light-pink), var(--baby-pink)); display: flex; align-items: center; justify-content: center; font-size: 48px; color: var(--main-pink); }
</style>

<!-- Search & Filters -->
<div class="card" style="margin-bottom: var(--space-lg);">
    <form method="GET" action="centers.php" style="display: flex; gap: var(--space-md); flex-wrap: wrap; align-items: flex-end;">
        <div class="form-group" style="flex: 2; min-width: 200px; margin: 0;">
            <label class="form-label">Search Centers</label>
            <input type="text" name="q" class="form-control" placeholder="Name, area, city..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="form-group" style="flex: 1; min-width: 140px; margin: 0;">
            <label class="form-label">City</label>
            <select name="city" class="form-control">
                <option value="">All Cities</option>
                <?php foreach ($cities as $c): ?>
                    <option value="<?= htmlspecialchars($c['city']) ?>" <?= $cityFilter == $c['city'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['city']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="flex: 1; min-width: 140px; margin: 0;">
            <label class="form-label">Type</label>
            <select name="type" class="form-control">
                <option value="">All Types</option>
                <option value="daycare"    <?= $typeFilter == 'daycare'     ? 'selected' : '' ?>>Daycare</option>
                <option value="creche"     <?= $typeFilter == 'creche'      ? 'selected' : '' ?>>Creche</option>
                <option value="preschool"  <?= $typeFilter == 'preschool'   ? 'selected' : '' ?>>Preschool</option>
                <option value="babysitting"<?= $typeFilter == 'babysitting' ? 'selected' : '' ?>>Babysitting</option>
                <option value="after_school"<?= $typeFilter == 'after_school'? 'selected' : '' ?>>After School</option>
            </select>
        </div>
        <div style="margin: 0;">
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
            <?php if ($search || $cityFilter || $typeFilter): ?>
                <a href="centers.php" class="btn btn-outline" style="margin-left: 8px;"><i class="fas fa-times"></i> Clear</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Results Count -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-md);">
    <h3 style="margin: 0; color: var(--dark-pink);">
        <?= count($centers) ?> Center<?= count($centers) != 1 ? 's' : '' ?> Found
    </h3>
    <a href="booking_create.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Quick Book</a>
</div>

<!-- Centers Grid -->
<?php if (count($centers) > 0): ?>
    <div class="center-grid">
        <?php foreach ($centers as $c): ?>
            <div class="center-card">
                <?php if (!empty($c['images'])): 
                    $imgs = json_decode($c['images'], true);
                    $firstImg = is_array($imgs) && count($imgs) > 0 ? $imgs[0] : null;
                ?>
                    <?php if ($firstImg): ?>
                        <img src="<?= SITE_URL . '/' . htmlspecialchars($firstImg) ?>" alt="<?= htmlspecialchars($c['name']) ?>" class="center-img">
                    <?php else: ?>
                        <div class="center-img-placeholder"><i class="fas fa-building"></i></div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="center-img-placeholder"><i class="fas fa-building"></i></div>
                <?php endif; ?>
                
                <div style="padding: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                        <h4 style="margin: 0; font-size: 16px; color: var(--near-black); flex: 1;">
                            <?= htmlspecialchars($c['name']) ?>
                        </h4>
                        <?php if ($c['rating'] > 0): ?>
                            <span style="display: flex; align-items: center; gap: 4px; font-size: 13px; color: var(--dark-gray); white-space: nowrap; margin-left: 8px;">
                                <i class="fas fa-star" style="color: #FFC107;"></i>
                                <?= number_format($c['rating'], 1) ?>
                                <span style="color: var(--medium-gray);">(<?= $c['total_reviews'] ?>)</span>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <p style="color: var(--medium-gray); font-size: 13px; margin-bottom: 12px;">
                        <i class="fas fa-map-marker-alt"></i> 
                        <?= htmlspecialchars(trim(($c['area'] ? $c['area'] . ', ' : '') . $c['city'])) ?>
                    </p>
                    
                    <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 12px;">
                        <span class="badge badge-pink" style="font-size: 11px;"><?= ucfirst(str_replace('_', ' ', $c['type'])) ?></span>
                        <?php if ($c['is_24x7']): ?>
                            <span class="badge badge-info" style="font-size: 11px;">24×7</span>
                        <?php endif; ?>
                        <?php if ($c['cctv_enabled']): ?>
                            <span class="badge badge-success" style="font-size: 11px; background: var(--success-bg); color: var(--success);">CCTV</span>
                        <?php endif; ?>
                        <?php if ($c['meals_included']): ?>
                            <span class="badge" style="font-size: 11px; background: #FFF3E0; color: #E65100;">Meals</span>
                        <?php endif; ?>
                    </div>
                    
                    <div style="border-top: 1px solid var(--light-gray); padding-top: 12px; display: flex; justify-content: space-between; align-items: center;">
                        <div style="font-size: 13px; color: var(--dark-gray);">
                            <?php if ($c['pricing_hourly']): ?>
                                <strong style="color: var(--main-pink); font-size: 16px;">
                                    <?= formatCurrency($c['pricing_hourly']) ?>
                                </strong><span style="color: var(--medium-gray);">/hr</span>
                            <?php else: ?>
                                <span style="color: var(--medium-gray);">Contact for pricing</span>
                            <?php endif; ?>
                        </div>
                        <a href="<?= SITE_URL ?>/center-details.php?id=<?= $c['id'] ?>" class="btn btn-primary btn-sm">
                            View & Book
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="card text-center" style="padding: var(--space-3xl) 0;">
        <div style="font-size: 64px; color: var(--light-pink); margin-bottom: var(--space-md);">
            <i class="fas fa-search"></i>
        </div>
        <h3 style="color: var(--dark-gray);">No Centers Found</h3>
        <p style="color: var(--medium-gray); margin-bottom: var(--space-lg);">
            Try adjusting your filters or search terms.
        </p>
        <a href="centers.php" class="btn btn-outline">Clear Filters</a>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
