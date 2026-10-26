<?php

namespace App\Models;

/**
 * Class Vehicle
 * Represents a vehicle record in the inventory.
 */
class Vehicle
{
    private ?int $id;
    private string $brand;
    private string $model;
    private int $year;
    private string $color;
    private string $transmission; // 'Automatic', 'Manual', 'CVT', 'Dual-Clutch'
    private string $fuelType;     // 'Gasoline', 'Diesel', 'Hybrid', 'Electric'
    private float $price;
    private int $mileage;
    private string $vin;
    private string $status;       // 'Available' or 'Sold'
    private ?string $imageUrl;
    private ?string $description;
    private ?string $createdAt;
    private ?string $updatedAt;

    public function __construct(
        ?int $id = null,
        string $brand = '',
        string $model = '',
        int $year = 2024,
        string $color = '',
        string $transmission = 'Automatic',
        string $fuelType = 'Gasoline',
        float $price = 0.0,
        int $mileage = 0,
        string $vin = '',
        string $status = 'Available',
        ?string $imageUrl = null,
        ?string $description = null,
        ?string $createdAt = null,
        ?string $updatedAt = null
    ) {
        $this->id = $id;
        $this->brand = $brand;
        $this->model = $model;
        $this->year = $year;
        $this->color = $color;
        $this->transmission = $transmission;
        $this->fuelType = $fuelType;
        $this->price = $price;
        $this->mileage = $mileage;
        $this->vin = $vin;
        $this->status = $status;
        $this->imageUrl = $imageUrl;
        $this->description = $description;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            isset($data['id']) ? (int)$data['id'] : null,
            $data['brand'] ?? '',
            $data['model'] ?? '',
            isset($data['year']) ? (int)$data['year'] : 2024,
            $data['color'] ?? '',
            $data['transmission'] ?? 'Automatic',
            $data['fuel_type'] ?? 'Gasoline',
            isset($data['price']) ? (float)$data['price'] : 0.0,
            isset($data['mileage']) ? (int)$data['mileage'] : 0,
            $data['vin'] ?? '',
            $data['status'] ?? 'Available',
            $data['image_url'] ?? null,
            $data['description'] ?? null,
            $data['created_at'] ?? null,
            $data['updated_at'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'brand' => $this->brand,
            'model' => $this->model,
            'year' => $this->year,
            'color' => $this->color,
            'transmission' => $this->transmission,
            'fuel_type' => $this->fuelType,
            'price' => $this->price,
            'mileage' => $this->mileage,
            'vin' => $this->vin,
            'status' => $this->status,
            'image_url' => $this->imageUrl,
            'description' => $this->description,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getBrand(): string { return $this->brand; }
    public function getModel(): string { return $this->model; }
    public function getYear(): int { return $this->year; }
    public function getColor(): string { return $this->color; }
    public function getTransmission(): string { return $this->transmission; }
    public function getFuelType(): string { return $this->fuelType; }
    public function getPrice(): float { return $this->price; }
    public function getMileage(): int { return $this->mileage; }
    public function getVin(): string { return $this->vin; }
    public function getStatus(): string { return $this->status; }
    public function getImageUrl(): ?string { return $this->imageUrl; }
    public function getDescription(): ?string { return $this->description; }
    public function getCreatedAt(): ?string { return $this->createdAt; }
    public function getUpdatedAt(): ?string { return $this->updatedAt; }

    // Setters
    public function setId(int $id): void { $this->id = $id; }
    public function setBrand(string $brand): void { $this->brand = $brand; }
    public function setModel(string $model): void { $this->model = $model; }
    public function setYear(int $year): void { $this->year = $year; }
    public function setColor(string $color): void { $this->color = $color; }
    public function setTransmission(string $transmission): void { $this->transmission = $transmission; }
    public function setFuelType(string $fuelType): void { $this->fuelType = $fuelType; }
    public function setPrice(float $price): void { $this->price = $price; }
    public function setMileage(int $mileage): void { $this->mileage = $mileage; }
    public function setVin(string $vin): void { $this->vin = $vin; }
    public function setStatus(string $status): void { $this->status = $status; }
    public function setImageUrl(?string $imageUrl): void { $this->imageUrl = $imageUrl; }
    public function setDescription(?string $description): void { $this->description = $description; }

    // Helpers
    public function isAvailable(): bool { return strcasecmp($this->status, 'Available') === 0; }
    public function isSold(): bool { return strcasecmp($this->status, 'Sold') === 0; }
    public function getFullName(): string { return "{$this->year} {$this->brand} {$this->model}"; }
    public function getFormattedPrice(): string { return '$' . number_format($this->price, 2); }
    public function getFormattedMileage(): string { return number_format($this->mileage) . ' mi'; }
}
