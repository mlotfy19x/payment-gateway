# ML Payment Gateway Package - Development Plan

## 📋 Overview

This document outlines the complete development plan and architecture of the ML Payment Gateway Package. This package extracts payment gateway functionality (Tabby and Tamara) from the main application into a reusable Laravel package.

## 🎯 Goals

1. **Extract payment gateway logic** into a standalone, reusable package
2. **Remove dependencies** on application-specific models (`Order`, `Client`)
3. **Use DTOs (Data Transfer Objects)** for type-safe data handling
4. **Implement Events** instead of callbacks for payment event handling
5. **Support polymorphic relationships** for payment transactions
6. **Provide unified callback/webhook handling** mechanism

## 🏗️ Package Structure

```
packages/ML/PaymentGateway/
├── config/
│   ├── payment-gateway.php    # Main configuration
│   ├── tabby.php               # Tabby gateway configuration
│   └── tamara.php              # Tamara gateway configuration
├── database/
│   └── migrations/
│       └── create_payment_transactions_table.php
├── routes/
│   └── web.php                 # Package routes (callbacks & webhooks)
├── src/
│   ├── Actions/
│   │   └── HandlePaymentAction.php
│   ├── Builders/
│   │   ├── TabbyPaymentDTOBuilder.php
│   │   └── TamaraPaymentDTOBuilder.php
│   ├── Contracts/
│   │   └── PaymentGatewayInterface.php
│   ├── DTOs/
│   │   ├── AddressDTO.php
│   │   ├── BuyerDTO.php
│   │   ├── BuyerHistoryDTO.php
│   │   ├── ConsumerDTO.php
│   │   ├── OrderHistoryDTO.php
│   │   ├── OrderItemDTO.php
│   │   ├── PaymentOrderDTO.php
│   │   ├── TabbyPaymentDTO.php
│   │   ├── TamaraOrderItemDTO.php
│   │   └── TamaraPaymentDTO.php
│   ├── Enums/
│   │   └── PaymentStatusEnum.php
│   ├── Events/
│   │   ├── PaymentSuccess.php
│   │   ├── PaymentFailed.php
│   │   ├── PaymentCancelled.php
│   │   └── PaymentPending.php
│   ├── Factory/
│   │   └── PaymentGatewayFactory.php
│   ├── Gateways/
│   │   ├── TabbyPaymentService.php
│   │   └── TamaraPaymentService.php
│   ├── Handlers/
│   │   └── PaymentCallbackHandler.php
│   ├── Http/
│   │   └── Controllers/
│   │       ├── PaymentCallbackController.php
│   │       └── PaymentWebhookController.php
│   ├── Models/
│   │   └── PaymentTransaction.php
│   └── PaymentGatewayServiceProvider.php
├── composer.json
├── README.md
├── PLAN.md (this file)
└── LICENSE
```

## 🔑 Key Design Decisions

### 1. DTOs Instead of Models

**Decision:** Use Data Transfer Objects (DTOs) instead of direct model dependencies.

**Reason:** 
- Removes tight coupling to application-specific models (`Order`, `Client`)
- Provides type safety and validation
- Gives users full control over data structure
- Makes the package truly reusable across different applications

**Implementation:**
- Base DTOs: `PaymentOrderDTO`, `BuyerDTO`, `AddressDTO`, `OrderItemDTO`
- Gateway-specific DTOs: `TabbyPaymentDTO`, `TamaraPaymentDTO`
- Optional Builders: `TabbyPaymentDTOBuilder`, `TamaraPaymentDTOBuilder`

### 2. Events Instead of Callbacks

**Decision:** Use Laravel Events instead of callbacks in config.

**Reason:**
- Standard Laravel pattern
- More flexible (multiple listeners)
- Can be queued
- Easier to test
- Better separation of concerns

**Implementation:**
- `PaymentSuccess` event
- `PaymentFailed` event
- `PaymentCancelled` event
- `PaymentPending` event

### 3. Polymorphic Relationships

**Decision:** Use polymorphic relationships for `PaymentTransaction`.

**Reason:**
- Can link to any model (Order, Invoice, Subscription, etc.)
- More flexible than hardcoded relationships
- Supports multiple payable types

**Implementation:**
```php
PaymentTransaction::create([
    'payable_type' => Order::class,
    'payable_id' => $order->id,
    // ...
]);
```

### 4. Unified Callback/Webhook Handling

**Decision:** Single endpoint for all payment callbacks and webhooks.

**Reason:**
- Consistent handling across gateways
- Easier to maintain
- Centralized logging and error handling

**Implementation:**
- `PaymentCallbackController` - handles callbacks
- `PaymentWebhookController` - handles webhooks
- `PaymentCallbackHandler` - unified processing logic

## 📦 Components Breakdown

### 1. DTOs (Data Transfer Objects)

#### Base DTOs
- **`PaymentOrderDTO`**: Order information (id, referenceId, amount, currency, description)
- **`BuyerDTO`**: Buyer information (name, email, phone)
- **`AddressDTO`**: Address information (city, address, zip, countryCode)
- **`OrderItemDTO`**: Individual order item (referenceId, title, description, quantity, unitPrice)

#### Tabby-Specific DTOs
- **`TabbyPaymentDTO`**: Main DTO for Tabby payments
  - Composed of: `PaymentOrderDTO`, `BuyerDTO`, `AddressDTO`, `OrderItemDTO[]`
  - Optional: `BuyerHistoryDTO`, `OrderHistoryDTO[]`
- **`BuyerHistoryDTO`**: Buyer purchase history for Tabby
- **`OrderHistoryDTO`**: Individual order history item

#### Tamara-Specific DTOs
- **`TamaraPaymentDTO`**: Main DTO for Tamara payments
  - Composed of: `PaymentOrderDTO`, `ConsumerDTO`, `AddressDTO`, `TamaraOrderItemDTO[]`
- **`ConsumerDTO`**: Extends `BuyerDTO` with additional Tamara-specific fields
- **`TamaraOrderItemDTO`**: Extends `OrderItemDTO` with Tamara-specific fields

### 2. Builders

**Purpose:** Facilitate building complex DTOs with fluent interface.

- **`TabbyPaymentDTOBuilder`**: Builder for `TabbyPaymentDTO`
- **`TamaraPaymentDTOBuilder`**: Builder for `TamaraPaymentDTO`

**Usage:**
```php
$dto = TabbyPaymentDTOBuilder::new()
    ->order(...)
    ->buyer(...)
    ->shippingAddress(...)
    ->item(...)
    ->build();
```

### 3. Payment Gateways

#### `PaymentGatewayInterface`
```php
interface PaymentGatewayInterface
{
    public function initiatePayment(TabbyPaymentDTO|TamaraPaymentDTO $paymentDTO): array;
}
```

#### `TabbyPaymentService`
- `initiatePayment(TabbyPaymentDTO $dto): array`
- `getPayment(string $paymentId): array`
- `capturePayment(string $paymentId, float $amount, ?string $referenceId): array`

#### `TamaraPaymentService`
- `initiatePayment(TamaraPaymentDTO $dto): array`
- `createCheckout(TamaraPaymentDTO $dto): array`
- `getOrder(string $orderId): array`
- `authorizeOrder(string $orderId): array`
- `captureOrder(string $orderId, float $amount): array`
- `cancelOrder(string $orderId): array`
- `refundOrder(string $orderId, float $amount): array`
- `verifyTamaraToken(Request $request): bool`

### 4. Factory Pattern

**`PaymentGatewayFactory`**
- `make(string $provider): PaymentGatewayInterface`
- `getSupportedGateways(): array`

**Supported Gateways:**
- `tabby`
- `tamara`

### 5. Payment Processing

#### `HandlePaymentAction`
**Purpose:** Process payment responses from gateways and update transaction status.

**Key Methods:**
- `handle(array $data, string $gateway, bool $is_webhook): bool|array`
- `parseGatewayResponse(array $data, string $gateway, bool $is_webhook): ?array`
- `parseTabbyResponse(array $data, bool $is_webhook): ?array`
- `parseTamaraResponse(array $data, bool $is_webhook): ?array`

**Flow:**
1. Parse gateway-specific response
2. Find payment transaction
3. Update transaction status
4. Dispatch appropriate event (Success/Failed/Cancelled)

### 6. Events

All events receive `PaymentTransaction` model:

- **`PaymentSuccess`**: `$event->transaction`
- **`PaymentFailed`**: `$event->transaction`, `$event->reason`
- **`PaymentCancelled`**: `$event->transaction`
- **`PaymentPending`**: `$event->transaction`

### 7. Controllers

#### `PaymentCallbackController`
- Route: `POST /payment/callback/{gateway}`
- Handles user redirects after payment

#### `PaymentWebhookController`
- Route: `POST /webhooks/payment/{gateway}`
- Handles server-to-server notifications

### 8. Models

#### `PaymentTransaction`
**Polymorphic Relationship:**
```php
public function payable(): MorphTo
{
    return $this->morphTo();
}
```

**Fields:**
- `payable_type` (polymorphic)
- `payable_id` (polymorphic)
- `track_id` (merchant reference)
- `payment_id` (gateway payment ID)
- `payment_gateway` (tabby/tamara)
- `amount`
- `status` (enum: pending, success, failed)
- `response` (JSON)

### 9. Configuration

#### `config/payment-gateway.php`
- `default_gateway`: Default gateway to use
- `transaction`: Transaction configuration (table name, polymorphic)

#### `config/tabby.php`
- API credentials (secret_key, public_key, merchant_code)
- URLs (success_url, failure_url, cancel_url, redirect URLs)
- `sandbox_mode`
- `currency`

#### `config/tamara.php`
- API credentials (api_token, notification_token, webhook_token, public_key)
- URLs (success_url, failure_url, cancel_url, redirect URLs)
- `sandbox_mode`
- Payment options (default_payment_type, default_instalments)
- Localization (currency, country_code, locale)

## 🔄 Payment Flow

### 1. Payment Initiation

```
User Request
    ↓
Create DTOs (TabbyPaymentDTO or TamaraPaymentDTO)
    ↓
PaymentGatewayFactory->make('tabby'|'tamara')
    ↓
Gateway->initiatePayment($dto)
    ↓
Create PaymentTransaction record
    ↓
Return payment URL
    ↓
Redirect user to gateway
```

### 2. Payment Callback/Webhook

```
Gateway sends callback/webhook
    ↓
PaymentCallbackController / PaymentWebhookController
    ↓
PaymentCallbackHandler / HandlePaymentAction
    ↓
Parse gateway response
    ↓
Find PaymentTransaction
    ↓
Update transaction status
    ↓
Dispatch Event (PaymentSuccess/Failed/Cancelled)
    ↓
User's Event Listeners handle the event
```

## 🚀 Implementation Steps (Completed)

### Phase 1: Package Structure ✅
- [x] Create package directory structure
- [x] Set up `composer.json`
- [x] Create `PaymentGatewayServiceProvider`

### Phase 2: DTOs ✅
- [x] Create base DTOs (PaymentOrderDTO, BuyerDTO, AddressDTO, OrderItemDTO)
- [x] Create Tabby-specific DTOs (TabbyPaymentDTO, BuyerHistoryDTO, OrderHistoryDTO)
- [x] Create Tamara-specific DTOs (TamaraPaymentDTO, ConsumerDTO, TamaraOrderItemDTO)
- [x] Add validation to DTOs

### Phase 3: Builders ✅
- [x] Create `TabbyPaymentDTOBuilder`
- [x] Create `TamaraPaymentDTOBuilder`

### Phase 4: Payment Gateways ✅
- [x] Create `PaymentGatewayInterface`
- [x] Refactor `TabbyPaymentService` to use DTOs
- [x] Refactor `TamaraPaymentService` to use DTOs
- [x] Remove dependencies on Order/Client models

### Phase 5: Factory ✅
- [x] Create `PaymentGatewayFactory`
- [x] Support only Tabby and Tamara

### Phase 6: Payment Processing ✅
- [x] Refactor `HandlePaymentAction` to use DTOs
- [x] Remove model dependencies
- [x] Implement gateway response parsing

### Phase 7: Events System ✅
- [x] Create payment events (Success, Failed, Cancelled, Pending)
- [x] Replace callbacks with events in `HandlePaymentAction`
- [x] Remove callbacks from config
- [x] Update controllers to remove callback logic

### Phase 8: Models & Migrations ✅
- [x] Create `PaymentTransaction` model with polymorphic support
- [x] Create migration for `payment_transactions` table

### Phase 9: Controllers & Routes ✅
- [x] Create `PaymentCallbackController`
- [x] Create `PaymentWebhookController`
- [x] Create `PaymentCallbackHandler`
- [x] Set up routes in `routes/web.php`

### Phase 10: Configuration ✅
- [x] Create `config/payment-gateway.php`
- [x] Create `config/tabby.php`
- [x] Create `config/tamara.php`
- [x] Remove callbacks from config

### Phase 11: Documentation ✅
- [x] Write comprehensive README.md
- [x] Document all configuration files
- [x] Document Events usage
- [x] Add usage examples
- [x] Create DEPLOYMENT.md
- [x] Create QUICK_START_DEPLOY.md

### Phase 12: Package Deployment Files ✅
- [x] Create `.gitignore`
- [x] Create `.gitattributes`
- [x] Create `LICENSE` (MIT)
- [x] Create `CHANGELOG.md`
- [x] Update `composer.json` with metadata

## 🔧 Important Notes for Future Modifications

### When Adding a New Gateway:

1. **Create Gateway-Specific DTO:**
   - Create new DTO class (e.g., `NewGatewayPaymentDTO`)
   - Compose it from base DTOs or create gateway-specific ones

2. **Create Gateway Service:**
   - Implement `PaymentGatewayInterface`
   - Use DTOs instead of models
   - Implement `initiatePayment()` method

3. **Update Factory:**
   - Add new gateway to `PaymentGatewayFactory::make()`
   - Add to `getSupportedGateways()`

4. **Update HandlePaymentAction:**
   - Add parsing method: `parseNewGatewayResponse()`
   - Add case in `parseGatewayResponse()`

5. **Update Config:**
   - Create `config/newgateway.php`
   - Update `PaymentGatewayServiceProvider` to publish it

6. **Update Documentation:**
   - Add to README.md
   - Update examples

### When Modifying DTOs:

- **Never remove required fields** - use optional fields instead
- **Maintain backward compatibility** when possible
- **Update Builders** if DTO structure changes
- **Update documentation** with changes

### When Modifying Events:

- **Never remove event properties** - add new ones if needed
- **Maintain event structure** for backward compatibility
- **Update documentation** with event changes

### CSRF Configuration:

**Important:** Users must add these routes to `VerifyCsrfToken`:
```php
protected $except = [
    'payment/callback/*',
    'webhooks/payment/*',
];
```

This is documented in README.md.

## 📝 Code Style Guidelines

1. **Use type hints** everywhere (parameters, return types)
2. **Use DTOs** instead of arrays where possible
3. **Use Enums** for status values
4. **Follow PSR-12** coding standards
5. **Add PHPDoc** comments for all public methods
6. **Use dependency injection** instead of facades where possible
7. **Handle errors gracefully** with try-catch and logging

## 🧪 Testing Considerations

When adding tests (future work):

1. **Unit Tests:**
   - DTO validation
   - Builder pattern
   - Factory pattern
   - Gateway services (mock API calls)

2. **Integration Tests:**
   - Payment flow end-to-end
   - Event dispatching
   - Callback/webhook handling

3. **Test Data:**
   - Use factories for PaymentTransaction
   - Mock gateway API responses

## 🔐 Security Considerations

1. **Webhook Verification:**
   - Tamara: Token verification implemented
   - Tabby: Verify signatures if available

2. **CSRF Protection:**
   - Callback/webhook routes excluded (documented)

3. **Sensitive Data:**
   - Never log full payment responses
   - Mask sensitive information in logs

## 📚 References

- Original implementation: `app/Domain/Order/Services/PaymentGateways/`
- Original action: `app/Domain/Order/Actions/HandlePaymentAction.php`
- Original model: `app/Domain/Order/Models/PaymentTransaction.php`

## 🎯 Future Enhancements (Optional)

- [ ] Add unit tests
- [ ] Add integration tests
- [ ] Add more payment gateways (if needed)
- [ ] Add payment retry mechanism
- [ ] Add payment status polling
- [ ] Add payment analytics
- [ ] Add webhook signature verification for all gateways
- [ ] Add rate limiting for webhooks
- [ ] Add payment transaction query builder helpers

## 📞 Support

For questions or issues:
- Check README.md for usage examples
- Review this PLAN.md for architecture decisions
- Check CHANGELOG.md for version history

---

**Last Updated:** 2025-01-XX
**Version:** 1.0.0
**Status:** ✅ Complete
