<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Payment Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | Main configuration file for the payment gateway package
    |
    */

    'default_gateway' => env('PAYMENT_DEFAULT_GATEWAY', 'tabby'),

    /*
    |--------------------------------------------------------------------------
    | Callback Redirect Fallback URL
    |--------------------------------------------------------------------------
    |
    | When gateway-specific redirect_success_url / redirect_error_url /
    | redirect_cancel_url are not set, the callback will redirect here with
    | ?status=success|error|cancel&gateway=tabby|tamara. Leave empty to
    | fall back to the application root (url('/')).
    |
    */

    'redirect_fallback_url' => env('PAYMENT_REDIRECT_FALLBACK_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | Redirect URL after status page (package Blade)
    |--------------------------------------------------------------------------
    |
    | URL the user is sent to after viewing the success/error/cancel Blade.
    | Use {order_id} as placeholder; it is replaced by the payable_id (e.g. order id).
    | Example: https://yourdomain.com/orders/{order_id}
    |
    */

    'redirect_after_status_url' => env('PAYMENT_REDIRECT_AFTER_STATUS_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | Fallback redirect when redirect_after_status_url is not set
    |--------------------------------------------------------------------------
    |
    | When redirect_after_status_url is empty, the Blade redirects here after 5s.
    | Use a public route (e.g. dashboard, home). Avoid auth/login routes.
    | Leave empty to use the application root (url('/')).
    |
    */

    'redirect_after_status_fallback_url' => env('PAYMENT_REDIRECT_AFTER_STATUS_FALLBACK_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | Payment Transaction Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for payment transactions
    |
    */

    'transaction' => [
        'table' => 'payment_transactions',
        'polymorphic' => true, // Use polymorphic relationship
    ],
];
