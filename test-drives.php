<?php
/**
 * Test Drive Management & FIFO Queue Processing
 * CarDeal Dealership System
 */

require_once __DIR__ . '/autoload.php';

use App\Services\AuthService;
use App\Services\TestDriveService;
use App\Services\VehicleService;

AuthService::requireAuth();

$currentUser = AuthService::getCurrentUser();
$pageTitle = 'Test Drive Management & Queue';
$errors = [];
$dispatchResult = null;

// ==========================================
// HANDLE ACTIONS (CREATE, QUEUE DISPATCH, UPDATE STATUS)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Create Test Drive (Enqueue)
    if ($action === 'create') {
        $result = TestDriveService::create($_POST);
        if ($result['success']) {
            header("Location: test-drives.php?success=" . urlencode($result['message']));
            exit;
        } else {
            $errors = $result['errors'] ?? [];
            $errorMessage = $result['message'];
        }
    }

    // 2. FIFO Queue Dispatch (Dequeue Next in Line)
    elseif ($action === 'dequeue_next') {
        $dispatchStatus = $_POST['dispatch_status'] ?? 'Approved';
        $dispatchNotes = $_POST['dispatch_notes'] ?? 'Dispatched from FIFO queue';
        $dispatchResult = TestDriveService::processNextInQueue($dispatchStatus, $dispatchNotes);
        if ($dispatchResult['success']) {
            $successMessage = $dispatchResult['message'];
        } else {
            $errorMessage = $dispatchResult['message'];
        }
    }

    // 3. Update Individual Status
    elseif ($action === 'update_status') {
        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? 'Pending';
        $notes = $_POST['notes'] ?? null;
        $result = TestDriveService::updateStatus($id, $status, $notes);
        if ($result['success']) {
            header("Location: test-drives.php?success=" . urlencode($result['message']));
        } else {
            header("Location: test-drives.php?error=" . urlencode($result['message']));
        }
        exit;
    }
}

// ==========================================
// DATA FETCHING & FIFO QUEUE STATUS
// ==========================================
$statusFilter = $_GET['status'] ?? 'all';
$allDrives = TestDriveService::getAll($statusFilter);
$stats = TestDriveService::getStats();

// Build FIFO Queue of all Pending requests
$pendingQueue = TestDriveService::getPendingQueue();
$pendingQueueArray = $pendingQueue->toArray();
$frontRequest = $pendingQueue->peek();

// Available vehicles for scheduling modal
$availableVehicles = VehicleService::getAll(false); // only available vehicles
$preselectVehicleId = !empty($_GET['preselect_vehicle']) ? (int)$_GET['preselect_vehicle'] : null;

require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/sidebar.php';
?>

<main class="main-content">
    <div class="topbar">
        <div class="topbar-left">
            <h1 class="page-title">Test Drive Management</h1>
        </div>
        <div class="topbar-right">
            <button class="btn btn-primary btn-sm" data-modal-open="addDriveModal">
                + Book New Test Drive
            </button>
        </div>
    </div>

    <div class="content-area">
        <?php require_once __DIR__ . '/templates/alerts.php'; ?>

        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
        <?php endif; ?>

        <?php if (!empty($successMessage)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($successMessage) ?></div>
        <?php endif; ?>

        <!-- Queue Dispatch Metrics Banner if just processed -->
        <?php if ($dispatchResult && $dispatchResult['success']): ?>
            <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid var(--success); border-radius: var(--radius-md); padding: 14px 20px; margin-bottom: 24px;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <strong style="color: #34d399; font-size: 14px;">⚡ FIFO Queue Dequeue Successful (O(1))</strong>
                        <div style="font-size: 13px; color: var(--text-main); margin-top: 2px;">
                            Processed Customer: <strong><?= htmlspecialchars($dispatchResult['processed_request']['customer_name']) ?></strong> | 
                            Queue Size Before: <?= $dispatchResult['queue_size_before'] ?> → After: <?= $dispatchResult['queue_size_after'] ?> | 
                            Execution Time: <?= $dispatchResult['execution_time_ms'] ?> ms
                        </div>
                    </div>
                    <span class="badge badge-dsa">FIFO Queue Operation</span>
                </div>
            </div>
        <?php endif; ?>

        <!-- FIFO Queue Processing Box -->
        <div class="queue-box">
            <div class="queue-header">
                <div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <h2 style="font-size: 20px; font-weight: 800;">FIFO Request Processing Queue</h2>
                        <span class="badge badge-dsa">DSA Component 5.4</span>
                    </div>
                    <p style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">
                        Test drive requests wait in First-In, First-Out sequence. The earliest registered customer is always at the Front.
                    </p>
                </div>

                <div style="display: flex; gap: 10px;">
                    <?php if (!$pendingQueue->isEmpty()): ?>
                        <button type="button" class="btn btn-primary btn-sm" data-modal-open="dispatchModal">
                            ⚡ Dispatch Next from Queue (FIFO)
                        </button>
                    <?php else: ?>
                        <span class="badge badge-available">Queue Empty (0 Waiting)</span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (empty($pendingQueueArray)): ?>
                <div style="text-align: center; padding: 32px 16px; background: rgba(15, 27, 45, 0.6); border-radius: var(--radius-lg);">
                    <div style="font-size: 32px; margin-bottom: 8px;">⏳</div>
                    <h4 style="font-size: 16px;">Queue is Currently Empty</h4>
                    <p style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">
                        No customers are waiting in the pending queue. New bookings will automatically enqueue here at the rear.
                    </p>
                </div>
            <?php else: ?>
                <div class="queue-track">
                    <?php foreach ($pendingQueueArray as $idx => $item): ?>
                        <div class="queue-card <?= $idx === 0 ? 'front' : '' ?>">
                            <div class="queue-tag">
                                <?= $idx === 0 ? 'FRONT (HEAD POINTER)' : 'QUEUE POSITION #' . ($idx + 1) ?>
                            </div>
                            <div style="font-size: 16px; font-weight: 800; margin-top: 8px;">
                                <?= htmlspecialchars($item->getCustomerName()) ?>
                            </div>
                            <div style="font-size: 13px; color: var(--primary); font-weight: 700; margin: 4px 0;">
                                🚘 <?= htmlspecialchars($item->getVehicleInfo() ?: 'Vehicle #' . $item->getVehicleId()) ?>
                            </div>
                            <div style="font-size: 12px; color: var(--text-muted);">
                                📅 <?= htmlspecialchars($item->getFormattedDateTime()) ?>
                            </div>
                            <div style="font-size: 12px; color: var(--text-dim); margin-top: 4px;">
                                📞 <?= htmlspecialchars($item->getContactNumber()) ?>
                            </div>
                            <?php if ($item->getLicenseNumber()): ?>
                                <div style="font-size: 11px; color: var(--text-dim); margin-top: 2px;">
                                    🪪 License: <?= htmlspecialchars($item->getLicenseNumber()) ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($idx === 0): ?>
                                <div style="margin-top: 14px; padding-top: 10px; border-top: 1px solid var(--border-color);">
                                    <button type="button" class="btn btn-primary btn-sm" style="width: 100%;" data-modal-open="dispatchModal">
                                        Process Head Request
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Metrics Overview Grid -->
        <div class="stats-grid">
            <div class="stat-card primary">
                <div class="stat-icon">⏱️</div>
                <div>
                    <div class="stat-value"><?= number_format($stats['total_requests']) ?></div>
                    <div class="stat-label">Total Test Drives Logged</div>
                </div>
            </div>

            <div class="stat-card warning">
                <div class="stat-icon">⏳</div>
                <div>
                    <div class="stat-value"><?= number_format($stats['pending_count']) ?></div>
                    <div class="stat-label">Pending in Queue (FIFO)</div>
                </div>
            </div>

            <div class="stat-card info">
                <div class="stat-icon">📋</div>
                <div>
                    <div class="stat-value"><?= number_format($stats['approved_count']) ?></div>
                    <div class="stat-label">Approved & Scheduled</div>
                </div>
            </div>

            <div class="stat-card success">
                <div class="stat-icon">🏁</div>
                <div>
                    <div class="stat-value"><?= number_format($stats['completed_count']) ?></div>
                    <div class="stat-label">Completed Drives</div>
                </div>
            </div>
        </div>

        <!-- Filter Toolbar -->
        <div class="filter-toolbar">
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <a href="test-drives.php?status=all" class="btn btn-sm <?= $statusFilter === 'all' ? 'btn-primary' : 'btn-outline' ?>">
                    All (<?= $stats['total_requests'] ?>)
                </a>
                <a href="test-drives.php?status=Pending" class="btn btn-sm <?= $statusFilter === 'Pending' ? 'btn-primary' : 'btn-outline' ?>">
                    Pending in Queue (<?= $stats['pending_count'] ?>)
                </a>
                <a href="test-drives.php?status=Approved" class="btn btn-sm <?= $statusFilter === 'Approved' ? 'btn-primary' : 'btn-outline' ?>">
                    Approved (<?= $stats['approved_count'] ?>)
                </a>
                <a href="test-drives.php?status=In-Progress" class="btn btn-sm <?= $statusFilter === 'In-Progress' ? 'btn-primary' : 'btn-outline' ?>">
                    In-Progress (<?= $stats['in_progress_count'] ?>)
                </a>
                <a href="test-drives.php?status=Completed" class="btn btn-sm <?= $statusFilter === 'Completed' ? 'btn-primary' : 'btn-outline' ?>">
                    Completed (<?= $stats['completed_count'] ?>)
                </a>
                <a href="test-drives.php?status=Cancelled" class="btn btn-sm <?= $statusFilter === 'Cancelled' ? 'btn-primary' : 'btn-outline' ?>">
                    Cancelled (<?= $stats['cancelled_count'] ?>)
                </a>
            </div>
        </div>

        <!-- Table Card -->
        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Test Drive Schedule & Records</h3>
                    <p style="font-size: 12px; color: var(--text-muted);">
                        Comprehensive schedule of customer vehicle trials, driver verification, and statuses.
                    </p>
                </div>
            </div>
            <div class="card-body" style="padding: 0;">
                <?php if (empty($allDrives)): ?>
                    <div style="text-align: center; padding: 48px 24px; color: var(--text-muted);">
                        No test drive records found matching the criteria.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Customer Name</th>
                                    <th>Contact</th>
                                    <th>Selected Vehicle</th>
                                    <th>Scheduled Date & Time</th>
                                    <th>Driver's License</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allDrives as $drive): ?>
                                    <tr>
                                        <td><strong>#<?= $drive->getId() ?></strong></td>
                                        <td>
                                            <div style="font-weight: 600;"><?= htmlspecialchars($drive->getCustomerName()) ?></div>
                                            <?php if ($drive->getNotes()): ?>
                                                <div style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">
                                                    <?= htmlspecialchars($drive->getNotes()) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div>📞 <?= htmlspecialchars($drive->getContactNumber()) ?></div>
                                            <?php if ($drive->getEmail()): ?>
                                                <div style="font-size: 12px; color: var(--text-muted);">✉️ <?= htmlspecialchars($drive->getEmail()) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="vehicle-details.php?id=<?= $drive->getVehicleId() ?>" style="color: var(--primary); font-weight: 600;">
                                                🚘 <?= htmlspecialchars($drive->getVehicleInfo() ?: 'Vehicle #' . $drive->getVehicleId()) ?>
                                            </a>
                                        </td>
                                        <td>
                                            <div>📅 <?= date('M d, Y', strtotime($drive->getScheduledDate())) ?></div>
                                            <div style="font-size: 12px; color: var(--text-muted);">🕒 <?= date('h:i A', strtotime($drive->getScheduledTime())) ?></div>
                                        </td>
                                        <td>
                                            <?= $drive->getLicenseNumber() ? '<code class="code-pill">' . htmlspecialchars($drive->getLicenseNumber()) . '</code>' : '<span style="color:var(--text-dim); font-size:12px;">Not Recorded</span>' ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $stClass = 'badge-pending';
                                            if ($drive->getStatus() === 'Approved') $stClass = 'badge-approved';
                                            elseif ($drive->getStatus() === 'In-Progress') $stClass = 'badge-dsa';
                                            elseif ($drive->getStatus() === 'Completed') $stClass = 'badge-completed';
                                            elseif ($drive->getStatus() === 'Cancelled') $stClass = 'badge-cancelled';
                                            ?>
                                            <span class="badge <?= $stClass ?>"><?= $drive->getStatus() ?></span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-secondary btn-sm"
                                                    onclick='populateEditTestDrive(<?= json_encode($drive->toArray()) ?>)'>
                                                Update Status
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</main>

<!-- =======================================================
     MODAL: DISPATCH NEXT REQUEST IN FIFO QUEUE
     ======================================================= -->
<?php if ($frontRequest): ?>
<div class="modal-overlay" id="dispatchModal">
    <div class="modal-container" style="max-width: 500px;">
        <div class="modal-header">
            <h3 class="card-title">⚡ Process Front Request (FIFO)</h3>
            <button class="modal-close-btn" data-modal-close="dispatchModal">&times;</button>
        </div>
        <form method="POST" action="test-drives.php">
            <input type="hidden" name="action" value="dequeue_next">
            <div class="modal-body">
                <div style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 16px; margin-bottom: 18px;">
                    <div style="font-size: 11px; text-transform: uppercase; color: var(--primary); font-weight: 800; letter-spacing: 0.05em;">
                        Head of Queue (Request #<?= $frontRequest->getId() ?>)
                    </div>
                    <h4 style="font-size: 17px; margin-top: 4px;"><?= htmlspecialchars($frontRequest->getCustomerName()) ?></h4>
                    <p style="font-size: 13px; color: var(--text-muted); margin-top: 2px;">
                        Vehicle: <strong><?= htmlspecialchars($frontRequest->getVehicleInfo() ?: 'Vehicle #' . $frontRequest->getVehicleId()) ?></strong>
                    </p>
                    <p style="font-size: 12px; color: var(--text-dim); margin-top: 2px;">
                        Scheduled: <?= htmlspecialchars($frontRequest->getFormattedDateTime()) ?>
                    </p>
                </div>

                <div class="form-group">
                    <label class="form-label" for="dispatch_status">Target Status on Dequeue *</label>
                    <select id="dispatch_status" name="dispatch_status" class="form-select" required>
                        <option value="Approved" selected>Approved (Confirm customer schedule)</option>
                        <option value="In-Progress">In-Progress (Vehicle out on test drive)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="dispatch_notes">Dispatch Notes / Route</label>
                    <textarea id="dispatch_notes" name="dispatch_notes" class="form-control" rows="2" placeholder="e.g. Approved by Staff, verified driver's license."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-modal-close="dispatchModal">Cancel</button>
                <button type="submit" class="btn btn-primary">Dequeue & Process</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- =======================================================
     MODAL: BOOK NEW TEST DRIVE
     ======================================================= -->
<div class="modal-overlay <?= $preselectVehicleId ? 'open' : '' ?>" id="addDriveModal">
    <div class="modal-container">
        <div class="modal-header">
            <h3 class="card-title">Book Test Drive (Enqueue into Queue)</h3>
            <button class="modal-close-btn" data-modal-close="addDriveModal">&times;</button>
        </div>
        <form method="POST" action="test-drives.php">
            <input type="hidden" name="action" value="create">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="drive_customer_name">Customer Full Name *</label>
                        <input type="text" id="drive_customer_name" name="customer_name" class="form-control" placeholder="e.g. Jennifer Lopez" required>
                        <?php if (isset($errors['customer_name'])): ?><div class="form-error"><?= $errors['customer_name'] ?></div><?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="drive_contact_number">Contact Phone Number *</label>
                        <input type="text" id="drive_contact_number" name="contact_number" class="form-control" placeholder="e.g. +1 (555) 392-1829" required>
                        <?php if (isset($errors['contact_number'])): ?><div class="form-error"><?= $errors['contact_number'] ?></div><?php endif; ?>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="drive_email">Customer Email</label>
                        <input type="email" id="drive_email" name="email" class="form-control" placeholder="customer@example.com">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="drive_license">Driver's License No.</label>
                        <input type="text" id="drive_license" name="license_number" class="form-control" placeholder="e.g. DL-1928374-CA">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="drive_vehicle_id">Select Available Vehicle *</label>
                    <select id="drive_vehicle_id" name="vehicle_id" class="form-select" required>
                        <option value="">-- Choose an Available Vehicle --</option>
                        <?php foreach ($availableVehicles as $v): ?>
                            <option value="<?= $v->getId() ?>" <?= $preselectVehicleId === $v->getId() ? 'selected' : '' ?>>
                                #<?= $v->getId() ?> - <?= htmlspecialchars($v->getFullName()) ?> (<?= $v->getFormattedPrice() ?>) - <?= htmlspecialchars($v->getColor()) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['vehicle_id'])): ?><div class="form-error"><?= $errors['vehicle_id'] ?></div><?php endif; ?>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="drive_date">Scheduled Date *</label>
                        <input type="date" id="drive_date" name="scheduled_date" class="form-control" value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>" required>
                        <?php if (isset($errors['scheduled_date'])): ?><div class="form-error"><?= $errors['scheduled_date'] ?></div><?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="drive_time">Scheduled Time Slot *</label>
                        <select id="drive_time" name="scheduled_time" class="form-select" required>
                            <option value="09:00:00">09:00 AM</option>
                            <option value="10:00:00" selected>10:00 AM</option>
                            <option value="11:00:00">11:00 AM</option>
                            <option value="13:00:00">01:00 PM</option>
                            <option value="14:00:00">02:00 PM</option>
                            <option value="15:00:00">03:00 PM</option>
                            <option value="16:00:00">04:00 PM</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="drive_notes">Special Requests / Driving Notes</label>
                    <textarea id="drive_notes" name="notes" class="form-control" rows="2" placeholder="Route preference, trade-in vehicle, questions, etc."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-modal-close="addDriveModal">Cancel</button>
                <button type="submit" class="btn btn-primary">Enqueue Request</button>
            </div>
        </form>
    </div>
</div>

<!-- =======================================================
     MODAL: EDIT TEST DRIVE STATUS
     ======================================================= -->
<div class="modal-overlay" id="editTestDriveModal">
    <div class="modal-container" style="max-width: 500px;">
        <div class="modal-header">
            <h3 class="card-title">Update Test Drive Status</h3>
            <button class="modal-close-btn" data-modal-close="editTestDriveModal">&times;</button>
        </div>
        <form method="POST" action="test-drives.php">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" id="edit_drive_id" name="id" value="">
            <div class="modal-body">
                <p style="font-size: 14px; margin-bottom: 16px;">
                    Customer: <strong id="edit_drive_customer" style="color: #ffffff;"></strong>
                </p>

                <div class="form-group">
                    <label class="form-label" for="edit_drive_status">Select Status *</label>
                    <select id="edit_drive_status" name="status" class="form-select" required>
                        <option value="Pending">Pending (In Queue)</option>
                        <option value="Approved">Approved</option>
                        <option value="In-Progress">In-Progress (Out on road)</option>
                        <option value="Completed">Completed (Returned)</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="edit_drive_notes">Follow-up / Outcome Notes</label>
                    <textarea id="edit_drive_notes" name="notes" class="form-control" rows="3" placeholder="Customer feedback, inspection status, negotiation result, etc."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-modal-close="editTestDriveModal">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Status</button>
            </div>
        </form>
    </div>
</div>

<?php
require_once __DIR__ . '/templates/footer.php';
?>
