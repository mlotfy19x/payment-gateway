# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-01-XX

### Added

#### Core Features
- Initial release of ML Payment Gateway Package
- Tabby payment gateway integration with full API support
- Tamara payment gateway integration with full API support
- Payment gateway factory pattern for easy gateway instantiation
- Unified payment gateway interface (`PaymentGatewayInterface`)

#### Data Transfer Objects (DTOs)
- Base DTOs for type-safe data handling:
  - `PaymentOrderDTO` - Order information
  - `BuyerDTO` - Buyer information
  - `ConsumerDTO` - Consumer information (extends BuyerDTO for Tamara)
  - `AddressDTO` - Address information
  - `OrderItemDTO` - Order item information
  - `TamaraOrderItemDTO` - Tamara-specific order item
- Gateway-specific DTOs:
  - `TabbyPaymentDTO` - Complete Tabby payment data structure
  - `TamaraPaymentDTO` - Complete Tamara payment data structure
  - `BuyerHistoryDTO` - Buyer purchase history for Tabby
  - `OrderHistoryDTO` - Order history items for Tabby
- DTO validation to ensure data integrity

#### Builder Pattern
- `TabbyPaymentDTOBuilder` - Fluent interface for building Tabby payment DTOs
- `TamaraPaymentDTOBuilder` - Fluent interface for building Tamara payment DTOs
- Support for adding single items via `item()` method
- Support for adding multiple items via `items()` method (accepts DTOs or arrays)
- Method chaining for easy DTO construction

#### Payment Processing
- `HandlePaymentAction` - Centralized payment response handling
- Gateway response parsing for Tabby and Tamara
- Automatic payment status updates
- Support for payment retries (failed transactions can be retried)
- Payment capture handling for authorized payments

#### Events System
- `PaymentSuccess` event - Dispatched when payment succeeds
- `PaymentFailed` event - Dispatched when payment fails (includes failure reason)
- `PaymentCancelled` event - Dispatched when payment is cancelled
- `PaymentPending` event - Dispatched when payment is pending
- Event-based architecture replacing callback system for better flexibility

#### Webhook & Callback Handling
- `PaymentWebhookController` - Unified webhook endpoint for all gateways
- `PaymentCallbackController` - Unified callback endpoint for user redirects
- `PaymentCallbackHandler` - Centralized callback processing logic
- Webhook signature verification service (`WebhookVerificationService`)
  - Tamara JWT token verification
  - Tabby HMAC-SHA256 signature verification
- Automatic webhook verification before processing
- Support for both webhook and callback flows

#### Models & Database
- `PaymentTransaction` model with polymorphic relationships
- Support for linking transactions to any model (Order, Invoice, etc.)
- Migration file for `payment_transactions` table
- Payment status enum (`PaymentStatusEnum`)

#### Configuration
- Main configuration file (`config/payment-gateway.php`)
- Tabby-specific configuration (`config/tabby.php`)
  - API credentials (secret_key, public_key, merchant_code)
  - Callback URLs (success, failure, cancel)
  - Redirect URLs
  - Sandbox mode support
  - Currency configuration
  - Webhook signature verification toggle
- Tamara-specific configuration (`config/tamara.php`)
  - API credentials (api_token, notification_token, webhook_token, public_key)
  - Callback URLs (success, failure, cancel)
  - Redirect URLs
  - Sandbox mode support
  - Payment options (default_payment_type, default_instalments)
  - Localization settings (currency, country_code, locale)

#### Routes
- `POST /payment/callback/{gateway}` - Unified callback endpoint
- `POST /webhooks/payment/{gateway}` - Unified webhook endpoint
- Automatic route registration via service provider

#### Security
- Webhook signature verification for Tamara (JWT tokens)
- Webhook signature verification for Tabby (HMAC-SHA256)
- CSRF protection exclusion for webhook/callback routes (documented)
- Secure token/signature validation before processing webhooks

#### Documentation
- Comprehensive README.md with:
  - Installation instructions (Composer, Git, Local)
  - Configuration guide for all config files
  - Usage examples for both gateways
  - Builder pattern examples
  - Multiple items examples
  - Events handling guide
  - Webhook verification documentation
  - CSRF configuration guide
- `PLAN.md` - Complete development plan and architecture documentation
- `DEPLOYMENT.md` - Deployment guide for Git and Packagist
- `QUICK_START_DEPLOY.md` - Quick deployment instructions
- `CHANGELOG.md` - This file
- Code comments and PHPDoc throughout

#### Package Files
- `composer.json` with proper autoloading and dependencies
- `.gitignore` for package files
- `.gitattributes` for consistent line endings
- `LICENSE` (MIT License)
- Service provider for Laravel integration

### Changed

#### Architecture Improvements
- Removed dependencies on application-specific models (`Order`, `Client`)
- Replaced callback system with Laravel Events for better flexibility
- Centralized webhook verification in dedicated service
- Improved error handling and logging throughout

#### Builder Pattern Enhancements
- Added `items()` method to support adding multiple items at once
- Support for both DTO instances and arrays in `items()` method
- Better flexibility in item construction

### Security

- Implemented webhook signature verification for all gateways
- Added secure token validation for Tamara webhooks
- Added HMAC-SHA256 signature verification for Tabby webhooks
- Documented CSRF exclusion requirements

### Documentation

- Added comprehensive examples for all features
- Documented webhook verification process
- Added multiple items usage examples
- Documented events system with examples
- Added configuration file documentation

---

## Future Enhancements

Potential features for future versions:

- [ ] Unit tests
- [ ] Integration tests
- [ ] Additional payment gateways
- [ ] Payment retry mechanism
- [ ] Payment status polling
- [ ] Payment analytics
- [ ] Rate limiting for webhooks
- [ ] Payment transaction query builder helpers
- [ ] Enhanced Tabby webhook verification based on official documentation

---

**Note:** This is the initial release. All features listed above are included in version 1.0.0.
