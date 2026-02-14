# Store Owner App API - Implementation Summary

## What Was Added

Two new authenticated API endpoints for the Store Owner Flutter app to view payment history and order details with picking information.

## Endpoints Overview

### 1. Payment Statement (`GET /api/v1/store/payment-statement`)

**Purpose:** Provides a passbook/statement view of all store orders with payment breakdown

**Authentication:** Requires `store.auth` middleware (Bearer token)

**Key Features:**
- ✅ FIFO ordering (oldest orders first)
- ✅ Summary statistics (total amounts, order counts by payment status)
- ✅ Per-order payment details from both sources:
  - `payment_allocations` + `payment_ledgers` (FIFO trail)
  - `order_payments` (legacy fallback)
- ✅ Excludes cancelled/failed/returned orders
- ✅ Shows payment method, date, and transaction reference

**Response Structure:**
```
{
  success: true,
  data: {
    summary: {
      total_order_amount,
      total_paid_amount,
      total_due_amount,
      total_orders,
      paid_orders,
      partial_orders,
      unpaid_orders
    },
    orders: [
      {
        order_id,
        order_date,
        order_amount,
        paid_amount,
        due_amount,
        payment_status,
        order_status,
        payments: [
          {amount, payment_method, date, transaction_ref}
        ]
      }
    ]
  }
}
```

### 2. Order Detail (`GET /api/v1/store/orders/{order_id}`)

**Purpose:** Shows detailed order information including picking changes (what was ordered vs what was actually picked)

**Authentication:** Requires `store.auth` middleware (Bearer token)

**Key Features:**
- ✅ Store ownership validation (404 if order doesn't belong to authenticated store)
- ✅ Item-level picking details:
  - Ordered quantity vs picked quantity
  - Missing quantities with reasons (out_of_stock, damaged, expired, not_found)
  - Picking status (pending, picked, partial, missing)
- ✅ Amount calculations:
  - Original amount (sum of ordered quantities × price)
  - Final amount (current order amount after picking adjustments)
  - Picking adjustment (difference)
- ✅ Payment information (same sources as payment statement)
- ✅ Product details (name, image)

**Response Structure:**
```
{
  success: true,
  data: {
    order_id,
    order_date,
    order_status,
    payment_status,
    original_amount,
    final_amount,
    picking_adjustment,
    has_picking_changes,
    items: [
      {
        product_id,
        product_name,
        product_image,
        ordered_qty,
        picked_qty,
        missing_qty,
        missing_reason,
        picking_status,
        unit_price,
        original_total,
        final_total,
        adjustment
      }
    ],
    payments: [
      {amount, payment_method, date, transaction_ref}
    ]
  }
}
```

## Files Modified

1. **app/Http/Controllers/Api/StoreAuthController.php**
   - Added imports for required models
   - Added `paymentStatement()` method
   - Added `orderDetail()` method

2. **routes/api.php**
   - Registered `GET /api/v1/store/payment-statement` route
   - Registered `GET /api/v1/store/orders/{order_id}` route

3. **TESTING_STORE_OWNER_API.md** (new file)
   - Comprehensive testing guide
   - cURL examples for manual testing
   - Database setup instructions
   - Expected response examples

## Design Decisions

### Authentication Pattern
- Both endpoints follow the same pattern as existing `me()` and `getArrear()` methods
- Check `$request->attributes->get('auth_store')` first (set by middleware)
- Fallback to manual token verification from headers
- Supports both `Authorization: Bearer {token}` and `X-Store-Token: {token}` headers

### Payment Data Sources
The implementation handles payment data from two sources to ensure backward compatibility:

1. **Primary:** `payment_allocations` + `payment_ledgers`
   - Represents the FIFO allocation system
   - Provides detailed transaction trail
   - Used when available

2. **Fallback:** `order_payments`
   - Legacy payment records
   - Used only if no allocations exist
   - Ensures older data is still accessible

### Picking Data Handling
- If `order_picking_items` exist: use actual picking data
- If no picking data: assume all items were picked (pending status)
- This handles both picked and unpicked orders gracefully

### Order Filtering
Payment statement excludes orders with status:
- `cancelled`
- `failed`
- `returned`

This ensures the statement only shows relevant, active orders.

## Testing

See `TESTING_STORE_OWNER_API.md` for:
- Manual test cases with cURL commands
- Expected responses for different scenarios
- Database setup SQL queries
- Postman collection setup guide

## Security Considerations

- ✅ All endpoints protected by `store.auth` middleware
- ✅ Order ownership validation (stores can only see their own orders)
- ✅ No SQL injection vulnerabilities (using Eloquent ORM)
- ✅ Token validation in place
- ✅ Account status checks (approval_status, can_login)

## Compatibility

- Compatible with existing payment systems (both FIFO and legacy)
- Compatible with existing picking workflow
- Handles missing data gracefully (no picking records, no payment records)
- Works with existing authentication middleware

## Next Steps for Flutter App

The Flutter Store Owner App should:
1. Implement login flow to obtain auth token
2. Call `/payment-statement` to display order history with payments
3. Call `/orders/{id}` when user taps on an order to see details
4. Display picking changes clearly (e.g., red highlight for missing items)
5. Show running balance/arrear from payment statement summary
6. Handle 401 responses by redirecting to login
7. Handle 404 on order detail (show error message)

## API Usage Example

```bash
# Login to get token
curl -X POST http://localhost:8000/api/v1/store/login \
  -H "Content-Type: application/json" \
  -d '{"phone_number": "+911234567890", "password": "mypassword", "skip_otp": true}'

# Get payment statement
curl -X GET http://localhost:8000/api/v1/store/payment-statement \
  -H "Authorization: Bearer {token_from_login}"

# Get order detail
curl -X GET http://localhost:8000/api/v1/store/orders/1234 \
  -H "Authorization: Bearer {token_from_login}"
```
