<?php

use Illuminate\Support\Facades\Route;

Route::post('/ecommerce/stripe/webhook/{store_slug}', [Modules\Ecommerce\Http\Controllers\WebhookController::class, 'stripe'])
    ->name('ecommerce.webhook.stripe');
