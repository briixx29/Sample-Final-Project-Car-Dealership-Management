<?php
/**
 * Customer Inquiry Management
 * CarDeal Dealership System
 */

require_once __DIR__ . '/autoload.php';

use App\Services\AuthService;
use App\Services\InquiryService;
use App\Services\VehicleService;

AuthService::requireAuth();

$currentUser = AuthService::getCurrentUser();
$pageTitle = 'Customer Inquiries';
$errors = [];

// ==========================================
// HANDLE ACTIONS (CREATE, UPDATE STATUS, DELETE)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Create Inquiry
    if ($action === 'create') {
        $result = InquiryService::create($_POST);
        if ($result['success']) {
            header("Location: inquiries.php?success=" . urlencode($result['message']));
            exit;
        } else {
            $errors = $result['errors'] ?? [];
            $errorMessage = $result['message'];
        }
    }

    // 2. Update Status & Staff Notes
    elseif ($action === 'update_status') {
        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? 'Pending';
        $notes = $_POST['staff_notes'] ?? null;
        $result = InquiryService::updateStatus($id, $status, $notes);
        if ($result['success']) {
            header("Location: inquiries.php?success=" . urlencode($result['message']));
        } else {
            header("Location: inquiries.php?error=" . urlencode($result['message']));
        }
        exit;
    }

    // 3. Delete Inquiry
    elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $result = InquiryService::delete($id);
        if ($result['success']) {
            header("Location: inquiries.php?success=" . urlencode($result['message']));
        } else {
            header("Location: inquiries.php?error=" . urlencode($result['message']));
        }
        exit;
    }
}

// ==========================================
// DATA FETCHING & FILTERING
// ==========================================
$statusFilter = $_GET['status'] ?? 'all';
$inquiries = InquiryService::getAll($statusFilter);
$stats = InquiryService::getCountByStatus();

// Fetch available vehicles for the Add modal dropdown
$availableVehicles = VehicleService::getAll(true);
$preselectVehicleId = !empty($_GET['preselect_vehicle']) ? (int)$_GET['preselect_vehicle'] : null;

require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/sidebar.php';
?>

<main class="main-content">
    <div class="topbar">
        <div class="topbar-left">
            <h1 class="page-title">Customer Inquiry Management</h1>
        </div>
        <div class="topbar-right">
            <button class="btn btn-primary btn-sm" data-modal-open="addInquiryModal">
                + Record New Inquiry
            </button>
        </div>
    </div>

    <div class="content-area">
        <?php require_once __DIR__ . '/templates/alerts.php'; ?>

        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
        <?php endif; ?>

        <!-- Inquiry Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card primary">
                <div class="stat-icon">💬</div>
                <div>
                    <div class="stat-value"><?= number_format($stats['total_inquiries']) ?></div>
                    <div class="stat-label">Total Inquiries Logged</div>
                </div>
            </div>

            <div class="stat-card warning">
                <div class="stat-icon">⏳</div>
                <div>
                    <div class="stat-value"><?= number_format($stats['pending_count'] ?? 0) ?></div>
                    <div class="stat-label">Awaiting Staff Response</div>
                </div>
            </div>

            <div class="stat-card info">
                <div class="stat-icon">📞</div>
                <div>
                    <div class="stat-value"><?= number_format($stats['contacted_count'] ?? 0) ?></div>
                    <div class="stat-label">Customer Contacted</div>
                </div>
            </div>

            <div class="stat-card success">
                <div class="stat-icon">✅</div>
                <div>
                    <div class="stat-value"><?= number_format($stats['resolved_count'] ?? 0) ?></div>
                    <div class="stat-label">Resolved Inquiries</div>
                </div>
            </div>
        </div>

        <!-- Filter Toolbar -->
        <div class="filter-toolbar">
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <a href="inquiries.php?status=all" class="btn btn-sm <?= $statusFilter === 'all' ? 'btn-primary' : 'btn-outline' ?>">
                    All (<?= $stats['total_inquiries'] ?>)
                </a>
                <a href="inquiries.php?status=Pending" class="btn btn-sm <?= $statusFilter === 'Pending' ? 'btn-primary' : 'btn-outline' ?>">
                    Pending (<?= $stats['pending_count'] ?? 0 ?>)
                </a>
                <a href="inquiries.php?status=Contacted" class="btn btn-sm <?= $statusFilter === 'Contacted' ? 'btn-primary' : 'btn-outline' ?>">
                    Contacted (<?= $stats['contacted_count'] ?? 0 ?>)
                </a>
                <a href="inquiries.php?status=Resolved" class="btn btn-sm <?= $statusFilter === 'Resolved' ? 'btn-primary' : 'btn-outline' ?>">
                    Resolved (<?= $stats['resolved_count'] ?? 0 ?>)
                </a>
                <a href="inquiries.php?status=Closed" class="btn btn-sm <?= $statusFilter === 'Closed' ? 'btn-primary' : 'btn-outline' ?>">
                    Closed
                </a>
            </div>
        </div>

        <!-- Inquiries Table Card -->
        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Customer Inquiry Records</h3>
                    <p style="font-size: 12px; color: var(--text-muted);">
                        Track incoming customer questions, interest in specific vehicles, and dealership follow-up notes.
                    </p>
                </div>
            </div>
            <div class="card-body" style="padding: 0;">
                <?php if (empty($inquiries)): ?>
                    <div style="text-align: center; padding: 48px 24px; color: var(--text-muted);">
                        No inquiries match the selected filter.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Customer</th>
                                    <th>Contact Info</th>
                                    <th>Vehicle Inquired</th>
                                    <th>Question / Details</th>
                                    <th>Inquiry Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($inquiries as $inq): ?>
                                    <tr>
                                        <td><strong>#<?= $inq->getId() ?></strong></td>
                                        <td>
                                            <div style="font-weight: 600;"><?= htmlspecialchars($inq->getCustomerName()) ?></div>
                                        </td>
                                        <td>
                                            <div>📞 <?= htmlspecialchars($inq->getContactNumber()) ?></div>
                                            <?php if ($inq->getEmail()): ?>
                                                <div style="font-size: 12px; color: var(--text-muted);">✉️ <?= htmlspecialchars($inq->getEmail()) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($inq->getVehicleId()): ?>
                                                <a href="vehicle-details.php?id=<?= $inq->getVehicleId() ?>" style="color: var(--primary); font-weight: 600;">
                                                    🚘 <?= htmlspecialchars($inq->getVehicleInfo() ?: 'Vehicle #' . $inq->getVehicleId()) ?>
                                                </a>
                                            <?php else: ?>
                                                <span style="color: var(--text-dim);">General Inquiry</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="max-width: 280px;">
                                            <div style="font-size: 13px; line-height: 1.4;">
                                                <?= htmlspecialchars($inq->getQuestion()) ?>
                                            </div>
                                            <?php if ($inq->getStaffNotes()): ?>
                                                <div style="font-size: 11px; color: #38bdf8; margin-top: 6px; background: var(--bg-surface); padding: 4px 8px; border-radius: 4px;">
                                                    <strong>Staff note:</strong> <?= htmlspecialchars($inq->getStaffNotes()) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span style="font-size: 12px; color: var(--text-muted);">
                                                <?= date('M d, Y', strtotime($inq->getInquiryDate())) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            $badgeClass = 'badge-pending';
                                            if ($inq->getStatus() === 'Contacted') $badgeClass = 'badge-approved';
                                            elseif ($inq->getStatus() === 'Resolved') $badgeClass = 'badge-completed';
                                            elseif ($inq->getStatus() === 'Closed') $badgeClass = 'badge-sold';
                                            ?>
                                            <span class="badge <?= $badgeClass ?>"><?= $inq->getStatus() ?></span>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 6px;">
                                                <button type="button" class="btn btn-secondary btn-sm"
                                                        onclick='populateEditInquiry(<?= json_encode($inq->toArray()) ?>)'>
                                                    Update
                                                </button>
                                                <form method="POST" style="margin: 0;" onsubmit="return confirm('Delete inquiry #<?= $inq->getId() ?>?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= $inq->getId() ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm" title="Delete">✕</button>
                                                </form>
                                            </div>
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
     MODAL: ADD NEW INQUIRY
     ======================================================= -->
<div class="modal-overlay <?= $preselectVehicleId ? 'open' : '' ?>" id="addInquiryModal">
    <div class="modal-container">
        <div class="modal-header">
            <h3 class="card-title">Record Customer Inquiry</h3>
            <button class="modal-close-btn" data-modal-close="addInquiryModal">&times;</button>
        </div>
        <form method="POST" action="inquiries.php">
            <input type="hidden" name="action" value="create">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="inq_customer_name">Customer Full Name *</label>
                        <input type="text" id="inq_customer_name" name="customer_name" class="form-control" placeholder="e.g. Michael Jordan" required>
                        <?php if (isset($errors['customer_name'])): ?><div class="form-error"><?= $errors['customer_name'] ?></div><?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="inq_contact_number">Contact Phone Number *</label>
                        <input type="text" id="inq_contact_number" name="contact_number" class="form-control" placeholder="e.g. +1 (555) 019-2834" required>
                        <?php if (isset($errors['contact_number'])): ?><div class="form-error"><?= $errors['contact_number'] ?></div><?php endif; ?>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="inq_email">Customer Email Address</label>
                        <input type="email" id="inq_email" name="email" class="form-control" placeholder="customer@example.com">
                        <?php if (isset($errors['email'])): ?><div class="form-error"><?= $errors['email'] ?></div><?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="inq_vehicle_id">Target Vehicle (Optional)</label>
                        <select id="inq_vehicle_id" name="vehicle_id" class="form-select">
                            <option value="">-- General Dealership Inquiry --</option>
                            <?php foreach ($availableVehicles as $v): ?>
                                <option value="<?= $v->getId() ?>" <?= $preselectVehicleId === $v->getId() ? 'selected' : '' ?>>
                                    #<?= $v->getId() ?> - <?= htmlspecialchars($v->getFullName()) ?> (<?= $v->getFormattedPrice() ?>) - <?= $v->getStatus() ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="inq_date">Inquiry Date *</label>
                        <input type="date" id="inq_date" name="inquiry_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="inq_status">Initial Status</label>
                        <select id="inq_status" name="status" class="form-select">
                            <option value="Pending" selected>Pending (Requires follow-up)</option>
                            <option value="Contacted">Contacted</option>
                            <option value="Resolved">Resolved</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="inq_question">Customer Question / Details *</label>
                    <textarea id="inq_question" name="question" class="form-control" rows="3" placeholder="Financing, features, test-drive request, trade-in, etc." required></textarea>
                    <?php if (isset($errors['question'])): ?><div class="form-error"><?= $errors['question'] ?></div><?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label" for="inq_staff_notes">Initial Staff Notes</label>
                    <textarea id="inq_staff_notes" name="staff_notes" class="form-control" rows="2" placeholder="Internal notes on conversation or action items"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-modal-close="addInquiryModal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Inquiry</button>
            </div>
        </form>
    </div>
</div>

<!-- =======================================================
     MODAL: EDIT / UPDATE INQUIRY STATUS
     ======================================================= -->
<div class="modal-overlay" id="editInquiryModal">
    <div class="modal-container" style="max-width: 500px;">
        <div class="modal-header">
            <h3 class="card-title">Update Inquiry Status</h3>
            <button class="modal-close-btn" data-modal-close="editInquiryModal">&times;</button>
        </div>
        <form method="POST" action="inquiries.php">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" id="edit_inquiry_id" name="id" value="">
            <div class="modal-body">
                <p style="font-size: 14px; margin-bottom: 16px;">
                    Updating record for customer: <strong id="edit_inq_customer" style="color: #ffffff;"></strong>
                </p>

                <div class="form-group">
                    <label class="form-label" for="edit_inq_status">Select Status *</label>
                    <select id="edit_inq_status" name="status" class="form-select" required>
                        <option value="Pending">Pending (Awaiting action)</option>
                        <option value="Contacted">Contacted (Spoke with customer)</option>
                        <option value="Resolved">Resolved (Question answered / Deal made)</option>
                        <option value="Closed">Closed (Not interested / Closed)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="edit_inq_notes">Staff Follow-up Notes</label>
                    <textarea id="edit_inq_notes" name="staff_notes" class="form-control" rows="3" placeholder="Enter notes from call, quote sent, etc."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-modal-close="editInquiryModal">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Status</button>
            </div>
        </form>
    </div>
</div>

<?php
require_once __DIR__ . '/templates/footer.php';
?>
