<?php

use Illuminate\Support\Facades\Route;
use Modules\MobileMoney\Http\Controllers\CommissionRuleController;
use Modules\MobileMoney\Http\Controllers\InstallController;
use Modules\MobileMoney\Http\Controllers\OperatorController;
use Modules\MobileMoney\Http\Controllers\ReportController;
use Modules\MobileMoney\Http\Controllers\SettingsController;
use Modules\MobileMoney\Http\Controllers\TransactionController;

Route::middleware(['web', 'auth', 'SetSessionData', 'language', 'timezone', 'AdminSidebarMenu'])
    ->prefix('mobile-money')
    ->group(function () {
        Route::get('/install', [InstallController::class, 'index'])->name('mobilemoney.install');
        Route::get('/install/update', [InstallController::class, 'update'])->name('mobilemoney.install.update');
        Route::get('/install/uninstall', [InstallController::class, 'uninstall'])->name('mobilemoney.install.uninstall');

        Route::get('/settings', [SettingsController::class, 'edit'])->name('mobilemoney.settings.edit');
        Route::put('/settings', [SettingsController::class, 'update'])->name('mobilemoney.settings.update');

        Route::get('/operators', [OperatorController::class, 'index'])->name('mobilemoney.operators.index');
        Route::post('/operators', [OperatorController::class, 'store'])->name('mobilemoney.operators.store');
        Route::put('/operators/{operator}', [OperatorController::class, 'update'])->name('mobilemoney.operators.update');

        Route::get('/commission-rules', [CommissionRuleController::class, 'index'])->name('mobilemoney.commission_rules.index');
        Route::post('/commission-rules', [CommissionRuleController::class, 'store'])->name('mobilemoney.commission_rules.store');
        Route::put('/commission-rules/{rule}', [CommissionRuleController::class, 'update'])->name('mobilemoney.commission_rules.update');
        Route::delete('/commission-rules/{rule}', [CommissionRuleController::class, 'destroy'])->name('mobilemoney.commission_rules.destroy');

        Route::get('/transactions', [TransactionController::class, 'index'])->name('mobilemoney.transactions.index');
        Route::get('/transactions/create/{type?}', [TransactionController::class, 'create'])->name('mobilemoney.transactions.create');
        Route::get('/transactions/preview-commission', [TransactionController::class, 'previewCommission'])->name('mobilemoney.transactions.preview_commission');
        Route::post('/transactions', [TransactionController::class, 'store'])->name('mobilemoney.transactions.store');
        Route::get('/transactions/{transaction}', [TransactionController::class, 'show'])->name('mobilemoney.transactions.show');
        Route::post('/transactions/{transaction}/cancel', [TransactionController::class, 'cancel'])->name('mobilemoney.transactions.cancel');

        Route::get('/reports', [ReportController::class, 'index'])->name('mobilemoney.reports.index');
    });
