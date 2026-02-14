# Delivery Status Feature - Implementation Documentation

## Overview
This document provides details about the new "Delivery Status" feature added to the admin panel. This feature allows administrators to track delivered orders with UPI payment information and manage collection status.

## Feature Summary
- **New Admin Menu**: "Delivery Status" tab under Order Management section
- **Route**: `/admin/delivery-status`
- **Purpose**: Track delivered orders, view UPI payment details, and mark orders as collected

## Database Changes

### Migration: `2026_02_14_220000_add_is_collected_to_orders_table.php`

Added new column to `orders` table:
- **Column**: `is_collected`
- **Type**: `boolean`
- **Default**: `false`
- **Purpose**: Tracks whether the order payment has been collected/reconciled

To run the migration:
```bash
php artisan migrate
```

## Backend Implementation

### Routes (`routes/admin.php`)

Two new routes added within the order management middleware group:

```php
Route::group(['prefix' => 'delivery-status', 'as' => 'delivery-status.', 'middleware' => ['module:order_management']], function () {
    Route::get('/', [OrderController::class, 'deliveryStatus'])->name('index');
    Route::post('mark-collected/{order_id}', [OrderController::class, 'markAsCollected'])->name('mark-collected');
});
```

### Controller Methods (`app/Http/Controllers/Admin/OrderController.php`)

#### 1. `deliveryStatus()` Method
- **Route**: `GET /admin/delivery-status`
- **Purpose**: Display delivered orders with UPI payment information
- **Features**:
  - Filters orders with status = 'delivered'
  - Loads relationships: customer, delivery_man, time_slot, branch, store
  - Fetches UPI payment data from `payment_ledgers` table
  - Supports search by order ID
  - Supports branch filtering
  - Pagination: 15 orders per page

#### 2. `markAsCollected()` Method
- **Route**: `POST /admin/delivery-status/mark-collected/{order_id}`
- **Purpose**: Mark an order as collected
- **Features**:
  - Validates order exists
  - Checks if already collected (prevents duplicate marking)
  - Updates `is_collected` flag to `true`
  - Returns JSON response for AJAX handling

### Model Updates (`app/Model/Order.php`)

Added `is_collected` to:
- **$fillable** array: Allows mass assignment
- **$casts** array: Casts to boolean type

## Frontend Implementation

### View File: `resources/views/admin-views/order/delivery-status.blade.php`

#### Table Columns:
1. **#** - Row number (with pagination)
2. **Order ID** - Linked to order details page
3. **Delivery Date** - Formatted date (d M Y)
4. **Deliveryman** - Name + Phone (or "Not Assigned")
5. **Time Slot** - Start time - End time (or "No Time Slot")
6. **Customer** - Name + Phone (supports stores and regular customers)
7. **Branch** - Store/branch name
8. **Total Amount** - Order total with currency symbol
9. **Paid Amount** - Amount paid from `paid_amount` field
10. **UPI Payment** - Shows: `₹ Amount | Transaction ID`
    - Displays UPI payment from `payment_ledgers` table
    - Format: `₹ 1000.00 | TXN-ABC123`
    - Shows "-" if no UPI payment found
11. **Order Status** - Status badge (always "delivered")
12. **Order Type** - Order type badge
13. **Action** - "Mark as Collected" button

#### Features:
- **Search**: Search orders by Order ID
- **Branch Filter**: Filter by branch using dropdown
- **Pagination**: 15 orders per page with Laravel pagination links
- **Responsive Design**: Uses existing admin theme styles

### JavaScript Implementation

**AJAX Handler for "Mark as Collected" Button:**

```javascript
$('.mark-collected-btn').on('click', function() {
    let button = $(this);
    let orderId = button.data('order-id');
    
    // Disable button immediately
    button.prop('disabled', true);
    
    $.ajax({
        url: '/admin/delivery-status/mark-collected/' + orderId,
        type: 'POST',
        data: { _token: '{{ csrf_token() }}' },
        success: function(response) {
            if (response.success) {
                // Update button to gray/disabled state
                button.removeClass('btn-primary')
                      .addClass('btn-collected')
                      .html('✓ Collected');
                
                // Show success toast
                toastr.success(response.message);
            }
        },
        error: function(xhr) {
            // Re-enable button on error
            button.prop('disabled', false);
            toastr.error('An error occurred');
        }
    });
});
```

**Button States:**
- **Before Collection**: Blue button, enabled
- **After Collection**: Gray button, disabled, shows "✓ Collected"
- **Persistent State**: Button state persists after page reload (based on `is_collected` in database)

## Admin Sidebar Navigation

### Location
Added under Order Management section, after "Delivery Details"

### Menu Item Details:
- **Label**: "📦 Delivery Status"
- **Icon**: `tio-gift` + 📦 emoji
- **Route**: `{{ route('admin.delivery-status.index') }}`
- **Active State**: Highlights when on `/admin/delivery-status*` routes
- **Permission**: Protected by `module:order_management` middleware

### Code:
```blade
<li class="navbar-vertical-aside-has-menu {{ Request::is('admin/delivery-status*') ? 'active' : '' }}">
    <a class="js-navbar-vertical-aside-menu-link nav-link"
        href="{{ route('admin.delivery-status.index') }}" 
        title="{{ translate('Delivery Status') }}">
        <i class="tio-gift nav-icon"></i>
        <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
            📦 {{ translate('Delivery Status') }}
        </span>
    </a>
</li>
```

## UPI Payment Data

### Data Source: `payment_ledgers` Table

The UPI payment information is fetched using the following logic:

```php
$upiPayment = PaymentLedger::where('store_id', $order->store_id)
    ->where('order_id', $order->id)
    ->where('payment_method', 'upi')
    ->where('entry_type', 'CREDIT')
    ->first();
```

### Display Format:
- **With UPI Payment**: `₹ 1000.00 | TXN-ABC123`
- **Without UPI Payment**: `-`

## Usage Workflow

1. **Admin accesses Delivery Status page**
   - Navigate to sidebar: "📦 Delivery Status"
   - Page loads with all delivered orders

2. **View Order Information**
   - See delivery date, deliveryman, customer, branch
   - Check total amount and paid amount
   - View UPI payment details (if any)

3. **Filter Orders** (Optional)
   - Use search box to find specific order ID
   - Select branch from dropdown to filter by branch

4. **Mark Order as Collected**
   - Click "Mark as Collected" button
   - Button immediately disables to prevent double-clicks
   - AJAX request updates database
   - Success message appears via toastr
   - Button changes to gray "Collected" state permanently

5. **Collection Status Persists**
   - Once marked as collected, button stays disabled
   - State persists after page reload
   - Cannot be unmarked (intentional design)

## Important Notes

### Design Decisions:
1. **Separate from Orders List**: This is a NEW feature, not a modification of existing order lists
2. **Read-Only Orders List**: Does NOT modify `/admin/orders/list/delivered`
3. **One-Way Collection**: Orders cannot be unmarked as collected (prevents accidental changes)
4. **UPI-Specific**: Shows UPI payments specifically, not all payment methods

### Security:
- CSRF token protection on AJAX requests
- Order management permission required
- Validates order exists before updating
- Checks for duplicate collection attempts

### Performance Considerations:
- Pagination (15 orders/page) prevents slow page loads
- Eager loading of relationships (customer, delivery_man, etc.)
- **Optimized UPI payment loading**: Batch-loads all UPI payments for current page in single query (N+1 issue resolved)
- Handles orders with or without store_id efficiently

## Testing Checklist

- [x] Migration syntax validated
- [x] Routes defined correctly
- [x] Controller methods implemented
- [x] View file created with all columns
- [x] Sidebar menu item added
- [ ] Database migration executed
- [ ] Page loads with delivered orders
- [ ] Search functionality works
- [ ] Branch filter works
- [ ] UPI payment displays correctly
- [ ] Mark as collected button works
- [ ] Button state persists after reload
- [ ] Toastr notifications appear
- [ ] Permission middleware protects routes

## Future Enhancements (Not in Current Scope)

1. **Bulk Collection**: Mark multiple orders as collected at once
2. **Collection Date**: Track when order was marked as collected
3. **Collection By**: Track which admin user marked it as collected
4. **Export Feature**: Export collected/uncollected orders to Excel
5. **UPI Payment Join**: Optimize query to join payment_ledgers in main query
6. **Collection Notes**: Add optional notes when marking as collected
7. **Unmark Feature**: Allow admins to unmark if needed (with audit trail)

## Files Modified/Created

### Created:
1. `database/migrations/2026_02_14_220000_add_is_collected_to_orders_table.php`
2. `resources/views/admin-views/order/delivery-status.blade.php`

### Modified:
1. `app/Model/Order.php` - Added `is_collected` to fillable and casts
2. `app/Http/Controllers/Admin/OrderController.php` - Added two new methods
3. `routes/admin.php` - Added delivery-status route group
4. `resources/views/layouts/admin/partials/_sidebar.blade.php` - Added menu item

## Conclusion

This feature provides a streamlined interface for tracking delivered orders and managing their collection status, with special emphasis on UPI payment tracking. The implementation follows existing patterns in the codebase and maintains separation from the existing order management functionality.
