<?php

namespace App\Services;

use App\Config\Database;
use App\Models\TestDrive;
use App\DataStructures\Queue;
use App\Services\VehicleService;
use PDO;
use Exception;

/**
 * Class TestDriveService
 * Connects the FIFO Queue data structure with the actual test-drive booking workflow.
 */
class TestDriveService
{
    /**
     * Build the active FIFO Queue of pending test drive requests.
     * Orders by creation timestamp / ID ascending so earliest request is at the front.
     */
    public static function getPendingQueue(): Queue
    {
        $db = Database::getConnection();
        $stmt = $db->query("
            SELECT t.*, CONCAT(v.year, ' ', v.brand, ' ', v.model) AS vehicle_brand_model
            FROM test_drives t
            JOIN vehicles v ON t.vehicle_id = v.id
            WHERE t.status = 'Pending'
            ORDER BY t.id ASC
        ");

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $queue = new Queue();

        foreach ($rows as $row) {
            $queue->enqueue(TestDrive::fromArray($row));
        }

        return $queue;
    }

    /**
     * Inspect the request at the front of the FIFO queue without dequeuing.
     */
    public static function peekNextInQueue(): ?TestDrive
    {
        $queue = self::getPendingQueue();
        return $queue->peek();
    }

    /**
     * Process (Dequeue) the next pending test-drive in FIFO order.
     * Fulfills DSA Component 5.4 requirement.
     *
     * @param string $action 'Approved' or 'In-Progress'
     * @param string|null $notes Optional staff processing note
     * @return array Result of queue dispatch
     */
    public static function processNextInQueue(string $action = 'Approved', ?string $notes = null): array
    {
        $startTime = microtime(true);
        $queue = self::getPendingQueue();

        if ($queue->isEmpty()) {
            return [
                'success' => false,
                'message' => 'The pending test-drive queue is currently empty. No requests to process.'
            ];
        }

        $queueSizeBefore = $queue->getSize();

        // Dequeue from front pointer (O(1))
        /** @var TestDrive $dequeuedRequest */
        $dequeuedRequest = $queue->dequeue();

        if (!$dequeuedRequest || !$dequeuedRequest->getId()) {
            return ['success' => false, 'message' => 'Failed to dequeue valid request.'];
        }

        // Update record in MySQL
        $db = Database::getConnection();
        $targetStatus = in_array($action, ['Approved', 'In-Progress']) ? $action : 'Approved';

        $stmt = $db->prepare("
            UPDATE test_drives 
            SET status = :status, 
                notes = CASE WHEN :notes IS NOT NULL AND :notes != '' THEN CONCAT(COALESCE(notes, ''), ' [Dispatch Note: ', :notes2, ']') ELSE notes END,
                updated_at = NOW()
            WHERE id = :id
        ");

        $stmt->execute([
            'status' => $targetStatus,
            'notes' => $notes,
            'notes2' => $notes,
            'id' => $dequeuedRequest->getId()
        ]);

        $durationMs = (microtime(true) - $startTime) * 1000;
        $queueSizeAfter = $queue->getSize();

        return [
            'success' => true,
            'message' => "Successfully dequeued Request #{$dequeuedRequest->getId()} ({$dequeuedRequest->getCustomerName()}) and marked as '{$targetStatus}'.",
            'processed_request' => $dequeuedRequest->toArray(),
            'algorithm' => 'Linked List FIFO Queue (Front Dequeue)',
            'time_complexity' => 'O(1) with front pointer',
            'queue_size_before' => $queueSizeBefore,
            'queue_size_after' => $queueSizeAfter,
            'next_in_line' => $queue->peek() ? $queue->peek()->toArray() : null,
            'execution_time_ms' => round($durationMs, 4)
        ];
    }

    /**
     * Get all test drive records
     */
    public static function getAll(?string $status = null): array
    {
        $db = Database::getConnection();
        $sql = "
            SELECT t.*, CONCAT(v.year, ' ', v.brand, ' ', v.model) AS vehicle_brand_model
            FROM test_drives t
            JOIN vehicles v ON t.vehicle_id = v.id
        ";

        $params = [];
        if ($status && $status !== 'all') {
            $sql .= " WHERE t.status = :status";
            $params['status'] = $status;
        }

        $sql .= " ORDER BY t.id DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $list = [];
        foreach ($rows as $row) {
            $list[] = TestDrive::fromArray($row);
        }

        return $list;
    }

    /**
     * Validate test drive input (normal, empty, missing, and invalid data checks)
     */
    public static function validate(array $data): array
    {
        $errors = [];

        // Customer Name
        if (!isset($data['customer_name']) || trim($data['customer_name']) === '') {
            $errors['customer_name'] = 'Customer name is required.';
        }

        // Contact Number
        if (!isset($data['contact_number']) || trim($data['contact_number']) === '') {
            $errors['contact_number'] = 'Contact number is required.';
        } elseif (!preg_match('/^[0-9+\-\s()]{7,20}$/', trim($data['contact_number']))) {
            $errors['contact_number'] = 'Please enter a valid phone number.';
        }

        // Email (optional)
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email address.';
        }

        // Vehicle ID check
        if (empty($data['vehicle_id']) || (int)$data['vehicle_id'] <= 0) {
            $errors['vehicle_id'] = 'Please select a valid vehicle.';
        } else {
            $vehicle = VehicleService::getById((int)$data['vehicle_id']);
            if (!$vehicle) {
                $errors['vehicle_id'] = 'Selected vehicle does not exist in inventory.';
            } elseif ($vehicle->isSold()) {
                $errors['vehicle_id'] = 'Vehicle is already sold and cannot be scheduled for test-drives.';
            }
        }

        // Schedule Date
        if (empty($data['scheduled_date'])) {
            $errors['scheduled_date'] = 'Test drive date is required.';
        } else {
            $scheduleTimestamp = strtotime($data['scheduled_date']);
            $todayTimestamp = strtotime(date('Y-m-d'));
            if ($scheduleTimestamp === false) {
                $errors['scheduled_date'] = 'Invalid date format.';
            } elseif ($scheduleTimestamp < $todayTimestamp) {
                $errors['scheduled_date'] = 'Scheduled date cannot be in the past.';
            }
        }

        // Schedule Time
        if (empty($data['scheduled_time'])) {
            $errors['scheduled_time'] = 'Preferred time slot is required.';
        }

        return $errors;
    }

    /**
     * Record a new test-drive request and enqueue it into FIFO queue
     */
    public static function create(array $data): array
    {
        $errors = self::validate($data);
        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => 'Please correct the validation errors.',
                'errors' => $errors
            ];
        }

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                INSERT INTO test_drives (
                    customer_name, contact_number, email, vehicle_id, 
                    scheduled_date, scheduled_time, status, license_number, notes, created_at, updated_at
                ) VALUES (
                    :customer_name, :contact_number, :email, :vehicle_id, 
                    :scheduled_date, :scheduled_time, :status, :license_number, :notes, NOW(), NOW()
                )
            ");

            $status = $data['status'] ?? 'Pending';

            $stmt->execute([
                'customer_name' => trim($data['customer_name']),
                'contact_number' => trim($data['contact_number']),
                'email' => !empty($data['email']) ? trim($data['email']) : null,
                'vehicle_id' => (int)$data['vehicle_id'],
                'scheduled_date' => $data['scheduled_date'],
                'scheduled_time' => $data['scheduled_time'],
                'status' => $status,
                'license_number' => !empty($data['license_number']) ? trim($data['license_number']) : null,
                'notes' => !empty($data['notes']) ? trim($data['notes']) : null
            ]);

            $id = (int)$db->lastInsertId();

            return [
                'success' => true,
                'message' => "Test drive request #{$id} registered and queued successfully.",
                'id' => $id
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update individual test drive status
     */
    public static function updateStatus(int $id, string $status, ?string $notes = null): array
    {
        $validStatuses = ['Pending', 'Approved', 'In-Progress', 'Completed', 'Cancelled'];
        if (!in_array($status, $validStatuses)) {
            return ['success' => false, 'message' => 'Invalid status option.'];
        }

        try {
            $db = Database::getConnection();
            $sql = "UPDATE test_drives SET status = :status, updated_at = NOW()";
            $params = ['status' => $status, 'id' => $id];

            if ($notes !== null && trim($notes) !== '') {
                $sql .= ", notes = :notes";
                $params['notes'] = trim($notes);
            }

            $sql .= " WHERE id = :id";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            return [
                'success' => true,
                'message' => "Test drive #{$id} status set to '{$status}'."
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get statistics
     */
    public static function getStats(): array
    {
        $db = Database::getConnection();
        $stmt = $db->query("
            SELECT 
                COUNT(*) as total_requests,
                SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved_count,
                SUM(CASE WHEN status = 'In-Progress' THEN 1 ELSE 0 END) as in_progress_count,
                SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_count,
                SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled_count
            FROM test_drives
        ");
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
            'total_requests' => 0,
            'pending_count' => 0,
            'approved_count' => 0,
            'in_progress_count' => 0,
            'completed_count' => 0,
            'cancelled_count' => 0
        ];
    }
}
