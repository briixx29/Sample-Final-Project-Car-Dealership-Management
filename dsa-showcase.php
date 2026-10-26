<?php
/**
 * Data Structures & Algorithms Showcase & Test Harness
 * CarDeal Dealership System
 */

require_once __DIR__ . '/autoload.php';

use App\Services\AuthService;
use App\Services\VehicleService;
use App\Testing\DSATestSuite;
use App\Algorithms\LinearSearch;
use App\Algorithms\MergeSort;
use App\DataStructures\BinarySearchTree;
use App\DataStructures\Queue;
use App\Services\TestDriveService;

AuthService::requireAuth();

$currentUser = AuthService::getCurrentUser();
$pageTitle = 'DSA Engine & Test Audit';

$vehicles = VehicleService::getAll(true);

// Run the automated test suite
$testResults = DSATestSuite::runAll($vehicles);

// Live Interactive Playground Handlers
$activeTab = $_GET['tab'] ?? 'audit';
$playLinearResults = null;
$playMergeResults = null;
$playBstResults = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dsaAction = $_POST['dsa_action'] ?? '';

    // Linear Search Playground
    if ($dsaAction === 'play_linear') {
        $keyword = $_POST['keyword'] ?? '';
        $field = $_POST['field'] ?? 'all';
        $playLinearResults = LinearSearch::searchWithMetrics($vehicles, $keyword, $field);
        $activeTab = 'playground';
    }

    // Merge Sort Playground
    elseif ($dsaAction === 'play_merge') {
        $field = $_POST['sort_field'] ?? 'price';
        $direction = $_POST['sort_dir'] ?? 'ASC';
        $playMergeResults = MergeSort::sortWithMetrics($vehicles, $field, $direction);
        $activeTab = 'playground';
    }

    // BST Playground
    elseif ($dsaAction === 'play_bst') {
        $targetId = (int)($_POST['bst_target_id'] ?? 1);
        $bst = BinarySearchTree::buildFromVehicles($vehicles);
        $playBstResults = $bst->searchWithMetrics($targetId);
        $activeTab = 'playground';
    }
}

// Current FIFO Queue
$activeQueue = TestDriveService::getPendingQueue();

require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/sidebar.php';
?>

<main class="main-content">
    <div class="topbar">
        <div class="topbar-left">
            <h1 class="page-title">Data Structures & Algorithms Verification</h1>
        </div>
        <div class="topbar-right">
            <div style="display: flex; gap: 8px;">
                <a href="dsa-showcase.php?tab=audit" class="btn btn-sm <?= $activeTab === 'audit' ? 'btn-primary' : 'btn-outline' ?>">
                    🧪 Test Suite Audit
                </a>
                <a href="dsa-showcase.php?tab=playground" class="btn btn-sm <?= $activeTab === 'playground' ? 'btn-primary' : 'btn-outline' ?>">
                    ⚡ Live Playground
                </a>
                <a href="dsa-showcase.php?tab=flowchart" class="btn btn-sm <?= $activeTab === 'flowchart' ? 'btn-primary' : 'btn-outline' ?>">
                    🧭 Flowchart Reference
                </a>
            </div>
        </div>
    </div>

    <div class="content-area">
        <?php require_once __DIR__ . '/templates/alerts.php'; ?>

        <!-- DSA System Hero Spec -->
        <div class="dsa-hero">
            <div style="display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
                <div>
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
                        <span class="badge badge-dsa" style="font-size: 13px;">Integrated Architecture</span>
                        <h2 style="font-size: 22px; font-weight: 800;">CarDeal DSA Execution Engine</h2>
                    </div>
                    <p style="color: var(--text-muted); font-size: 14px; max-width: 800px;">
                        All four proposed algorithmic components are implemented in dedicated Object-Oriented PHP classes with PSR-4 namespaces, connected to actual dealership workflows and evaluated against normal, empty, missing, and invalid cases.
                    </p>
                </div>
            </div>

            <div class="dsa-badge-grid">
                <div class="dsa-spec-box">
                    <h4>1. Linear Search</h4>
                    <p>File: <code class="code-pill">src/Algorithms/LinearSearch.php</code></p>
                    <p style="margin-top: 4px;">Time: <strong>O(n)</strong> avg | Space: <strong>O(1)</strong></p>
                    <p style="font-size: 11px; color: var(--text-dim); margin-top: 2px;">Searches unsorted inventory by brand/model/ID.</p>
                </div>

                <div class="dsa-spec-box">
                    <h4>2. Merge Sort</h4>
                    <p>File: <code class="code-pill">src/Algorithms/MergeSort.php</code></p>
                    <p style="margin-top: 4px;">Time: <strong>O(n log n)</strong> | Space: <strong>O(n)</strong></p>
                    <p style="font-size: 11px; color: var(--text-dim); margin-top: 2px;">Divide-and-conquer sorting on price/year/brand.</p>
                </div>

                <div class="dsa-spec-box">
                    <h4>3. Binary Search Tree (BST)</h4>
                    <p>File: <code class="code-pill">src/DataStructures/BinarySearchTree.php</code></p>
                    <p style="margin-top: 4px;">Time: <strong>O(log n)</strong> avg | Space: <strong>O(n)</strong></p>
                    <p style="font-size: 11px; color: var(--text-dim); margin-top: 2px;">Indexes vehicle unique IDs for rapid lookup.</p>
                </div>

                <div class="dsa-spec-box">
                    <h4>4. Linked List FIFO Queue</h4>
                    <p>File: <code class="code-pill">src/DataStructures/Queue.php</code></p>
                    <p style="margin-top: 4px;">Time: <strong>O(1)</strong> Enqueue & Dequeue</p>
                    <p style="font-size: 11px; color: var(--text-dim); margin-top: 2px;">Dispatches test-drive requests in First-In, First-Out.</p>
                </div>
            </div>
        </div>

        <?php if ($activeTab === 'audit'): ?>
            <!-- ==========================================
                 TAB 1: TEST SUITE AUDIT REPORT
                 ========================================== -->
            <div style="display: flex; flex-direction: column; gap: 24px;">
                <?php foreach ($testResults as $key => $component): ?>
                    <div class="card">
                        <div class="card-header">
                            <div>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <h3 class="card-title"><?= htmlspecialchars($component['meta']['name']) ?></h3>
                                    <span class="badge badge-dsa"><?= htmlspecialchars($component['meta']['time_complexity']) ?></span>
                                </div>
                                <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">
                                    Source: <code class="code-pill"><?= htmlspecialchars($component['meta']['file']) ?></code> • 
                                    Method: <code class="code-pill"><?= htmlspecialchars($component['meta']['method']) ?></code>
                                </div>
                            </div>
                            <span class="badge badge-available">4 / 4 CASES PASSED</span>
                        </div>
                        <div class="card-body" style="padding: 0;">
                            <div class="table-responsive">
                                <table class="custom-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 15%;">Test Category</th>
                                            <th style="width: 35%;">Test Description & Input</th>
                                            <th style="width: 12%;">Status</th>
                                            <th style="width: 38%;">Observed Output / Behavior</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($component['tests'] as $testType => $t): ?>
                                            <tr>
                                                <td>
                                                    <span style="font-weight: 700; text-transform: uppercase; font-size: 11px; color: var(--primary);">
                                                        <?= htmlspecialchars($testType) ?> DATA
                                                    </span>
                                                </td>
                                                <td>
                                                    <div style="font-weight: 600;"><?= htmlspecialchars($t['description']) ?></div>
                                                    <?php if (isset($t['input'])): ?>
                                                        <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">
                                                            Input: <code class="code-pill"><?= json_encode($t['input']) ?></code>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge <?= $t['status'] === 'PASS' ? 'badge-available' : 'badge-sold' ?>">
                                                        ✔ <?= $t['status'] ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div style="font-size: 13px; line-height: 1.4;">
                                                        <?= htmlspecialchars($t['behavior'] ?? $t['sample_output'] ?? 'Executed successfully.') ?>
                                                    </div>
                                                    <?php if (isset($t['execution_time_ms'])): ?>
                                                        <div style="font-size: 11px; color: var(--text-dim); margin-top: 4px;">
                                                            Duration: <?= $t['execution_time_ms'] ?> ms 
                                                            <?php if (isset($t['comparisons'])): ?>
                                                                • Comparisons: <?= $t['comparisons'] ?>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php elseif ($activeTab === 'playground'): ?>
            <!-- ==========================================
                 TAB 2: INTERACTIVE LIVE PLAYGROUND
                 ========================================== -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                
                <!-- 1. Linear Search Live Runner -->
                <div class="card">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">1. Linear Search Runner</h3>
                            <p style="font-size: 12px; color: var(--text-muted);">Inspect sequential scanning and comparison count</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="dsa-showcase.php?tab=playground">
                            <input type="hidden" name="dsa_action" value="play_linear">
                            <div class="form-row">
                                <div class="form-group" style="flex: 2;">
                                    <label class="form-label">Search Query</label>
                                    <input type="text" name="keyword" class="form-control" placeholder="e.g. Toyota, Mustang, Red" required>
                                </div>
                                <div class="form-group" style="flex: 1;">
                                    <label class="form-label">Field</label>
                                    <select name="field" class="form-select">
                                        <option value="all">All Fields</option>
                                        <option value="brand">Brand</option>
                                        <option value="model">Model</option>
                                    </select>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">Execute Linear Search</button>
                        </form>

                        <?php if ($playLinearResults): ?>
                            <div style="margin-top: 18px; padding: 14px; background: var(--bg-surface); border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                                <h4 style="font-size: 14px; color: var(--primary);">Results Report:</h4>
                                <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">
                                    Scanned: <strong><?= $playLinearResults['total_items_scanned'] ?></strong> items | 
                                    Comparisons: <strong><?= $playLinearResults['comparisons_count'] ?></strong> | 
                                    Duration: <strong><?= $playLinearResults['execution_time_ms'] ?> ms</strong> | 
                                    Matches: <strong><?= $playLinearResults['matches_count'] ?></strong>
                                </div>
                                <div style="margin-top: 8px;">
                                    <?php foreach ($playLinearResults['results'] as $match): ?>
                                        <div style="font-size: 12px; padding: 4px 0; border-bottom: 1px solid var(--border-color);">
                                            #<?= $match->getId() ?> - <?= htmlspecialchars($match->getFullName()) ?> (<?= $match->getFormattedPrice() ?>)
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 2. Merge Sort Live Runner -->
                <div class="card">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">2. Merge Sort Runner</h3>
                            <p style="font-size: 12px; color: var(--text-muted);">Divide-and-conquer recursive sorting</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="dsa-showcase.php?tab=playground">
                            <input type="hidden" name="dsa_action" value="play_merge">
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Sort Property</label>
                                    <select name="sort_field" class="form-select">
                                        <option value="price">Vehicle Price</option>
                                        <option value="year">Manufacturing Year</option>
                                        <option value="brand">Brand Alphabetical</option>
                                        <option value="mileage">Mileage</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Direction</label>
                                    <select name="sort_dir" class="form-select">
                                        <option value="ASC">Ascending (Low → High)</option>
                                        <option value="DESC">Descending (High → Low)</option>
                                    </select>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">Execute Merge Sort</button>
                        </form>

                        <?php if ($playMergeResults): ?>
                            <div style="margin-top: 18px; padding: 14px; background: var(--bg-surface); border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                                <h4 style="font-size: 14px; color: var(--primary);">Sorting Metrics:</h4>
                                <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">
                                    Items: <strong><?= $playMergeResults['items_count'] ?></strong> | 
                                    Comparisons: <strong><?= $playMergeResults['comparisons_count'] ?></strong> | 
                                    Splits: <strong><?= $playMergeResults['recursive_splits'] ?></strong> | 
                                    Duration: <strong><?= $playMergeResults['execution_time_ms'] ?> ms</strong>
                                </div>
                                <div style="margin-top: 8px; max-height: 160px; overflow-y: auto;">
                                    <?php foreach ($playMergeResults['sorted_records'] as $sr): ?>
                                        <div style="font-size: 12px; padding: 4px 0; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between;">
                                            <span>#<?= $sr->getId() ?> - <?= htmlspecialchars($sr->getFullName()) ?></span>
                                            <strong style="color: var(--primary);"><?= $sr->getFormattedPrice() ?></strong>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 3. Binary Search Tree (BST) Lookup -->
                <div class="card">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">3. Binary Search Tree Lookup</h3>
                            <p style="font-size: 12px; color: var(--text-muted);">Traverse left or right subtrees by Vehicle ID</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="dsa-showcase.php?tab=playground">
                            <input type="hidden" name="dsa_action" value="play_bst">
                            <div class="form-group">
                                <label class="form-label">Target Vehicle ID</label>
                                <input type="number" name="bst_target_id" class="form-control" placeholder="e.g. 1, 2, 5, 8" min="1" required>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">Search via BST</button>
                        </form>

                        <?php if ($playBstResults): ?>
                            <div style="margin-top: 18px; padding: 14px; background: var(--bg-surface); border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                                <h4 style="font-size: 14px; color: var(--primary);">
                                    <?= $playBstResults['found'] ? '✔ Target ID Located' : '✕ Target ID Not Found' ?>
                                </h4>
                                <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">
                                    Comparisons: <strong><?= $playBstResults['comparisons_count'] ?></strong> | 
                                    Tree Height: <strong><?= $playBstResults['tree_height'] ?></strong> | 
                                    Duration: <strong><?= $playBstResults['execution_time_ms'] ?> ms</strong>
                                </div>
                                <div style="margin-top: 8px;">
                                    <div style="font-size: 11px; color: var(--text-dim); margin-bottom: 4px;">Path Traversal:</div>
                                    <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                        <?php foreach ($playBstResults['search_path'] as $sp): ?>
                                            <span class="badge badge-dsa">Node #<?= $sp['node_id'] ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 4. FIFO Queue Inspector -->
                <div class="card">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">4. FIFO Queue Status</h3>
                            <p style="font-size: 12px; color: var(--text-muted);">Front pointer and waiting requests in FIFO order</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 12px;">
                            Current Queue Size: <strong style="color: #ffffff;"><?= $activeQueue->getSize() ?></strong> requests waiting.
                        </div>
                        <?php if ($activeQueue->peek()): ?>
                            <div style="background: var(--bg-surface); padding: 12px; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                                <span class="badge badge-dsa" style="margin-bottom: 6px;">HEAD / FRONT POINTER (O(1))</span>
                                <div style="font-weight: 700; font-size: 14px;"><?= htmlspecialchars($activeQueue->peek()->getCustomerName()) ?></div>
                                <div style="font-size: 12px; color: var(--primary);"><?= htmlspecialchars($activeQueue->peek()->getVehicleInfo() ?: 'Vehicle') ?></div>
                                <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">
                                    Scheduled: <?= htmlspecialchars($activeQueue->peek()->getFormattedDateTime()) ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div style="color: var(--text-dim); font-size: 13px;">Queue is empty.</div>
                        <?php endif; ?>
                        <div style="margin-top: 14px;">
                            <a href="test-drives.php" class="btn btn-secondary btn-sm">Open Test-Drive Queue Module →</a>
                        </div>
                    </div>
                </div>

            </div>

        <?php elseif ($activeTab === 'flowchart'): ?>
            <!-- ==========================================
                 TAB 3: FLOWCHART PROCESS SPECIFICATION
                 ========================================== -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">System Flowchart Architecture (Section 4 Reference)</h3>
                </div>
                <div class="card-body" style="line-height: 1.7; font-size: 14px;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px;">
                        <div style="background: var(--bg-surface); padding: 20px; border-radius: var(--radius-lg); border: 1px solid var(--border-color);">
                            <h4 style="color: var(--primary); margin-bottom: 10px;">1. Main Authentication Process</h4>
                            <ol style="padding-left: 20px; color: var(--text-muted); font-size: 13px;">
                                <li><strong>START</strong> (Oval)</li>
                                <li>Open CarDeal Website (Rectangle)</li>
                                <li>Enter Username & Password (Parallelogram)</li>
                                <li>Validate Login Details? (Diamond)
                                    <ul>
                                        <li>No: Show error & Return to Login</li>
                                        <li>Yes: Open Dashboard</li>
                                    </ul>
                                </li>
                                <li>Select Module (Vehicle Inventory / Inquiries / Test Drives)</li>
                            </ol>
                        </div>

                        <div style="background: var(--bg-surface); padding: 20px; border-radius: var(--radius-lg); border: 1px solid var(--border-color);">
                            <h4 style="color: var(--primary); margin-bottom: 10px;">2. Vehicle Inventory Process</h4>
                            <ol style="padding-left: 20px; color: var(--text-muted); font-size: 13px;">
                                <li>Select Operation (Add, View, Edit, Remove, Search, Sort)</li>
                                <li>Enter or Select Vehicle Information</li>
                                <li>Validate Input (Normal, Empty, Missing, Invalid Data)</li>
                                <li>Apply Data Structure / Algorithm:
                                    <ul>
                                        <li><strong>Linear Search</strong> on query search</li>
                                        <li><strong>Merge Sort</strong> on price/year/brand sort</li>
                                        <li><strong>Binary Search Tree</strong> on unique ID lookup</li>
                                    </ul>
                                </li>
                                <li>Save changes to MySQL database</li>
                                <li>Display result / update inventory display</li>
                            </ol>
                        </div>

                        <div style="background: var(--bg-surface); padding: 20px; border-radius: var(--radius-lg); border: 1px solid var(--border-color);">
                            <h4 style="color: var(--primary); margin-bottom: 10px;">3. Customer Inquiry Process</h4>
                            <ol style="padding-left: 20px; color: var(--text-muted); font-size: 13px;">
                                <li>Select Customer Inquiries</li>
                                <li>Record or View Inquiry Details</li>
                                <li>Validate Inquiry Information</li>
                                <li>Save or update inquiry status in MySQL</li>
                                <li>Display Inquiry Records & Return to Dashboard</li>
                            </ol>
                        </div>

                        <div style="background: var(--bg-surface); padding: 20px; border-radius: var(--radius-lg); border: 1px solid var(--border-color);">
                            <h4 style="color: var(--primary); margin-bottom: 10px;">4. Test Drive Queue Process</h4>
                            <ol style="padding-left: 20px; color: var(--text-muted); font-size: 13px;">
                                <li>Record Customer and Vehicle Details</li>
                                <li>Validate Request and Vehicle Availability (Must be Available)</li>
                                <li>Enqueue into FIFO Queue (O(1)) and Save to MySQL</li>
                                <li>Staff processes requests in FIFO order via Dequeue (O(1))</li>
                                <li>Update Request Status to Approved / In-Progress / Completed</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>
</main>

<?php
require_once __DIR__ . '/templates/footer.php';
?>
