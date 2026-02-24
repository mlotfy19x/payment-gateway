# ML Payment Gateway Package

Laravel package for integrating Tabby and Tamara payment gateways.

> 📋 **For detailed architecture and development plan, see [PLAN.md](./PLAN.md)**

## Installation

### Via Composer (if published to Packagist)

```bash
composer require mlquarizm/payment-gateway
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
        "mlquarizm/payment-gateway": "dev-main"
    }
}
```

Then run:

```bash
composer require mlquarizm/payment-gateway:dev-main
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
        "mlquarizm/payment-gateway": "*"
    }
}
```

Then run:

```bash
composer require mlquarizm/payment-gateway
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

# Redirect URLs (where the user lands after we process the callback; e.g. your app's status page)
TABBY_REDIRECT_SUCCESS_URL=https://yourdomain.com/payment-status/success/ar
TABBY_REDIRECT_FAILURE_URL=https://yourdomain.com/payment-status/error/ar
TABBY_REDIRECT_CANCEL_URL=https://yourdomain.com/payment-status/cancel/ar
TAMARA_REDIRECT_SUCCESS_URL=https://yourdomain.com/payment-status/success/ar
TAMARA_REDIRECT_FAILURE_URL=https://yourdomain.com/payment-status/error/ar
TAMARA_REDIRECT_CANCEL_URL=https://yourdomain.com/payment-status/cancel/ar
```

**Callback vs redirect URLs**

- **TABBY_SUCCESS_URL / TAMARA_SUCCESS_URL (and failure/cancel):** Must point to **this project’s** callback route so the package can process the payment. Use the package route, e.g. `https://yourdomain.com/payment/callback/tabby` (same idea for Tamara). The gateway redirects the user here after payment; the package then processes the result and redirects the user again.
- **TABBY_REDIRECT_* / TAMARA_REDIRECT_*:** Where to send the user **after** the package has processed the callback (e.g. your `payment-status/{status}/{language}` page or frontend URL). If set, the callback returns a redirect to this URL instead of JSON.

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
use MLQuarizm\PaymentGateway\Factory\PaymentGatewayFactory;
use MLQuarizm\PaymentGateway\DTOs\TabbyPaymentDTO;
use MLQuarizm\PaymentGateway\DTOs\PaymentOrderDTO;
use MLQuarizm\PaymentGateway\DTOs\BuyerDTO;
use MLQuarizm\PaymentGateway\DTOs\AddressDTO;
use MLQuarizm\PaymentGateway\DTOs\OrderItemDTO;
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

// Record the transaction so callback/webhook can find and update it (required)
use MLQuarizm\PaymentGateway\Facades\PaymentGateway;
PaymentGateway::recordTransaction(
    $order,
    (string) $order->id,
    $paymentInfo['payment_id'] ?? null,
    'tabby',
    500.00,
    $paymentInfo
);

// Redirect to payment URL
return redirect($paymentInfo['url']);
```

**Important:** You must call `PaymentGateway::recordTransaction(...)` (or create a row with the package’s `PaymentTransaction` model) **after** `initiatePayment` and **before** redirecting the user to the gateway. Otherwise the callback/webhook will not find a transaction to update.

### Callback flow (same project, redirect like webhook)

The package callback (`GET` or `POST` to `payment/callback/{gateway}`) uses the same “pay or not” logic as the webhook: it finds the transaction by `track_id`/`payment_id`, updates status, and fires events. The only difference is the **response**:

- If `redirect_success_url` / `redirect_error_url` / `redirect_cancel_url` are set in config, the callback **redirects** the user to the matching URL (with `?status=...&gateway=...`). This matches the usual “return from gateway → process → show success/error/cancel page” flow.
- If those redirect URLs are not set, the callback returns JSON (for backward compatibility).

You can use your existing `payment-status/{status}/{language}` routes as the redirect URLs so the behaviour stays the same as in your main project.

### Using Builder Pattern

#### Single Item

```php
use MLQuarizm\PaymentGateway\Builders\TabbyPaymentDTOBuilder;
use MLQuarizm\PaymentGateway\Factory\PaymentGatewayFactory;

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

#### Multiple Items

You can add multiple items in two ways:

**Option 1: Using `item()` method multiple times**

```php
$tabbyDTO = TabbyPaymentDTOBuilder::new()
    ->order(...)
    ->buyer(...)
    ->shippingAddress(...)
    ->item(
        referenceId: "service-1",
        title: "Service 1",
        description: "Description 1",
        quantity: 1,
        unitPrice: 200.00
    )
    ->item(
        referenceId: "service-2",
        title: "Service 2",
        description: "Description 2",
        quantity: 2,
        unitPrice: 150.00
    )
    ->build();
```

**Option 2: Using `items()` method with array**

```php
use MLQuarizm\PaymentGateway\DTOs\OrderItemDTO;

$items = [
    new OrderItemDTO(
        referenceId: "service-1",
        title: "Service 1",
        description: "Description 1",
        quantity: 1,
        unitPrice: 200.00
    ),
    new OrderItemDTO(
        referenceId: "service-2",
        title: "Service 2",
        description: "Description 2",
        quantity: 2,
        unitPrice: 150.00
    ),
];

$tabbyDTO = TabbyPaymentDTOBuilder::new()
    ->order(...)
    ->buyer(...)
    ->shippingAddress(...)
    ->items($items)
    ->build();
```

**Option 3: Using `items()` with array of arrays**

```php
$items = [
    [
        'referenceId' => "service-1",
        'title' => "Service 1",
        'description' => "Description 1",
        'quantity' => 1,
        'unitPrice' => 200.00
    ],
    [
        'referenceId' => "service-2",
        'title' => "Service 2",
        'description' => "Description 2",
        'quantity' => 2,
        'unitPrice' => 150.00
    ],
];

$tabbyDTO = TabbyPaymentDTOBuilder::new()
    ->order(...)
    ->buyer(...)
    ->shippingAddress(...)
    ->items($items)
    ->build();
```

### Using Tamara

```php
use MLQuarizm\PaymentGateway\Builders\TamaraPaymentDTOBuilder;
use MLQuarizm\PaymentGateway\Factory\PaymentGatewayFactory;

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

// Record the transaction and redirect (same as Tabby)
PaymentGateway::recordTransaction($order, (string) $order->id, $paymentInfo['payment_id'] ?? null, 'tamara', 500.00, $paymentInfo);
return redirect($paymentInfo['url']);
```

#### Multiple Items

You can add multiple items using the same methods as Tabby:

**Using `item()` method multiple times:**

```php
$tamaraDTO = TamaraPaymentDTOBuilder::new()
    ->order(...)
    ->consumer(...)
    ->billingAddress(...)
    ->shippingAddress(...)
    ->item(
        referenceId: "service-1",
        type: 'Physical',
        name: "Service 1",
        sku: "SERVICE-1",
        unitPrice: 200.00,
        totalAmount: 200.00
    )
    ->item(
        referenceId: "service-2",
        type: 'Physical',
        name: "Service 2",
        sku: "SERVICE-2",
        unitPrice: 150.00,
        totalAmount: 300.00,
        quantity: 2
    )
    ->build();
```

**Using `items()` method with array:**

```php
use MLQuarizm\PaymentGateway\DTOs\TamaraOrderItemDTO;

$items = [
    new TamaraOrderItemDTO(
        referenceId: "service-1",
        type: 'Physical',
        name: "Service 1",
        sku: "SERVICE-1",
        unitPrice: 200.00,
        totalAmount: 200.00
    ),
    new TamaraOrderItemDTO(
        referenceId: "service-2",
        type: 'Physical',
        name: "Service 2",
        sku: "SERVICE-2",
        unitPrice: 150.00,
        totalAmount: 300.00,
        quantity: 2
    ),
];

$tamaraDTO = TamaraPaymentDTOBuilder::new()
    ->order(...)
    ->consumer(...)
    ->billingAddress(...)
    ->shippingAddress(...)
    ->items($items)
    ->build();
```

## Handling Payment Events

The package uses Laravel Events to handle payment events. This allows you to listen to payment events in your application without modifying the package code.

### Available Events

The package dispatches the following events:

- **`MLQuarizm\PaymentGateway\Events\PaymentSuccess`** - Dispatched when a payment is successful
- **`MLQuarizm\PaymentGateway\Events\PaymentFailed`** - Dispatched when a payment fails
- **`MLQuarizm\PaymentGateway\Events\PaymentCancelled`** - Dispatched when a payment is cancelled
- **`MLQuarizm\PaymentGateway\Events\PaymentPending`** - Dispatched when a payment is pending

### Listening to Events

Register event listeners in your `app/Providers/EventServiceProvider.php`:

```php
use MLQuarizm\PaymentGateway\Events\PaymentSuccess;
use MLQuarizm\PaymentGateway\Events\PaymentFailed;
use MLQuarizm\PaymentGateway\Events\PaymentCancelled;
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

use MLQuarizm\PaymentGateway\Events\PaymentSuccess;
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
use MLQuarizm\PaymentGateway\Events\PaymentSuccess;
use MLQuarizm\PaymentGateway\Events\PaymentFailed;

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

## How to Use Callback and Webhook

The package handles payment results in two ways: **callback** (when the user is sent back to your site after paying) and **webhook** (when the gateway sends a server-to-server request). Both use the same logic to update the transaction and fire events; only the entry point and response differ.

### Callback (user redirect)

**What it is:** After the user completes or cancels payment on Tabby/Tamara, the gateway redirects the user to a URL you provide. That URL must be your app so the package can process the result and then send the user to your success/error/cancel page.

**How to use it:**

1. **Register the callback URL with the gateway**  
   In Tabby/Tamara merchant settings (and in your `.env`), set:
   - Success URL: `https://yourdomain.com/payment/callback/tabby` (or `.../payment/callback/tamara`)
   - Failure URL: same path
   - Cancel URL: same path  
   The package uses one route and decides success/failure/cancel from the request data.

2. **Exclude the callback from CSRF**  
   In `app/Http/Middleware/VerifyCsrfToken.php` add:
   ```php
   protected $except = [
       'payment/callback/*',
       'webhooks/payment/*',
   ];
   ```

3. **Set redirect URLs in config**  
   So the user is sent to your status page after processing, set in `.env` (or config):
   - `TABBY_REDIRECT_SUCCESS_URL`, `TABBY_REDIRECT_FAILURE_URL`, `TABBY_REDIRECT_CANCEL_URL`
   - `TAMARA_REDIRECT_SUCCESS_URL`, etc.  
   Example: `https://yourdomain.com/payment-status/success/ar`.  
   If these are set, the callback **redirects** the user to the right URL (with `?status=...&gateway=...`). If not set, the callback returns JSON.

4. **Flow**  
   User finishes on gateway → gateway redirects to `GET /payment/callback/{gateway}` (with query params) → package runs `HandlePaymentAction`, updates `PaymentTransaction`, fires events → package redirects to your redirect_success_url / redirect_error_url / redirect_cancel_url.

**Route:** `GET|POST /payment/callback/{gateway}` (e.g. `payment/callback/tabby`, `payment/callback/tamara`).

### Webhook (server-to-server)

**What it is:** Tabby/Tamara send an HTTP POST to your server to notify payment status. No user is in the browser; the gateway calls your URL directly.

**How to use it:**

1. **Register the webhook URL with the gateway**  
   In Tabby/Tamara merchant/dashboard settings, set the webhook URL to:
   - `https://yourdomain.com/webhooks/payment/tabby`
   - `https://yourdomain.com/webhooks/payment/tamara`

2. **Exclude the webhook from CSRF**  
   Same as above: add `webhooks/payment/*` to `VerifyCsrfToken::$except`.

3. **Configure verification (recommended)**  
   - **Tamara:** set `TAMARA_NOTIFICATION_TOKEN` in `.env`; the package verifies the JWT.
   - **Tabby:** set `TABBY_SECRET_KEY` and optionally `TABBY_WEBHOOK_VERIFY_SIGNATURE=true` for HMAC verification.

4. **Flow**  
   Gateway sends `POST /webhooks/payment/{gateway}` → package verifies signature/token → runs same `HandlePaymentAction`, updates transaction, fires same events → returns 200 so the gateway does not retry.

**Route:** `POST /webhooks/payment/{gateway}`.

### Summary: callback vs webhook

| | Callback | Webhook |
|---|----------|--------|
| **Who calls** | User’s browser (redirect from gateway) | Gateway’s server |
| **Method** | GET (or POST) | POST |
| **Route** | `/payment/callback/{gateway}` | `/webhooks/payment/{gateway}` |
| **Response** | Redirect to your redirect_*_url or JSON | Always 200 (body not used by gateway) |
| **Logic** | Same: find transaction, update status, fire events | Same |

### Reacting to payment result (both callback and webhook)

Listen to package events; they are fired for both callback and webhook:

```php
use Illuminate\Support\Facades\Event;
use MLQuarizm\PaymentGateway\Events\PaymentSuccess;
use MLQuarizm\PaymentGateway\Events\PaymentFailed;
use MLQuarizm\PaymentGateway\Events\PaymentCancelled;
use MLQuarizm\PaymentGateway\Events\PaymentPending;

// In a service provider or dedicated listener class
Event::listen(PaymentSuccess::class, function (PaymentSuccess $event) {
    $transaction = $event->transaction;
    // Update order, send notification, etc.
});

Event::listen(PaymentFailed::class, function (PaymentFailed $event) {
    $transaction = $event->transaction;
    $reason = $event->reason;
});

Event::listen(PaymentCancelled::class, function (PaymentCancelled $event) {
    $transaction = $event->transaction;
});
```

## Routes

The package automatically registers:

- `GET|POST /payment/callback/{gateway}` – Callback: user redirect from gateway; redirects to redirect_*_url when configured.
- `POST /webhooks/payment/{gateway}` – Webhook: gateway server-to-server notification.

## Webhook Signature Verification

The package includes built-in webhook signature verification for security. All webhooks are automatically verified before processing.

### Tamara Webhook Verification

Tamara uses JWT tokens for webhook verification. The token can be provided in:
- Query parameter: `?tamaraToken=...`
- Authorization header: `Bearer <token>`

The package verifies:
- JWT token format (header.payload.signature)
- Signature using HMAC-SHA256 with `notification_token`
- Token expiration (`exp` claim)

**Configuration:**
```env
TAMARA_NOTIFICATION_TOKEN=your_notification_token
```

### Tabby Webhook Verification

Tabby uses HMAC-SHA256 signature verification. The signature is expected in:
- `X-Tabby-Signature` header (preferred)
- `X-Signature` header (fallback)
- `Signature` header (fallback)

**Configuration:**
```env
TABBY_SECRET_KEY=your_secret_key
TABBY_WEBHOOK_VERIFY_SIGNATURE=true  # Enable strict signature verification
```

**Note:** By default, Tabby webhook signature verification is disabled (`TABBY_WEBHOOK_VERIFY_SIGNATURE=false`) to maintain backward compatibility. Enable it when you're ready to enforce signature verification.

### How It Works

1. Webhook request arrives at `POST /webhooks/payment/{gateway}`
2. `WebhookVerificationService` verifies the signature/token
3. If verification fails, the request is rejected (but returns 200 to prevent gateway retries)
4. If verification succeeds, the request is processed normally

### Custom Verification

You can extend `WebhookVerificationService` to add custom verification logic for other gateways or modify existing verification methods.

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
