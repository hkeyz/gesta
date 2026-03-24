<?php

use App\Http\Controllers\Install;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Installation Web Routes
|--------------------------------------------------------------------------
|
| Routes related to installation of the software
|
*/

Route::get('/install', [Install\InstallController::class, 'index']);
Route::get('/install-start', [Install\InstallController::class, 'index'])->name('install.index');
Route::get('/installer/check-server', [Install\InstallController::class, 'checkServer'])->name('install.checkServer');
Route::get('/installer/details', [Install\InstallController::class, 'details'])->name('install.details');
Route::post('/installer/post-details', [Install\InstallController::class, 'postDetails'])->name('install.postDetails');
Route::post('/installer/install-alternate', [Install\InstallController::class, 'installAlternate'])->name('install.installAlternate');
Route::get('/installer/success', [Install\InstallController::class, 'success'])->name('install.success');

Route::get('/installer/update', [Install\InstallController::class, 'updateConfirmation'])->name('install.updateConfirmation');
Route::post('/installer/update', [Install\InstallController::class, 'update'])->name('install.update');
