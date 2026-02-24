<?php

namespace MLQuarizm\PaymentGateway;

use MLQuarizm\PaymentGateway\Services\PaymentGatewayService;
use Illuminate\Support\ServiceProvider;

class PaymentGatewayServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(PaymentGatewayService::class, function ($app) {
            return new PaymentGatewayService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish config files
        $this->publishes([
            __DIR__ . '/../config/payment-gateway.php' => config_path('payment-gateway.php'),
            __DIR__ . '/../config/tabby.php' => config_path('tabby.php'),
            __DIR__ . '/../config/tamara.php' => config_path('tamara.php'),
        ], 'payment-gateway-config');

        // Publish migrations
        $this->publishes([
            __DIR__ . '/../database/migrations/create_payment_transactions_table.php' => database_path('migrations/' . date('Y_m_d_His') . '_create_payment_transactions_table.php'),
        ], 'payment-gateway-migrations');

        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        // Load package views (Blade: success, error, cancel)
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'payment-gateway');

        // Merge config
        $this->mergeConfigFrom(
            __DIR__ . '/../config/payment-gateway.php',
            'payment-gateway'
        );
    }
}
