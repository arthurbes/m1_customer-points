<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Mock authentication service simulating an external customer identity provider.
 * Validates customer tokens (X-Customer-Token header) and returns profile data.
 */
class AuthService
{
    private static array $customers = [
        'cust-001' => ['name' => 'Maria Silva', 'email' => 'maria@example.com', 'tier' => 'gold'],
        'cust-002' => ['name' => 'João Santos', 'email' => 'joao@example.com', 'tier' => 'silver'],
        'cust-003' => ['name' => 'Ana Oliveira', 'email' => 'ana@example.com', 'tier' => 'bronze'],
    ];

    private static array $admins = [
        'adm-001' => ['name' => 'System Admin', 'role' => 'superuser'],
    ];

    /** Authenticate via X-Customer-Token header. Token format: "token-{customerId}". */
    public static function authenticate(array $headers): ?array
    {
        $token = $headers['X-Customer-Token'] ?? null;
        if (!$token || !str_starts_with($token, 'token-')) {
            return null;
        }
        return self::getCustomer(substr($token, 6));
    }

    /** Authenticates via X-Admin-Token header. Format: "token-admin-{adminId}" */
    public static function authenticateAdmin(array $headers): ?array
    {
        $token = $headers['X-Admin-Token'] ?? null;

        if (!$token || !str_starts_with($token, 'token-admin-')) {
            return null;
        }

        $adminId = "adm-" . substr($token, 12);

        if (!isset(self::$admins[$adminId])) {
            return null;
        }

        return array_merge(['id' => $adminId], self::$admins[$adminId]);
    }

    public static function getCustomer(string $customerId): ?array
    {
        if (!isset(self::$customers[$customerId])) {
            return null;
        }
        return array_merge(['id' => $customerId], self::$customers[$customerId]);
    }
}