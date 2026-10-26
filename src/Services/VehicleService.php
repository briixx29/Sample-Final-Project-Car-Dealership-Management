<?php

namespace App\Services;

use App\Config\Database;
use App\Models\Vehicle;
use App\Algorithms\LinearSearch;
use App\Algorithms\MergeSort;
use App\DataStructures\BinarySearchTree;
use PDO;
use Exception;

/**
 * Class VehicleService
 * Manages vehicle records, inventory operations, and integrates LinearSearch, MergeSort, and BST.
 */
class VehicleService
{
    private static ?BinarySearchTree $bstIndex = null;

    /**
     * Get all vehicles from database
     *
     * @param bool $includeSold
     * @return Vehicle[]
     */
    public static function getAll(bool $includeSold = true): array
    {
        $db = Database::getConnection();
        $sql = "SELECT * FROM vehicles";
        if (!$includeSold) {
            $sql .= " WHERE status = 'Available'";
        }
        $sql .= " ORDER BY id DESC";

        $stmt = $db->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $vehicles = [];
        foreach ($rows as $row) {
            $vehicles[] = Vehicle::fromArray($row);
        }

        return $vehicles;
    }

    /**
     * Get a vehicle by ID using SQL
     */
    public static function getById(int $id): ?Vehicle
    {
        if ($id <= 0) {
            return null;
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM vehicles WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? Vehicle::fromArray($row) : null;
    }

    /**
     * Search vehicle using Binary Search Tree (BST)
     * Demonstrates DSA component 5.3
     */
    public static function getByIdViaBST(int $id): array
    {
        // Build or get cached BST
        $vehicles = self::getAll(true);
        $bst = BinarySearchTree::buildFromVehicles($vehicles);
        self::$bstIndex = $bst;

        return $bst->searchWithMetrics($id);
    }

    /**
     * Search vehicles using Linear Search
     * Demonstrates DSA component 5.1
     */
    public static function searchVehicles(string $keyword, string $field = 'all'): array
    {
        $allVehicles = self::getAll(true);
        return LinearSearch::search($allVehicles, $keyword, $field);
    }

    /**
     * Sort vehicles using Merge Sort
     * Demonstrates DSA component 5.2
     */
    public static function sortVehicles(array $vehicles, string $field = 'price', string $direction = 'ASC'): array
    {
        return MergeSort::sort($vehicles, $field, $direction);
    }

    /**
     * Get inventory statistics for dashboard
     */
    public static function getStats(): array
    {
        $db = Database::getConnection();
        $stmt = $db->query("
            SELECT 
                COUNT(*) as total_vehicles,
                SUM(CASE WHEN status = 'Available' THEN 1 ELSE 0 END) as available_count,
                SUM(CASE WHEN status = 'Sold' THEN 1 ELSE 0 END) as sold_count,
                COALESCE(SUM(CASE WHEN status = 'Available' THEN price ELSE 0 END), 0) as total_available_value,
                COALESCE(AVG(price), 0) as average_price
            FROM vehicles
        ");
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
            'total_vehicles' => 0,
            'available_count' => 0,
            'sold_count' => 0,
            'total_available_value' => 0,
            'average_price' => 0
        ];
    }

    /**
     * Input validation testing normal, empty, missing, and invalid data
     */
    public static function validate(array $data, bool $isUpdate = false): array
    {
        $errors = [];

        // Required fields check (Empty & Missing data validation)
        $requiredFields = ['brand', 'model', 'year', 'color', 'transmission', 'fuel_type', 'price'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || trim((string)$data[$field]) === '') {
                $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required and cannot be empty.';
            }
        }

        // Year validation (Invalid data test)
        if (isset($data['year']) && trim((string)$data['year']) !== '') {
            $year = filter_var($data['year'], FILTER_VALIDATE_INT);
            $currentYear = (int)date('Y') + 1;
            if ($year === false || $year < 1980 || $year > $currentYear) {
                $errors['year'] = "Vehicle year must be a valid integer between 1980 and {$currentYear}.";
            }
        }

        // Price validation (Invalid data test: negative, non-numeric)
        if (isset($data['price']) && trim((string)$data['price']) !== '') {
            if (!is_numeric($data['price']) || (float)$data['price'] <= 0) {
                $errors['price'] = 'Vehicle price must be a positive number greater than zero.';
            }
        }

        // Mileage validation
        if (isset($data['mileage']) && trim((string)$data['mileage']) !== '') {
            if (!is_numeric($data['mileage']) || (int)$data['mileage'] < 0) {
                $errors['mileage'] = 'Mileage cannot be negative.';
            }
        }

        // Transmission validation
        $validTransmissions = ['Automatic', 'Manual', 'CVT', 'Dual-Clutch'];
        if (isset($data['transmission']) && !in_array($data['transmission'], $validTransmissions)) {
            $errors['transmission'] = 'Transmission must be Automatic, Manual, CVT, or Dual-Clutch.';
        }

        // Fuel type validation
        $validFuels = ['Gasoline', 'Diesel', 'Hybrid', 'Electric', 'Plug-in Hybrid'];
        if (isset($data['fuel_type']) && !in_array($data['fuel_type'], $validFuels)) {
            $errors['fuel_type'] = 'Invalid fuel type selected.';
        }

        // Status validation
        if (isset($data['status'])) {
            $validStatuses = ['Available', 'Sold'];
            if (!in_array($data['status'], $validStatuses)) {
                $errors['status'] = 'Vehicle status must be Available or Sold.';
            }
        }

        return $errors;
    }

    /**
     * Create a new vehicle record
     */
    public static function create(array $data): array
    {
        $errors = self::validate($data);
        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => 'Validation failed. Please correct the highlighted errors.',
                'errors' => $errors
            ];
        }

        try {
            $db = Database::getConnection();

            // Default image placeholder if none supplied
            $imageUrl = trim($data['image_url'] ?? '');
            if ($imageUrl === '') {
                $brand = strtolower(trim($data['brand']));
                $imageUrl = "https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&fit=crop&w=800&q=80";
            }

            // Auto-generate VIN if missing
            $vin = trim($data['vin'] ?? '');
            if ($vin === '') {
                $vin = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $data['brand'] . $data['model']), 0, 5)) 
                     . date('y') . rand(100000, 999999);
            }

            $stmt = $db->prepare("
                INSERT INTO vehicles (
                    brand, model, year, color, transmission, fuel_type, 
                    price, mileage, vin, status, image_url, description, created_at, updated_at
                ) VALUES (
                    :brand, :model, :year, :color, :transmission, :fuel_type, 
                    :price, :mileage, :vin, :status, :image_url, :description, NOW(), NOW()
                )
            ");

            $stmt->execute([
                'brand' => trim($data['brand']),
                'model' => trim($data['model']),
                'year' => (int)$data['year'],
                'color' => trim($data['color']),
                'transmission' => $data['transmission'],
                'fuel_type' => $data['fuel_type'],
                'price' => (float)$data['price'],
                'mileage' => (int)($data['mileage'] ?? 0),
                'vin' => $vin,
                'status' => $data['status'] ?? 'Available',
                'image_url' => $imageUrl,
                'description' => trim($data['description'] ?? '')
            ]);

            $newId = (int)$db->lastInsertId();

            return [
                'success' => true,
                'message' => "Vehicle added successfully with ID #{$newId}.",
                'id' => $newId
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update an existing vehicle record
     */
    public static function update(int $id, array $data): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Invalid vehicle ID.'];
        }

        $existing = self::getById($id);
        if (!$existing) {
            return ['success' => false, 'message' => "Vehicle with ID #{$id} does not exist."];
        }

        $errors = self::validate($data, true);
        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => 'Validation failed. Please correct the highlighted errors.',
                'errors' => $errors
            ];
        }

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                UPDATE vehicles SET
                    brand = :brand,
                    model = :model,
                    year = :year,
                    color = :color,
                    transmission = :transmission,
                    fuel_type = :fuel_type,
                    price = :price,
                    mileage = :mileage,
                    vin = :vin,
                    status = :status,
                    image_url = :image_url,
                    description = :description,
                    updated_at = NOW()
                WHERE id = :id
            ");

            $stmt->execute([
                'id' => $id,
                'brand' => trim($data['brand']),
                'model' => trim($data['model']),
                'year' => (int)$data['year'],
                'color' => trim($data['color']),
                'transmission' => $data['transmission'],
                'fuel_type' => $data['fuel_type'],
                'price' => (float)$data['price'],
                'mileage' => (int)($data['mileage'] ?? $existing->getMileage()),
                'vin' => trim($data['vin'] ?? $existing->getVin()),
                'status' => $data['status'] ?? $existing->getStatus(),
                'image_url' => trim($data['image_url'] ?? $existing->getImageUrl()),
                'description' => trim($data['description'] ?? $existing->getDescription())
            ]);

            return [
                'success' => true,
                'message' => "Vehicle #{$id} updated successfully."
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update vehicle status ('Available' or 'Sold')
     */
    public static function updateStatus(int $id, string $status): array
    {
        $status = ucfirst(strtolower($status));
        if (!in_array($status, ['Available', 'Sold'])) {
            return ['success' => false, 'message' => 'Status must be Available or Sold.'];
        }

        $vehicle = self::getById($id);
        if (!$vehicle) {
            return ['success' => false, 'message' => "Vehicle #{$id} not found."];
        }

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("UPDATE vehicles SET status = :status, updated_at = NOW() WHERE id = :id");
            $stmt->execute(['status' => $status, 'id' => $id]);

            return [
                'success' => true,
                'message' => "Vehicle #{$id} status updated to '{$status}'."
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Delete vehicle record
     * Note from spec: "Sold vehicle records should normally be retained for reference instead of permanently deleted."
     */
    public static function delete(int $id, bool $permanent = false): array
    {
        $vehicle = self::getById($id);
        if (!$vehicle) {
            return ['success' => false, 'message' => "Vehicle #{$id} not found."];
        }

        if ($vehicle->isSold() && !$permanent) {
            return [
                'success' => false,
                'message' => "Vehicle #{$id} is marked as Sold and retained for sales reference. Only administrators can permanently delete records."
            ];
        }

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("DELETE FROM vehicles WHERE id = :id");
            $stmt->execute(['id' => $id]);

            return [
                'success' => true,
                'message' => "Vehicle #{$id} has been removed from records."
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
