# ML Payment Gateway Package

Laravel package for integrating Tabby and Tamara payment gateways.

> 📋 **For detailed architecture and development plan, see [PLAN.md](./PLAN.md)**

## Installation

### Via Composer (if published to Packagist)

```bash
composer require ml/payment-gateway
```

### Via Git Repository (Private Package)

Add to your `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/your-org/ml-payment-gateway.git"
        }
    ],
    "require": {
        "ml/payment-gateway": "dev-main"
    }
}
```

Then run:

```bash
composer require ml/payment-gateway:dev-main
```

### Via Local Path (Development)

Add to your `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "./packages/ML/PaymentGateway"
        }
    ],
    "require": {
        "ml/payment-gateway": "*"
    }
}
```

Then run:

```bash
composer require ml/payment-gateway
```

## Configuration

Publish the configuration files:

```bash
php artisan vendor:publish --tag=payment-gateway-config
php artisan vendor:publish --tag=payment-gateway-migrations
```

Run migrations:

```bash
php artisan migrate
```

## CSRF Configuration

Since payment callbacks and webhooks come from external sources, you need to exclude them from CSRF verification.

Add the following routes to your `app/Http/Middleware/VerifyCsrfToken.php`:

```php
<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'payment/callback/*',    // Payment callbacks
        'webhooks/payment/*',    // Payment webhooks
    ];
}
```

## Environment Variables

Add to your `.env` file:

```env
# Tabby
TABBY_SANDBOX_MODE=true
TABBY_SECRET_KEY=your_secret_key
TABBY_PUBLIC_KEY=your_public_key
TABBY_MERCHANT_CODE=your_merchant_code
TABBY_SUCCESS_URL=https://yourdomain.com/payment/callback/tabby?status=success
TABBY_FAILURE_URL=https://yourdomain.com/payment/callback/tabby?status=failure
TABBY_CANCEL_URL=https://yourdomain.com/payment/callback/tabby?status=cancel

# Tamara
TAMARA_SANDBOX_MODE=true
TAMARA_API_TOKEN=your_api_token
TAMARA_NOTIFICATION_TOKEN=your_notification_token
TAMARA_SUCCESS_URL=https://yourdomain.com/payment/callback/tamara?status=success
TAMARA_FAILURE_URL=https://yourdomain.com/payment/callback/tamara?status=failure
TAMARA_CANCEL_URL=https://yourdomain.com/payment/callback/tamara?status=cancel
```

## Configuration Files

The package includes three configuration files that you can customize after publishing:

### 1. `config/payment-gateway.php` (Main Configuration)

This is the main configuration file for the package:

```php
return [
    // Default payment gateway to use when not specified
    'default_gateway' => env('PAYMENT_DEFAULT_GATEWAY', 'tabby'),

    // Callbacks configuration for payment events
    'callbacks' => [
        'tabby' => [
            'on_success' => null, // Callable: fn($transaction) => void
            'on_failure' => null, // Callable: fn($transaction, $reason) => void
        ],
        'tamara' => [
            'on_success' => null, // Callable: fn($transaction) => void
            'on_failure' => null, // Callable: fn($transaction, $reason) => void
        ],
    ],

    // Payment transaction configuration
    'transaction' => [
        'table' => 'payment_transactions', // Database table name
        'polymorphic' => true, // Enable polymorphic relationships
    ],
];
```

**Configuration Options:**

- **`default_gateway`**: The default payment gateway to use when not explicitly specified. Options: `'tabby'` or `'tamara'`.

- **`transaction`**: Configuration for payment transactions:
  - `table`: Database table name for storing payment transactions.
  - `polymorphic`: Enable polymorphic relationships to link transactions to different models (Order, Invoice, etc.).

### 2. `config/tabby.php` (Tabby Gateway Configuration)

Configuration specific to Tabby payment gateway:

```php
return [
    // Enable/disable sandbox mode (true for testing, false for production)
    'sandbox_mode' => env('TABBY_SANDBOX_MODE', true),

    // Tabby API credentials
    'secret_key' => env('TABBY_SECRET_KEY', ''),
    'public_key' => env('TABBY_PUBLIC_KEY', ''),
    'merchant_code' => env('TABBY_MERCHANT_CODE', ''),

    // Callback URLs (where Tabby redirects after payment)
    'success_url' => env('TABBY_SUCCESS_URL', ''),
    'failure_url' => env('TABBY_FAILURE_URL', ''),
    'cancel_url' => env('TABBY_CANCEL_URL', ''),

    // Redirect URLs (where to redirect user after processing callback)
    'redirect_success_url' => env('TABBY_REDIRECT_SUCCESS_URL', ''),
    'redirect_error_url' => env('TABBY_REDIRECT_FAILURE_URL', ''),
    'redirect_cancel_url' => env('TABBY_REDIRECT_CANCEL_URL', ''),

    // Currency code (default: SAR)
    'currency' => env('TABBY_CURRENCY', 'SAR'),
];
```

**Configuration Options:**

- **`sandbox_mode`**: Set to `true` for testing, `false` for production. When enabled, uses Tabby's sandbox environment.

- **API Credentials:**
  - `secret_key`: Your Tabby secret key (obtained from Tabby dashboard).
  - `public_key`: Your Tabby public key.
  - `merchant_code`: Your merchant code.

- **Callback URLs:** URLs where Tabby sends payment status updates:
  - `success_url`: Called when payment succeeds.
  - `failure_url`: Called when payment fails.
  - `cancel_url`: Called when user cancels payment.

- **Redirect URLs:** URLs where users are redirected after processing the callback:
  - `redirect_success_url`: User redirect after successful payment.
  - `redirect_error_url`: User redirect after failed payment.
  - `redirect_cancel_url`: User redirect after cancelled payment.

- **`currency`**: Currency code (ISO 4217). Default: `'SAR'` (Saudi Riyal).

### 3. `config/tamara.php` (Tamara Gateway Configuration)

Configuration specific to Tamara payment gateway:

```php
return [
    // Enable/disable sandbox mode (true for testing, false for production)
    'sandbox_mode' => env('TAMARA_SANDBOX_MODE', true),

    // Tamara API credentials
    'api_token' => env('TAMARA_API_TOKEN', ''),
    'notification_token' => env('TAMARA_NOTIFICATION_TOKEN', ''),
    'webhook_token' => env('TAMARA_WEBHOOK_TOKEN', ''),
    'public_key' => env('TAMARA_PUBLIC_KEY', ''),

    // Callback URLs (where Tamara redirects after payment)
    'success_url' => env('TAMARA_SUCCESS_URL', ''),
    'failure_url' => env('TAMARA_FAILURE_URL', ''),
    'cancel_url' => env('TAMARA_CANCEL_URL', ''),

    // Redirect URLs (where to redirect user after processing callback)
    'redirect_success_url' => env('TAMARA_REDIRECT_SUCCESS_URL', ''),
    'redirect_error_url' => env('TAMARA_REDIRECT_FAILURE_URL', ''),
    'redirect_cancel_url' => env('TAMARA_REDIRECT_CANCEL_URL', ''),

    // Payment options
    'default_payment_type' => env('TAMARA_DEFAULT_PAYMENT_TYPE', 'PAY_BY_INSTALMENTS'),
    'default_instalments' => env('TAMARA_DEFAULT_INSTALMENTS', 3),

    // Localization
    'currency' => env('TAMARA_CURRENCY', 'SAR'),
    'country_code' => env('TAMARA_COUNTRY_CODE', 'SA'),
    'locale' => env('TAMARA_LOCALE', 'ar_SA'),
];
```

**Configuration Options:**

- **`sandbox_mode`**: Set to `true` for testing, `false` for production. When enabled, uses Tamara's sandbox environment.

- **API Credentials:**
  - `api_token`: Your Tamara API token (obtained from Tamara dashboard).
  - `notification_token`: Token for verifying notifications.
  - `webhook_token`: Token for verifying webhook requests.
  - `public_key`: Your Tamara public key.

- **Callback URLs:** URLs where Tamara sends payment status updates:
  - `success_url`: Called when payment succeeds.
  - `failure_url`: Called when payment fails.
  - `cancel_url`: Called when user cancels payment.

- **Redirect URLs:** URLs where users are redirected after processing the callback:
  - `redirect_success_url`: User redirect after successful payment.
  - `redirect_error_url`: User redirect after failed payment.
  - `redirect_cancel_url`: User redirect after cancelled payment.

- **Payment Options:**
  - `default_payment_type`: Default payment type. Options: `'PAY_BY_INSTALMENTS'`, `'PAY_LATER'`, etc.
  - `default_instalments`: Default number of instalments (typically 3, 6, or 12).

- **Localization:**
  - `currency`: Currency code (ISO 4217). Default: `'SAR'`.
  - `country_code`: Country code (ISO 3166-1 alpha-2). Default: `'SA'` (Saudi Arabia).
  - `locale`: Locale code. Default: `'ar_SA'` (Arabic - Saudi Arabia).

## Usage

### Basic Usage with Tabby

```php
use ML\PaymentGateway\Factory\PaymentGatewayFactory;
use ML\PaymentGateway\DTOs\TabbyPaymentDTO;
use ML\PaymentGateway\DTOs\PaymentOrderDTO;
use ML\PaymentGateway\DTOs\BuyerDTO;
use ML\PaymentGateway\DTOs\AddressDTO;
use ML\PaymentGateway\DTOs\OrderItemDTO;
use ML\PaymentGateway\Models\PaymentTransaction;

// Build DTOs
$orderDTO = new PaymentOrderDTO(
    id: $order->id,
    referenceId: (string) $order->id,
    amount: 500.00,
    currency: 'SAR',
    description: "Order #{$order->id}"
);

$buyerDTO = new BuyerDTO(
    name: $client->name,
    email: $client->email,
    phone: $client->full_phone
);

$addressDTO = new AddressDTO(
    city: $order->city->name,
    address: $order->address,
    zip: $order->postal_code,
    countryCode: 'SA'
);

$itemDTO = new OrderItemDTO(
    referenceId: "service-{$order->service->id}",
    title: $order->service->name,
    description: $order->service->description,
    quantity: 1,
    unitPrice: 500.00
);

$tabbyDTO = new TabbyPaymentDTO(
    order: $orderDTO,
    buyer: $buyerDTO,
    shippingAddress: $addressDTO,
    items: [$itemDTO]
);

// Initiate payment
$factory = new PaymentGatewayFactory();
$gateway = $factory->make('tabby');
$paymentInfo = $gateway->initiatePayment($tabbyDTO);

// Create transaction record
PaymentTransaction::create([
    'payable_type' => Order::class,
    'payable_id' => $order->id,
    'track_id' => (string) $order->id,
    'payment_id' => $paymentInfo['payment_id'],
    'payment_gateway' => 'tabby',
    'amount' => 500.00,
    'status' => 'pending',
    'response' => $paymentInfo,
]);

// Redirect to payment URL
return redirect($paymentInfo['url']);
```

### Using Builder Pattern

```php
use ML\PaymentGateway\Builders\TabbyPaymentDTOBuilder;
use ML\PaymentGateway\Factory\PaymentGatewayFactory;

$tabbyDTO = TabbyPaymentDTOBuilder::new()
    ->order(
        id: $order->id,
        referenceId: (string) $order->id,
        amount: 500.00,
        currency: 'SAR',
        description: "Order #{$order->id}"
    )
    ->buyer(
        name: $client->name,
        email: $client->email,
        phone: $client->full_phone
    )
    ->shippingAddress(
        city: $order->city->name,
        address: $order->address,
        zip: $order->postal_code,
        countryCode: 'SA'
    )
    ->item(
        referenceId: "service-{$order->service->id}",
        title: $order->service->name,
        description: $order->service->description,
        quantity: 1,
        unitPrice: 500.00
    )
    ->build();

$factory = new PaymentGatewayFactory();
$gateway = $factory->make('tabby');
$paymentInfo = $gateway->initiatePayment($tabbyDTO);
```

### Using Tamara

```php
use ML\PaymentGateway\Builders\TamaraPaymentDTOBuilder;
use ML\PaymentGateway\Factory\PaymentGatewayFactory;

$tamaraDTO = TamaraPaymentDTOBuilder::new()
    ->order(
        id: $order->id,
        referenceId: (string) $order->id,
        amount: 500.00,
        currency: 'SAR',
        description: "Order #{$order->id}"
    )
    ->consumer(
        firstName: $client->name,
        lastName: '',
        phoneNumber: $client->full_phone,
        email: $client->email,
        dateOfBirth: '1990-01-01'
    )
    ->billingAddress(
        city: $order->city->name,
        line1: $order->address,
        zip: $order->postal_code,
        countryCode: 'SA'
    )
    ->shippingAddress(
        city: $order->city->name,
        line1: $order->address,
        zip: $order->postal_code,
        countryCode: 'SA'
    )
    ->item(
        referenceId: "service-{$order->service->id}",
        type: 'Physical',
        name: $order->service->name,
        sku: "SERVICE-{$order->service->id}",
        unitPrice: 500.00,
        totalAmount: 500.00
    )
    ->build();

$factory = new PaymentGatewayFactory();
$gateway = $factory->make('tamara');
$paymentInfo = $gateway->initiatePayment($tamaraDTO);
```

## Handling Payment Events

The package uses Laravel Events to handle payment events. This allows you to listen to payment events in your application without modifying the package code.

### Available Events

The package dispatches the following events:

- **`ML\PaymentGateway\Events\PaymentSuccess`** - Dispatched when a payment is successful
- **`ML\PaymentGateway\Events\PaymentFailed`** - Dispatched when a payment fails
- **`ML\PaymentGateway\Events\PaymentCancelled`** - Dispatched when a payment is cancelled
- **`ML\PaymentGateway\Events\PaymentPending`** - Dispatched when a payment is pending

### Listening to Events

Register event listeners in your `app/Providers/EventServiceProvider.php`:

```php
use ML\PaymentGateway\Events\PaymentSuccess;
use ML\PaymentGateway\Events\PaymentFailed;
use ML\PaymentGateway\Events\PaymentCancelled;
use App\Listeners\HandlePaymentSuccess;
use App\Listeners\HandlePaymentFailure;
use App\Listeners\HandlePaymentCancellation;

protected $listen = [
    PaymentSuccess::class => [
        HandlePaymentSuccess::class,
    ],
    PaymentFailed::class => [
        HandlePaymentFailure::class,
    ],
    PaymentCancelled::class => [
        HandlePaymentCancellation::class,
    ],
];
```

### Creating Event Listeners

Create listeners using Artisan:

```bash
php artisan make:listener HandlePaymentSuccess
php artisan make:listener HandlePaymentFailure
php artisan make:listener HandlePaymentCancellation
```

Example listener:

```php
<?php

namespace App\Listeners;

use ML\PaymentGateway\Events\PaymentSuccess;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class HandlePaymentSuccess implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(PaymentSuccess $event): void
    {
        $transaction = $event->transaction;
        $order = $transaction->payable; // Your Order model or any payable model
        
        // Update order status
        $order->update(['payment_status' => 'paid']);
        
        // Send notifications
        $order->client->notify(new PaymentSuccessfulNotification($order));
        
        // Dispatch jobs, etc.
        dispatch(new ProcessSuccessfulPaymentJob($order));
    }
}
```

### Using Closures (Alternative)

You can also use closures in `EventServiceProvider`:

```php
use Illuminate\Support\Facades\Event;
use ML\PaymentGateway\Events\PaymentSuccess;
use ML\PaymentGateway\Events\PaymentFailed;

public function boot(): void
{
    Event::listen(PaymentSuccess::class, function (PaymentSuccess $event) {
        $transaction = $event->transaction;
        $order = $transaction->payable;
        
        $order->update(['payment_status' => 'paid']);
        // Handle success...
    });

    Event::listen(PaymentFailed::class, function (PaymentFailed $event) {
        $transaction = $event->transaction;
        $reason = $event->reason;
        
        // Log failure, notify admin, etc.
        Log::error('Payment failed', [
            'transaction_id' => $transaction->id,
            'reason' => $reason
        ]);
    });
}
```

## Routes

The package automatically registers the following routes:

- `POST /payment/callback/{gateway}` - Unified callback endpoint
- `POST /webhooks/payment/{gateway}` - Webhook endpoint

## Payment Transaction Model

The `PaymentTransaction` model uses polymorphic relationships, so it can be associated with any model:

```php
PaymentTransaction::create([
    'payable_type' => Order::class, // or any model
    'payable_id' => $order->id,
    'track_id' => (string) $order->id,
    'payment_id' => $paymentInfo['payment_id'],
    'payment_gateway' => 'tabby',
    'amount' => 500.00,
    'status' => 'pending',
]);
```

## License

MIT
