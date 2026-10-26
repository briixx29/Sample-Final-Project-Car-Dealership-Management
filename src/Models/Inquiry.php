<?php

namespace App\Models;

/**
 * Class Inquiry
 * Represents a customer inquiry record.
 */
class Inquiry
{
    private ?int $id;
    private string $customerName;
    private string $contactNumber;
    private ?string $email;
    private ?int $vehicleId;
    private ?string $vehicleInfo; // Cached brand/model for quick display
    private string $question;
    private string $inquiryDate;
    private string $status; // 'Pending', 'Contacted', 'Resolved', 'Closed'
    private ?string $staffNotes;
    private ?string $createdAt;
    private ?string $updatedAt;

    public function __construct(
        ?int $id = null,
        string $customerName = '',
        string $contactNumber = '',
        ?string $email = null,
        ?int $vehicleId = null,
        ?string $vehicleInfo = null,
        string $question = '',
        string $inquiryDate = '',
        string $status = 'Pending',
        ?string $staffNotes = null,
        ?string $createdAt = null,
        ?string $updatedAt = null
    ) {
        $this->id = $id;
        $this->customerName = $customerName;
        $this->contactNumber = $contactNumber;
        $this->email = $email;
        $this->vehicleId = $vehicleId;
        $this->vehicleInfo = $vehicleInfo;
        $this->question = $question;
        $this->inquiryDate = $inquiryDate ?: date('Y-m-d');
        $this->status = $status;
        $this->staffNotes = $staffNotes;
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
            isset($data['vehicle_id']) && $data['vehicle_id'] !== '' ? (int)$data['vehicle_id'] : null,
            $data['vehicle_info'] ?? ($data['vehicle_brand_model'] ?? null),
            $data['question'] ?? ($data['message'] ?? ''),
            $data['inquiry_date'] ?? date('Y-m-d'),
            $data['status'] ?? 'Pending',
            $data['staff_notes'] ?? null,
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
            'question' => $this->question,
            'inquiry_date' => $this->inquiryDate,
            'status' => $this->status,
            'staff_notes' => $this->staffNotes,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getCustomerName(): string { return $this->customerName; }
    public function getContactNumber(): string { return $this->contactNumber; }
    public function getEmail(): ?string { return $this->email; }
    public function getVehicleId(): ?int { return $this->vehicleId; }
    public function getVehicleInfo(): ?string { return $this->vehicleInfo; }
    public function getQuestion(): string { return $this->question; }
    public function getInquiryDate(): string { return $this->inquiryDate; }
    public function getStatus(): string { return $this->status; }
    public function getStaffNotes(): ?string { return $this->staffNotes; }
    public function getCreatedAt(): ?string { return $this->createdAt; }
    public function getUpdatedAt(): ?string { return $this->updatedAt; }

    // Setters
    public function setId(int $id): void { $this->id = $id; }
    public function setCustomerName(string $name): void { $this->customerName = $name; }
    public function setContactNumber(string $contact): void { $this->contactNumber = $contact; }
    public function setEmail(?string $email): void { $this->email = $email; }
    public function setVehicleId(?int $vehicleId): void { $this->vehicleId = $vehicleId; }
    public function setVehicleInfo(?string $info): void { $this->vehicleInfo = $info; }
    public function setQuestion(string $question): void { $this->question = $question; }
    public function setInquiryDate(string $date): void { $this->inquiryDate = $date; }
    public function setStatus(string $status): void { $this->status = $status; }
    public function setStaffNotes(?string $notes): void { $this->staffNotes = $notes; }
}
