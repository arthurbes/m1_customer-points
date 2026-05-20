<?php

declare(strict_types=1);

/**
 * SmokeTest.php
 *
 * Validates the 'happy path' functionality of the Customer Points Cart API.
 * Ensures basic cart and points operations work for authenticated customers.
 */

it('can view cart items for an authenticated customer', function () {
    $client = createClient();
    $headers = ['X-Customer-Token' => 'token-cust-001'];

    $response = $client->request('GET', '/cart', $headers);

    expect($response['status'])->toBe(200)
        ->and($response['body']['success'])->toBeTrue()
        ->and($response['body']['data']['items'])->toBeArray()
        ->and($response['body']['data']['items'])->toHaveCount(1)
        ->and($response['body']['data']['total'])->toBe(59.98);
});

it('can add a product to the cart', function () {
    $client = createClient();
    $headers = ['X-Customer-Token' => 'token-cust-001'];
    $body = ['product_id' => 'prod-003', 'quantity' => 1];

    $response = $client->request('POST', '/cart/items', $headers, $body);

    expect($response['status'])->toBe(201)
        ->and($response['body']['success'])->toBeTrue()
        ->and($response['body']['data']['product_id'])->toBe('prod-003')
        ->and($response['body']['data']['product_name'])->toBe('Hub USB-C')
        ->and($response['body']['data']['quantity'])->toBe(1);
});

it('can update quantity of a cart item', function () {
    $client = createClient();
    $headers = ['X-Customer-Token' => 'token-cust-001'];
    $body = ['quantity' => 5];

    $response = $client->request('PUT', '/cart/items/item-1001', $headers, $body);

    expect($response['status'])->toBe(200)
        ->and($response['body']['success'])->toBeTrue()
        ->and($response['body']['data']['quantity'])->toBe(5)
        ->and($response['body']['data']['subtotal'])->toBe(149.95);
});

it('can remove an item from the cart', function () {
    $client = createClient();
    $headers = ['X-Customer-Token' => 'token-cust-002'];

    $response = $client->request('DELETE', '/cart/items/item-2001', $headers);

    expect($response['status'])->toBe(200)
        ->and($response['body']['success'])->toBeTrue()
        ->and($response['body']['message'])->toContain('removed');
});

it('can view reward points balance', function () {
    $client = createClient();
    $headers = ['X-Customer-Token' => 'token-cust-001'];

    $response = $client->request('GET', '/points', $headers);

    expect($response['status'])->toBe(200)
        ->and($response['body']['success'])->toBeTrue()
        ->and($response['body']['data']['balance'])->toBe(5000);
});

it('requires authentication to access cart', function () {
    $client = createClient();
    $response = $client->request('GET', '/cart');

    expect($response['status'])->toBe(401)
        ->and($response['body']['success'])->toBeFalse()
        ->and($response['body']['message'])->toContain('Unauthorized');
});

it('requires authentication to access points', function () {
    $client = createClient();
    $response = $client->request('GET', '/points');

    expect($response['status'])->toBe(401)
        ->and($response['body']['success'])->toBeFalse();
});

it('requires admin access to earn points to a customer', function () {
    $client = createClient();
    $response = $client->request('POST', '/admin/points/earn');

    expect($response['status'])->toBe(403)
        ->and($response['body']['success'])->toBeFalse()
        ->and($response['body']['message'])->toContain('Forbidden');
});

it('can earn reward points as admin', function () {
    $client = createClient();
    $headers = ['X-Admin-Token' => 'token-admin-001'];

    $body = [
        'customer_id' => 'cust-003',
        'amount' => 25.00
    ];

    $response = $client->request('POST', '/admin/points/earn', $headers, $body);

    expect($response['status'])->toBe(200)
        ->and($response['body']['success'])->toBeTrue()
        ->and($response['body']['message'])->toContain('250 points');
});