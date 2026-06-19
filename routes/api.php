<?php

use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\MetaLeadWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/payment/{provider}', PaymentWebhookController::class)
    ->middleware('throttle:payment-webhooks')
    ->name('webhooks.payment');

Route::get('/webhooks/leads/meta', [MetaLeadWebhookController::class, 'verify'])
    ->middleware('throttle:meta-leads')
    ->name('webhooks.leads.meta.verify');

Route::post('/webhooks/leads/meta', [MetaLeadWebhookController::class, 'store'])
    ->middleware('throttle:meta-leads')
    ->name('webhooks.leads.meta');
