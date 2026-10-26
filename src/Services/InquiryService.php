<?php

namespace App\Services;

use App\Config\Database;
use App\Models\Inquiry;
use PDO;
use Exception;

/**
 * Class InquiryService
 * Handles customer inquiries recording, validation, status updates, and retrieval.
 */
class InquiryService
{
    /**
     * Get all inquiries, optionally filtered by status
     *
     * @param string|null $statusFilter
     * @return Inquiry[]
     */
    public static function getAll(?string $statusFilter = null): array
    {
        $db = Database::getConnection();
        $sql = "
            SELECT i.*, CONCAT(v.year, ' ', v.brand, ' ', v.model) AS vehicle_brand_model
            FROM inquiries i
            LEFT JOIN vehicles v ON i.vehicle_id = v.id
        ";

        $params = [];
        if ($statusFilter && $statusFilter !== 'all') {
            $sql .= " WHERE i.status = :status";
            $params['status'] = $statusFilter;
        }

        $sql .= " ORDER BY i.id DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $inquiries = [];
        foreach ($rows as $row) {
            $inquiries[] = Inquiry::fromArray($row);
        }

        return $inquiries;
    }

    /**
     * Get inquiry by ID
     */
    public static function getById(int $id): ?Inquiry
    {
        if ($id <= 0) return null;

        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT i.*, CONCAT(v.year, ' ', v.brand, ' ', v.model) AS vehicle_brand_model
            FROM inquiries i
            LEFT JOIN vehicles v ON i.vehicle_id = v.id
            WHERE i.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? Inquiry::fromArray($row) : null;
    }

    /**
     * Validate inquiry input
     */
    public static function validate(array $data): array
    {
        $errors = [];

        // Customer Name
        if (!isset($data['customer_name']) || trim($data['customer_name']) === '') {
            $errors['customer_name'] = 'Customer name is required.';
        } elseif (strlen(trim($data['customer_name'])) < 2) {
            $errors['customer_name'] = 'Customer name must be at least 2 characters.';
        }

        // Contact Number
        if (!isset($data['contact_number']) || trim($data['contact_number']) === '') {
            $errors['contact_number'] = 'Contact phone number is required.';
        } elseif (!preg_match('/^[0-9+\-\s()]{7,20}$/', trim($data['contact_number']))) {
            $errors['contact_number'] = 'Please provide a valid contact phone number.';
        }

        // Email (optional, but if provided must be valid)
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'The email address format is invalid.';
        }

        // Question / Message
        if (!isset($data['question']) || trim($data['question']) === '') {
            $errors['question'] = 'Inquiry question/details cannot be empty.';
        }

        // Status
        $validStatuses = ['Pending', 'Contacted', 'Resolved', 'Closed'];
        if (isset($data['status']) && !in_array($data['status'], $validStatuses)) {
            $errors['status'] = 'Invalid inquiry status.';
        }

        return $errors;
    }

    /**
     * Record a new customer inquiry
     */
    public static function create(array $data): array
    {
        $errors = self::validate($data);
        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => 'Please fix the errors below.',
                'errors' => $errors
            ];
        }

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                INSERT INTO inquiries (
                    customer_name, contact_number, email, vehicle_id, 
                    question, inquiry_date, status, staff_notes, created_at, updated_at
                ) VALUES (
                    :customer_name, :contact_number, :email, :vehicle_id, 
                    :question, :inquiry_date, :status, :staff_notes, NOW(), NOW()
                )
            ");

            $vehicleId = !empty($data['vehicle_id']) ? (int)$data['vehicle_id'] : null;
            $inquiryDate = !empty($data['inquiry_date']) ? $data['inquiry_date'] : date('Y-m-d');

            $stmt->execute([
                'customer_name' => trim($data['customer_name']),
                'contact_number' => trim($data['contact_number']),
                'email' => !empty($data['email']) ? trim($data['email']) : null,
                'vehicle_id' => $vehicleId,
                'question' => trim($data['question']),
                'inquiry_date' => $inquiryDate,
                'status' => $data['status'] ?? 'Pending',
                'staff_notes' => !empty($data['staff_notes']) ? trim($data['staff_notes']) : null
            ]);

            return [
                'success' => true,
                'message' => 'Customer inquiry recorded successfully.',
                'id' => (int)$db->lastInsertId()
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update inquiry status and staff notes
     */
    public static function updateStatus(int $id, string $status, ?string $staffNotes = null): array
    {
        $validStatuses = ['Pending', 'Contacted', 'Resolved', 'Closed'];
        if (!in_array($status, $validStatuses)) {
            return ['success' => false, 'message' => 'Invalid status option.'];
        }

        try {
            $db = Database::getConnection();
            $sql = "UPDATE inquiries SET status = :status, updated_at = NOW()";
            $params = ['status' => $status, 'id' => $id];

            if ($staffNotes !== null) {
                $sql .= ", staff_notes = :notes";
                $params['notes'] = trim($staffNotes);
            }

            $sql .= " WHERE id = :id";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            return [
                'success' => true,
                'message' => "Inquiry #{$id} status updated to '{$status}'."
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Delete inquiry
     */
    public static function delete(int $id): array
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("DELETE FROM inquiries WHERE id = :id");
            $stmt->execute(['id' => $id]);

            return [
                'success' => true,
                'message' => "Inquiry #{$id} deleted successfully."
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Count inquiries by status
     */
    public static function getCountByStatus(): array
    {
        $db = Database::getConnection();
        $stmt = $db->query("
            SELECT 
                COUNT(*) as total_inquiries,
                SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN status = 'Contacted' THEN 1 ELSE 0 END) as contacted_count,
                SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) as resolved_count
            FROM inquiries
        ");
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total_inquiries' => 0, 'pending_count' => 0];
    }
}
