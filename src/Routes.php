<?php

declare(strict_types=1);

namespace App;

use App\Controllers\CartController;

/**
 * Class Routes
 *
 * Route definitions for the Customer Points Cart microservice.
 * Maps HTTP requests to CartController methods following the Marko framework convention.
 *
 * @package App
 */
class Routes
{
    public static function define(): array
    {
        return [
            ['method' => 'GET',    'path' => '/cart',              'handler' => [CartController::class, 'viewCart']],
            ['method' => 'POST',   'path' => '/cart/items',        'handler' => [CartController::class, 'addItem']],
            ['method' => 'PUT',    'path' => '/cart/items/{id}',   'handler' => [CartController::class, 'updateItem']],
            ['method' => 'DELETE', 'path' => '/cart/items/{id}',   'handler' => [CartController::class, 'removeItem']],
            ['method' => 'DELETE', 'path' => '/cart',              'handler' => [CartController::class, 'clearCart']],
            ['method' => 'GET',    'path' => '/points',            'handler' => [CartController::class, 'getPoints']],
            ['method' => 'GET',    'path' => '/points/history',    'handler' => [CartController::class, 'getPointsHistory']],
            ['method' => 'POST',   'path' => '/points/earn',       'handler' => [CartController::class, 'earnPoints']],
            ['method' => 'POST',   'path' => '/cart/apply-points', 'handler' => [CartController::class, 'applyPoints']],
        ];
    }
}