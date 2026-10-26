<?php

namespace App\Algorithms;

use App\Models\Vehicle;

/**
 * Class LinearSearch
 * Implements linear search algorithm to find vehicle records by ID, brand, or model.
 * 
 * Algorithm Characteristics:
 * - Time Complexity: Best O(1), Average O(n), Worst O(n)
 * - Space Complexity: O(1) auxiliary space
 * - Pre-condition: Does NOT require the collection to be sorted.
 */
class LinearSearch
{
    /**
     * Search an array of Vehicle objects or associative arrays by query keyword across specified fields.
     *
     * @param array $vehicles Array of Vehicle instances or assoc arrays
     * @param string $keyword Search keyword
     * @param string $field 'all', 'id', 'brand', 'model', or 'vin'
     * @return array Matching vehicles
     */
    public static function search(array $vehicles, string $keyword, string $field = 'all'): array
    {
        $trimmed = trim($keyword);
        if ($trimmed === '') {
            return $vehicles;
        }

        $results = [];
        $lowerQuery = mb_strtolower($trimmed);

        foreach ($vehicles as $item) {
            $matched = false;

            // Extract values whether element is Vehicle object or array
            $id = $item instanceof Vehicle ? (string)$item->getId() : (string)($item['id'] ?? '');
            $brand = $item instanceof Vehicle ? $item->getBrand() : ($item['brand'] ?? '');
            $model = $item instanceof Vehicle ? $item->getModel() : ($item['model'] ?? '');
            $vin = $item instanceof Vehicle ? $item->getVin() : ($item['vin'] ?? '');
            $year = $item instanceof Vehicle ? (string)$item->getYear() : (string)($item['year'] ?? '');

            switch (strtolower($field)) {
                case 'id':
                    if (strcasecmp($id, $trimmed) === 0) {
                        $matched = true;
                    }
                    break;
                case 'brand':
                    if (str_contains(mb_strtolower($brand), $lowerQuery)) {
                        $matched = true;
                    }
                    break;
                case 'model':
                    if (str_contains(mb_strtolower($model), $lowerQuery)) {
                        $matched = true;
                    }
                    break;
                case 'all':
                default:
                    if (
                        strcasecmp($id, $trimmed) === 0 ||
                        str_contains(mb_strtolower($brand), $lowerQuery) ||
                        str_contains(mb_strtolower($model), $lowerQuery) ||
                        str_contains(mb_strtolower($vin), $lowerQuery) ||
                        str_contains($year, $trimmed)
                    ) {
                        $matched = true;
                    }
                    break;
            }

            if ($matched) {
                $results[] = $item;
            }
        }

        return $results;
    }

    /**
     * Find single vehicle by its primary ID using sequential comparison.
     *
     * @param array $vehicles
     * @param int $id
     * @return Vehicle|array|null
     */
    public static function findById(array $vehicles, int $id)
    {
        if ($id <= 0) {
            return null;
        }

        foreach ($vehicles as $item) {
            $itemId = $item instanceof Vehicle ? $item->getId() : (int)($item['id'] ?? 0);
            if ($itemId === $id) {
                return $item; // Best case O(1) if first element
            }
        }

        return null; // Worst case O(n) if not found
    }

    /**
     * Search with detailed performance analytics for DSA test/demonstration.
     *
     * @param array $vehicles
     * @param string $keyword
     * @param string $field
     * @return array Analytics report with execution metrics
     */
    public static function searchWithMetrics(array $vehicles, string $keyword, string $field = 'all'): array
    {
        $startTime = microtime(true);
        $comparisons = 0;
        $matches = [];
        $trimmed = trim($keyword);
        $lowerQuery = mb_strtolower($trimmed);

        if ($trimmed !== '') {
            foreach ($vehicles as $item) {
                $comparisons++;
                $id = $item instanceof Vehicle ? (string)$item->getId() : (string)($item['id'] ?? '');
                $brand = $item instanceof Vehicle ? $item->getBrand() : ($item['brand'] ?? '');
                $model = $item instanceof Vehicle ? $item->getModel() : ($item['model'] ?? '');

                $matched = false;
                if ($field === 'id') {
                    $matched = (strcasecmp($id, $trimmed) === 0);
                } elseif ($field === 'brand') {
                    $matched = str_contains(mb_strtolower($brand), $lowerQuery);
                } elseif ($field === 'model') {
                    $matched = str_contains(mb_strtolower($model), $lowerQuery);
                } else {
                    $matched = (strcasecmp($id, $trimmed) === 0 ||
                                str_contains(mb_strtolower($brand), $lowerQuery) ||
                                str_contains(mb_strtolower($model), $lowerQuery));
                }

                if ($matched) {
                    $matches[] = $item;
                }
            }
        } else {
            $matches = $vehicles;
        }

        $durationMs = (microtime(true) - $startTime) * 1000;

        return [
            'algorithm' => 'Linear Search',
            'field' => $field,
            'keyword' => $keyword,
            'total_items_scanned' => count($vehicles),
            'comparisons_count' => $comparisons,
            'matches_count' => count($matches),
            'execution_time_ms' => round($durationMs, 4),
            'time_complexity' => 'Best: O(1) | Average: O(n) | Worst: O(n)',
            'space_complexity' => 'O(1) auxiliary',
            'results' => $matches
        ];
    }
}
