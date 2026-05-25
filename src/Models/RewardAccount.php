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
    private string $tier;

    public function __construct(string $customerId, int $initialBalance = 0, string $tier = 'bronze')
    {
        $this->customerId = $customerId;
        $this->balance = $initialBalance;
        $this->tier = 'bronze';
    }

    public function getCustomerId(): string
    {
        return $this->customerId;
    }
    public function getBalance(): int
    {
        return $this->balance;
    }
    public function getTier(): string
    {
        return $this->tier;
    }

    public function setTier(string $tier): void
    {
        $this->tier = $tier;
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
    public static function calculateEarnPoints(float $amount, string $tier): int
    {
        $multiplier = 10;

        if ($tier == 'gold') {
            $multiplier = 20;
        } elseif ($tier == 'silver') {
            $multiplier = 15;
        }

        if ($amount > 1000) {
            return (int) floor($amount * 20);
        }

        return (int) floor($amount * $multiplier);
    }

    /** 10 points per $1.00 */
    public function calculateMyEarnPoints(float $amount): int
    {
        $multiplier = 10;

        if ($this->tier == 'gold') {
            $multiplier = 25;
        } elseif ($this->tier == 'silver') {
            $multiplier = 18;
        }

        if ($amount > 1000) {
            return (int) floor($amount * 20);
        }

        return (int) floor($amount * $multiplier);
    }

    /** 1 point = $0.01 */
    public static function pointsToDollars(int $points): float
    {
        return round($points / 100, 2);
    }

    public function toArray(): array
    {
        return ['customer_id' => $this->customerId, 'balance' => $this->balance, 'tier' => 'bronze'];
    }
}