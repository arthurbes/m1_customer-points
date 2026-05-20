<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Mock external product catalog microservice.
 * Provides product lookup, listing, and stock availability checks.
 */
class CatalogService
{
    private static array $products = [
        'prod-001' => ['name' => 'Mouse Wireless', 'price' => 29.99, 'in_stock' => true, 'stock' => 5],
        'prod-002' => ['name' => 'Teclado Mecânico', 'price' => 89.99, 'in_stock' => true, 'stock' => 5],
        'prod-003' => ['name' => 'Hub USB-C', 'price' => 45.00, 'in_stock' => true, 'stock' => 5],
        'prod-004' => ['name' => 'Suporte Monitor', 'price' => 35.00, 'in_stock' => false, 'stock' => 5],
        'prod-005' => ['name' => 'Webcam HD', 'price' => 59.99, 'in_stock' => true, 'stock' => 5],
    ];

    public static function getProduct(string $productId): ?array
    {
        if (!isset(self::$products[$productId])) {
            return null;
        }
        return array_merge(['id' => $productId], self::$products[$productId]);
    }

    public static function listProducts(): array
    {
        $list = [];
        foreach (self::$products as $id => $product) {
            $list[] = array_merge(['id' => $id], $product);
        }
        return $list;
    }

    public static function isInStock(string $productId): bool
    {
        return self::$products[$productId]['in_stock'] ?? false;
    }

    public static function setStock(string $productId, int $quantity): void
    {
        if (!isset(self::$products[$productId])) {
            throw new Exception('Product not found');
        }
        self::$products[$productId]['in_stock'] = ($quantity > 0);
    }

    public static function getStock(string $productId): int
    {
        return self::$products[$productId]['in_stock'] ? 1 : 0;
    }

    public static function reduceStock(string $productId, int $quantity): void
    {
        if (!isset(self::$products[$productId])) {
            throw new Exception('Product not found');
        }
        self::$products[$productId]['in_stock'] = ($quantity > 0);
    }
}