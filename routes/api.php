<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StoreAuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| These routes are loaded by RouteServiceProvider
| and assigned the "api" middleware group.
*/

Route::prefix('v1/store')->group(function () {

    // Store self registration
    Route::post('/register', [StoreAuthController::class, 'register']);

    // Login step 1 (phone + password → OTP)
    Route::post('/login', [StoreAuthController::class, 'login']);

    // Login step 2 (verify OTP)
    Route::post('/verify-otp', [StoreAuthController::class, 'verifyOtp']);

    // Protected routes (token required)
    Route::middleware('store.auth')->group(function () {
        Route::get('/me', [StoreAuthController::class, 'me']);
        Route::get('/arrear', [StoreAuthController::class, 'getArrear']);
        Route::get('/payment-statement', [StoreAuthController::class, 'paymentStatement']);
        Route::get('/orders/{order_id}', [StoreAuthController::class, 'orderDetail']);
        Route::post('/logout', [StoreAuthController::class, 'logout']);
        Route::post('/change-password', [StoreAuthController::class, 'changePassword']);
    });

});
