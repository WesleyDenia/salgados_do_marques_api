<?php

namespace Tests\Support;

final class ControllerArchitectureWhitelist
{
    /**
     * Temporary exceptions while the API refactor is still being completed.
     *
     * @return array<string, string>
     */
    public static function forbiddenPatternControllerExceptions(): array
    {
        return [
            'app/Http/Controllers/Admin/AdminAuthController.php' => 'Admin auth still validates credentials inline and manages session auth directly.',
            'app/Http/Controllers/Admin/OrderController.php' => 'Admin order index still validates read filters inline.',
            'app/Http/Controllers/Admin/StoreController.php' => 'Admin store destroy still deletes the route model directly.',
            'app/Http/Controllers/Api/V1/CategoryController.php' => 'API category controller still uses repository/model-level access from the controller.',
            'app/Http/Controllers/Api/V1/CouponController.php' => 'API coupon controller still uses repository/model-level access from the controller.',
            'app/Http/Controllers/Api/V1/FlavorController.php' => 'API flavor controller still uses repository/model-level access from the controller.',
            'app/Http/Controllers/Api/V1/LoyaltyRewardController.php' => 'API loyalty reward index still depends on framework request directly.',
            'app/Http/Controllers/Api/V1/NotificationController.php' => 'API notification controller still validates payload inline.',
            'app/Http/Controllers/Api/V1/OrderController.php' => 'API order read/cancel actions still depend on framework request directly.',
            'app/Http/Controllers/Api/V1/ProductController.php' => 'API product controller still uses repository/model-level access from the controller.',
            'app/Http/Controllers/Api/V1/PromotionController.php' => 'API promotion controller still injects repository directly.',
            'app/Http/Controllers/Api/V1/UserCouponAdminController.php' => 'API user coupon admin controller still injects repository directly.',
            'app/Http/Controllers/Api/V1/UserCouponController.php' => 'API user coupon read/destroy actions still depend on framework request directly.',
        ];
    }

    /**
     * Methods that remain exempt from the "mutable actions must use FormRequest" rule.
     *
     * @return array<string, array<string, string>>
     */
    public static function formRequestMethodExceptions(): array
    {
        return [
            'App\Http\Controllers\Admin\AdminAuthController' => [
                'login' => 'Pending extraction of admin login request/service.',
            ],
            'App\Http\Controllers\Api\V1\OrderController' => [
                'cancel' => 'Cancellation has no request payload beyond authenticated user and route model.',
            ],
            'App\Http\Controllers\Api\V1\UserCouponController' => [
                'destroy' => 'Destroy action has no mutable payload beyond authenticated user and route parameter.',
            ],
        ];
    }

    /**
     * Controllers that still inject repositories directly until their dedicated service is extracted.
     *
     * @return array<string, string>
     */
    public static function repositoryInjectionExceptions(): array
    {
        return [
            'App\Http\Controllers\Api\V1\CategoryController' => 'Category API still lacks an intermediate service.',
            'App\Http\Controllers\Api\V1\CouponController' => 'Coupon API still lacks an intermediate service.',
            'App\Http\Controllers\Api\V1\ProductController' => 'Product API still lacks an intermediate service.',
            'App\Http\Controllers\Api\V1\PromotionController' => 'Promotion API still lacks an intermediate service.',
            'App\Http\Controllers\Api\V1\UserCouponAdminController' => 'User coupon admin API still lacks an intermediate service.',
        ];
    }
}
