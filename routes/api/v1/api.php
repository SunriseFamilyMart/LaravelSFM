<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use App\Http\Controllers\Api\V1\GuestUserController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\CustomerWalletController;
use App\Http\Controllers\Api\V1\OfflinePaymentMethodController;
use App\Http\Controllers\Api\V1\DeliverymanController;
use App\Http\Controllers\Api\V1\BannerController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ConfigController;
use App\Http\Controllers\Api\V1\ConversationController;
use App\Http\Controllers\Api\V1\CouponController;
use App\Http\Controllers\Api\V1\DeliveryManReviewController;
use App\Http\Controllers\Api\V1\LoyaltyPointController;
use App\Http\Controllers\Api\V1\MapApiController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\OfferController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\WishlistController;
use App\Http\Controllers\Api\V1\TimeSlotController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\Auth\CustomerAuthController;
use App\Http\Controllers\Api\V1\Auth\PasswordResetController;
use App\Http\Controllers\Api\V1\Auth\DeliveryManLoginController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StoreAuthController;
use App\Http\Controllers\Api\StoreOrderController;
use App\Http\Controllers\Api\V1\StoreVisitController;
use App\Http\Controllers\Api\V1\OrderApiController;
use App\Http\Controllers\Api\V1\DeliveryManAuthController;
use App\Http\Controllers\Api\V1\UpiPaymentController;
use Mpdf\Tag\Del;

Route::get('/hello', function () {
    return response()->json(['message' => 'Hello']);
});

Route::options('/{any}', function (Request $request) {
    return Response::make('', 200, [
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS, PUT, DELETE, PATCH',
        'Access-Control-Allow-Headers' => 'Origin, Content-Type, Accept, Authorization, guest-id, x-localization, X-Store-Token, X-Requested-With',
        'Access-Control-Expose-Headers' => '*',
    ]);
})->where('any', '.*');

Route::group(['namespace' => 'Api\V1', 'middleware' => 'localization'], function () {
    Route::get('/delivery-trip-statuses', [DeliveryManController::class, 'getTripStatuses']);
    Route::get('order-edit-reasons', [DeliveryManController::class, 'reasons']);


    Route::group(['prefix' => 'auth', 'namespace' => 'Auth'], function () {
        Route::post('register', [CustomerAuthController::class, 'registration']);
        Route::post('login', [CustomerAuthController::class, 'login']);
        Route::post('social-customer-login', [CustomerAuthController::class, 'customerSocialLogin']);

        Route::post('check-phone', [CustomerAuthController::class, 'checkPhone']);
        Route::post('verify-phone', [CustomerAuthController::class, 'verifyPhone']);
        Route::post('check-email', [CustomerAuthController::class, 'checkEmail']);
        Route::post('verify-email', [CustomerAuthController::class, 'verifyEmail']);
        Route::post('firebase-auth-verify', [CustomerAuthController::class, 'firebaseAuthVerify']);
        Route::post('verify-otp', [CustomerAuthController::class, 'verifyOTP']);
        Route::post('registration-with-otp', [CustomerAuthController::class, 'registrationWithOTP']);
        Route::post('existing-account-check', [CustomerAuthController::class, 'existingAccountCheck']);
        Route::post('registration-with-social-media', [CustomerAuthController::class, 'registrationWithSocialMedia']);

        Route::post('forgot-password', [PasswordResetController::class, 'resetPasswordRequest']);
        Route::post('verify-token', [PasswordResetController::class, 'verifyToken']);
        Route::put('reset-password', [PasswordResetController::class, 'resetPasswordSubmit']);

        Route::group(['prefix' => 'delivery-man'], function () {
            Route::post('register', [DeliveryManLoginController::class, 'registration']);
            Route::post('login', [DeliveryManLoginController::class, 'login']);
        });
    });

    Route::group(['prefix' => 'config'], function () {
        Route::get('/', [ConfigController::class, 'configuration']);
        Route::get('delivery-fee', [ConfigController::class, 'deliveryFree']);
    });

    Route::group(['prefix' => 'products'], function () {
        Route::get('all', [ProductController::class, 'getAllProducts']);
        Route::get('latest', [ProductController::class, 'getLatestProducts']);
        Route::get('popular', [ProductController::class, 'getPopularProducts']);
        Route::get('discounted', [ProductController::class, 'getDiscountedProducts']);
        Route::get('search', [ProductController::class, 'getSearchedProducts']);
        Route::get('details/{id}', [ProductController::class, 'getProduct']);
        Route::get('related-products/{product_id}', [ProductController::class, 'getRelatedProducts']);
        Route::get('reviews/{product_id}', [ProductController::class, 'getProductReviews']);
        Route::get('rating/{product_id}', [ProductController::class, 'getProductRating']);
        Route::get('daily-needs', [ProductController::class, 'getDailyNeedProducts']);
        Route::post('reviews/submit', [ProductController::class, 'submitProductReview'])->middleware('auth:api');

        Route::group(['prefix' => 'favorite', 'middleware' => ['auth:api', 'customer_is_block']], function () {
            Route::get('/', [ProductController::class, 'getFavoriteProducts']);
            Route::post('/', [ProductController::class, 'addFavoriteProducts']);
            Route::delete('/', [ProductController::class, 'removeFavoriteProducts']);
        });

        Route::get('featured', [ProductController::class, 'featuredProducts']);
        Route::get('most-viewed', [ProductController::class, 'getMostViewedProducts']);
        Route::get('trending', [ProductController::class, 'getTrendingProducts']);
        Route::get('recommended', [ProductController::class, 'getRecommendedProducts']);
        Route::get('most-reviewed', [ProductController::class, 'getMostReviewedProducts']);
    });

    Route::group(['prefix' => 'banners'], function () {
        Route::get('/', [BannerController::class, 'getBanners']);
    });

    Route::group(['prefix' => 'notifications'], function () {
        Route::get('/', [NotificationController::class, 'getNotifications']);
    });

    Route::group(['prefix' => 'categories'], function () {
        Route::get('/', [CategoryController::class, 'getCategories']);
        Route::get('childes/{category_id}', [CategoryController::class, 'getChildes']);
        Route::get('products/{category_id}', [CategoryController::class, 'getProducts']);
        Route::get('products/{category_id}/all', [CategoryController::class, 'getAllProducts']);
    });

    Route::group(['prefix' => 'customer', 'middleware' => ['auth:api', 'customer_is_block']], function () {
        Route::get('info', [CustomerController::class, 'info']);
        Route::put('update-profile', [CustomerController::class, 'updateProfile']);
        Route::post('verify-profile-info', [CustomerController::class, 'verifyProfileInfo']);
        Route::put('cm-firebase-token', [CustomerController::class, 'updateFirebaseToken']);
        Route::delete('remove-account', [CustomerController::class, 'removeAccount']);

        Route::group(['prefix' => 'address', 'middleware' => 'guest_user'], function () {
            Route::get('list', [CustomerController::class, 'addressList'])->withoutMiddleware(['auth:api', 'customer_is_block']);
            Route::post('add', [CustomerController::class, 'addNewAddress'])->withoutMiddleware(['auth:api', 'customer_is_block']);
            Route::put('update/{id}', [CustomerController::class, 'updateAddress'])->withoutMiddleware(['auth:api', 'customer_is_block']);
            Route::delete('delete', [CustomerController::class, 'deleteAddress'])->withoutMiddleware(['auth:api', 'customer_is_block']);
        });
        Route::get('last-ordered-address', [CustomerController::class, 'lastOrderedAddress']);

        Route::group(['prefix' => 'order', 'middleware' => 'guest_user'], function () {
            Route::get('list', [OrderController::class, 'getOrderList'])->withoutMiddleware(['auth:api', 'customer_is_block']);
            Route::post('details', [OrderController::class, 'getOrderDetails'])->withoutMiddleware(['auth:api', 'customer_is_block']);
            Route::post('place', [OrderController::class, 'placeOrder'])->withoutMiddleware(['auth:api', 'customer_is_block']);
            Route::put('cancel', [OrderController::class, 'cancelOrder'])->withoutMiddleware(['auth:api', 'customer_is_block']);
            Route::post('track', [OrderController::class, 'trackOrder'])->withoutMiddleware(['auth:api', 'customer_is_block']);
            Route::put('payment-method', [OrderController::class, 'updatePaymentMethod'])->withoutMiddleware(['auth:api', 'customer_is_block']);
        });
        Route::group(['prefix' => 'message'], function () {
            //customer-admin
            Route::get('get-admin-message', [ConversationController::class, 'getAdminMessage']);
            Route::post('send-admin-message', [ConversationController::class, 'storeAdminMessage']);
            //customer-deliveryman
            Route::get('get-order-message', [ConversationController::class, 'getMessageByOrder']);
            Route::post('send/{sender_type}', [ConversationController::class, 'storeMessageByOrder']);

        });

        Route::group(['prefix' => 'wish-list'], function () {
            Route::get('/', [WishlistController::class, 'getWishlist']);
            Route::post('add', [WishlistController::class, 'addToWishlist']);
            Route::delete('remove', [WishlistController::class, 'removeFromWishlist']);
        });

        Route::post('transfer-point-to-wallet', [CustomerWalletController::class, 'transferLoyaltyPointToWallet']);
        Route::get('wallet-transactions', [CustomerWalletController::class, 'walletTransactions']);
        Route::get('bonus/list', [CustomerWalletController::class, 'walletBonusList']);

        Route::get('loyalty-point-transactions', [LoyaltyPointController::class, 'pointTransactions']);

    });

    Route::group(['prefix' => 'coupon', 'middleware' => ['auth:api', 'customer_is_block']], function () {
        Route::get('list', [CouponController::class, 'list'])->withoutMiddleware(['auth:api', 'customer_is_block']);
        Route::get('apply', [CouponController::class, 'apply'])->withoutMiddleware(['auth:api', 'customer_is_block']);
    });

    Route::group(['prefix' => 'timeSlot'], function () {
        Route::get('/', [TimeSlotController::class, 'getTimeSlot']);
    });

    Route::get('/coupon/latest', [CouponController::class, 'latestCoupon']);
    Route::get('/coupons', [CouponController::class, 'index']);

    Route::group(['prefix' => 'mapapi'], function () {
        Route::get('place-api-autocomplete', [MapApiController::class, 'placeApiAutocomplete']);
        Route::get('distance-api', [MapApiController::class, 'distanceApi']);
        Route::get('place-api-details', [MapApiController::class, 'placeApiDetails']);
        Route::get('geocode-api', [MapApiController::class, 'geocodeApi']);
    });

    Route::group(['prefix' => 'flash-deals'], function () {
        Route::get('/', [OfferController::class, 'getFlashDeal']);
        Route::get('products/{flash_deal_id}', [OfferController::class, 'getFlashDealProducts']);
    });

    Route::post('subscribe-newsletter', [CustomerController::class, 'subscribeNewsletter']);

    Route::group(['prefix' => 'delivery-man'], function () {
        Route::match(['get', 'post'], 'trips', [DeliverymanController::class, 'getTrips']);
        Route::post('trip/update-status', [DeliverymanController::class, 'updateTripStatus']);
        Route::post('/send-otp', [DeliveryManAuthController::class, 'sendOtp']);
        Route::post('/verify-otp', [DeliveryManAuthController::class, 'verifyOtp']);
        Route::group(['middleware' => 'deliveryman_is_active'], function () {
            Route::post('/order/edit-product', [DeliverymanController::class, 'editOrderProduct']);
            Route::get('profile', [DeliverymanController::class, 'getProfile']);
            Route::get('current-orders', [DeliverymanController::class, 'getCurrentOrders']);
            Route::get('all-orders', [DeliverymanController::class, 'getAllOrders']);
            Route::post('record-location-data', [DeliverymanController::class, 'recordLocationData']);
            Route::get('order-delivery-history', [DeliverymanController::class, 'getOrderHistory']);
            Route::put('update-order-status', [DeliverymanController::class, 'updateOrderStatus']);
            Route::put('update-payment-status', [DeliverymanController::class, 'orderPaymentStatusUpdate']);
            Route::match(['get', 'post'], 'order-details', [DeliverymanController::class, 'getOrderDetails']);
            Route::get('last-location', [DeliverymanController::class, 'getLastLocation']);
            Route::put('update-fcm-token', [DeliverymanController::class, 'updateFcmToken']);
            Route::get('order-model', [DeliverymanController::class, 'orderModel']);
            Route::match(['get', 'post'], 'trips', [DeliverymanController::class, 'getTrips']);
            Route::post('/order/payment', [DeliverymanController::class, 'storeFlexiblePayment']);
            Route::get('/order-payments', [DeliverymanController::class, 'getOrderPayments']);

            Route::get('/order/payments/list', [DeliverymanController::class, 'index']);
            Route::get('/order/payment-methods', [DeliverymanController::class, 'getPaymentMethods']);
            Route::post('order/delete-product', [DeliverymanController::class, 'deleteOrderProduct']);
            Route::get('/orders/arrear', [DeliverymanController::class, 'getAllOrdersArrear']);
            Route::post('/order-edit-logs', [DeliverymanController::class, 'orderEditLogs']);

        });

        //delivery-man message
        Route::group(['prefix' => 'message'], function () {
            Route::post('get-message', [ConversationController::class, 'getOrderMessageForDeliveryman']);
            Route::post('send/{sender_type}', [ConversationController::class, 'storeMessageByOrder']);
        });

        Route::group(['prefix' => 'reviews', 'middleware' => ['auth:api', 'customer_is_block']], function () {
            Route::get('/{delivery_man_id}', [DeliveryManReviewController::class, 'getReviews']);
            Route::get('rating/{delivery_man_id}', [DeliveryManReviewController::class, 'getRating']);
            Route::post('/submit', [DeliveryManReviewController::class, 'submitReview']);
        });
    });

    Route::group(['prefix' => 'guest'], function () {
        Route::post('/add', [GuestUserController::class, 'guestStore']);
    });

    Route::group(['prefix' => 'offline-payment-method'], function () {
        Route::get('/list', [OfflinePaymentMethodController::class, 'list']);
    });

    Route::post('customer/change-language', [CustomerController::class, 'changeLanguage']);
    Route::post('delivery-man/change-language', [DeliverymanController::class, 'changeLanguage']);

    // ===================== SALES PERSON ROUTES =====================
    Route::post('/sales/request-otp', [AuthController::class, 'requestOtp']);
    Route::post('/sales/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/sales/new-customer', [AuthController::class, 'newCustomerApi']);
    Route::post('/sales/logout', [AuthController::class, 'salesPersonLogout']);
    Route::post('/stores', [AuthController::class, 'stores']);
    Route::get('/my-stores', [AuthController::class, 'myStores']);
    Route::get('/sales/profile', [AuthController::class, 'saleprofile']);
    Route::post('/sales/orders', [AuthController::class, 'orders']);
    Route::get('/sales/customers', [AuthController::class, 'listCustomers']);
    Route::get('/sales/totalorders', [AuthController::class, 'totalOrders']);
    Route::get('/sales/conversations', [AuthController::class, 'conversations']);
    Route::post('/sales/send-message', [AuthController::class, 'sendMessage']);
    Route::post('/{orderId}/reorder', [AuthController::class, 'reorder']);
    Route::post('/sales/add', [AuthController::class, 'addToCart']);
    Route::post('/sales/remove', [AuthController::class, 'removeFromCart']);
    Route::get('/sales/items', [AuthController::class, 'getCartItems']);
    Route::post('/cart/clear', [AuthController::class, 'clearCart']);
    Route::post('/sales/nearby-stores', [AuthController::class, 'nearbyStores']);
    Route::get('/sales/order-amount', [OrderApiController::class, 'getOrderAmountByToken']);
    Route::get('/sales/distance-today', [AuthController::class, 'getTodayDistance']);
    Route::post('/sales/store-visit', [StoreVisitController::class, 'store']);
    Route::get('/sales/store-visits', [StoreVisitController::class, 'index']);
    Route::get('/sales/delivery-men', [AuthController::class, 'allDeliveryMen']);
    Route::post('/sales/login', [AuthController::class, 'login']);
    Route::get('/orders/arrear', [AuthController::class, 'getAllOrdersArrear']);

    // ===================== SALES PERSON UPI PAYMENT =====================
    Route::group(['prefix' => 'sales/upi'], function () {
        // Initiate UPI payment for order
        Route::post('/initiate', [UpiPaymentController::class, 'initiateSalesPerson']);
        
        // Confirm UPI payment with GPay/PhonePe response
        Route::post('/confirm', [UpiPaymentController::class, 'confirmSalesPerson']);
        
        // Cancel pending payment
        Route::post('/cancel', [UpiPaymentController::class, 'cancel']);
        
        // Check payment status
        Route::get('/status/{payment_ref}', [UpiPaymentController::class, 'status']);
    });

    // ===================== STORE SELF APP =====================
    // Store cannot login until admin approves + assigns salesperson.
    Route::post('/store/register', [StoreAuthController::class, 'register']);
    Route::post('/store/login', [StoreAuthController::class, 'login']);
    Route::post('/store/verify-otp', [StoreAuthController::class, 'verifyOtp']);
    
    // Store authenticated routes
    Route::group(['prefix' => 'store', 'middleware' => 'store.auth'], function () {
        // Profile with arrear amount and sales person details (name, phone)
        Route::get('/me', [StoreAuthController::class, 'me']);
        
        // Get arrear/outstanding amount details
        Route::get('/arrear', [StoreAuthController::class, 'getArrear']);
        
        // Logout
        Route::post('/logout', [StoreAuthController::class, 'logout']);
        
        // Orders
        Route::get('/orders', [StoreOrderController::class, 'index']);
        Route::post('/orders', [StoreOrderController::class, 'place']);
        
        // ===================== STORE UPI PAYMENT =====================
        Route::group(['prefix' => 'upi'], function () {
            // Initiate UPI payment for order
            Route::post('/initiate', [UpiPaymentController::class, 'initiateStore']);
            
            // Confirm UPI payment with GPay/PhonePe response
            Route::post('/confirm', [UpiPaymentController::class, 'confirmStore']);
            
            // Cancel pending payment
            Route::post('/cancel', [UpiPaymentController::class, 'cancel']);
            
            // Check payment status
            Route::get('/status/{payment_ref}', [UpiPaymentController::class, 'status']);
        });
    });

    // ===================== ONLINE PAYMENT SYSTEM =====================
    // Real-time payment intent system (like GPay/PhonePe)
    Route::group(['prefix' => 'payment'], function () {
        // Get UPI/Bank details for payment
        Route::get('/upi-details', [\App\Http\Controllers\Api\V1\OnlinePaymentController::class, 'getUpiDetails']);
        
        // Create payment intent (returns UPI details + payment reference)
        Route::post('/create-intent', [\App\Http\Controllers\Api\V1\OnlinePaymentController::class, 'createIntent']);
        
        // Check payment status (for real-time polling)
        Route::get('/status/{payment_ref}', [\App\Http\Controllers\Api\V1\OnlinePaymentController::class, 'checkStatus']);
        
        // Cancel payment
        Route::post('/cancel', [\App\Http\Controllers\Api\V1\OnlinePaymentController::class, 'cancelPayment']);
    });

    // Delivery man confirms payment
    Route::group(['prefix' => 'delivery-man', 'middleware' => 'deliveryman_is_active'], function () {
        Route::post('/payment/confirm', [\App\Http\Controllers\Api\V1\OnlinePaymentController::class, 'confirmPayment']);
    });

    // ===================== UPI INTENT PAYMENT (GPay/PhonePe) =====================
    // Captures actual UPI response from GPay/PhonePe/Paytm/BHIM
    
    // Public endpoint - Get UPI merchant details
    Route::get('/upi/details', [UpiPaymentController::class, 'getUpiDetails']);
    
    // Delivery Man UPI Payment endpoints
    Route::group(['prefix' => 'delivery-man/upi', 'middleware' => 'deliveryman_is_active'], function () {
        // Initiate UPI payment - Creates payment intent
        Route::post('/initiate', [UpiPaymentController::class, 'initiate']);
        
        // Confirm UPI payment - With actual GPay/PhonePe response
        Route::post('/confirm', [UpiPaymentController::class, 'confirm']);
        
        // Cancel pending UPI payment
        Route::post('/cancel', [UpiPaymentController::class, 'cancel']);
        
        // Check payment status
        Route::get('/status/{payment_ref}', [UpiPaymentController::class, 'status']);
    });
    
    // Admin - Mark payment as settled (after bank reconciliation)
    Route::post('/admin/upi/settle', [UpiPaymentController::class, 'markAsSettled']);

});