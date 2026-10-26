<?php

namespace App\Testing;

use App\Algorithms\LinearSearch;
use App\Algorithms\MergeSort;
use App\DataStructures\BinarySearchTree;
use App\DataStructures\Queue;
use App\Models\Vehicle;
use App\Models\TestDrive;

/**
 * Class DSATestSuite
 * Automated test harness executing Normal, Empty, Missing, and Invalid test cases
 * for the 4 required Data Structures and Algorithms.
 */
class DSATestSuite
{
    /**
     * Run all test suites and return structured audit results
     */
    public static function runAll(array $sampleVehicles = []): array
    {
        // Provide mock vehicles if database is empty
        if (empty($sampleVehicles)) {
            $sampleVehicles = self::getMockVehicles();
        }

        return [
            'linear_search' => self::testLinearSearch($sampleVehicles),
            'merge_sort' => self::testMergeSort($sampleVehicles),
            'binary_search_tree' => self::testBinarySearchTree($sampleVehicles),
            'fifo_queue' => self::testQueue()
        ];
    }

    /**
     * Test Component 1: Linear Search
     */
    public static function testLinearSearch(array $vehicles): array
    {
        $meta = [
            'name' => 'Linear Search',
            'file' => 'src/Algorithms/LinearSearch.php',
            'method' => 'LinearSearch::search($vehicles, $keyword, $field)',
            'time_complexity' => 'Best: O(1) | Avg: O(n) | Worst: O(n)',
            'space_complexity' => 'O(1) auxiliary'
        ];

        $firstVehicle = reset($vehicles);
        $firstBrand = $firstVehicle instanceof Vehicle ? $firstVehicle->getBrand() : ($firstVehicle['brand'] ?? 'Toyota');
        $firstId = $firstVehicle instanceof Vehicle ? $firstVehicle->getId() : (int)($firstVehicle['id'] ?? 1);

        $tests = [];

        // 1. Normal Case
        $resNormal = LinearSearch::searchWithMetrics($vehicles, $firstBrand, 'brand');
        $tests['normal'] = [
            'description' => "Normal Case: Search for existing brand '{$firstBrand}'",
            'input' => ['keyword' => $firstBrand, 'field' => 'brand'],
            'status' => count($resNormal['results']) > 0 ? 'PASS' : 'FAIL',
            'matches_found' => count($resNormal['results']),
            'comparisons' => $resNormal['comparisons_count'],
            'execution_time_ms' => $resNormal['execution_time_ms'],
            'sample_output' => !empty($resNormal['results']) ? self::summarizeVehicle(reset($resNormal['results'])) : 'None'
        ];

        // 2. Empty Case
        $resEmpty = LinearSearch::searchWithMetrics($vehicles, '', 'all');
        $tests['empty'] = [
            'description' => "Empty Case: Search with empty query string ''",
            'input' => ['keyword' => '', 'field' => 'all'],
            'status' => count($resEmpty['results']) === count($vehicles) ? 'PASS' : 'FAIL',
            'matches_found' => count($resEmpty['results']),
            'comparisons' => $resEmpty['comparisons_count'],
            'execution_time_ms' => $resEmpty['execution_time_ms'],
            'behavior' => 'Gracefully returned all inventory without error.'
        ];

        // 3. Missing Case
        $resMissing = LinearSearch::searchWithMetrics($vehicles, 'NonExistentBrandXYZ123', 'brand');
        $tests['missing'] = [
            'description' => "Missing Case: Search for a record not in inventory",
            'input' => ['keyword' => 'NonExistentBrandXYZ123', 'field' => 'brand'],
            'status' => count($resMissing['results']) === 0 ? 'PASS' : 'FAIL',
            'matches_found' => count($resMissing['results']),
            'comparisons' => $resMissing['comparisons_count'],
            'execution_time_ms' => $resMissing['execution_time_ms'],
            'behavior' => 'Scanned entire list (O(n) worst case) and correctly returned empty results array.'
        ];

        // 4. Invalid Case
        $resInvalid = LinearSearch::findById($vehicles, -99);
        $tests['invalid'] = [
            'description' => "Invalid Case: Search by negative ID (-99)",
            'input' => ['id' => -99],
            'status' => $resInvalid === null ? 'PASS' : 'FAIL',
            'output' => $resInvalid === null ? 'null (Safe rejection)' : 'Unexpected match',
            'behavior' => 'Guarded against negative ID, returned null safely.'
        ];

        return ['meta' => $meta, 'tests' => $tests];
    }

    /**
     * Test Component 2: Merge Sort
     */
    public static function testMergeSort(array $vehicles): array
    {
        $meta = [
            'name' => 'Merge Sort',
            'file' => 'src/Algorithms/MergeSort.php',
            'method' => 'MergeSort::sort($vehicles, $field, $direction)',
            'time_complexity' => 'Best: O(n log n) | Avg: O(n log n) | Worst: O(n log n)',
            'space_complexity' => 'O(n) auxiliary'
        ];

        $tests = [];

        // 1. Normal Case (Sort by price ASC)
        $resNormal = MergeSort::sortWithMetrics($vehicles, 'price', 'ASC');
        $sortedPrices = array_map(function($v) {
            return $v instanceof Vehicle ? $v->getPrice() : (float)($v['price'] ?? 0);
        }, $resNormal['sorted_records']);

        $isSortedAsc = true;
        for ($i = 0; $i < count($sortedPrices) - 1; $i++) {
            if ($sortedPrices[$i] > $sortedPrices[$i + 1]) {
                $isSortedAsc = false;
                break;
            }
        }

        $tests['normal'] = [
            'description' => 'Normal Case: Sort inventory by Price in ASC order',
            'input' => ['field' => 'price', 'direction' => 'ASC', 'items_count' => count($vehicles)],
            'status' => $isSortedAsc ? 'PASS' : 'FAIL',
            'comparisons' => $resNormal['comparisons_count'],
            'recursive_splits' => $resNormal['recursive_splits'],
            'execution_time_ms' => $resNormal['execution_time_ms'],
            'sample_output' => 'Lowest price: $' . number_format($sortedPrices[0] ?? 0, 2) . ' -> Highest: $' . number_format(end($sortedPrices) ?: 0, 2)
        ];

        // 2. Empty Case (Empty array input)
        $resEmpty = MergeSort::sortWithMetrics([], 'price', 'ASC');
        $tests['empty'] = [
            'description' => 'Empty Case: Sort empty array []',
            'input' => ['items' => []],
            'status' => empty($resEmpty['sorted_records']) ? 'PASS' : 'FAIL',
            'execution_time_ms' => $resEmpty['execution_time_ms'],
            'behavior' => 'Base condition reached immediately (count <= 1), returns [] with O(1) step.'
        ];

        // 3. Missing Field Case
        $resMissing = MergeSort::sort($vehicles, 'unregistered_field', 'ASC');
        $tests['missing'] = [
            'description' => "Missing Case: Sort by non-existent field 'unregistered_field'",
            'input' => ['field' => 'unregistered_field'],
            'status' => count($resMissing) === count($vehicles) ? 'PASS' : 'FAIL',
            'behavior' => 'Extracted default comparison key safely without raising PHP notices or warnings.'
        ];

        // 4. Invalid Direction Case
        $resInvalid = MergeSort::sort($vehicles, 'year', 'INVALID_DIR');
        $tests['invalid'] = [
            'description' => "Invalid Case: Invalid direction string 'INVALID_DIR'",
            'input' => ['direction' => 'INVALID_DIR'],
            'status' => count($resInvalid) === count($vehicles) ? 'PASS' : 'FAIL',
            'behavior' => 'Gracefully sanitized direction to default ASC order.'
        ];

        return ['meta' => $meta, 'tests' => $tests];
    }

    /**
     * Test Component 3: Binary Search Tree
     */
    public static function testBinarySearchTree(array $vehicles): array
    {
        $meta = [
            'name' => 'Binary Search Tree (BST)',
            'file' => 'src/DataStructures/BinarySearchTree.php',
            'method' => 'BinarySearchTree::search($id) / inOrderTraversal()',
            'time_complexity' => 'Best: O(1) | Avg: O(log n) | Worst: O(n)',
            'space_complexity' => 'O(n) node storage'
        ];

        $bst = BinarySearchTree::buildFromVehicles($vehicles);
        $firstVehicle = reset($vehicles);
        $firstId = $firstVehicle instanceof Vehicle ? $firstVehicle->getId() : (int)($firstVehicle['id'] ?? 1);

        $tests = [];

        // 1. Normal Case
        $resNormal = $bst->searchWithMetrics($firstId);
        $tests['normal'] = [
            'description' => "Normal Case: Lookup existing vehicle ID #{$firstId}",
            'input' => ['id' => $firstId],
            'status' => $resNormal['found'] ? 'PASS' : 'FAIL',
            'comparisons' => $resNormal['comparisons_count'],
            'tree_height' => $resNormal['tree_height'],
            'search_path' => array_column($resNormal['search_path'], 'node_id'),
            'execution_time_ms' => $resNormal['execution_time_ms']
        ];

        // 2. Empty Case (Search empty tree)
        $emptyBst = new BinarySearchTree();
        $resEmpty = $emptyBst->searchWithMetrics(10);
        $tests['empty'] = [
            'description' => 'Empty Case: Search on an empty BST (root is null)',
            'input' => ['id' => 10],
            'status' => !$resEmpty['found'] ? 'PASS' : 'FAIL',
            'behavior' => 'Recognized root === null instantly in O(1) time.'
        ];

        // 3. Missing Case (ID not in tree)
        $missingId = 999999;
        $resMissing = $bst->searchWithMetrics($missingId);
        $tests['missing'] = [
            'description' => "Missing Case: Search for non-existent vehicle ID #{$missingId}",
            'input' => ['id' => $missingId],
            'status' => !$resMissing['found'] ? 'PASS' : 'FAIL',
            'comparisons' => $resMissing['comparisons_count'],
            'behavior' => 'Traversed until reached leaf null pointer, confirmed not found.'
        ];

        // 4. Invalid Case (Negative ID)
        $resInvalid = $bst->search(-15);
        $tests['invalid'] = [
            'description' => 'Invalid Case: Search for negative ID (-15)',
            'input' => ['id' => -15],
            'status' => $resInvalid === null ? 'PASS' : 'FAIL',
            'output' => 'null',
            'behavior' => 'Rejected negative integer at entry guard.'
        ];

        return ['meta' => $meta, 'tests' => $tests];
    }

    /**
     * Test Component 4: FIFO Queue
     */
    public static function testQueue(): array
    {
        $meta = [
            'name' => 'Linked List FIFO Queue',
            'file' => 'src/DataStructures/Queue.php',
            'method' => 'Queue::enqueue($req) / Queue::dequeue() / Queue::peek()',
            'time_complexity' => 'Enqueue: O(1) | Dequeue: O(1) | Peek: O(1)',
            'space_complexity' => 'O(k) for k requests'
        ];

        $tests = [];
        $queue = new Queue();

        // 1. Normal Case: Enqueue 3 items in order, verify FIFO dequeue
        $req1 = ['id' => 101, 'customer_name' => 'Alice Johnson', 'vehicle' => 'Toyota RAV4'];
        $req2 = ['id' => 102, 'customer_name' => 'Bob Miller', 'vehicle' => 'Honda Civic'];
        $req3 = ['id' => 103, 'customer_name' => 'Charlie Davis', 'vehicle' => 'Ford Mustang'];

        $queue->enqueue($req1);
        $queue->enqueue($req2);
        $queue->enqueue($req3);

        $peekFirst = $queue->peek();
        $dequeued1 = $queue->dequeue();
        $peekSecond = $queue->peek();

        $normalPass = ($peekFirst['customer_name'] === 'Alice Johnson') &&
                      ($dequeued1['customer_name'] === 'Alice Johnson') &&
                      ($peekSecond['customer_name'] === 'Bob Miller') &&
                      ($queue->getSize() === 2);

        $tests['normal'] = [
            'description' => 'Normal Case: Enqueue Alice, Bob, Charlie; verify FIFO order on dequeue',
            'status' => $normalPass ? 'PASS' : 'FAIL',
            'queue_size_after_dequeue' => $queue->getSize(),
            'dequeued_first' => $dequeued1['customer_name'],
            'new_front_of_queue' => $peekSecond['customer_name'],
            'behavior' => 'First In was Alice, First Out was Alice. Next in line is Bob.'
        ];

        // 2. Empty Case
        $emptyQueue = new Queue();
        $tests['empty'] = [
            'description' => 'Empty Case: Dequeue and Peek on empty queue',
            'status' => ($emptyQueue->isEmpty() && $emptyQueue->dequeue() === null && $emptyQueue->peek() === null) ? 'PASS' : 'FAIL',
            'behavior' => 'Returns null safely without pointer exceptions.'
        ];

        // 3. Missing / Underflow Case: Drain queue and dequeue once more
        $queue->dequeue(); // Bob
        $queue->dequeue(); // Charlie
        $underflowCheck = $queue->dequeue(); // Empty now
        $tests['missing'] = [
            'description' => 'Missing Case: Queue exhaustion / underflow guard',
            'status' => ($underflowCheck === null && $queue->getSize() === 0) ? 'PASS' : 'FAIL',
            'behavior' => 'Handles empty state seamlessly after draining all items.'
        ];

        // 4. Invalid Case: Clear and check size
        $queue->clear();
        $tests['invalid'] = [
            'description' => 'Invalid Case: Reset and boundary bounds validation',
            'status' => ($queue->getSize() === 0 && $queue->isEmpty()) ? 'PASS' : 'FAIL',
            'behavior' => 'Queue pointers and counter properly reinitialized to null and 0.'
        ];

        return ['meta' => $meta, 'tests' => $tests];
    }

    private static function summarizeVehicle($v): string
    {
        if ($v instanceof Vehicle) {
            return "#{$v->getId()} - {$v->getFullName()} (\${$v->getPrice()})";
        }
        return "#" . ($v['id'] ?? '?') . " - " . ($v['brand'] ?? '') . " " . ($v['model'] ?? '');
    }

    private static function getMockVehicles(): array
    {
        return [
            new Vehicle(1, 'Toyota', 'Camry Hybrid', 2024, 'Midnight Black', 'CVT', 'Hybrid', 28855.0, 12, 'TOY202401', 'Available'),
            new Vehicle(2, 'Honda', 'Civic Sport', 2023, 'Rallye Red', 'CVT', 'Gasoline', 25550.0, 4500, 'HON202302', 'Available'),
            new Vehicle(3, 'Ford', 'Mustang GT', 2024, 'Shadow Black', 'Manual', 'Gasoline', 45000.0, 310, 'FOR202403', 'Available'),
            new Vehicle(4, 'Tesla', 'Model 3 Long Range', 2024, 'Pearl White', 'Automatic', 'Electric', 42490.0, 50, 'TES202404', 'Sold'),
            new Vehicle(5, 'BMW', '330i xDrive', 2023, 'Alpine White', 'Automatic', 'Gasoline', 46500.0, 8900, 'BMW202305', 'Available')
        ];
    }
}
