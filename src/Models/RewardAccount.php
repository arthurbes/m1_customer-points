<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Manages a customer's reward points balance and transaction history.
 * Earn rate: 10 points per $1.00 spent. Redeem rate: 1 point = $0.01 discount.
 */
class RewardAccount
{
    private string $customerId;
    private int $balance;
    private array $transactions = [];

    public function __construct(string $customerId, int $initialBalance = 0)
    {
        $this->customerId = $customerId;
        $this->balance = $initialBalance;
    }

    public function getCustomerId(): string
    {
        return $this->customerId;
    }
    public function getBalance(): int
    {
        return $this->balance;
    }

    /** Credit points to the account. Points must be positive. */
    public function earn(int $points, string $description): void
    {
        if ($points <= 0) {
            return;
        }
        $this->balance += $points;
        $this->transactions[] = [
            'type' => 'earn',
            'points' => $points,
            'description' => $description,
            'balance' => $this->balance,
            'timestamp' => date('c'),
        ];
    }

    /** Debit points. Returns false if insufficient balance. */
    public function redeem(int $points, string $description): bool
    {
        if ($points <= 0 || $points > $this->balance) {
            return false;
        }
        $this->balance -= $points;
        $this->transactions[] = [
            'type' => 'redeem',
            'points' => $points,
            'description' => $description,
            'balance' => $this->balance,
            'timestamp' => date('c'),
        ];
        return true;
    }

    public function getHistory(): array
    {
        return $this->transactions;
    }

    /** 10 points per $1.00 */
    public static function calculateEarnPoints(float $amount): int
    {
        return (int) floor($amount * 10);
    }

    /** 1 point = $0.01 */
    public static function pointsToDollars(int $points): float
    {
        return round($points / 100, 2);
    }

    public function toArray(): array
    {
        return ['customer_id' => $this->customerId, 'balance' => $this->balance];
    }
}