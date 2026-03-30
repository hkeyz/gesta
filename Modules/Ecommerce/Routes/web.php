<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'SetSessionData', 'language', 'timezone', 'AdminSidebarMenu'])
    ->prefix('ecommerce')
    ->group(function () {
        Route::get('/install', [Modules\Ecommerce\Http\Controllers\InstallController::class, 'index'])
            ->name('ecommerce.install');
        Route::get('/install/update', [Modules\Ecommerce\Http\Controllers\InstallController::class, 'update'])
            ->name('ecommerce.install.update');
        Route::get('/install/uninstall', [Modules\Ecommerce\Http\Controllers\InstallController::class, 'uninstall'])
            ->name('ecommerce.install.uninstall');

        Route::get('/settings', [Modules\Ecommerce\Http\Controllers\StoreController::class, 'edit'])
            ->name('ecommerce.settings');
        Route::put('/settings', [Modules\Ecommerce\Http\Controllers\StoreController::class, 'update'])
            ->name('ecommerce.settings.update');
    });

Route::prefix('shop/{store_slug}')->group(function () {
    Route::get('/', [Modules\Ecommerce\Http\Controllers\StorefrontController::class, 'index'])
        ->name('ecommerce.storefront.home');
    Route::get('/products', [Modules\Ecommerce\Http\Controllers\StorefrontController::class, 'catalog'])
        ->name('ecommerce.storefront.products');
    Route::get('/category/{category_slug}', [Modules\Ecommerce\Http\Controllers\StorefrontController::class, 'category'])
        ->name('ecommerce.storefront.category');
    Route::get('/product/{product_slug}', [Modules\Ecommerce\Http\Controllers\StorefrontController::class, 'show'])
        ->name('ecommerce.storefront.product');

    Route::get('/cart', [Modules\Ecommerce\Http\Controllers\CartController::class, 'show'])
        ->name('ecommerce.cart.show');
    Route::post('/cart/add', [Modules\Ecommerce\Http\Controllers\CartController::class, 'add'])
        ->name('ecommerce.cart.add');
    Route::post('/cart/update', [Modules\Ecommerce\Http\Controllers\CartController::class, 'update'])
        ->name('ecommerce.cart.update');
    Route::post('/cart/remove/{variation_id}', [Modules\Ecommerce\Http\Controllers\CartController::class, 'remove'])
        ->name('ecommerce.cart.remove');

    Route::get('/buy-now', [Modules\Ecommerce\Http\Controllers\CartController::class, 'buyNowRedirect'])
        ->name('ecommerce.buy_now');
    Route::post('/buy-now', [Modules\Ecommerce\Http\Controllers\CartController::class, 'buyNow'])
        ->name('ecommerce.buy_now.store');

    Route::get('/checkout', [Modules\Ecommerce\Http\Controllers\CheckoutController::class, 'show'])
        ->name('ecommerce.checkout.show');
    Route::post('/checkout', [Modules\Ecommerce\Http\Controllers\CheckoutController::class, 'store'])
        ->name('ecommerce.checkout.store');
    Route::get('/checkout/success/{token}', [Modules\Ecommerce\Http\Controllers\CheckoutController::class, 'success'])
        ->name('ecommerce.checkout.success');
    Route::get('/checkout/cancel/{token}', [Modules\Ecommerce\Http\Controllers\CheckoutController::class, 'cancel'])
        ->name('ecommerce.checkout.cancel');

    Route::middleware('ecom.guest')->group(function () {
        Route::get('/account/login', [Modules\Ecommerce\Http\Controllers\CustomerAuthController::class, 'showLogin'])
            ->name('ecommerce.account.login');
        Route::post('/account/login', [Modules\Ecommerce\Http\Controllers\CustomerAuthController::class, 'login'])
            ->name('ecommerce.account.login.store');
        Route::get('/account/register', [Modules\Ecommerce\Http\Controllers\CustomerAuthController::class, 'showRegister'])
            ->name('ecommerce.account.register');
        Route::post('/account/register', [Modules\Ecommerce\Http\Controllers\CustomerAuthController::class, 'register'])
            ->name('ecommerce.account.register.store');
    });

    Route::middleware('ecom.auth')->group(function () {
        Route::post('/account/logout', [Modules\Ecommerce\Http\Controllers\CustomerAuthController::class, 'logout'])
            ->name('ecommerce.account.logout');
        Route::get('/account', [Modules\Ecommerce\Http\Controllers\AccountController::class, 'orders'])
            ->name('ecommerce.account.index');
        Route::get('/account/orders', [Modules\Ecommerce\Http\Controllers\AccountController::class, 'orders'])
            ->name('ecommerce.account.orders');
        Route::get('/account/orders/{transaction}', [Modules\Ecommerce\Http\Controllers\AccountController::class, 'showOrder'])
            ->name('ecommerce.account.orders.show');
    });
});
