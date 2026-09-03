<?php
/**
 * Provider Slot & Availability Management
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
requireRole('provider');

$pageTitleHeader = 'Availability & Slots';
$pageTitle = 'Manage Availability';

$conn = getDBConnection();
$providerId = $_SESSION['user_id'];

// Get centers for provider
$centers = $conn->query("SELECT id, name FROM daycare_centers WHERE provider_id = $providerId ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
$selectedCenterId = isset($_GET['center_id']) ? (int)$_GET['center_id'] : ($centers[0]['id'] ?? 0);
$selectedDate = $_GET['date'] ?? date('Y-m-d');

// Handle Add Slot or Toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verifyCSRFToken($_POST['csrf_token'])) {
        $action = $_POST['action'] ?? '';

        if ($action === 'add_slot') {
            $centerId  = (int)$_POST['center_id'];
            $slotDate  = $_POST['slot_date'];
            $startTime = $_POST['start_time'];
            $endTime   = $_POST['end_time'];
            $capacity  = (int)$_POST['max_capacity'];
            $price     = (float)$_POST['price'];
            $notes     = sanitizeInput($conn, $_POST['notes'] ?? '');

            $stmt = $conn->prepare("
                INSERT INTO availability (center_id, date, start_time, end_time, max_capacity, price, is_available, notes)
                VALUES (?, ?, ?, ?, ?, ?, 1, ?)
            ");
            $stmt->bind_param("isssids", $centerId, $slotDate, $startTime, $endTime, $capacity, $price, $notes);
            $stmt->execute();
            setFlashMessage('success', 'New time slot added.');
            redirect('/provider/availability.php?center_id=' . $centerId . '&date=' . $slotDate);

        } elseif ($action === 'toggle_slot') {
            $slotId = (int)$_POST['slot_id'];
            $newVal = (int)$_POST['new_val'];
            $stmt = $conn->prepare("UPDATE availability SET is_available = ? WHERE id = ?");
            $stmt->bind_param("ii", $newVal, $slotId);
            $stmt->execute();
            setFlashMessage('success', 'Slot availability status updated.');
            redirect('/provider/availability.php?center_id=' . $selectedCenterId . '&date=' . $selectedDate);
            
        } elseif ($action === 'bulk_template') {
            $centerId = (int)$_POST['center_id'];
            $daysAhead = 7;
            for ($i = 0; $i < $daysAhead; $i++) {
                $targetDate = date('Y-m-d', strtotime("+$i days"));
                
                // Morning slot
                $conn->query("INSERT IGNORE INTO availability (center_id, date, start_time, end_time, max_capacity, price, is_available, notes)
                              VALUES ($centerId, '$targetDate', '08:00:00', '13:00:00', 15, 800.00, 1, 'Morning Learning Session')");
                // Afternoon slot
                $conn->query("INSERT IGNORE INTO availability (center_id, date, start_time, end_time, max_capacity, price, is_available, notes)
                              VALUES ($centerId, '$targetDate', '13:00:00', '18:00:00', 15, 800.00, 1, 'Afternoon Nap & Play')");
            }
            setFlashMessage('success', 'Weekly schedule template applied for the next 7 days!');
            redirect('/provider/availability.php?center_id=' . $centerId . '&date=' . $selectedDate);
        }
    }
}

// Fetch Slots for selected center & date
$slots = [];
if ($selectedCenterId > 0) {
    $stmt = $conn->prepare("SELECT * FROM availability WHERE center_id = ? AND date = ? ORDER BY start_time ASC");
    $stmt->bind_param("is", $selectedCenterId, $selectedDate);
    $stmt->execute();
    $slots = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$conn->close();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Filter & Actions Bar -->
<div class="filter-card" style="margin-bottom: 24px;">
    <form method="GET" class="filter-form" style="justify-content: space-between;">
        <div style="display: flex; gap: 14px; align-items: center; flex-wrap: wrap;">
            <?php if (!empty($centers)): ?>
                <div>
                    <label class="form-label" style="font-size: 12px;">Select Center</label>
                    <select name="center_id" class="filter-input" onchange="this.form.submit()">
                        <?php foreach($centers as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= $selectedCenterId === (int)$c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            
            <div>
                <label class="form-label" style="font-size: 12px;">Select Date</label>
                <input type="date" name="date" class="filter-input" value="<?= htmlspecialchars($selectedDate) ?>" onchange="this.form.submit()">
            </div>
            
            <div style="align-self: flex-end;">
                <button type="submit" class="btn btn-secondary" style="padding: 9px 16px;">View Day</button>
            </div>
        </div>
        
        <div style="display: flex; gap: 10px; align-self: flex-end;">
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('bulkModal').style.display='flex'">
                <i class="fas fa-magic"></i> Auto-Generate Weekly Slots
            </button>
            <button type="button" class="btn btn-primary" onclick="document.getElementById('addSlotModal').style.display='flex'">
                <i class="fas fa-plus"></i> Add Single Slot
            </button>
        </div>
    </form>
</div>

<!-- Slots Status Legend -->
<div style="display: flex; gap: 18px; margin-bottom: 20px; font-size: 13px; color: #616161;">
    <span style="display: flex; align-items: center; gap: 6px;">
        <span style="width: 12px; height: 12px; background: #4CAF50; border-radius: 3px;"></span> Available Spots
    </span>
    <span style="display: flex; align-items: center; gap: 6px;">
        <span style="width: 12px; height: 12px; background: #F44336; border-radius: 3px;"></span> Fully Booked
    </span>
    <span style="display: flex; align-items: center; gap: 6px;">
        <span style="width: 12px; height: 12px; background: #9E9E9E; border-radius: 3px;"></span> Slot Disabled
    </span>
</div>

<!-- Slot Cards Grid -->
<div class="calendar-card">
    <h3 style="margin: 0 0 16px 0; color: var(--provider-dark-pink); font-size: 18px;">
        <i class="fas fa-calendar-day" style="margin-right: 6px;"></i> Time Slots for <?= formatDate($selectedDate, 'l, d F Y') ?>
    </h3>
    
    <?php if(empty($slots)): ?>
        <div style="text-align: center; padding: 48px 0; color: #9E9E9E;">
            <i class="fas fa-calendar-times" style="font-size: 40px; color: var(--provider-pastel-pink); margin-bottom: 12px; display: block;"></i>
            <h4 style="margin: 0 0 6px 0; color: #212121;">No Time Slots Defined for This Day</h4>
            <p style="margin: 0 0 16px 0; font-size: 14px;">Add a slot manually or use the 7-day auto-generator template above.</p>
            <button type="button" class="btn btn-primary" onclick="document.getElementById('addSlotModal').style.display='flex'">
                <i class="fas fa-plus"></i> Add Slot for Today
            </button>
        </div>
    <?php else: ?>
        <div class="slot-grid">
            <?php foreach($slots as $slot): ?>
                <?php 
                $isFull = ($slot['booked_count'] >= $slot['max_capacity']);
                $cardClass = !$slot['is_available'] ? 'disabled' : ($isFull ? 'full' : 'available');
                ?>
                <div class="slot-card <?= $cardClass ?>">
                    <div class="slot-time">
                        <i class="fas fa-clock"></i>
                        <?= date('h:i A', strtotime($slot['start_time'])) ?> - <?= date('h:i A', strtotime($slot['end_time'])) ?>
                    </div>
                    
                    <div class="slot-meta">
                        <div>
                            <strong><?= $slot['booked_count'] ?></strong> / <?= $slot['max_capacity'] ?> Booked
                        </div>
                        <div style="font-weight: 700; color: var(--provider-pink);">
                            <?= formatCurrency($slot['price']) ?>
                        </div>
                    </div>
                    
                    <?php if($slot['notes']): ?>
                        <div style="font-size: 12px; color: #757575; margin-bottom: 12px;">
                            <?= htmlspecialchars($slot['notes']) ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="slot-actions">
                        <form method="POST" style="display: inline;">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="toggle_slot">
                            <input type="hidden" name="slot_id" value="<?= $slot['id'] ?>">
                            <input type="hidden" name="new_val" value="<?= $slot['is_available'] ? 0 : 1 ?>">
                            <button type="submit" class="btn <?= $slot['is_available'] ? 'btn-secondary' : 'btn-success' ?>" style="padding: 4px 10px; font-size: 12px;">
                                <i class="fas <?= $slot['is_available'] ? 'fa-ban' : 'fa-check' ?>"></i>
                                <?= $slot['is_available'] ? 'Disable' : 'Enable' ?>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Add Slot Modal -->
<div class="modal-overlay" id="addSlotModal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3>Add New Childcare Slot</h3>
            <button type="button" class="modal-close-btn" onclick="document.getElementById('addSlotModal').style.display='none'">&times;</button>
        </div>
        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="add_slot">
            <input type="hidden" name="center_id" value="<?= $selectedCenterId ?>">
            
            <div class="modal-body">
                <div class="form-group" style="margin-bottom: 14px;">
                    <label class="form-label">Slot Date</label>
                    <input type="date" name="slot_date" class="form-control" value="<?= htmlspecialchars($selectedDate) ?>" required>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                    <div class="form-group">
                        <label class="form-label">Start Time</label>
                        <input type="time" name="start_time" class="form-control" value="08:00" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">End Time</label>
                        <input type="time" name="end_time" class="form-control" value="13:00" required>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                    <div class="form-group">
                        <label class="form-label">Max Spots / Capacity</label>
                        <input type="number" name="max_capacity" class="form-control" value="15" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Price for Slot (₹)</label>
                        <input type="number" step="1" name="price" class="form-control" value="850" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Slot Label / Activity Notes</label>
                    <input type="text" name="notes" class="form-control" placeholder="e.g. Morning Montessori Session">
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('addSlotModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Slot</button>
            </div>
        </form>
    </div>
</div>

<!-- Bulk Template Modal -->
<div class="modal-overlay" id="bulkModal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3>Generate 7-Day Schedule Template</h3>
            <button type="button" class="modal-close-btn" onclick="document.getElementById('bulkModal').style.display='none'">&times;</button>
        </div>
        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="bulk_template">
            <input type="hidden" name="center_id" value="<?= $selectedCenterId ?>">
            
            <div class="modal-body">
                <p style="color: #424242; font-size: 14px; line-height: 1.6;">
                    This will automatically populate morning (08:00 - 13:00) and afternoon (13:00 - 18:00) childcare slots for the next <strong>7 days</strong> with standard capacity of 15 children.
                </p>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('bulkModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Generate 7-Day Schedule</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
