<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Tabby Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Tabby payment gateway integration
    |
    */

    'sandbox_mode' => env('TABBY_SANDBOX_MODE', true),

    'secret_key' => env('TABBY_SECRET_KEY', ''),

    'public_key' => env('TABBY_PUBLIC_KEY', ''),

    'merchant_code' => env('TABBY_MERCHANT_CODE', ''),

    'success_url' => env('TABBY_SUCCESS_URL', ''),

    'failure_url' => env('TABBY_FAILURE_URL', ''),

    'cancel_url' => env('TABBY_CANCEL_URL', ''),

    'redirect_success_url' => env('TABBY_REDIRECT_SUCCESS_URL', ''),

    'redirect_error_url' => env('TABBY_REDIRECT_FAILURE_URL', ''),

    'redirect_cancel_url' => env('TABBY_REDIRECT_CANCEL_URL', ''),

    'currency' => env('TABBY_CURRENCY', 'SAR'),
];
