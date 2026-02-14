# Delivery Status Feature - Setup & Testing Guide

## Quick Setup

### Step 1: Run Database Migration
```bash
cd /home/runner/work/LaravelSFM/LaravelSFM
php artisan migrate
```

This will add the `is_collected` column to your `orders` table.

### Step 2: Clear Cache (Optional but Recommended)
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

### Step 3: Access the Feature

1. Log in to the admin panel
2. Look for "📦 Delivery Status" in the sidebar (under Order Management section)
3. Click on it to access `/admin/delivery-status`

## Testing the Feature

### Test 1: View Delivered Orders

**Expected Behavior:**
- Page should load with a table showing only orders with `order_status = 'delivered'`
- All 13 columns should be visible
- Pagination should show 15 orders per page

**Test Steps:**
1. Navigate to `/admin/delivery-status`
2. Verify orders are displayed
3. Check that all columns have data (or proper fallbacks like "Not Assigned", "-", etc.)

### Test 2: UPI Payment Display

**Expected Behavior:**
- UPI Payment column should show: `₹ 1000.00 | TXN-ABC123` format
- If no UPI payment exists, should show: `-`

**Test Steps:**
1. Find an order that has a UPI payment in `payment_ledgers` table
2. Verify the amount and transaction reference are displayed correctly
3. Find an order without UPI payment
4. Verify it shows `-` in the UPI Payment column

**Sample SQL to Insert Test UPI Payment:**
```sql
INSERT INTO payment_ledgers (store_id, order_id, entry_type, amount, payment_method, transaction_ref, created_at, updated_at)
VALUES (1, 123, 'CREDIT', 1000.00, 'upi', 'TXN-ABC123', NOW(), NOW());
```

### Test 3: Search Functionality

**Expected Behavior:**
- Search box should filter orders by Order ID
- Pagination should update accordingly

**Test Steps:**
1. Enter an order ID in search box
2. Click "Search"
3. Verify only matching orders are shown
4. Clear search and verify all orders return

### Test 4: Branch Filter

**Expected Behavior:**
- Dropdown should list all branches
- Selecting a branch should filter orders to that branch only
- "All Branches" should show all orders

**Test Steps:**
1. Select a specific branch from dropdown
2. Verify only orders from that branch are displayed
3. Select "All Branches"
4. Verify all orders are shown again

### Test 5: Mark as Collected Button

**Expected Behavior:**
- Blue "Mark as Collected" button should be visible for uncollected orders
- Clicking button should:
  - Disable immediately
  - Send AJAX request to server
  - Show success toastr message
  - Change to gray "Collected" button permanently
- Collected orders should show gray disabled "Collected" button
- Button state should persist after page reload

**Test Steps:**
1. Find an order with blue "Mark as Collected" button
2. Click the button
3. Verify it immediately disables (prevents double-click)
4. Verify success toastr message appears
5. Verify button changes to gray "Collected" state
6. Refresh the page
7. Verify button is still gray and disabled
8. Try clicking the gray button - nothing should happen

**Database Verification:**
```sql
SELECT id, is_collected FROM orders WHERE id = 123;
```
Should show `is_collected = 1` after marking as collected.

### Test 6: Error Handling

**Test 6a: Non-existent Order**
```bash
# Using curl or Postman
POST /admin/delivery-status/mark-collected/999999
```
**Expected:** 404 error with message "Order not found"

**Test 6b: Already Collected Order**
1. Mark an order as collected
2. Try to mark it again (manually via AJAX or direct API call)
**Expected:** 400 error with message "Order already marked as collected"

### Test 7: Permission Check

**Expected Behavior:**
- Only users with `order_management` module permission should access the page
- Unauthorized users should be redirected or see permission error

**Test Steps:**
1. Create/use a user role without `order_management` permission
2. Try to access `/admin/delivery-status`
3. Verify access is denied

## Sample Test Data

### Create Test Delivered Order (if needed)

```sql
-- Update an existing order to delivered status
UPDATE orders 
SET order_status = 'delivered', 
    delivery_date = '2026-02-14'
WHERE id = 1;

-- Add UPI payment for the order
INSERT INTO payment_ledgers (store_id, order_id, entry_type, amount, payment_method, transaction_ref, created_at, updated_at)
VALUES 
(1, 1, 'CREDIT', 1500.00, 'upi', 'UPI-TEST-001', NOW(), NOW());
```

## Common Issues & Solutions

### Issue 1: Page Returns 404
**Solution:** Make sure routes are cached properly
```bash
php artisan route:clear
php artisan route:cache
```

### Issue 2: "Table orders has no column named is_collected"
**Solution:** Run the migration
```bash
php artisan migrate
```

### Issue 3: UPI Payment Not Showing
**Check:**
1. Does the order have a `store_id`?
2. Does `payment_ledgers` table have an entry with:
   - `store_id` matching order's store_id
   - `order_id` matching order's id
   - `payment_method = 'upi'`
   - `entry_type = 'CREDIT'`

**Sample Query:**
```sql
SELECT pl.* 
FROM payment_ledgers pl
JOIN orders o ON pl.store_id = o.store_id AND pl.order_id = o.id
WHERE o.id = YOUR_ORDER_ID 
  AND pl.payment_method = 'upi' 
  AND pl.entry_type = 'CREDIT';
```

### Issue 4: AJAX Error on Mark as Collected
**Check:**
1. Browser console for JavaScript errors
2. Network tab to see actual error response
3. CSRF token is included in request
4. Route is correct: `/admin/delivery-status/mark-collected/{order_id}`

### Issue 5: Sidebar Menu Not Showing
**Check:**
1. User has `order_management` permission
2. Blade cache is cleared: `php artisan view:clear`
3. Check MANAGEMENT_SECTION constant is defined

## Browser Console Testing

Open browser console and test AJAX manually:

```javascript
// Test marking order as collected
$.ajax({
    url: '/admin/delivery-status/mark-collected/1',
    type: 'POST',
    data: {
        _token: $('meta[name="csrf-token"]').attr('content')
    },
    success: function(response) {
        console.log('Success:', response);
    },
    error: function(xhr) {
        console.log('Error:', xhr.responseJSON);
    }
});
```

## Expected Console Logs

**Success Response:**
```json
{
    "success": true,
    "message": "Order marked as collected successfully"
}
```

**Error Response (Already Collected):**
```json
{
    "success": false,
    "message": "Order already marked as collected"
}
```

## Performance Notes

- Page loads 15 orders at a time (paginated for efficiency)
- UPI payments are batch-loaded in a single query (N+1 issue resolved)
- Optimized query fetches all UPI payments for the current page's orders at once
- Works efficiently even for orders without store_id associations

## Screenshots Location

After testing, take screenshots of:
1. Delivery Status page with orders listed
2. UPI Payment column showing data
3. Mark as Collected button (before click)
4. Collected button (after click)
5. Success toastr message
6. Sidebar with new menu item highlighted

Save screenshots in: `/docs/screenshots/delivery-status/`

## Completion Checklist

- [ ] Migration executed successfully
- [ ] Page accessible at `/admin/delivery-status`
- [ ] Delivered orders are displayed
- [ ] All 13 columns show correct data
- [ ] UPI payments display correctly
- [ ] Search works
- [ ] Branch filter works
- [ ] Mark as Collected button works
- [ ] Button state persists after reload
- [ ] Toastr notifications appear
- [ ] Sidebar menu item visible
- [ ] Sidebar highlights when active
- [ ] No console errors
- [ ] No PHP errors in logs

## Next Steps

1. Run migration in production (with backup!)
2. Test in staging environment first
3. Train admin users on the new feature
4. Monitor for any issues in first week
5. Consider adding audit logging (future enhancement)

---

**Feature Documentation:** See `DELIVERY_STATUS_FEATURE.md` for complete technical details.
