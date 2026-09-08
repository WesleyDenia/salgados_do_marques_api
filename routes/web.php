<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AppTesterController as AdminAppTesterController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ContentHomeController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FlavorController;
use App\Http\Controllers\Admin\HomeComponentController;
use App\Http\Controllers\Admin\LoyaltyRewardController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\PartnerCampaignController;
use App\Http\Controllers\Admin\PartnerController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\QueueMonitorController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\StoreController;
use App\Http\Controllers\Admin\UrbanCampaignController as AdminUrbanCampaignController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\WhatsAppController as AdminWhatsAppController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::view('/privacidade', 'privacy')->name('privacy');
Route::view('/delete-account', 'delete-account')->name('delete-account');

Route::get('/admin/login', [AdminAuthController::class, 'showLoginForm'])
    ->middleware('guest')
    ->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'login'])
    ->middleware('guest')
    ->name('admin.login.post');

Route::middleware(['auth', 'can:manage'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

        Route::post('content-home/reorder', [ContentHomeController::class, 'reorder'])->name('content-home.reorder');
        Route::resource('content-home', ContentHomeController::class)->except('show');
        Route::resource('home-components', HomeComponentController::class)->except('show');
        Route::post('categories/reorder', [CategoryController::class, 'reorder'])->name('categories.reorder');
        Route::resource('categories', CategoryController::class)->except('show');
        Route::resource('products', ProductController::class)->except('show');
        Route::resource('flavors', FlavorController::class)->except('show');
        Route::get('orders/daily', [AdminOrderController::class, 'daily'])->name('orders.daily');
        Route::get('orders', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
        Route::patch('orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.status');
        Route::resource('coupons', CouponController::class)->except('show');
        Route::resource('partners', PartnerController::class)->except('show');
        Route::resource('partner-campaigns', PartnerCampaignController::class)->except('show');
        Route::get('urban-campaign', [AdminUrbanCampaignController::class, 'index'])->name('urban-campaign.index');
        Route::post('urban-campaign/qr-codes', [AdminUrbanCampaignController::class, 'storeQrCode'])->name('urban-campaign.qr-codes.store');
        Route::get('urban-campaign/qr-codes/{qrCode}/edit', [AdminUrbanCampaignController::class, 'editQrCode'])->name('urban-campaign.qr-codes.edit');
        Route::put('urban-campaign/qr-codes/{qrCode}', [AdminUrbanCampaignController::class, 'updateQrCode'])->name('urban-campaign.qr-codes.update');
        Route::delete('urban-campaign/qr-codes/{qrCode}', [AdminUrbanCampaignController::class, 'destroyQrCode'])->name('urban-campaign.qr-codes.destroy');
        Route::post('urban-campaign/questions', [AdminUrbanCampaignController::class, 'storeQuestion'])->name('urban-campaign.questions.store');
        Route::get('urban-campaign/questions/{question}/edit', [AdminUrbanCampaignController::class, 'editQuestion'])->name('urban-campaign.questions.edit');
        Route::put('urban-campaign/questions/{question}', [AdminUrbanCampaignController::class, 'updateQuestion'])->name('urban-campaign.questions.update');
        Route::delete('urban-campaign/questions/{question}', [AdminUrbanCampaignController::class, 'destroyQuestion'])->name('urban-campaign.questions.destroy');
        Route::post('urban-campaign/responses', [AdminUrbanCampaignController::class, 'storeResponse'])->name('urban-campaign.responses.store');
        Route::get('urban-campaign/responses/{response}/edit', [AdminUrbanCampaignController::class, 'editResponse'])->name('urban-campaign.responses.edit');
        Route::put('urban-campaign/responses/{response}', [AdminUrbanCampaignController::class, 'updateResponse'])->name('urban-campaign.responses.update');
        Route::delete('urban-campaign/responses/{response}', [AdminUrbanCampaignController::class, 'destroyResponse'])->name('urban-campaign.responses.destroy');
        Route::post('urban-campaign/coupon-configs', [AdminUrbanCampaignController::class, 'storeCouponConfig'])->name('urban-campaign.coupon-configs.store');
        Route::get('urban-campaign/coupon-configs/{couponConfig}/edit', [AdminUrbanCampaignController::class, 'editCouponConfig'])->name('urban-campaign.coupon-configs.edit');
        Route::put('urban-campaign/coupon-configs/{couponConfig}', [AdminUrbanCampaignController::class, 'updateCouponConfig'])->name('urban-campaign.coupon-configs.update');
        Route::delete('urban-campaign/coupon-configs/{couponConfig}', [AdminUrbanCampaignController::class, 'destroyCouponConfig'])->name('urban-campaign.coupon-configs.destroy');
        Route::resource('loyalty-rewards', LoyaltyRewardController::class)->except('show');
        Route::resource('stores', StoreController::class)->except('show');
        Route::resource('settings', AdminSettingController::class)->only(['index', 'create', 'store', 'edit', 'update']);
        Route::get('app-testers', [AdminAppTesterController::class, 'index'])->name('app-testers.index');
        Route::get('whatsapp', [AdminWhatsAppController::class, 'index'])->name('whatsapp.index');
        Route::post('whatsapp/health-check', [AdminWhatsAppController::class, 'healthCheck'])->name('whatsapp.health-check');
        Route::get('queue', [QueueMonitorController::class, 'index'])->name('queue.index');
        Route::post('queue/users/{user}/sync', [QueueMonitorController::class, 'enqueueUser'])->name('queue.users.sync');
        Route::post('queue/coupon-imports/{import}/retry', [QueueMonitorController::class, 'retryCouponImport'])->name('queue.coupon-imports.retry');
        Route::post('queue/coupon-imports/{import}/close', [QueueMonitorController::class, 'closeCouponImport'])->name('queue.coupon-imports.close');
        Route::post('queue/whatsapp/{item}/retry', [QueueMonitorController::class, 'retryWhatsAppMessage'])->name('queue.whatsapp.retry');
        Route::post('queue/whatsapp/{item}/close', [QueueMonitorController::class, 'closeWhatsAppMessage'])->name('queue.whatsapp.close');
        Route::post('queue/tasks/{task}/retry', [QueueMonitorController::class, 'retryTask'])->name('queue.tasks.retry');
        Route::post('queue/tasks/{task}/status', [QueueMonitorController::class, 'updateTaskStatus'])->name('queue.tasks.status');
        Route::get('clientes', [AdminUserController::class, 'customersIndex'])->name('customers.index');
        Route::get('clientes/{user}', [AdminUserController::class, 'customerShow'])->name('customers.show');
        Route::get('users/password', [AdminUserController::class, 'editPassword'])->name('users.password.edit');
        Route::put('users/password', [AdminUserController::class, 'updatePassword'])->name('users.password.update');
        Route::get('users/create', [AdminUserController::class, 'create'])->name('users.create');
        Route::post('users', [AdminUserController::class, 'store'])->name('users.store');
        Route::get('users', [AdminUserController::class, 'index'])->name('users.index');
        Route::get('users/{user}', [AdminUserController::class, 'show'])->name('users.show');
        Route::get('users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
        Route::put('users/{user}', [AdminUserController::class, 'update'])->name('users.update');
        Route::delete('users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
        Route::post('users/{user}/loyalty', [AdminUserController::class, 'storeLoyalty'])->name('users.loyalty.store');
        Route::post('users/{user}/coupons', [AdminUserController::class, 'storeCoupon'])->name('users.coupons.store');
    });
