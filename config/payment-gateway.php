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
