<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Tamara Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Tamara payment gateway integration
    |
    */

    'sandbox_mode' => env('TAMARA_SANDBOX_MODE', true),

    'api_token' => env('TAMARA_API_TOKEN', ''),

    'notification_token' => env('TAMARA_NOTIFICATION_TOKEN', ''),

    'webhook_token' => env('TAMARA_WEBHOOK_TOKEN', ''),

    'success_url' => env('TAMARA_SUCCESS_URL', ''),

    'failure_url' => env('TAMARA_FAILURE_URL', ''),

    'cancel_url' => env('TAMARA_CANCEL_URL', ''),

    'redirect_success_url' => env('TAMARA_REDIRECT_SUCCESS_URL', ''),

    'redirect_error_url' => env('TAMARA_REDIRECT_FAILURE_URL', ''),

    'redirect_cancel_url' => env('TAMARA_REDIRECT_CANCEL_URL', ''),

    'default_payment_type' => env('TAMARA_DEFAULT_PAYMENT_TYPE', 'PAY_BY_INSTALMENTS'),

    'default_instalments' => env('TAMARA_DEFAULT_INSTALMENTS', 3),

    'currency' => env('TAMARA_CURRENCY', 'SAR'),

    'country_code' => env('TAMARA_COUNTRY_CODE', 'SA'),

    'locale' => env('TAMARA_LOCALE', 'ar_SA'),

    'public_key' => env('TAMARA_PUBLIC_KEY', ''),
];
