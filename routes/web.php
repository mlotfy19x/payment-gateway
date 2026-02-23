<?php

use Illuminate\Support\Facades\Route;
use MLQuarizm\PaymentGateway\Http\Controllers\PaymentCallbackController;
use MLQuarizm\PaymentGateway\Http\Controllers\PaymentWebhookController;

/*
|--------------------------------------------------------------------------
| Payment Gateway Routes
|--------------------------------------------------------------------------
|
| Routes for payment gateway callbacks and webhooks
|
*/

// Payment Callbacks (unified endpoint)
Route::post('payment/callback/{gateway}', [PaymentCallbackController::class, 'handle'])
    ->name('payment.callback');

// Payment Webhooks
Route::post('webhooks/payment/{gateway}', [PaymentWebhookController::class, 'handle'])
    ->name('webhooks.payment');
