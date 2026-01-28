<?php

/**
 * Payment Configuration
 * 
 * File Location: config/payment.php
 * 
 * After creating this file, run: php artisan config:cache
 */

return [
    
    /*
    |--------------------------------------------------------------------------
    | UPI Payment Configuration
    |--------------------------------------------------------------------------
    |
    | Configure your UPI merchant details here.
    | These values are used for UPI Intent payments.
    |
    | Set these in your .env file:
    | UPI_MERCHANT_ID=yourbusiness@upi
    | UPI_MERCHANT_NAME=Your Business Name
    |
    */
    
    'upi_id' => env('UPI_MERCHANT_ID', 'merchant@upi'),
    
    'merchant_name' => env('UPI_MERCHANT_NAME', 'Store'),
    
    /*
    |--------------------------------------------------------------------------
    | Payment Expiry
    |--------------------------------------------------------------------------
    |
    | Time in minutes after which a payment request expires.
    |
    */
    
    'payment_expiry_minutes' => env('PAYMENT_EXPIRY_MINUTES', 30),
];