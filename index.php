<?php
/**
 * Dashboard
 * CarDeal: Web-Based Car Dealership Management System
 */

require_once __DIR__ . '/autoload.php';

use App\Services\AuthService;
use App\Services\VehicleService;
use App\Services\InquiryService;
use App\Services\TestDriveService;

AuthService::requireAuth();

$currentUser = AuthService::getCurrentUser();
$pageTitle = 'Dashboard';

// Handle quick queue dispatch from dashboard
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'process_queue') {
    $res = TestDriveService::processNextInQueue('Approved', 'Processed via Dashboard Queue Dispatcher');
    if ($res['success']) {
        header("Location: index.php?success=" . urlencode($res['message']));
    } else {
        header("Location: index.php?error=" . urlencode($res['message']));
    }
    exit;
}

// Fetch stats and lists
$invStats = VehicleService::getStats();
$inqStats = InquiryService::getCountByStatus();
$driveStats = TestDriveService::getStats();

$pendingQueue = TestDriveService::getPendingQueue();
$pendingList = $pendingQueue->toArray();

$recentVehicles = array_slice(VehicleService::getAll(true), 0, 4);

require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/sidebar.php';
?>

<main class="main-content">
    <div class="topbar">
        <div class="topbar-left">
            <h1 class="page-title">Dealership Dashboard</h1>
        </div>
        <div class="topbar-right">
            <div class="clock-widget" id="liveClock">Loading time...</div>
            <a href="vehicles.php" class="btn btn-primary btn-sm">+ Add Vehicle</a>
        </div>
    </div>

    <div class="content-area">
        <?php require_once __DIR__ . '/templates/alerts.php'; ?>

        <!-- Welcome Banner -->
        <div class="dsa-hero">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
                <div>
                    <h2 style="font-size: 24px; font-weight: 800; margin-bottom: 6px;">
                        Welcome back, <?= htmlspecialchars($currentUser['full_name']) ?>!
                    </h2>
                    <p style="color: var(--text-muted); font-size: 14px; max-width: 650px;">
                        CarDeal centralizes vehicle inventory tracking, customer inquiries, and FIFO test-drive queue dispatching powered by high-performance data structures and algorithms.
                    </p>
                </div>
                <div style="display: flex; gap: 10px;">
                    <a href="dsa-showcase.php" class="btn btn-secondary btn-sm">
                        <span>⚡</span> View DSA Engine
                    </a>
                    <a href="test-drives.php" class="btn btn-primary btn-sm">
                        <span>⏱️</span> Test-Drive Queue (<?= $pendingQueue->getSize() ?>)
                    </a>
                </div>
            </div>
        </div>

        <!-- Metric Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card primary">
                <div class="stat-icon">🚘</div>
                <div>
                    <div class="stat-value"><?= number_format($invStats['total_vehicles']) ?></div>
                    <div class="stat-label">Total Vehicles in Stock</div>
                </div>
            </div>

            <div class="stat-card success">
                <div class="stat-icon">✨</div>
                <div>
                    <div class="stat-value"><?= number_format($invStats['available_count']) ?></div>
                    <div class="stat-label">Available for Sale</div>
                </div>
            </div>

            <div class="stat-card warning">
                <div class="stat-icon">🏷️</div>
                <div>
                    <div class="stat-value"><?= number_format($invStats['sold_count']) ?></div>
                    <div class="stat-label">Sold (Retained in DB)</div>
                </div>
            </div>

            <div class="stat-card info">
                <div class="stat-icon">💬</div>
                <div>
                    <div class="stat-value"><?= number_format($inqStats['pending_count'] ?? 0) ?></div>
                    <div class="stat-label">Pending Inquiries</div>
                </div>
            </div>

            <div class="stat-card primary">
                <div class="stat-icon">⏱️</div>
                <div>
                    <div class="stat-value"><?= $pendingQueue->getSize() ?></div>
                    <div class="stat-label">In Test-Drive Queue (FIFO)</div>
                </div>
            </div>

            <div class="stat-card success">
                <div class="stat-icon">💰</div>
                <div>
                    <div class="stat-value">$<?= number_format($invStats['total_available_value']) ?></div>
                    <div class="stat-label">Active Inventory Value</div>
                </div>
            </div>
        </div>

        <!-- FIFO Queue Processing Section -->
        <div class="queue-box">
            <div class="queue-header">
                <div>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <h3 style="font-size: 18px;">Active FIFO Test-Drive Queue</h3>
                        <span class="badge badge-dsa">Data Structure: Linked List Queue</span>
                    </div>
                    <p style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">
                        Requests are processed strictly in First-In, First-Out sequence (O(1) Enqueue & Dequeue).
                    </p>
                </div>

                <?php if (!$pendingQueue->isEmpty()): ?>
                    <form method="POST" style="margin: 0;" onsubmit="return confirm('Process and approve the FRONT request in the FIFO Queue?');">
                        <input type="hidden" name="action" value="process_queue">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <span style="font-size: 14px;">⚡</span> Dispatch / Process Next (FIFO)
                        </button>
                    </form>
                <?php else: ?>
                    <span class="badge badge-available">Queue Empty • All Caught Up</span>
                <?php endif; ?>
            </div>

            <?php if (empty($pendingList)): ?>
                <div style="text-align: center; padding: 24px; color: var(--text-muted); font-size: 14px;">
                    No pending test-drive requests in queue. <a href="test-drives.php" style="color:var(--primary); text-decoration:underline;">Schedule a new test-drive</a>.
                </div>
            <?php else: ?>
                <div class="queue-track">
                    <?php foreach ($pendingList as $idx => $drive): ?>
                        <div class="queue-card <?= $idx === 0 ? 'front' : '' ?>">
                            <div class="queue-tag">
                                <?= $idx === 0 ? 'FRONT (NEXT IN LINE)' : 'POSITION #' . ($idx + 1) ?>
                            </div>
                            <div style="font-weight: 700; font-size: 15px; margin-top: 6px;">
                                <?= htmlspecialchars($drive->getCustomerName()) ?>
                            </div>
                            <div style="font-size: 12px; color: var(--primary); font-weight: 600; margin: 4px 0;">
                                <?= htmlspecialchars($drive->getVehicleInfo() ?: 'Vehicle #' . $drive->getVehicleId()) ?>
                            </div>
                            <div style="font-size: 12px; color: var(--text-muted);">
                                📅 <?= htmlspecialchars($drive->getFormattedDateTime()) ?>
                            </div>
                            <div style="font-size: 11px; color: var(--text-dim); margin-top: 8px;">
                                📞 <?= htmlspecialchars($drive->getContactNumber()) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Vehicle Inventory Preview -->
        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Recent Vehicles in Inventory</h3>
                    <p style="font-size: 12px; color: var(--text-muted);">
                        Sorted using Merge Sort & Indexed via Binary Search Tree (BST).
                    </p>
                </div>
                <div style="display: flex; gap: 8px;">
                    <a href="vehicles.php" class="btn btn-outline btn-sm">View Full Inventory →</a>
                </div>
            </div>
            <div class="card-body">
                <div class="vehicle-grid">
                    <?php foreach ($recentVehicles as $v): ?>
                        <div class="vehicle-card">
                            <div class="vehicle-thumb">
                                <img src="<?= htmlspecialchars($v->getImageUrl()) ?>" alt="<?= htmlspecialchars($v->getFullName()) ?>" loading="lazy">
                                <span class="vehicle-year-badge"><?= $v->getYear() ?></span>
                                <span class="badge vehicle-status-badge <?= $v->isAvailable() ? 'badge-available' : 'badge-sold' ?>">
                                    <?= $v->getStatus() ?>
                                </span>
                            </div>
                            <div class="vehicle-body">
                                <h4 class="vehicle-title"><?= htmlspecialchars($v->getBrand() . ' ' . $v->getModel()) ?></h4>
                                <div class="vehicle-meta-tags">
                                    <span class="meta-pill">ID #<?= $v->getId() ?></span>
                                    <span class="meta-pill"><?= htmlspecialchars($v->getColor()) ?></span>
                                    <span class="meta-pill"><?= htmlspecialchars($v->getTransmission()) ?></span>
                                    <span class="meta-pill"><?= htmlspecialchars($v->getFuelType()) ?></span>
                                    <span class="meta-pill"><?= $v->getFormattedMileage() ?></span>
                                </div>
                                <div class="vehicle-price">
                                    <span><?= $v->getFormattedPrice() ?></span>
                                    <a href="vehicle-details.php?id=<?= $v->getId() ?>" class="btn btn-outline btn-sm">Details</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    </div>
</main>

<?php
require_once __DIR__ . '/templates/footer.php';
?>
