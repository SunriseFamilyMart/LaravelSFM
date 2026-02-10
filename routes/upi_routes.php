<?php

/**
 * ============================================================
 * UPI PAYMENT ROUTES
 * ============================================================
 * Add these routes to your routes/api.php file
 * ============================================================
 */

use App\Http\Controllers\Api\V1\UpiPaymentController;

/*
|--------------------------------------------------------------------------
| ADD THESE ROUTES TO YOUR routes/api.php FILE
|--------------------------------------------------------------------------
|
| Copy the Route definitions below into your existing api.php file
| inside your v1 route group.
|
*/

// ============================================================
// OPTION 1: If you have existing v1 prefix group
// ============================================================
/*
Route::prefix('v1')->group(function () {
    
    // ... your existing routes ...
    
    // ========== UPI Payment Routes ==========
    
    // Public: Get UPI merchant details
    Route::get('/payment/upi-details', [UpiPaymentController::class, 'getUpiDetails']);
    
    // Delivery Man: UPI Intent operations
    Route::prefix('delivery-man/upi')->group(function () {
        Route::post('/initiate', [UpiPaymentController::class, 'initiate']);
        Route::post('/confirm', [UpiPaymentController::class, 'confirm']);
        Route::post('/cancel', [UpiPaymentController::class, 'cancel']);
        Route::get('/status/{payment_ref}', [UpiPaymentController::class, 'status']);
    });
    
    // Admin: Settlement (bank reconciliation)
    Route::prefix('admin/upi')->middleware('admin')->group(function () {
        Route::post('/settle', [UpiPaymentController::class, 'markAsSettled']);
    });
});
*/


// ============================================================
// OPTION 2: Complete standalone route file
// ============================================================

Route::prefix('v1')->group(function () {
    
    /*
    |--------------------------------------------------------------------------
    | UPI Payment Routes
    |--------------------------------------------------------------------------
    |
    | Endpoints:
    | GET  /api/v1/payment/upi-details           - Get merchant UPI ID
    | POST /api/v1/delivery-man/upi/initiate     - Create payment intent
    | POST /api/v1/delivery-man/upi/confirm      - Confirm payment
    | POST /api/v1/delivery-man/upi/cancel       - Cancel payment
    | GET  /api/v1/delivery-man/upi/status/{ref} - Check payment status
    | POST /api/v1/admin/upi/settle              - Mark as settled (admin)
    |
    */
    
    // Public endpoint - Get UPI details
    Route::get('/payment/upi-details', [UpiPaymentController::class, 'getUpiDetails']);
    
    // Delivery Man endpoints
    Route::prefix('delivery-man/upi')->group(function () {
        
        // Initiate UPI payment - Creates payment intent
        Route::post('/initiate', [UpiPaymentController::class, 'initiate']);
        
        // Confirm UPI payment - After user completes in UPI app
        Route::post('/confirm', [UpiPaymentController::class, 'confirm']);
        
        // Cancel pending UPI payment
        Route::post('/cancel', [UpiPaymentController::class, 'cancel']);
        
        // Check payment status
        Route::get('/status/{payment_ref}', [UpiPaymentController::class, 'status']);
    });

     // ================= SALES PERSON (✅ FIXED) =================
    Route::prefix('sales/upi')->group(function () {
        Route::post('/initiate', [UpiPaymentController::class, 'initiateSalesPerson']);
        Route::post('/confirm',  [UpiPaymentController::class, 'confirmSalesPerson']);
        Route::post('/cancel',   [UpiPaymentController::class, 'cancel']);
        Route::get('/status/{payment_ref}', [UpiPaymentController::class, 'status']);
    });
    
    // Admin endpoints (add your admin middleware)
    Route::prefix('admin/upi')->group(function () {
        
        // Mark payment as settled (after bank reconciliation)
        Route::post('/settle', [UpiPaymentController::class, 'markAsSettled']);
    });
});

/*
|--------------------------------------------------------------------------
| ENVIRONMENT CONFIGURATION (.env)
|--------------------------------------------------------------------------
|
| Add these to your .env file:
|
| UPI_MERCHANT_ID=yourbusiness@upi
| UPI_MERCHANT_NAME=Your Business Name
|
*/


/*
|--------------------------------------------------------------------------
| CONFIG FILE (config/payment.php)
|--------------------------------------------------------------------------
|
| Create this file for cleaner configuration:
|
| <?php
| return [
|     'upi_id' => env('UPI_MERCHANT_ID', 'merchant@upi'),
|     'merchant_name' => env('UPI_MERCHANT_NAME', 'Store'),
| ];
|
*/


/*
|--------------------------------------------------------------------------
| API ENDPOINTS SUMMARY
|--------------------------------------------------------------------------
|
| Method | Endpoint                              | Description
| -------|---------------------------------------|---------------------------
| GET    | /api/v1/payment/upi-details           | Get merchant UPI ID
| POST   | /api/v1/delivery-man/upi/initiate     | Create payment intent
| POST   | /api/v1/delivery-man/upi/confirm      | Confirm payment (user)
| POST   | /api/v1/delivery-man/upi/cancel       | Cancel payment
| GET    | /api/v1/delivery-man/upi/status/{ref} | Check payment status
| POST   | /api/v1/admin/upi/settle              | Mark as settled (admin)
|
*/