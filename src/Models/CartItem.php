<?php

declare(strict_types=1);

namespace App\Models;

/** Represents a single item in a customer's shopping cart. */
class CartItem
{
    private string $id;
    private string $customerId;
    private string $productId;
    private string $productName;
    private float $unitPrice;
    private int $quantity;

    public function __construct(string $id, string $customerId, string $productId, string $productName, float $unitPrice, int $quantity)
    {
        $this->id = $id;
        $this->customerId = $customerId;
        $this->productId = $productId;
        $this->productName = $productName;
        $this->unitPrice = $unitPrice;
        $this->quantity = $quantity;
    }

    public function getId(): string { return $this->id; }
    public function getCustomerId(): string { return $this->customerId; }
    public function getProductId(): string { return $this->productId; }
    public function getProductName(): string { return $this->productName; }
    public function getUnitPrice(): float { return $this->unitPrice; }
    public function getQuantity(): int { return $this->quantity; }
    public function setQuantity(int $quantity): void { $this->quantity = $quantity; }

    public function getSubtotal(): float
    {
        return round($this->unitPrice * $this->quantity, 2);
    }

    public function toArray(): array
    {
        return [
            'id'           => $this->id,
            'customer_id'  => $this->customerId,
            'product_id'   => $this->productId,
            'product_name' => $this->productName,
            'unit_price'   => $this->unitPrice,
            'quantity'     => $this->quantity,
            'subtotal'     => $this->getSubtotal(),
        ];
    }
}