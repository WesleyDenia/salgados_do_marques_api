<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ContentHomeController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\LoyaltyRewardController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\StoreController;

Route::get('/', function () {
    return view('welcome');
});

Route::view('/privacidade', 'privacy')->name('privacy');

Route::get('/admin/login', [AdminAuthController::class, 'showLoginForm'])
    ->middleware('guest')
    ->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'login'])
    ->middleware('guest')
    ->name('admin.login.post');

Route::middleware('auth')
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', [AdminAuthController::class, 'dashboard'])->name('dashboard');
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

        Route::resource('content-home', ContentHomeController::class)->except('show');
        Route::resource('categories', CategoryController::class)->except('show');
        Route::resource('products', ProductController::class)->except('show');
        Route::resource('coupons', CouponController::class)->except('show');
        Route::resource('loyalty-rewards', LoyaltyRewardController::class)->except('show');
        Route::resource('stores', StoreController::class)->except('show');
        Route::resource('settings', AdminSettingController::class)->only(['index', 'create', 'store', 'edit', 'update']);
    });
