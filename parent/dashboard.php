<?php
/**
 * Parent Dashboard
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
requireRole('parent');

$pageTitleHeader = 'Dashboard';
$pageTitle = 'Dashboard';

$conn = getDBConnection();
$userId = (int)($_SESSION['user_id'] ?? 0);

// Helper function for safe query execution
function safeQuery($conn, $query, $params = [], $types = '') {
    try {
        if ($params) {
            $stmt = $conn->prepare($query);
            if (!$stmt) return false;
            if ($types) $stmt->bind_param($types, ...$params);
            $stmt->execute();
            return $stmt->get_result();
        } else {
            return $conn->query($query);
        }
    } catch (Exception $e) {
        error_log("Query error: " . $e->getMessage() . " | Query: " . $query);
        return false;
    }
}

// Get Upcoming Bookings
$upcomingBookings = [];
$res = safeQuery($conn, "
    SELECT b.*, c.name as center_name, c.area, c.city 
    FROM bookings b
    JOIN daycare_centers c ON b.center_id = c.id
    WHERE b.user_id = ? AND b.start_datetime > NOW() AND b.status IN ('pending', 'confirmed')
    ORDER BY b.start_datetime ASC LIMIT 3
", [$userId], 'i');
if ($res) {
    $upcomingBookings = $res->fetch_all(MYSQLI_ASSOC);
}

// Get Active Subscriptions
$subscriptions = [];
$res = safeQuery($conn, "
    SELECT s.*, c.name as center_name 
    FROM subscriptions s
    JOIN daycare_centers c ON s.center_id = c.id
    WHERE s.user_id = ? AND s.status = 'active'
", [$userId], 'i');
if ($res) {
    $subscriptions = $res->fetch_all(MYSQLI_ASSOC);
}

// Note: Do NOT close connection here — includes/header.php reuses the singleton connection

require_once __DIR__ . '/includes/header.php';
?>

<style>
/* Glassmorphism Dashboard Base */
.dashboard-wrapper {
    position: relative;
    z-index: 1;
    min-height: 80vh;
}
.animated-bg-blob {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    z-index: -1;
    opacity: 0.6;
    animation: floatBlob 12s infinite alternate ease-in-out;
}
.blob-1 {
    width: 400px;
    height: 400px;
    background: rgba(252, 108, 133, 0.3); /* main-pink */
    top: -50px;
    left: -100px;
}
.blob-2 {
    width: 350px;
    height: 350px;
    background: rgba(156, 39, 176, 0.2); /* purple */
    bottom: -100px;
    right: -50px;
    animation-delay: -5s;
}
@keyframes floatBlob {
    0% { transform: translate(0, 0) scale(1); }
    100% { transform: translate(30px, 50px) scale(1.1); }
}

/* Glass Cards */
.glass-card {
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(255, 255, 255, 0.8);
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.03), inset 0 1px 0 rgba(255,255,255,1);
    padding: var(--space-xl);
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
}
.glass-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(233,30,99,0.1), inset 0 1px 0 rgba(255,255,255,1);
}

/* Enhanced Stat Cards */
.stat-card-premium {
    display: flex;
    align-items: center;
    gap: var(--space-md);
    margin-bottom: 0;
}
.icon-glow-wrap {
    width: 60px;
    height: 60px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    position: relative;
    overflow: hidden;
}
.icon-glow-wrap::after {
    content: '';
    position: absolute;
    width: 100%;
    height: 100%;
    background: inherit;
    filter: blur(15px);
    z-index: -1;
    opacity: 0.6;
}
.icon-blue { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; box-shadow: 0 8px 20px rgba(79,172,254,0.3); }
.icon-green { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; box-shadow: 0 8px 20px rgba(67,233,123,0.3); }

.stat-value-text { font-size: 32px; font-weight: 800; color: var(--near-black); line-height: 1; margin-bottom: 4px; }
.stat-label-text { font-size: 13px; color: var(--medium-gray); text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; }

/* Stunning CTA Button */
.cta-premium {
    position: relative;
    background: linear-gradient(135deg, var(--main-pink) 0%, #9C27B0 100%);
    border-radius: 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: white !important;
    text-decoration: none !important;
    overflow: hidden;
    height: 100%;
    box-shadow: 0 15px 30px rgba(233,30,99,0.3);
    transition: all 0.4s ease;
}
.cta-premium::before {
    content: '';
    position: absolute;
    top: 0; left: -100%; width: 50%; height: 100%;
    background: linear-gradient(to right, rgba(255,255,255,0) 0%, rgba(255,255,255,0.3) 50%, rgba(255,255,255,0) 100%);
    transform: skewX(-25deg);
    animation: shine 4s infinite;
}
@keyframes shine {
    0% { left: -100%; }
    20% { left: 200%; }
    100% { left: 200%; }
}
.cta-premium:hover {
    transform: translateY(-5px) scale(1.02);
    box-shadow: 0 20px 40px rgba(156,39,176,0.4);
}
.cta-premium i {
    font-size: 36px;
    margin-bottom: 12px;
    transition: transform 0.3s ease;
}
.cta-premium:hover i { transform: scale(1.2) rotate(90deg); }
.cta-text { font-size: 18px; font-weight: 700; letter-spacing: 0.5px; }

/* Booking Items */
.booking-item-glass {
    background: rgba(255,255,255,0.6);
    border: 1px solid rgba(255,255,255,0.9);
    border-radius: 16px;
    display: flex;
    overflow: hidden;
    margin-bottom: var(--space-md);
    transition: transform 0.2s, box-shadow 0.2s;
}
.booking-item-glass:hover {
    transform: translateX(5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.05);
    background: rgba(255,255,255,0.9);
}
.calendar-badge {
    background: linear-gradient(135deg, var(--light-pink) 0%, #fff 100%);
    padding: var(--space-md) var(--space-lg);
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    border-right: 1px dashed rgba(233,30,99,0.2);
}
.cal-month { font-size: 13px; font-weight: 700; color: var(--main-pink); text-transform: uppercase; letter-spacing: 1px; }
.cal-day { font-size: 32px; font-weight: 800; color: var(--dark-pink); line-height: 1.1; }
.cal-weekday { font-size: 12px; color: var(--medium-gray); font-weight: 500; }

.booking-content { padding: var(--space-md) var(--space-lg); flex-grow: 1; }
.booking-title { font-size: 18px; font-weight: 700; color: var(--near-black); margin-bottom: 6px; }
.booking-title a { color: inherit; text-decoration: none; transition: color 0.2s; }
.booking-title a:hover { color: var(--main-pink); }
.booking-meta { color: var(--medium-gray); font-size: 14px; margin-bottom: 4px; display: flex; align-items: center; gap: 8px; }
.booking-meta i { color: var(--main-pink); width: 16px; text-align: center; }

/* Empty States */
.empty-glass {
    text-align: center;
    padding: var(--space-2xl) var(--space-xl);
}
.empty-icon-wrap {
    width: 100px;
    height: 100px;
    background: linear-gradient(135deg, rgba(252,108,133,0.1) 0%, rgba(156,39,176,0.1) 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto var(--space-lg);
    font-size: 40px;
    color: var(--main-pink);
    position: relative;
}
.empty-icon-wrap::after {
    content: ''; position: absolute; width: 100%; height: 100%; border-radius: 50%;
    border: 2px dashed rgba(233,30,99,0.3); animation: spin 20s linear infinite;
}
@keyframes spin { 100% { transform: rotate(360deg); } }
.empty-title { font-size: 20px; font-weight: 700; color: var(--near-black); margin-bottom: 8px; }
.empty-text { color: var(--medium-gray); margin-bottom: var(--space-xl); font-size: 15px; }

/* Section Titles */
.section-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: var(--space-lg);
}
.section-header i { font-size: 20px; color: var(--main-pink); }
.section-header h3 { color: var(--near-black); font-size: 22px; font-weight: 800; margin: 0; }
</style>

<div class="dashboard-wrapper">
    <!-- Background Blobs -->
    <div class="animated-bg-blob blob-1"></div>
    <div class="animated-bg-blob blob-2"></div>

    <!-- Quick Actions -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: var(--space-lg); margin-bottom: var(--space-2xl);">
        
        <!-- Bookings Stat -->
        <div class="glass-card stat-card-premium">
            <div class="icon-glow-wrap icon-blue">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div style="flex-grow: 1;">
                <div class="stat-value-text"><?= count($upcomingBookings) ?></div>
                <div class="stat-label-text">Upcoming Bookings</div>
            </div>
            <a href="bookings.php" class="btn btn-outline" style="border-radius: 12px; font-size: 13px; padding: 8px 16px;">View All</a>
        </div>
        
        <!-- Subscriptions Stat -->
        <div class="glass-card stat-card-premium">
            <div class="icon-glow-wrap icon-green">
                <i class="fas fa-sync-alt"></i>
            </div>
            <div style="flex-grow: 1;">
                <div class="stat-value-text"><?= count($subscriptions) ?></div>
                <div class="stat-label-text">Active Subscriptions</div>
            </div>
            <a href="subscriptions.php" class="btn btn-outline" style="border-radius: 12px; font-size: 13px; padding: 8px 16px;">Manage</a>
        </div>
        
        <!-- Primary Action CTA -->
        <div style="height: 100%; min-height: 120px;">
            <a href="centers.php" class="cta-premium">
                <i class="fas fa-plus-circle"></i>
                <span class="cta-text">Book New Childcare</span>
            </a>
        </div>
    </div>
    
    <!-- Main Content Grid -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: var(--space-xl);">
        
        <!-- Upcoming Bookings Column -->
        <div>
            <div class="section-header">
                <i class="fas fa-calendar-day"></i>
                <h3>Upcoming Care</h3>
            </div>
            
            <?php if (count($upcomingBookings) > 0): ?>
                <?php foreach ($upcomingBookings as $booking): ?>
                    <div class="booking-item-glass">
                        <div class="calendar-badge">
                            <span class="cal-month"><?= date('M', strtotime($booking['start_datetime'])) ?></span>
                            <span class="cal-day"><?= date('d', strtotime($booking['start_datetime'])) ?></span>
                            <span class="cal-weekday"><?= date('l', strtotime($booking['start_datetime'])) ?></span>
                        </div>
                        <div class="booking-content">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <h4 class="booking-title">
                                    <a href="center-details.php?id=<?= $booking['center_id'] ?>"><?= htmlspecialchars($booking['center_name']) ?></a>
                                </h4>
                                <?php if ($booking['status'] == 'confirmed'): ?>
                                    <span class="badge badge-success" style="box-shadow: 0 4px 10px rgba(40,167,69,0.2);">Confirmed</span>
                                <?php else: ?>
                                    <span class="badge badge-warning" style="box-shadow: 0 4px 10px rgba(255,193,7,0.2);">Pending</span>
                                <?php endif; ?>
                            </div>
                            <div class="booking-meta">
                                <i class="fas fa-clock"></i> 
                                <span><?= date('h:i A', strtotime($booking['start_datetime'])) ?> - <?= date('h:i A', strtotime($booking['end_datetime'])) ?></span>
                            </div>
                            <div class="booking-meta">
                                <i class="fas fa-child"></i> 
                                <span>Care for <strong><?= htmlspecialchars($booking['child_name']) ?></strong> (<?= round($booking['child_age_months'] / 12, 1) ?> yrs)</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="glass-card empty-glass">
                    <div class="empty-icon-wrap">
                        <i class="fas fa-calendar-times"></i>
                    </div>
                    <h4 class="empty-title">No Upcoming Care</h4>
                    <p class="empty-text">Your schedule is clear! You don't have any upcoming childcare bookings scheduled at the moment.</p>
                    <a href="centers.php" class="btn btn-primary" style="padding: 12px 30px; font-size: 16px; border-radius: 30px; box-shadow: 0 8px 20px rgba(233,30,99,0.3);">Find a Center</a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Subscriptions Column -->
        <div>
            <div class="section-header">
                <i class="fas fa-star"></i>
                <h3>Active Subscriptions</h3>
            </div>
            
            <?php if (count($subscriptions) > 0): ?>
                <?php foreach ($subscriptions as $sub): ?>
                    <div class="glass-card" style="padding: var(--space-lg); margin-bottom: var(--space-md); border-left: 4px solid var(--success);">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                            <h4 style="font-size: 16px; font-weight: 700; color: var(--near-black); margin: 0;"><?= htmlspecialchars($sub['center_name']) ?></h4>
                            <span class="badge badge-success" style="font-size: 10px;">Active</span>
                        </div>
                        <div class="booking-meta" style="font-size: 13px;">
                            <i class="fas fa-child"></i> <strong><?= htmlspecialchars($sub['child_name']) ?></strong>
                        </div>
                        <div class="booking-meta" style="font-size: 13px; color: var(--main-pink);">
                            <i class="fas fa-sync-alt"></i> Renews: <?= date('d M Y', strtotime($sub['end_date'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="glass-card empty-glass" style="padding: var(--space-xl) var(--space-lg);">
                    <div class="empty-icon-wrap" style="width: 70px; height: 70px; font-size: 28px; margin-bottom: var(--space-md);">
                        <i class="fas fa-gem"></i>
                    </div>
                    <h4 class="empty-title" style="font-size: 18px;">No Active Plans</h4>
                    <p class="empty-text" style="font-size: 14px; margin-bottom: var(--space-lg);">You are not subscribed to any monthly childcare plans.</p>
                    <a href="centers.php" class="btn btn-outline" style="border-radius: 20px;">Explore Plans</a>
                </div>
            <?php endif; ?>
        </div>
        
    </div>
</div>

<style>
    @media (max-width: 991px) {
        div[style*="grid-template-columns: 2fr 1fr"] { grid-template-columns: 1fr !important; }
    }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
