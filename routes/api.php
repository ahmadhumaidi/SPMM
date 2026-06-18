<?php

use App\Http\Controllers\PaymentWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/payment/{provider}', PaymentWebhookController::class)
    ->middleware('throttle:payment-webhooks')
    ->name('webhooks.payment');
