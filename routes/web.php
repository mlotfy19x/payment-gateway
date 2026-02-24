<?php

use Illuminate\Support\Facades\Route;
use MLQuarizm\PaymentGateway\Http\Controllers\PaymentCallbackController;
use MLQuarizm\PaymentGateway\Http\Controllers\PaymentStatusController;
use MLQuarizm\PaymentGateway\Http\Controllers\PaymentWebhookController;

/*
|--------------------------------------------------------------------------
| Payment Gateway Routes
|--------------------------------------------------------------------------
|
| Routes for payment gateway callbacks and webhooks
|
*/

// Payment Callbacks (unified endpoint; GET for user redirect from gateway, POST for server callbacks)
Route::match(['get', 'post'], 'payment/callback/{gateway}', [PaymentCallbackController::class, 'handle'])
    ->name('payment.callback');

// Payment status page (package Blade; redirect URL with order id from env)
Route::get('payment-gateway/status/{status}', [PaymentStatusController::class, 'show'])
    ->where('status', 'success|error|cancel')
    ->name('payment-gateway.status');

// Payment Webhooks
Route::post('webhooks/payment/{gateway}', [PaymentWebhookController::class, 'handle'])
    ->name('webhooks.payment');
