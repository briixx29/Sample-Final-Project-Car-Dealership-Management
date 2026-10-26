<?php

namespace App\Models;

/**
 * Class TestDrive
 * Represents a test-drive appointment and request.
 */
class TestDrive
{
    private ?int $id;
    private string $customerName;
    private string $contactNumber;
    private ?string $email;
    private int $vehicleId;
    private ?string $vehicleInfo; // Cached brand/model/year
    private string $scheduledDate;
    private string $scheduledTime;
    private string $status; // 'Pending', 'Approved', 'In-Progress', 'Completed', 'Cancelled'
    private ?string $licenseNumber;
    private ?string $notes;
    private ?string $createdAt;
    private ?string $updatedAt;

    public function __construct(
        ?int $id = null,
        string $customerName = '',
        string $contactNumber = '',
        ?string $email = null,
        int $vehicleId = 0,
        ?string $vehicleInfo = null,
        string $scheduledDate = '',
        string $scheduledTime = '10:00:00',
        string $status = 'Pending',
        ?string $licenseNumber = null,
        ?string $notes = null,
        ?string $createdAt = null,
        ?string $updatedAt = null
    ) {
        $this->id = $id;
        $this->customerName = $customerName;
        $this->contactNumber = $contactNumber;
        $this->email = $email;
        $this->vehicleId = $vehicleId;
        $this->vehicleInfo = $vehicleInfo;
        $this->scheduledDate = $scheduledDate ?: date('Y-m-d');
        $this->scheduledTime = $scheduledTime;
        $this->status = $status;
        $this->licenseNumber = $licenseNumber;
        $this->notes = $notes;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            isset($data['id']) ? (int)$data['id'] : null,
            $data['customer_name'] ?? '',
            $data['contact_number'] ?? '',
            $data['email'] ?? null,
            isset($data['vehicle_id']) ? (int)$data['vehicle_id'] : 0,
            $data['vehicle_info'] ?? ($data['vehicle_brand_model'] ?? null),
            $data['scheduled_date'] ?? date('Y-m-d'),
            $data['scheduled_time'] ?? '10:00:00',
            $data['status'] ?? 'Pending',
            $data['license_number'] ?? null,
            $data['notes'] ?? null,
            $data['created_at'] ?? null,
            $data['updated_at'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'customer_name' => $this->customerName,
            'contact_number' => $this->contactNumber,
            'email' => $this->email,
            'vehicle_id' => $this->vehicleId,
            'vehicle_info' => $this->vehicleInfo,
            'scheduled_date' => $this->scheduledDate,
            'scheduled_time' => $this->scheduledTime,
            'status' => $this->status,
            'license_number' => $this->licenseNumber,
            'notes' => $this->notes,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getCustomerName(): string { return $this->customerName; }
    public function getContactNumber(): string { return $this->contactNumber; }
    public function getEmail(): ?string { return $this->email; }
    public function getVehicleId(): int { return $this->vehicleId; }
    public function getVehicleInfo(): ?string { return $this->vehicleInfo; }
    public function getScheduledDate(): string { return $this->scheduledDate; }
    public function getScheduledTime(): string { return $this->scheduledTime; }
    public function getStatus(): string { return $this->status; }
    public function getLicenseNumber(): ?string { return $this->licenseNumber; }
    public function getNotes(): ?string { return $this->notes; }
    public function getCreatedAt(): ?string { return $this->createdAt; }
    public function getUpdatedAt(): ?string { return $this->updatedAt; }

    // Setters
    public function setId(int $id): void { $this->id = $id; }
    public function setCustomerName(string $name): void { $this->customerName = $name; }
    public function setContactNumber(string $contact): void { $this->contactNumber = $contact; }
    public function setEmail(?string $email): void { $this->email = $email; }
    public function setVehicleId(int $vehicleId): void { $this->vehicleId = $vehicleId; }
    public function setVehicleInfo(?string $info): void { $this->vehicleInfo = $info; }
    public function setScheduledDate(string $date): void { $this->scheduledDate = $date; }
    public function setScheduledTime(string $time): void { $this->scheduledTime = $time; }
    public function setStatus(string $status): void { $this->status = $status; }
    public function setLicenseNumber(?string $num): void { $this->licenseNumber = $num; }
    public function setNotes(?string $notes): void { $this->notes = $notes; }

    public function isPending(): bool { return strcasecmp($this->status, 'Pending') === 0; }
    public function isApproved(): bool { return strcasecmp($this->status, 'Approved') === 0; }
    public function getFormattedDateTime(): string {
        return date('M d, Y', strtotime($this->scheduledDate)) . ' at ' . date('h:i A', strtotime($this->scheduledTime));
    }
}
