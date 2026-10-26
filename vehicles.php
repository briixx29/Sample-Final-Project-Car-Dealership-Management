<?php
/**
 * Vehicle Inventory Management
 * CarDeal Dealership System
 */

require_once __DIR__ . '/autoload.php';

use App\Services\AuthService;
use App\Services\VehicleService;
use App\Algorithms\LinearSearch;
use App\Algorithms\MergeSort;
use App\DataStructures\BinarySearchTree;

AuthService::requireAuth();

$currentUser = AuthService::getCurrentUser();
$pageTitle = 'Vehicle Inventory';
$errors = [];

// ==========================================
// HANDLE ACTIONS (CREATE, UPDATE, DELETE, TOGGLE)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Create Vehicle
    if ($action === 'create') {
        $result = VehicleService::create($_POST);
        if ($result['success']) {
            header("Location: vehicles.php?success=" . urlencode($result['message']));
            exit;
        } else {
            $errors = $result['errors'] ?? [];
            $errorMessage = $result['message'];
        }
    }

    // 2. Update Vehicle
    elseif ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $result = VehicleService::update($id, $_POST);
        if ($result['success']) {
            header("Location: vehicles.php?success=" . urlencode($result['message']));
            exit;
        } else {
            $errors = $result['errors'] ?? [];
            $errorMessage = $result['message'];
        }
    }

    // 3. Toggle Status (Available <-> Sold)
    elseif ($action === 'toggle_status') {
        $id = (int)($_POST['id'] ?? 0);
        $targetStatus = $_POST['target_status'] ?? 'Sold';
        $result = VehicleService::updateStatus($id, $targetStatus);
        if ($result['success']) {
            header("Location: vehicles.php?success=" . urlencode($result['message']));
        } else {
            header("Location: vehicles.php?error=" . urlencode($result['message']));
        }
        exit;
    }

    // 4. Delete Vehicle (Admin only or soft-delete check)
    elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $permanent = AuthService::isAdmin();
        $result = VehicleService::delete($id, $permanent);
        if ($result['success']) {
            header("Location: vehicles.php?success=" . urlencode($result['message']));
        } else {
            header("Location: vehicles.php?error=" . urlencode($result['message']));
        }
        exit;
    }
}

// ==========================================
// SEARCH, SORT & BST LOOKUP DATA PROCESSING
// ==========================================
$allVehicles = VehicleService::getAll(true);

$searchQuery = trim($_GET['search'] ?? '');
$searchField = $_GET['search_field'] ?? 'all';
$sortField = $_GET['sort_field'] ?? 'price';
$sortOrder = strtoupper($_GET['sort_order'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
$statusFilter = $_GET['status_filter'] ?? 'all';
$bstSearchId = !empty($_GET['bst_id']) ? (int)$_GET['bst_id'] : null;

$dsaMetrics = null;

// Filter by Status first if specified
$filteredVehicles = $allVehicles;
if ($statusFilter === 'available') {
    $filteredVehicles = array_filter($filteredVehicles, fn($v) => $v->isAvailable());
} elseif ($statusFilter === 'sold') {
    $filteredVehicles = array_filter($filteredVehicles, fn($v) => $v->isSold());
}

// 1. Linear Search Integration (DSA Component 5.1)
if ($searchQuery !== '') {
    $searchReport = LinearSearch::searchWithMetrics($filteredVehicles, $searchQuery, $searchField);
    $filteredVehicles = $searchReport['results'];
    $dsaMetrics = [
        'title' => 'Linear Search Execution',
        'details' => "Found {$searchReport['matches_count']} record(s) matching '{$searchQuery}' across '{$searchField}'. Scanned {$searchReport['total_items_scanned']} vehicles in {$searchReport['execution_time_ms']} ms.",
        'complexity' => $searchReport['time_complexity']
    ];
}

// 2. BST Search Integration (DSA Component 5.3)
$bstReport = null;
if ($bstSearchId !== null && $bstSearchId > 0) {
    $bst = BinarySearchTree::buildFromVehicles($allVehicles);
    $bstReport = $bst->searchWithMetrics($bstSearchId);
    if ($bstReport['found'] && $bstReport['result']) {
        // Narrow display to found vehicle
        $filteredVehicles = [$bstReport['result']];
        $dsaMetrics = [
            'title' => 'Binary Search Tree (BST) Lookup',
            'details' => "Vehicle #{$bstSearchId} located via ID node traversal. Depth traversed: " . count($bstReport['search_path']) . " node(s) in {$bstReport['execution_time_ms']} ms.",
            'complexity' => 'O(log n) average search time'
        ];
    } else {
        $filteredVehicles = [];
        $dsaMetrics = [
            'title' => 'Binary Search Tree (BST) Lookup',
            'details' => "Vehicle ID #{$bstSearchId} not found in BST. Visited " . count($bstReport['search_path']) . " node(s) before reaching leaf null pointer.",
            'complexity' => 'O(log n) tree search'
        ];
    }
}

// 3. Merge Sort Integration (DSA Component 5.2)
if ($bstSearchId === null || empty($filteredVehicles)) {
    $sortReport = MergeSort::sortWithMetrics($filteredVehicles, $sortField, $sortOrder);
    $filteredVehicles = $sortReport['sorted_records'];
    if (!$dsaMetrics) {
        $dsaMetrics = [
            'title' => 'Merge Sort Execution',
            'details' => "Sorted {$sortReport['items_count']} vehicle(s) by " . strtoupper($sortField) . " ({$sortOrder}) in {$sortReport['execution_time_ms']} ms with {$sortReport['comparisons_count']} comparisons.",
            'complexity' => 'O(n log n) Best/Avg/Worst divide-and-conquer'
        ];
    }
}

require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/sidebar.php';
?>

<main class="main-content">
    <div class="topbar">
        <div class="topbar-left">
            <h1 class="page-title">Vehicle Inventory Management</h1>
        </div>
        <div class="topbar-right">
            <button class="btn btn-primary btn-sm" data-modal-open="addVehicleModal">
                + Add New Vehicle
            </button>
            <button class="btn btn-secondary btn-sm" data-modal-open="bstLookupModal">
                ⚡ BST ID Lookup
            </button>
        </div>
    </div>

    <div class="content-area">
        <?php require_once __DIR__ . '/templates/alerts.php'; ?>

        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($errorMessage) ?>
            </div>
        <?php endif; ?>

        <!-- DSA Execution Indicator Banner -->
        <?php if ($dsaMetrics): ?>
            <div style="background: rgba(214, 40, 57, 0.08); border: 1px solid var(--border-light); border-left: 4px solid var(--primary); border-radius: var(--radius-md); padding: 12px 18px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                <div>
                    <span style="font-weight: 700; color: #ffffff; font-size: 13px;">⚡ <?= htmlspecialchars($dsaMetrics['title']) ?>:</span>
                    <span style="color: var(--text-muted); font-size: 13px; margin-left: 6px;"><?= htmlspecialchars($dsaMetrics['details']) ?></span>
                </div>
                <span class="badge badge-dsa"><?= htmlspecialchars($dsaMetrics['complexity']) ?></span>
            </div>
        <?php endif; ?>

        <!-- Search, Sort & Filter Toolbar -->
        <div class="filter-toolbar">
            <form method="GET" action="vehicles.php" style="display: contents;">
                <!-- Linear Search Input -->
                <div class="search-box">
                    <span class="search-icon">🔍</span>
                    <input type="text" name="search" class="form-control" 
                           placeholder="Search brand, model, VIN, or year..." 
                           value="<?= htmlspecialchars($searchQuery) ?>">
                </div>

                <div class="filter-controls">
                    <!-- Search Field -->
                    <select name="search_field" class="form-select" style="width: auto;">
                        <option value="all" <?= $searchField === 'all' ? 'selected' : '' ?>>Search All Fields</option>
                        <option value="brand" <?= $searchField === 'brand' ? 'selected' : '' ?>>Brand</option>
                        <option value="model" <?= $searchField === 'model' ? 'selected' : '' ?>>Model</option>
                        <option value="id" <?= $searchField === 'id' ? 'selected' : '' ?>>Vehicle ID</option>
                    </select>

                    <!-- Merge Sort Field -->
                    <select name="sort_field" class="form-select" style="width: auto;">
                        <option value="price" <?= $sortField === 'price' ? 'selected' : '' ?>>Sort: Price</option>
                        <option value="year" <?= $sortField === 'year' ? 'selected' : '' ?>>Sort: Year</option>
                        <option value="brand" <?= $sortField === 'brand' ? 'selected' : '' ?>>Sort: Brand</option>
                        <option value="model" <?= $sortField === 'model' ? 'selected' : '' ?>>Sort: Model</option>
                        <option value="id" <?= $sortField === 'id' ? 'selected' : '' ?>>Sort: ID</option>
                    </select>

                    <!-- Sort Direction -->
                    <select name="sort_order" class="form-select" style="width: auto;">
                        <option value="ASC" <?= $sortOrder === 'ASC' ? 'selected' : '' ?>>Ascending (Low → High)</option>
                        <option value="DESC" <?= $sortOrder === 'DESC' ? 'selected' : '' ?>>Descending (High → Low)</option>
                    </select>

                    <!-- Status Filter -->
                    <select name="status_filter" class="form-select" style="width: auto;">
                        <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All Statuses</option>
                        <option value="available" <?= $statusFilter === 'available' ? 'selected' : '' ?>>Available Only</option>
                        <option value="sold" <?= $statusFilter === 'sold' ? 'selected' : '' ?>>Sold Only</option>
                    </select>

                    <button type="submit" class="btn btn-secondary btn-sm">Filter & Sort</button>
                    
                    <?php if ($searchQuery !== '' || $statusFilter !== 'all' || $bstSearchId !== null || $sortField !== 'price'): ?>
                        <a href="vehicles.php" class="btn btn-outline btn-sm">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Vehicles Grid -->
        <?php if (empty($filteredVehicles)): ?>
            <div class="card">
                <div class="card-body" style="text-align: center; padding: 48px 24px;">
                    <div style="font-size: 48px; margin-bottom: 12px;">🚘</div>
                    <h3>No Vehicles Found</h3>
                    <p style="color: var(--text-muted); margin-top: 6px; font-size: 14px;">
                        No vehicle records matched your search query or filter criteria.
                    </p>
                    <div style="margin-top: 18px;">
                        <a href="vehicles.php" class="btn btn-secondary btn-sm">Clear Filters</a>
                        <button class="btn btn-primary btn-sm" data-modal-open="addVehicleModal">+ Add New Vehicle</button>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="vehicle-grid">
                <?php foreach ($filteredVehicles as $v): ?>
                    <div class="vehicle-card">
                        <div class="vehicle-thumb">
                            <img src="<?= htmlspecialchars($v->getImageUrl()) ?>" alt="<?= htmlspecialchars($v->getFullName()) ?>" loading="lazy">
                            <span class="vehicle-year-badge"><?= $v->getYear() ?></span>
                            <span class="badge vehicle-status-badge <?= $v->isAvailable() ? 'badge-available' : 'badge-sold' ?>">
                                <?= $v->getStatus() ?>
                            </span>
                        </div>
                        <div class="vehicle-body">
                            <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                                <h4 class="vehicle-title"><?= htmlspecialchars($v->getBrand() . ' ' . $v->getModel()) ?></h4>
                                <span style="font-size:11px; color:var(--text-dim); font-family:monospace;">ID #<?= $v->getId() ?></span>
                            </div>

                            <div class="vehicle-meta-tags">
                                <span class="meta-pill"><?= htmlspecialchars($v->getColor()) ?></span>
                                <span class="meta-pill"><?= htmlspecialchars($v->getTransmission()) ?></span>
                                <span class="meta-pill"><?= htmlspecialchars($v->getFuelType()) ?></span>
                                <span class="meta-pill"><?= $v->getFormattedMileage() ?></span>
                            </div>

                            <p style="font-size: 12px; color: var(--text-muted); margin-bottom: 12px; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                <?= htmlspecialchars($v->getDescription() ?: 'No description provided.') ?>
                            </p>

                            <div class="vehicle-price">
                                <span><?= $v->getFormattedPrice() ?></span>
                                <a href="vehicle-details.php?id=<?= $v->getId() ?>" class="btn btn-outline btn-sm">Inspect</a>
                            </div>

                            <div class="vehicle-actions">
                                <!-- Status Toggle Form -->
                                <form method="POST" style="margin: 0; flex: 1;">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="id" value="<?= $v->getId() ?>">
                                    <input type="hidden" name="target_status" value="<?= $v->isAvailable() ? 'Sold' : 'Available' ?>">
                                    <button type="submit" class="btn btn-outline btn-sm" style="width: 100%;" 
                                            title="Toggle between Available and Sold status">
                                        <?= $v->isAvailable() ? 'Mark Sold' : 'Mark Available' ?>
                                    </button>
                                </form>

                                <!-- Edit Trigger Button -->
                                <button type="button" class="btn btn-secondary btn-sm" 
                                        onclick='populateEditVehicle(<?= json_encode($v->toArray()) ?>)'>
                                    Edit
                                </button>

                                <!-- Delete Form (with confirmation) -->
                                <form method="POST" style="margin: 0;" onsubmit="return confirm('Are you sure you want to delete vehicle #<?= $v->getId() ?> (<?= htmlspecialchars($v->getFullName()) ?>)?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $v->getId() ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" title="Remove Record">
                                        ✕
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</main>

<!-- =======================================================
     MODAL: ADD NEW VEHICLE
     ======================================================= -->
<div class="modal-overlay" id="addVehicleModal">
    <div class="modal-container">
        <div class="modal-header">
            <h3 class="card-title">Add Vehicle to Inventory</h3>
            <button class="modal-close-btn" data-modal-close="addVehicleModal">&times;</button>
        </div>
        <form method="POST" action="vehicles.php">
            <input type="hidden" name="action" value="create">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="add_brand">Brand *</label>
                        <input type="text" id="add_brand" name="brand" class="form-control" placeholder="e.g. Toyota" required>
                        <?php if (isset($errors['brand'])): ?><div class="form-error"><?= $errors['brand'] ?></div><?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="add_model">Model *</label>
                        <input type="text" id="add_model" name="model" class="form-control" placeholder="e.g. Camry XSE" required>
                        <?php if (isset($errors['model'])): ?><div class="form-error"><?= $errors['model'] ?></div><?php endif; ?>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="add_year">Year *</label>
                        <input type="number" id="add_year" name="year" class="form-control" value="<?= date('Y') ?>" min="1980" max="<?= date('Y') + 1 ?>" required>
                        <?php if (isset($errors['year'])): ?><div class="form-error"><?= $errors['year'] ?></div><?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="add_color">Color *</label>
                        <input type="text" id="add_color" name="color" class="form-control" placeholder="e.g. Midnight Black" required>
                        <?php if (isset($errors['color'])): ?><div class="form-error"><?= $errors['color'] ?></div><?php endif; ?>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="add_transmission">Transmission *</label>
                        <select id="add_transmission" name="transmission" class="form-select" required>
                            <option value="Automatic">Automatic</option>
                            <option value="Manual">Manual</option>
                            <option value="CVT">CVT</option>
                            <option value="Dual-Clutch">Dual-Clutch</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="add_fuel_type">Fuel Type *</label>
                        <select id="add_fuel_type" name="fuel_type" class="form-select" required>
                            <option value="Gasoline">Gasoline</option>
                            <option value="Diesel">Diesel</option>
                            <option value="Hybrid">Hybrid</option>
                            <option value="Electric">Electric</option>
                            <option value="Plug-in Hybrid">Plug-in Hybrid</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="add_price">Price ($) *</label>
                        <input type="number" id="add_price" name="price" step="0.01" min="1" class="form-control" placeholder="e.g. 28500" required>
                        <?php if (isset($errors['price'])): ?><div class="form-error"><?= $errors['price'] ?></div><?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="add_mileage">Mileage (mi)</label>
                        <input type="number" id="add_mileage" name="mileage" min="0" class="form-control" value="0">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="add_vin">VIN (Vehicle Identification No.)</label>
                        <input type="text" id="add_vin" name="vin" class="form-control" placeholder="Leave empty to auto-generate">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="add_status">Availability Status *</label>
                        <select id="add_status" name="status" class="form-select" required>
                            <option value="Available" selected>Available</option>
                            <option value="Sold">Sold</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="add_image_url">Photo Image URL</label>
                    <input type="url" id="add_image_url" name="image_url" class="form-control" placeholder="https://example.com/car-photo.jpg">
                </div>

                <div class="form-group">
                    <label class="form-label" for="add_description">Vehicle Description / Notes</label>
                    <textarea id="add_description" name="description" class="form-control" rows="3" placeholder="Condition, packages, warranty, etc."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-modal-close="addVehicleModal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Vehicle</button>
            </div>
        </form>
    </div>
</div>

<!-- =======================================================
     MODAL: EDIT VEHICLE
     ======================================================= -->
<div class="modal-overlay" id="editVehicleModal">
    <div class="modal-container">
        <div class="modal-header">
            <h3 class="card-title">Edit Vehicle Record</h3>
            <button class="modal-close-btn" data-modal-close="editVehicleModal">&times;</button>
        </div>
        <form method="POST" action="vehicles.php">
            <input type="hidden" name="action" value="update">
            <input type="hidden" id="edit_vehicle_id" name="id" value="">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="edit_brand">Brand *</label>
                        <input type="text" id="edit_brand" name="brand" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="edit_model">Model *</label>
                        <input type="text" id="edit_model" name="model" class="form-control" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="edit_year">Year *</label>
                        <input type="number" id="edit_year" name="year" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="edit_color">Color *</label>
                        <input type="text" id="edit_color" name="color" class="form-control" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="edit_transmission">Transmission *</label>
                        <select id="edit_transmission" name="transmission" class="form-select" required>
                            <option value="Automatic">Automatic</option>
                            <option value="Manual">Manual</option>
                            <option value="CVT">CVT</option>
                            <option value="Dual-Clutch">Dual-Clutch</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="edit_fuel_type">Fuel Type *</label>
                        <select id="edit_fuel_type" name="fuel_type" class="form-select" required>
                            <option value="Gasoline">Gasoline</option>
                            <option value="Diesel">Diesel</option>
                            <option value="Hybrid">Hybrid</option>
                            <option value="Electric">Electric</option>
                            <option value="Plug-in Hybrid">Plug-in Hybrid</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="edit_price">Price ($) *</label>
                        <input type="number" id="edit_price" name="price" step="0.01" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="edit_mileage">Mileage</label>
                        <input type="number" id="edit_mileage" name="mileage" class="form-control">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="edit_vin">VIN</label>
                        <input type="text" id="edit_vin" name="vin" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="edit_status">Status *</label>
                        <select id="edit_status" name="status" class="form-select" required>
                            <option value="Available">Available</option>
                            <option value="Sold">Sold</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="edit_image_url">Photo Image URL</label>
                    <input type="url" id="edit_image_url" name="image_url" class="form-control">
                </div>

                <div class="form-group">
                    <label class="form-label" for="edit_description">Description</label>
                    <textarea id="edit_description" name="description" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-modal-close="editVehicleModal">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Vehicle</button>
            </div>
        </form>
    </div>
</div>

<!-- =======================================================
     MODAL: BINARY SEARCH TREE LOOKUP MODAL
     ======================================================= -->
<div class="modal-overlay" id="bstLookupModal">
    <div class="modal-container" style="max-width: 480px;">
        <div class="modal-header">
            <h3 class="card-title">⚡ BST Direct ID Lookup</h3>
            <button class="modal-close-btn" data-modal-close="bstLookupModal">&times;</button>
        </div>
        <form method="GET" action="vehicles.php">
            <div class="modal-body">
                <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 16px;">
                    This feature utilizes the <strong>Binary Search Tree (BST)</strong> algorithm to locate vehicle nodes by ID in O(log n) average time.
                </p>
                <div class="form-group">
                    <label class="form-label" for="bst_id_input">Enter Vehicle ID</label>
                    <input type="number" id="bst_id_input" name="bst_id" class="form-control" placeholder="e.g. 1, 2, 5" min="1" required autofocus>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-modal-close="bstLookupModal">Cancel</button>
                <button type="submit" class="btn btn-primary">Search via BST</button>
            </div>
        </form>
    </div>
</div>

<?php
require_once __DIR__ . '/templates/footer.php';
?>
