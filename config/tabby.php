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

    /*
    |--------------------------------------------------------------------------
    | API Base URL
    |--------------------------------------------------------------------------
    |
    | Saudi Arabia (KSA): https://api.tabby.sa/api/v2
    | UAE / Kuwait:       https://api.tabby.ai/api/v2
    |
    */
    'base_url' => env('TABBY_BASE_URL', 'https://api.tabby.sa/api/v2'),

    'success_url' => env('TABBY_SUCCESS_URL', ''),

    'failure_url' => env('TABBY_FAILURE_URL', ''),

    'cancel_url' => env('TABBY_CANCEL_URL', ''),

    'redirect_success_url' => env('TABBY_REDIRECT_SUCCESS_URL', ''),

    'redirect_error_url' => env('TABBY_REDIRECT_FAILURE_URL', ''),

    'redirect_cancel_url' => env('TABBY_REDIRECT_CANCEL_URL', ''),

    'currency' => env('TABBY_CURRENCY', 'SAR'),

    /*
    |--------------------------------------------------------------------------
    | Webhook Verification
    |--------------------------------------------------------------------------
    |
    | Tabby sends a custom static header on every webhook (NOT an HMAC).
    | The header name and value are set when registering the webhook via API.
    |
    | TABBY_WEBHOOK_VERIFY_SIGNATURE=true  → check header value
    | TABBY_WEBHOOK_HEADER                 → header name  (e.g. X-Tabby-Signature)
    | TABBY_WEBHOOK_SECRET                 → expected header value (your random secret)
    |
    */

    'webhook_verify_signature' => env('TABBY_WEBHOOK_VERIFY_SIGNATURE', false),

    'webhook_header' => env('TABBY_WEBHOOK_HEADER', 'X-Tabby-Signature'),

    'webhook_secret' => env('TABBY_WEBHOOK_SECRET', ''),
];
