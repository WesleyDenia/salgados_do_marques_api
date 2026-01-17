<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\{
    AuthController,
    UserController,
    CategoryController,
    ProductController,
    PromotionController,
    CouponController,
    UserCouponController,
    UserCouponAdminController,
    UploadController,
    NotificationController,
    LoyaltyController,
    SettingController,
    LoyaltyBonusController,
    LoyaltyRewardController,
    ContentHomeController,
    LgpdController,
    PasswordResetController,
    StoreController,
    OrderController,
    OrderAdminController
};

Route::prefix('v1')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::get('lgpd/terms', [LgpdController::class, 'terms']);
    Route::prefix('auth')->group(function () {
        Route::post('forgot-password', [PasswordResetController::class, 'forgot']);
        Route::post('verify-otp', [PasswordResetController::class, 'verifyOtp']);
        Route::post('reset-password', [PasswordResetController::class, 'reset']);
    });
    Route::get('stores', [StoreController::class, 'index']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('orders/settings', [OrderController::class, 'settings']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::post('auth/refresh', [AuthController::class, 'refresh']);

        Route::put('user', [UserController::class, 'update']);

        // Public data
        Route::get('categories', [CategoryController::class, 'index']);
        Route::get('products', [ProductController::class, 'index']);
        Route::get('promotions', [PromotionController::class, 'index']);
        Route::get('coupons', [CouponController::class, 'index']);
        Route::get('content-home', [ContentHomeController::class, 'index']);

        // Admin protected
        Route::middleware('can:manage')->group(function () {
            Route::get('admin/orders', [OrderAdminController::class, 'index']);
            Route::get('admin/orders/{order}', [OrderAdminController::class, 'show']);
            Route::patch('admin/orders/{order}/status', [OrderAdminController::class, 'updateStatus']);
            Route::apiResource('products', ProductController::class)->except(['index', 'show']);
            Route::apiResource('promotions', PromotionController::class)->except(['index', 'show']);
            Route::apiResource('coupons', CouponController::class)->except(['index', 'show']);
            Route::apiResource('user-coupons', UserCouponAdminController::class);
            Route::post('upload', [UploadController::class, 'store']);
            Route::post('notifications/send', [NotificationController::class, 'send']);
            Route::post('loyalty/earn', [LoyaltyController::class, 'earn']);
            Route::get('settings', [SettingController::class, 'index']);
            Route::get('settings/{key}', [SettingController::class, 'show']);
            Route::put('settings/{key}', [SettingController::class, 'update']);
        });
        
        // Cliente
        Route::get('my-coupons', [UserCouponController::class, 'index']);
        Route::post('my-coupons', [UserCouponController::class, 'store']);
        Route::delete('my-coupons/{coupon}', [UserCouponController::class, 'destroy']);
        Route::post('notifications/register', [NotificationController::class, 'registerToken']);

        Route::get('orders', [OrderController::class, 'index']);
        Route::get('orders/{order}', [OrderController::class, 'show']);
        Route::post('orders', [OrderController::class, 'store']);
        Route::post('orders/{order}/cancel', [OrderController::class, 'cancel']);

        Route::get('loyalty/status', [LoyaltyController::class, 'status']);
        Route::get('loyalty/summary', [LoyaltyController::class, 'summary']);
        Route::get('loyalty/transactions', [LoyaltyController::class, 'transactions']);
        Route::get('loyalty/rewards', [LoyaltyRewardController::class, 'index']);
        Route::post('loyalty/rewards/{loyaltyReward}/redeem', [LoyaltyRewardController::class, 'redeem']);
        Route::post('/loyalty/welcome-bonus', [LoyaltyBonusController::class, 'claim']);

    });
});
