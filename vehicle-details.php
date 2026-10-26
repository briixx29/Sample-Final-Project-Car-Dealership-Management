<?php
/**
 * Vehicle Details View
 * CarDeal Dealership System
 */

require_once __DIR__ . '/autoload.php';

use App\Services\AuthService;
use App\Services\VehicleService;

AuthService::requireAuth();

$id = (int)($_GET['id'] ?? 0);
$vehicle = VehicleService::getById($id);

if (!$vehicle) {
    header("Location: vehicles.php?error=" . urlencode("Vehicle #{$id} not found."));
    exit;
}

// Demonstrate BST metrics for this vehicle
$bstLookup = VehicleService::getByIdViaBST($id);

$pageTitle = $vehicle->getFullName();

require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/sidebar.php';
?>

<main class="main-content">
    <div class="topbar">
        <div class="topbar-left">
            <a href="vehicles.php" class="btn btn-outline btn-sm">← Back to Inventory</a>
            <h1 class="page-title" style="margin-left: 8px;"><?= htmlspecialchars($vehicle->getFullName()) ?></h1>
        </div>
        <div class="topbar-right">
            <!-- Status Toggle -->
            <form method="POST" action="vehicles.php" style="margin: 0; display: inline;">
                <input type="hidden" name="action" value="toggle_status">
                <input type="hidden" name="id" value="<?= $vehicle->getId() ?>">
                <input type="hidden" name="target_status" value="<?= $vehicle->isAvailable() ? 'Sold' : 'Available' ?>">
                <button type="submit" class="btn <?= $vehicle->isAvailable() ? 'btn-danger' : 'btn-success' ?> btn-sm">
                    <?= $vehicle->isAvailable() ? 'Mark as Sold' : 'Mark as Available' ?>
                </button>
            </form>
            <a href="test-drives.php?preselect_vehicle=<?= $vehicle->getId() ?>" class="btn btn-primary btn-sm">
                ⏱️ Book Test-Drive
            </a>
        </div>
    </div>

    <div class="content-area">
        <?php require_once __DIR__ . '/templates/alerts.php'; ?>

        <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 28px; margin-bottom: 28px;">
            <!-- Vehicle Showcase Card -->
            <div class="card" style="margin-bottom: 0;">
                <div style="height: 360px; width: 100%; overflow: hidden; background: #08101a; position: relative;">
                    <img src="<?= htmlspecialchars($vehicle->getImageUrl()) ?>" alt="<?= htmlspecialchars($vehicle->getFullName()) ?>" 
                         style="width: 100%; height: 100%; object-fit: cover;">
                    <span class="badge vehicle-status-badge <?= $vehicle->isAvailable() ? 'badge-available' : 'badge-sold' ?>" 
                          style="position: absolute; top: 16px; right: 16px; font-size: 14px; padding: 6px 14px;">
                        <?= $vehicle->getStatus() ?>
                    </span>
                </div>
                <div class="card-body">
                    <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 12px;">
                        <h2 style="font-size: 26px;"><?= htmlspecialchars($vehicle->getFullName()) ?></h2>
                        <span style="font-size: 28px; font-weight: 800; color: var(--primary); font-family: 'Outfit';">
                            <?= $vehicle->getFormattedPrice() ?>
                        </span>
                    </div>

                    <p style="color: var(--text-muted); font-size: 14px; line-height: 1.6; margin-bottom: 20px;">
                        <?= nl2br(htmlspecialchars($vehicle->getDescription() ?: 'No additional notes provided for this vehicle.')) ?>
                    </p>

                    <div style="display: flex; gap: 10px;">
                        <a href="inquiries.php?preselect_vehicle=<?= $vehicle->getId() ?>" class="btn btn-secondary btn-sm">
                            💬 Record Customer Inquiry
                        </a>
                    </div>
                </div>
            </div>

            <!-- Technical Specifications Table & BST Info -->
            <div style="display: flex; flex-direction: column; gap: 24px;">
                <div class="card" style="margin-bottom: 0;">
                    <div class="card-header">
                        <h3 class="card-title">Technical Specifications</h3>
                    </div>
                    <div class="card-body" style="padding: 0;">
                        <table class="custom-table">
                            <tbody>
                                <tr>
                                    <td style="color: var(--text-muted); width: 40%;">Vehicle Record ID</td>
                                    <td><strong>#<?= $vehicle->getId() ?></strong></td>
                                </tr>
                                <tr>
                                    <td style="color: var(--text-muted);">Brand & Model</td>
                                    <td><?= htmlspecialchars($vehicle->getBrand() . ' ' . $vehicle->getModel()) ?></td>
                                </tr>
                                <tr>
                                    <td style="color: var(--text-muted);">Year of Manufacture</td>
                                    <td><?= $vehicle->getYear() ?></td>
                                </tr>
                                <tr>
                                    <td style="color: var(--text-muted);">Exterior Color</td>
                                    <td><?= htmlspecialchars($vehicle->getColor()) ?></td>
                                </tr>
                                <tr>
                                    <td style="color: var(--text-muted);">Transmission</td>
                                    <td><?= htmlspecialchars($vehicle->getTransmission()) ?></td>
                                </tr>
                                <tr>
                                    <td style="color: var(--text-muted);">Fuel / Powertrain</td>
                                    <td><?= htmlspecialchars($vehicle->getFuelType()) ?></td>
                                </tr>
                                <tr>
                                    <td style="color: var(--text-muted);">Odometer Mileage</td>
                                    <td><?= $vehicle->getFormattedMileage() ?></td>
                                </tr>
                                <tr>
                                    <td style="color: var(--text-muted);">VIN (Chassis No.)</td>
                                    <td><code class="code-pill"><?= htmlspecialchars($vehicle->getVin()) ?></code></td>
                                </tr>
                                <tr>
                                    <td style="color: var(--text-muted);">Inventory Status</td>
                                    <td>
                                        <span class="badge <?= $vehicle->isAvailable() ? 'badge-available' : 'badge-sold' ?>">
                                            <?= $vehicle->getStatus() ?>
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- BST Traversal Inspection Box -->
                <div class="card" style="margin-bottom: 0;">
                    <div class="card-header">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <h3 class="card-title">⚡ BST Traversal Index Path</h3>
                            <span class="badge badge-dsa">O(log n) Lookup</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 12px;">
                            This vehicle was indexed in the system's in-memory <strong>Binary Search Tree</strong>. Lookup path comparison trace:
                        </p>
                        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                            <?php foreach ($bstLookup['search_path'] as $stepIdx => $node): ?>
                                <span class="badge <?= $node['node_id'] === $vehicle->getId() ? 'badge-available' : 'badge-dsa' ?>">
                                    Node #<?= $node['node_id'] ?> (<?= htmlspecialchars($node['vehicle_name']) ?>)
                                </span>
                                <?php if ($stepIdx < count($bstLookup['search_path']) - 1): ?>
                                    <span style="color: var(--primary);">→</span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <div style="font-size: 12px; color: var(--text-dim); margin-top: 10px;">
                            Found in <?= $bstLookup['execution_time_ms'] ?> ms after <?= $bstLookup['comparisons_count'] ?> comparison(s).
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<?php
require_once __DIR__ . '/templates/footer.php';
?>
