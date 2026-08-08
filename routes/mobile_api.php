<?php

use App\MobileApi\V1\Http\Controllers\ActivityController;
use App\MobileApi\V1\Http\Controllers\AuthController;
use App\MobileApi\V1\Http\Controllers\BootstrapController;
use App\MobileApi\V1\Http\Controllers\CashRegisterController;
use App\MobileApi\V1\Http\Controllers\ContactController;
use App\MobileApi\V1\Http\Controllers\DashboardController;
use App\MobileApi\V1\Http\Controllers\InventoryController;
use App\MobileApi\V1\Http\Controllers\TransactionController;
use App\MobileApi\V1\Http\Middleware\EnsureMobileAccountIsActive;
use Illuminate\Support\Facades\Route;

Route::prefix('mobile/v1')
    ->name('mobile.v1.')
    ->group(function () {
        Route::post('auth/login', [AuthController::class, 'login'])
            ->middleware('throttle:10,1')
            ->name('auth.login');

        Route::middleware(['auth:api', EnsureMobileAccountIsActive::class])
            ->group(function () {
                Route::get('auth/me', [AuthController::class, 'me'])->name('auth.me');
                Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

                Route::get('bootstrap', BootstrapController::class)->name('bootstrap');
                Route::get('dashboard', DashboardController::class)->name('dashboard');
                Route::get('activities', ActivityController::class)->name('activities');

                Route::get('transactions', [TransactionController::class, 'index'])->name('transactions.index');
                Route::get('transactions/{transaction}', [TransactionController::class, 'show'])
                    ->whereNumber('transaction')
                    ->name('transactions.show');

                Route::get('sales', [TransactionController::class, 'sales'])->name('sales.index');
                Route::get('purchases', [TransactionController::class, 'purchases'])->name('purchases.index');
                Route::get('expenses', [TransactionController::class, 'expenses'])->name('expenses.index');

                Route::get('inventory/summary', [InventoryController::class, 'summary'])->name('inventory.summary');
                Route::get('inventory/categories', [InventoryController::class, 'categories'])->name('inventory.categories');
                Route::get('inventory/products', [InventoryController::class, 'products'])->name('inventory.products');
                Route::get('inventory/low-stock', [InventoryController::class, 'lowStock'])->name('inventory.low-stock');
                Route::get('inventory/products/{variation}', [InventoryController::class, 'show'])
                    ->whereNumber('variation')
                    ->name('inventory.show');

                Route::get('contacts', [ContactController::class, 'index'])->name('contacts.index');
                Route::get('contacts/{contact}', [ContactController::class, 'show'])
                    ->whereNumber('contact')
                    ->name('contacts.show');
                Route::get('cash-registers/open', [CashRegisterController::class, 'open'])->name('cash-registers.open');
                Route::get('cash-registers/{register}', [CashRegisterController::class, 'show'])
                    ->whereNumber('register')
                    ->name('cash-registers.show');
            });
    });
