<?php

namespace App\Algorithms;

use App\Models\Vehicle;

/**
 * Class MergeSort
 * Implements divide-and-conquer Merge Sort to arrange vehicle records by price, brand, model, or year.
 * 
 * Algorithm Characteristics:
 * - Time Complexity: Best O(n log n), Average O(n log n), Worst O(n log n)
 * - Space Complexity: O(n) auxiliary space
 * - Stability: Stable sorting algorithm
 */
class MergeSort
{
    private static int $comparisonsCount = 0;
    private static int $splitsCount = 0;

    /**
     * Sort an array of Vehicle objects or associative arrays using Merge Sort.
     *
     * @param array $vehicles Array of Vehicle instances or arrays
     * @param string $field 'price', 'brand', 'model', 'year', 'id'
     * @param string $direction 'ASC' or 'DESC'
     * @return array Sorted array
     */
    public static function sort(array $vehicles, string $field = 'price', string $direction = 'ASC'): array
    {
        if (count($vehicles) <= 1) {
            return $vehicles;
        }

        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';

        return self::divideAndConquer($vehicles, strtolower($field), $direction);
    }

    /**
     * Recursive divide-and-conquer function
     */
    private static function divideAndConquer(array $items, string $field, string $direction): array
    {
        $count = count($items);
        if ($count <= 1) {
            return $items;
        }

        self::$splitsCount++;
        $mid = (int)floor($count / 2);
        $left = array_slice($items, 0, $mid);
        $right = array_slice($items, $mid);

        $leftSorted = self::divideAndConquer($left, $field, $direction);
        $rightSorted = self::divideAndConquer($right, $field, $direction);

        return self::merge($leftSorted, $rightSorted, $field, $direction);
    }

    /**
     * Merge step combining two already-sorted sub-lists
     */
    private static function merge(array $left, array $right, string $field, string $direction): array
    {
        $result = [];
        $i = 0;
        $j = 0;
        $leftLen = count($left);
        $rightLen = count($right);

        while ($i < $leftLen && $j < $rightLen) {
            self::$comparisonsCount++;
            $valLeft = self::extractFieldValue($left[$i], $field);
            $valRight = self::extractFieldValue($right[$j], $field);

            $cmp = self::compareValues($valLeft, $valRight);

            if ($direction === 'DESC') {
                $cmp = -$cmp;
            }

            if ($cmp <= 0) {
                $result[] = $left[$i];
                $i++;
            } else {
                $result[] = $right[$j];
                $j++;
            }
        }

        // Append remaining items from left sub-list
        while ($i < $leftLen) {
            $result[] = $left[$i];
            $i++;
        }

        // Append remaining items from right sub-list
        while ($j < $rightLen) {
            $result[] = $right[$j];
            $j++;
        }

        return $result;
    }

    /**
     * Extract field value from either a Vehicle object or an array
     */
    private static function extractFieldValue($item, string $field)
    {
        if ($item instanceof Vehicle) {
            switch ($field) {
                case 'id': return $item->getId() ?? 0;
                case 'price': return $item->getPrice();
                case 'year': return $item->getYear();
                case 'brand': return mb_strtolower($item->getBrand());
                case 'model': return mb_strtolower($item->getModel());
                case 'mileage': return $item->getMileage();
                default: return $item->getPrice();
            }
        }

        if (is_array($item)) {
            $val = $item[$field] ?? null;
            if (in_array($field, ['price', 'year', 'id', 'mileage'])) {
                return (float)$val;
            }
            return mb_strtolower((string)$val);
        }

        return 0;
    }

    /**
     * Compare two values based on their types
     */
    private static function compareValues($a, $b): int
    {
        if (is_numeric($a) && is_numeric($b)) {
            if ($a < $b) return -1;
            if ($a > $b) return 1;
            return 0;
        }

        return strcmp((string)$a, (string)$b);
    }

    /**
     * Sort with detailed metrics for the DSA visualizer/demonstration
     */
    public static function sortWithMetrics(array $vehicles, string $field = 'price', string $direction = 'ASC'): array
    {
        self::$comparisonsCount = 0;
        self::$splitsCount = 0;

        $startTime = microtime(true);
        $sorted = self::sort($vehicles, $field, $direction);
        $durationMs = (microtime(true) - $startTime) * 1000;

        return [
            'algorithm' => 'Merge Sort',
            'field' => $field,
            'direction' => strtoupper($direction),
            'items_count' => count($vehicles),
            'comparisons_count' => self::$comparisonsCount,
            'recursive_splits' => self::$splitsCount,
            'execution_time_ms' => round($durationMs, 4),
            'time_complexity' => 'Best: O(n log n) | Average: O(n log n) | Worst: O(n log n)',
            'space_complexity' => 'O(n) auxiliary space',
            'sorted_records' => $sorted
        ];
    }
}
