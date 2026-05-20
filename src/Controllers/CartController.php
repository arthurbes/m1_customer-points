<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\CartItem;
use App\Models\RewardAccount;
use App\Services\AuthService;
use App\Services\CatalogService;
use Exception;

/**
 * Handles shopping cart operations and reward points management.
 * All endpoints require authentication via X-Customer-Token header.
 */
class CartController
{
    private array $carts;
    private array $rewardAccounts;
    private int $nextItemId = 3001;

    public function __construct()
    {
        $this->carts = [
            'cust-001' => [
                'item-1001' => new CartItem('item-1001', 'cust-001', 'prod-001', 'Mouse Wireless', 29.99, 2),
            ],
            'cust-002' => [
                'item-2001' => new CartItem('item-2001', 'cust-002', 'prod-002', 'Teclado Mecânico', 89.99, 1),
            ],
            'cust-003' => [],
        ];
        $this->rewardAccounts = [
            'cust-001' => new RewardAccount('cust-001', 5000),
            'cust-002' => new RewardAccount('cust-002', 1200),
            'cust-003' => new RewardAccount('cust-003', 0),
        ];
    }

    private function requireAuth(array $headers): string
    {
        $customer = AuthService::authenticate($headers);
        if (!$customer) {
            throw new Exception('Unauthorized', 401);
        }
        return $customer['id'];
    }

    private function requireAdmin(array $headers): array
    {
        $admin = AuthService::authenticateAdmin($headers);
        if (!$admin) {
            throw new Exception('Forbidden: Admin access required', 403);
        }
        return $admin;
    }

    /** GET /cart */
    public function viewCart(array $requestData): array
    {
        try {
            $customerId = $this->requireAuth($requestData['headers'] ?? []);
            $items = $this->carts[$customerId] ?? [];
            $total = 0.0;
            $serialized = [];
            foreach ($items as $item) {
                $serialized[] = $item->toArray();
                $total += $item->getSubtotal();
            }
            return ['status' => 200, 'body' => ['success' => true, 'data' => ['items' => $serialized, 'total' => round($total, 2)]]];
        } catch (Exception $e) {
            return $this->errorResponse($e);
        }
    }

    /** POST /cart/items — Body: { product_id, quantity } */
    public function addItem(array $requestData): array
    {
        try {
            $customerId = $this->requireAuth($requestData['headers'] ?? []);
            $productId = $requestData['body']['product_id'] ?? null;
            $quantity = $requestData['body']['quantity'] ?? null;

            if (!$productId || !$quantity) {
                throw new Exception('Missing product_id or quantity', 400);
            }
            if (!is_int($quantity) || $quantity < 1) {
                throw new Exception('Quantity must be a positive integer', 400);
            }

            $product = CatalogService::getProduct($productId);
            if (!$product) {
                throw new Exception('Product not found in catalog', 404);
            }
            if (!CatalogService::isInStock($productId)) {
                throw new Exception('Product is out of stock', 422);
            }

            $itemId = 'item-' . $this->nextItemId++;
            $cartItem = new CartItem($itemId, $customerId, $product['id'], $product['name'], $product['price'], $quantity);
            $this->carts[$customerId][$itemId] = $cartItem;

            return ['status' => 201, 'body' => ['success' => true, 'data' => $cartItem->toArray()]];
        } catch (Exception $e) {
            return $this->errorResponse($e);
        }
    }

    /** PUT /cart/items/{id} — Body: { quantity } */
    public function updateItem(string $itemId, array $requestData): array
    {
        try {
            $customerId = $this->requireAuth($requestData['headers'] ?? []);
            $quantity = $requestData['body']['quantity'] ?? null;

            if ($quantity === null || !is_int($quantity) || $quantity < 1) {
                throw new Exception('Quantity must be a positive integer', 400);
            }

            $items = $this->carts[$customerId] ?? [];
            if (!isset($items[$itemId])) {
                throw new Exception('Cart item not found', 404);
            }

            $item = $items[$itemId];
            if ($item->getCustomerId() !== $customerId) {
                throw new Exception('Forbidden', 403);
            }

            $item->setQuantity($quantity);
            return ['status' => 200, 'body' => ['success' => true, 'data' => $item->toArray()]];
        } catch (Exception $e) {
            return $this->errorResponse($e);
        }
    }

    /** DELETE /cart/items/{id} */
    public function removeItem(string $itemId, array $requestData): array
    {
        try {
            $customerId = $this->requireAuth($requestData['headers'] ?? []);
            $items = $this->carts[$customerId] ?? [];

            if (!isset($items[$itemId])) {
                throw new Exception('Cart item not found', 404);
            }
            if ($items[$itemId]->getCustomerId() !== $customerId) {
                throw new Exception('Forbidden', 403);
            }

            unset($this->carts[$customerId][$itemId]);
            return ['status' => 200, 'body' => ['success' => true, 'message' => 'Item removed from cart']];
        } catch (Exception $e) {
            return $this->errorResponse($e);
        }
    }

    /** DELETE /cart */
    public function clearCart(array $requestData): array
    {
        try {
            $customerId = $this->requireAuth($requestData['headers'] ?? []);
            $this->carts[$customerId] = [];
            return ['status' => 200, 'body' => ['success' => true, 'message' => 'Cart cleared']];
        } catch (Exception $e) {
            return $this->errorResponse($e);
        }
    }

    /** GET /points */
    public function getPoints(array $requestData): array
    {
        try {
            $customerId = $this->requireAuth($requestData['headers'] ?? []);
            $account = $this->rewardAccounts[$customerId] ?? new RewardAccount($customerId);
            return ['status' => 200, 'body' => ['success' => true, 'data' => $account->toArray()]];
        } catch (Exception $e) {
            return $this->errorResponse($e);
        }
    }

    /** GET /points/history */
    public function getPointsHistory(array $requestData): array
    {
        try {
            $customerId = $this->requireAuth($requestData['headers'] ?? []);
            $account = $this->rewardAccounts[$customerId] ?? new RewardAccount($customerId);
            return ['status' => 200, 'body' => ['success' => true, 'data' => ['balance' => $account->getBalance(), 'transactions' => $account->getHistory()]]];
        } catch (Exception $e) {
            return $this->errorResponse($e);
        }
    }

    /** POST /cart/apply-points — Body: { points } (number of points to redeem as discount) */
    public function applyPoints(array $requestData): array
    {
        try {
            $customerId = $this->requireAuth($requestData['headers'] ?? []);
            $points = $requestData['body']['points'] ?? null;

            if ($points === null || !is_int($points) || $points < 1) {
                throw new Exception('Points must be a positive integer', 400);
            }

            $items = $this->carts[$customerId] ?? [];
            if (empty($items)) {
                throw new Exception('Cart is empty', 422);
            }

            $cartTotal = 0.0;
            foreach ($items as $item) {
                $cartTotal += $item->getSubtotal();
            }

            $discount = RewardAccount::pointsToDollars($points);
            if ($discount > $cartTotal) {
                throw new Exception(sprintf('Discount ($%.2f) exceeds cart total ($%.2f)', $discount, $cartTotal), 422);
            }

            $account = $this->rewardAccounts[$customerId] ?? new RewardAccount($customerId);
            if (!$account->redeem($points, sprintf('Applied %d points to cart', $points))) {
                throw new Exception('Insufficient points balance', 422);
            }
            $this->rewardAccounts[$customerId] = $account;

            return [
                'status' => 200,
                'body' => [
                    'success' => true,
                    'data' => [
                        'cart_total' => round($cartTotal, 2),
                        'discount' => $discount,
                        'final_total' => round($cartTotal - $discount, 2),
                        'points_used' => $points,
                        'points_balance' => $account->getBalance(),
                    ]
                ]
            ];
        } catch (Exception $e) {
            return $this->errorResponse($e);
        }
    }

    /** POST /admin/points/grant — Body: { customer_id, points } (number of points to redeem as discount) */
    public function forceGrantPoints(array $requestData): array
    {
        try {
            $requesterId = $this->requireAuth($requestData['headers'] ?? []);

            $targetCustomerId = $requestData['body']['customer_id'] ?? null;
            $points = $requestData['body']['points'] ?? 0;
            $account = $this->rewardAccounts[$targetCustomerId];

            $account->earn($points, 'Admin (' . $requesterId . ') override grant');

            return [
                'status' => 200,
                'body' => [
                    'success' => true,
                    'message' => "Points granted to customer {$targetCustomerId}: {$points} points."
                ]
            ];
        } catch (Exception $e) {
            return $this->errorResponse($e);
        }
    }

    /** POST /admin/points/earn — Body: { customer_id, amount } (number of points to redeem as discount) */
    public function earnPoints(array $requestData): array
    {
        try {
            $admin = $this->requireAdmin($requestData['headers'] ?? []);

            $targetCustomerId = $requestData['body']['customer_id'] ?? null;

            $amount = $requestData['body']['amount'] ?? null;

            if ($amount === null || !is_numeric($amount) || $amount <= 0) {
                throw new Exception('Amount must be a positive number', 400);
            }

            $amount = (float) $amount;
            $points = RewardAccount::calculateEarnPoints($amount);
            $account = $this->rewardAccounts[$targetCustomerId];
            $account->earn($points, 'Admin (' . $admin['name'] . ') override earn');

            return [
                'status' => 200,
                'body' => [
                    'success' => true,
                    'message' => "Points earned to customer {$targetCustomerId}: {$points} points."
                ]
            ];
        } catch (Exception $e) {
            return $this->errorResponse($e);
        }
    }

    private function errorResponse(Exception $e): array
    {
        return ['status' => $e->getCode() ?: 500, 'body' => ['success' => false, 'message' => $e->getMessage()]];
    }
}