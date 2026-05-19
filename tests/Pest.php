<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

// uses(Tests\TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/*
|--------------------------------------------------------------------------
| Test Infrastructure
|--------------------------------------------------------------------------
| The TestClient acts as a stateful browser session. It holds a single 
| instance of the CartController in memory so you can simulate multiple 
| sequential requests (e.g., earning points, then spending them) within 
| the same test state.
*/

class TestClient
{
    private \App\Controllers\CartController $controller;

    public function __construct()
    {
        $this->controller = new \App\Controllers\CartController();
    }

    /**
     * Simulates an HTTP request to the microservice.
     */
    public function request(string $method, string $path, array $headers = [], array $body = []): array
    {
        $requestData = [
            'headers' => $headers,
            'body' => $body,
        ];

        // GET /cart
        if ($method === 'GET' && $path === '/cart') {
            return $this->controller->viewCart($requestData);
        }

        // POST /cart/items
        if ($method === 'POST' && $path === '/cart/items') {
            return $this->controller->addItem($requestData);
        }

        // PUT /cart/items/{id}
        if ($method === 'PUT' && preg_match('#^/cart/items/([^/]+)$#', $path, $m)) {
            return $this->controller->updateItem($m[1], $requestData);
        }

        // DELETE /cart/items/{id}
        if ($method === 'DELETE' && preg_match('#^/cart/items/([^/]+)$#', $path, $m)) {
            return $this->controller->removeItem($m[1], $requestData);
        }

        // DELETE /cart
        if ($method === 'DELETE' && $path === '/cart') {
            return $this->controller->clearCart($requestData);
        }

        // GET /points
        if ($method === 'GET' && $path === '/points') {
            return $this->controller->getPoints($requestData);
        }

        // GET /points/history
        if ($method === 'GET' && $path === '/points/history') {
            return $this->controller->getPointsHistory($requestData);
        }

        // POST /points/earn
        if ($method === 'POST' && $path === '/points/earn') {
            return $this->controller->earnPoints($requestData);
        }

        // POST /cart/apply-points
        if ($method === 'POST' && $path === '/cart/apply-points') {
            return $this->controller->applyPoints($requestData);
        }

        return [
            'status' => 404,
            'body' => [
                'success' => false,
                'message' => 'Not Found',
            ]
        ];
    }
}

/**
 * Creates a fresh, stateful HTTP client session for testing.
 */
function createClient(): TestClient
{
    return new TestClient();
}