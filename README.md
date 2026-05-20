# Customer Points Cart API

Shopping cart microservice with reward points management, built on the **Marko Framework** (PHP 8.5). Designed as an evaluation substrate for **PEST** test-suite authoring.

**⚠️ IMPORTANT: This repository contains intentionally vulnerable code. It was developed as a hands-on lab for security challenges and DOES NOT reflect the actual coding skills of the author or proper production practices. Do not use this in production environments! ⚠️**

## Architecture

```
src/
├── Routes.php                    Route definitions (11 endpoints)
├── Controllers/
│   └── CartController.php        Cart CRUD + reward points logic
├── Models/
│   ├── CartItem.php              Cart item with subtotal calculation
│   └── RewardAccount.php         Points balance, earn/redeem, history
└── Services/
    ├── AuthService.php           Mock external customer/admin identity provider
    └── CatalogService.php        Mock external product catalog
```

All state is in-memory (no database). The controller constructor seeds initial carts, reward accounts, and the services provide static mock data for customers and products.

## Authentication

Every endpoint requires an `X-Customer-Token` header. Token format: `token-{customerId}`.

| Customer ID | Name          | Tier   | Token               |
|-------------|---------------|--------|---------------------|
| `cust-001`  | Maria Silva   | gold   | `token-cust-001`    |
| `cust-002`  | João Santos   | silver | `token-cust-002`    |
| `cust-003`  | Ana Oliveira  | bronze | `token-cust-003`    |

For admin endpoints, the token format is `token-{adminId}`:

| Admin ID | Admin Name | Token |
|--------|------------|-------|
| `admin-001`  | Admin 1 | `token-admin-001`    |

## API Endpoints

### Cart

| Method   | Path                | Description                    | Body                              |
|----------|---------------------|--------------------------------|-----------------------------------|
| `GET`    | `/cart`             | List items in customer's cart  | —                                 |
| `POST`   | `/cart/items`       | Add product to cart            | `{ product_id, quantity }`        |
| `PUT`    | `/cart/items/{id}`  | Update item quantity           | `{ quantity }`                    |
| `DELETE` | `/cart/items/{id}`  | Remove item from cart          | —                                 |
| `DELETE` | `/cart`             | Clear entire cart              | —                                 |

### Reward Points

| Method | Path                | Description                          | Body           |
|--------|---------------------|--------------------------------------|----------------|
| `GET`  | `/points`           | Get points balance                   | —              |
| `GET`  | `/points/history`   | Get transaction history              | —              |
| `POST` | `/cart/apply-points` | Apply points as discount on cart    | `{ points }`   |

## Admin

| Method | Path                | Description                          | Body           |
|--------|---------------------|--------------------------------------|----------------|
| `POST` | `/admin/points/grant`  | Grants a number of points to a customer     | `{ "customer_id": "cust-001", "points": 1000 }` |
| `POST` | `/admin/points/earn`  | Grants points based on an amount to a customer     | `{ "customer_id": "cust-001", "amount": 1000 }` |

**Points rules:**
- Earn rate: **10 points per $1.00**
- Redeem rate: **1 point = $0.01 discount**
- Cannot redeem more points than current balance
- Discount cannot exceed cart total

## Mock Catalog

| Product ID | Name             | Price  | In Stock |
|------------|------------------|--------|----------|
| `prod-001` | Mouse Wireless   | $29.99 | Yes      |
| `prod-002` | Teclado Mecânico | $89.99 | Yes      |
| `prod-003` | Hub USB-C        | $45.00 | Yes      |
| `prod-004` | Suporte Monitor  | $35.00 | **No**   |
| `prod-005` | Webcam HD        | $59.99 | Yes      |

## Seed Data

**Carts:**
- `cust-001`: 1 item — `prod-001` (Mouse Wireless), qty 2 → `item-1001`
- `cust-002`: 1 item — `prod-002` (Teclado Mecânico), qty 1 → `item-2001`
- `cust-003`: empty cart

**Reward accounts:**
- `cust-001`: 5,000 points
- `cust-002`: 1,200 points
- `cust-003`: 0 points

## Response Format

All endpoints return the same envelope:

```json
{
  "status": 200,
  "body": {
    "success": true,
    "data": { }
  }
}
```

On error:

```json
{
  "status": 401,
  "body": {
    "success": false,
    "message": "Unauthorized"
  }
}
```

## How to Run and Test

Install dependencies (development/testing environment):

```bash
composer install
```

Run the validation test suite using Pest:

```bash
composer test
# or
./vendor/bin/pest
```

To use the application via Docker (which provides an isolated environment to run tests or explore via terminal):

```bash
# Build the image
docker build -t customer-points-api .

# Run the test suite inside the container
docker run --rm customer-points-api vendor/bin/pest

# Or enter the container interactively to use curl or explore
docker run -it customer-points-api
```

## Test Coverage

The included `tests/SmokeTest.php` covers **happy-path flows only** (8 tests):

1. View cart items
2. Add product to cart
3. Update item quantity
4. Remove item from cart
5. View points balance
6. Earn reward points
7. Auth required for cart
8. Auth required for points

### Areas intentionally left for the evaluee

The smoke tests do **not** cover:

- **Cross-customer isolation** — Can customer A access customer B's cart or points?
- **Out-of-stock products** — `prod-004` should be rejected when added to cart
- **Nonexistent products** — Adding a product ID that doesn't exist in the catalog
- **Invalid quantities** — Zero, negative, or non-integer values
- **Points edge cases** — Applying points to an empty cart, redeeming more than balance, discount exceeding cart total
- **Invalid/missing tokens** — Malformed tokens, missing header across all endpoints
- **Cart item ownership** — Updating or removing items that belong to another customer
- **Clear cart** — Verifying the cart is actually empty after clearing
- **Points history** — Verifying transaction records after earn/redeem operations