# Store Owner App API Endpoints - Testing Guide

## Overview
This guide provides instructions for manually testing the two new API endpoints added for the Store Owner App.

## Prerequisites
1. A store account must exist in the database with:
   - `approval_status` = 'approved'
   - `can_login` = true (or 1)
   - `auth_token` set (obtained from login)
   - `sales_person_id` assigned

2. Test data:
   - Orders belonging to the store
   - Order details with products
   - Optional: OrderPickingItems for testing picking changes
   - Optional: PaymentAllocations and PaymentLedgers for testing payment history

## Endpoints

### 1. Payment Statement (GET /api/v1/store/payment-statement)

**Description:** Returns a passbook/statement style view of all orders with FIFO payment breakdown.

**Headers:**
```
Authorization: Bearer {auth_token}
```
or
```
X-Store-Token: {auth_token}
```

**Expected Response:**
```json
{
    "success": true,
    "data": {
        "summary": {
            "total_order_amount": 45000.00,
            "total_paid_amount": 38000.00,
            "total_due_amount": 7000.00,
            "total_orders": 15,
            "paid_orders": 10,
            "partial_orders": 2,
            "unpaid_orders": 3
        },
        "orders": [
            {
                "order_id": 1001,
                "order_date": "2026-01-10",
                "order_amount": 5000.00,
                "paid_amount": 5000.00,
                "due_amount": 0.00,
                "payment_status": "paid",
                "order_status": "delivered",
                "payments": [
                    {
                        "amount": 3000.00,
                        "payment_method": "cash",
                        "date": "2026-01-12",
                        "transaction_ref": null
                    },
                    {
                        "amount": 2000.00,
                        "payment_method": "upi",
                        "date": "2026-01-15",
                        "transaction_ref": "TXN123456"
                    }
                ]
            }
        ]
    }
}
```

**Test Cases:**

1. **Test without authentication:**
   ```bash
   curl -X GET http://localhost:8000/api/v1/store/payment-statement
   ```
   Expected: 401 Unauthorized

2. **Test with valid token:**
   ```bash
   curl -X GET http://localhost:8000/api/v1/store/payment-statement \
     -H "Authorization: Bearer {valid_token}"
   ```
   Expected: 200 OK with payment statement data

3. **Test X-Store-Token header:**
   ```bash
   curl -X GET http://localhost:8000/api/v1/store/payment-statement \
     -H "X-Store-Token: {valid_token}"
   ```
   Expected: 200 OK with payment statement data

4. **Verify order sorting:**
   - Orders should be sorted by `created_at` ASC (oldest first)
   - Check that the first order in the response is the oldest order

5. **Verify excluded orders:**
   - Cancelled orders should not appear
   - Failed orders should not appear
   - Returned orders should not appear

### 2. Order Detail (GET /api/v1/store/orders/{order_id})

**Description:** Returns detailed order information including picking changes for a specific order.

**Headers:**
```
Authorization: Bearer {auth_token}
```
or
```
X-Store-Token: {auth_token}
```

**Expected Response:**
```json
{
    "success": true,
    "data": {
        "order_id": 1002,
        "order_date": "2026-01-12",
        "order_status": "delivered",
        "payment_status": "partial",
        "original_amount": 8480.00,
        "final_amount": 8000.00,
        "picking_adjustment": -480.00,
        "has_picking_changes": true,
        "items": [
            {
                "product_id": 101,
                "product_name": "Sunrise Rice 5kg",
                "product_image": "product/image.png",
                "ordered_qty": 5,
                "picked_qty": 5,
                "missing_qty": 0,
                "missing_reason": null,
                "picking_status": "picked",
                "unit_price": 500.00,
                "original_total": 2500.00,
                "final_total": 2500.00,
                "adjustment": 0.00
            },
            {
                "product_id": 102,
                "product_name": "Coconut Oil 1L",
                "product_image": "product/oil.png",
                "ordered_qty": 5,
                "picked_qty": 3,
                "missing_qty": 2,
                "missing_reason": "out_of_stock",
                "picking_status": "partial",
                "unit_price": 150.00,
                "original_total": 750.00,
                "final_total": 450.00,
                "adjustment": -300.00
            }
        ],
        "payments": [
            {
                "amount": 6000.00,
                "payment_method": "upi",
                "date": "2026-01-15",
                "transaction_ref": "TXN789012"
            }
        ]
    }
}
```

**Test Cases:**

1. **Test without authentication:**
   ```bash
   curl -X GET http://localhost:8000/api/v1/store/orders/1
   ```
   Expected: 401 Unauthorized

2. **Test with valid order:**
   ```bash
   curl -X GET http://localhost:8000/api/v1/store/orders/{order_id} \
     -H "Authorization: Bearer {valid_token}"
   ```
   Expected: 200 OK with order detail

3. **Test with non-existent order:**
   ```bash
   curl -X GET http://localhost:8000/api/v1/store/orders/999999 \
     -H "Authorization: Bearer {valid_token}"
   ```
   Expected: 404 Not Found

4. **Test with order from another store:**
   - Get a valid order_id that belongs to a different store
   ```bash
   curl -X GET http://localhost:8000/api/v1/store/orders/{other_store_order_id} \
     -H "Authorization: Bearer {valid_token}"
   ```
   Expected: 404 Not Found with message "Order not found or does not belong to this store."

5. **Test order without picking data:**
   - Order should show all items as picked with `picking_status: "pending"`
   - `has_picking_changes` should be false
   - `picked_qty` should equal `ordered_qty`

6. **Test order with picking changes:**
   - Items with `missing_qty > 0` should have appropriate `missing_reason`
   - `has_picking_changes` should be true
   - `picking_adjustment` should reflect the difference between original and final amounts

## Database Setup for Testing

### Create a test store:
```sql
INSERT INTO stores (store_name, customer_name, phone_number, address, approval_status, can_login, sales_person_id, auth_token, created_at, updated_at)
VALUES ('Test Store', 'Test Owner', '+911234567890', 'Test Address', 'approved', 1, 1, 'test-token-12345', NOW(), NOW());
```

### Create test orders:
```sql
-- Get the store_id from above insertion, then:
INSERT INTO orders (store_id, order_amount, paid_amount, payment_status, order_status, created_at, updated_at)
VALUES (1, 5000.00, 5000.00, 'paid', 'delivered', '2026-01-10', NOW());
```

### Create order details:
```sql
-- Assuming order_id = 1, product_id exists
INSERT INTO order_details (order_id, product_id, quantity, price, created_at, updated_at)
VALUES (1, 1, 2, 500.00, NOW(), NOW());
```

### Create picking items (optional):
```sql
-- Assuming order_detail_id = 1
INSERT INTO order_picking_items (order_id, order_detail_id, product_id, ordered_qty, picked_qty, missing_qty, missing_reason, status, original_price, created_at, updated_at)
VALUES (1, 1, 1, 2, 1, 1, 'out_of_stock', 'partial', 500.00, NOW(), NOW());
```

### Create payment allocations (optional):
```sql
-- First create payment ledger
INSERT INTO payment_ledgers (store_id, entry_type, amount, payment_method, transaction_ref, created_at, updated_at)
VALUES (1, 'CREDIT', 5000.00, 'cash', NULL, NOW(), NOW());

-- Then create allocation
INSERT INTO payment_allocations (payment_ledger_id, order_id, allocated_amount, created_at, updated_at)
VALUES (1, 1, 5000.00, NOW(), NOW());
```

## Using Postman

1. Create a new collection called "Store Owner App"
2. Add environment variables:
   - `base_url`: http://localhost:8000
   - `store_token`: {your_test_token}

3. Create requests:
   - **Payment Statement**: GET {{base_url}}/api/v1/store/payment-statement
   - **Order Detail**: GET {{base_url}}/api/v1/store/orders/:order_id

4. Add authorization header to both:
   - Type: Bearer Token
   - Token: {{store_token}}

## Notes

- All responses include a `success` boolean field
- Dates are formatted as `Y-m-d` (e.g., "2026-01-15")
- Amounts are rounded to 2 decimal places
- Payment information is sourced from:
  1. `payment_allocations` joined with `payment_ledgers` (preferred - FIFO trail)
  2. `order_payments` with `payment_status = 'complete'` (fallback)
- The middleware validates that:
  - Token is present
  - Token is valid
  - Store account is active (`can_login = true` and `approval_status = 'approved'`)
