<?php

namespace App\DataStructures;

use App\Models\Vehicle;

/**
 * Class BinarySearchTree
 * Organizes vehicle records by unique vehicle ID for logarithmic search and in-order traversal.
 * 
 * Algorithm Characteristics:
 * - Time Complexity for Search: Best O(1), Average O(log n), Worst O(n) (skewed)
 * - Space Complexity: O(n) for n vehicle nodes
 * - Property: left.id < node.id < right.id
 */
class BinarySearchTree
{
    private ?BSTNode $root = null;
    private int $nodeCount = 0;

    public function __construct()
    {
        $this->root = null;
        $this->nodeCount = 0;
    }

    /**
     * Build BST from an array of Vehicle records
     */
    public static function buildFromVehicles(array $vehicles): self
    {
        $bst = new self();
        foreach ($vehicles as $vehicle) {
            $id = $vehicle instanceof Vehicle ? $vehicle->getId() : (int)($vehicle['id'] ?? 0);
            if ($id > 0) {
                $bst->insert($id, $vehicle);
            }
        }
        return $bst;
    }

    /**
     * Insert a vehicle record into BST
     */
    public function insert(int $id, $data): void
    {
        if ($id <= 0) {
            return;
        }

        $newNode = new BSTNode($id, $data);
        if ($this->root === null) {
            $this->root = $newNode;
            $this->nodeCount++;
            return;
        }

        $current = $this->root;
        while (true) {
            if ($id < $current->id) {
                if ($current->left === null) {
                    $current->left = $newNode;
                    $this->nodeCount++;
                    break;
                }
                $current = $current->left;
            } elseif ($id > $current->id) {
                if ($current->right === null) {
                    $current->right = $newNode;
                    $this->nodeCount++;
                    break;
                }
                $current = $current->right;
            } else {
                // Key already exists: update existing data
                $current->data = $data;
                break;
            }
        }
    }

    /**
     * Search vehicle by ID in O(log n) average time
     */
    public function search(int $id)
    {
        if ($this->root === null || $id <= 0) {
            return null;
        }

        $current = $this->root;
        while ($current !== null) {
            if ($id === $current->id) {
                return $current->data;
            }
            if ($id < $current->id) {
                $current = $current->left;
            } else {
                $current = $current->right;
            }
        }

        return null;
    }

    /**
     * Search with path tracing and metrics for DSA verification
     */
    public function searchWithMetrics(int $id): array
    {
        $startTime = microtime(true);
        $path = [];
        $comparisons = 0;
        $found = false;
        $result = null;

        $current = $this->root;
        while ($current !== null) {
            $comparisons++;
            $path[] = [
                'node_id' => $current->id,
                'vehicle_name' => $current->data instanceof Vehicle ? $current->data->getFullName() : ($current->data['brand'] ?? 'Node')
            ];

            if ($id === $current->id) {
                $found = true;
                $result = $current->data;
                break;
            }

            if ($id < $current->id) {
                $current = $current->left;
            } else {
                $current = $current->right;
            }
        }

        $durationMs = (microtime(true) - $startTime) * 1000;

        return [
            'algorithm' => 'Binary Search Tree Lookup',
            'target_id' => $id,
            'found' => $found,
            'comparisons_count' => $comparisons,
            'search_path' => $path,
            'total_tree_nodes' => $this->nodeCount,
            'tree_height' => $this->getHeight(),
            'execution_time_ms' => round($durationMs, 4),
            'time_complexity' => 'Best: O(1) | Average: O(log n) | Worst: O(n)',
            'space_complexity' => 'O(n) tree storage',
            'result' => $result
        ];
    }

    /**
     * In-Order Traversal (Left, Root, Right)
     * Naturally produces records sorted in ascending order of vehicle ID.
     */
    public function inOrderTraversal(): array
    {
        $results = [];
        $this->inOrderRecursive($this->root, $results);
        return $results;
    }

    private function inOrderRecursive(?BSTNode $node, array &$results): void
    {
        if ($node === null) {
            return;
        }

        $this->inOrderRecursive($node->left, $results);
        $results[] = $node->data;
        $this->inOrderRecursive($node->right, $results);
    }

    /**
     * Pre-Order Traversal (Root, Left, Right)
     */
    public function preOrderTraversal(): array
    {
        $results = [];
        $this->preOrderRecursive($this->root, $results);
        return $results;
    }

    private function preOrderRecursive(?BSTNode $node, array &$results): void
    {
        if ($node === null) {
            return;
        }

        $results[] = $node->data;
        $this->preOrderRecursive($node->left, $results);
        $this->preOrderRecursive($node->right, $results);
    }

    /**
     * Find minimum ID node in BST (leftmost node)
     */
    public function getMin()
    {
        if ($this->root === null) {
            return null;
        }

        $current = $this->root;
        while ($current->left !== null) {
            $current = $current->left;
        }
        return $current->data;
    }

    /**
     * Find maximum ID node in BST (rightmost node)
     */
    public function getMax()
    {
        if ($this->root === null) {
            return null;
        }

        $current = $this->root;
        while ($current->right !== null) {
            $current = $current->right;
        }
        return $current->data;
    }

    /**
     * Calculate tree height
     */
    public function getHeight(): int
    {
        return $this->calculateHeight($this->root);
    }

    private function calculateHeight(?BSTNode $node): int
    {
        if ($node === null) {
            return 0;
        }

        $leftHeight = $this->calculateHeight($node->left);
        $rightHeight = $this->calculateHeight($node->right);

        return 1 + max($leftHeight, $rightHeight);
    }

    public function getNodeCount(): int
    {
        return $this->nodeCount;
    }

    public function getRoot(): ?BSTNode
    {
        return $this->root;
    }
}
