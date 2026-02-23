<?php

namespace MLQuarizm\PaymentGateway\Gateways;

use MLQuarizm\PaymentGateway\Contracts\PaymentGatewayInterface;
use MLQuarizm\PaymentGateway\DTOs\AddressDTO;
use MLQuarizm\PaymentGateway\DTOs\BuyerDTO;
use MLQuarizm\PaymentGateway\DTOs\BuyerHistoryDTO;
use MLQuarizm\PaymentGateway\DTOs\OrderHistoryDTO;
use MLQuarizm\PaymentGateway\DTOs\OrderItemDTO;
use MLQuarizm\PaymentGateway\DTOs\PaymentOrderDTO;
use MLQuarizm\PaymentGateway\DTOs\TabbyPaymentDTO;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TabbyPaymentService implements PaymentGatewayInterface
{
    private const API_VERSION = 'v2';
    private const DEFAULT_CURRENCY = 'SAR';

    protected string $baseUrl = 'https://api.tabby.ai/api/v2';
    protected string $secretKey;
    protected string $publicKey;
    protected string $merchantCode;
    protected bool $sandboxMode;
    protected string $successUrl;
    protected string $cancelUrl;
    protected string $failureUrl;

    public function __construct()
    {
        $this->sandboxMode = config('tabby.sandbox_mode', true);
        $this->secretKey = config('tabby.secret_key');
        $this->publicKey = config('tabby.public_key');
        $this->merchantCode = config('tabby.merchant_code');
        $this->successUrl = config('tabby.success_url');
        $this->cancelUrl = config('tabby.cancel_url');
        $this->failureUrl = config('tabby.failure_url');
    }

    public function initiatePayment(mixed $paymentData): array
    {
        if (!$paymentData instanceof TabbyPaymentDTO) {
            throw new \InvalidArgumentException('Payment data must be an instance of TabbyPaymentDTO');
        }

        $sessionData = $this->prepareSessionData($paymentData);
        $checkout = $this->createCheckout($sessionData);

        return [
            'url' => $checkout['checkout_url'] ?? null,
            'session_id' => $checkout['session_id'] ?? null,
            'payment_id' => $checkout['payment_id'] ?? null,
            'success' => $checkout['success'] ?? null,
        ];
    }

    public function createCheckout(array $sessionData): array
    {
        try {
            $response = $this->makeApiRequest('POST', '/checkout', $sessionData);

            if (!$response->successful()) {
                return $this->handleFailedCheckout($response, $sessionData);
            }

            return $this->parseCheckoutResponse($response->json());
        } catch (\Exception $e) {
            return $this->handleException('Tabby checkout exception', $e, ['session_data' => $sessionData]);
        }
    }

    public function capturePayment(string $paymentId, float $amount, ?string $referenceId = null): array
    {
        try {
            $captureData = array_filter([
                'amount' => $this->formatAmount($amount),
                'reference_id' => $referenceId,
            ]);

            $response = $this->makeApiRequest('POST', "/payments/{$paymentId}/captures", $captureData);

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }

            return [
                'success' => false,
                'message' => $response->json()['message'] ?? 'Capture failed',
            ];
        } catch (\Exception $e) {
            return $this->handleException('Tabby capture payment exception', $e, ['payment_id' => $paymentId]);
        }
    }

    public function getPayment(string $paymentId): array
    {
        try {
            $response = $this->makeApiRequest('GET', "/payments/{$paymentId}");

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }

            return [
                'success' => false,
                'message' => $response->json()['message'] ?? 'Failed to get payment details',
            ];
        } catch (\Exception $e) {
            return $this->handleException('Tabby get payment exception', $e, ['payment_id' => $paymentId]);
        }
    }

    // -------------------------------------------------------------------------
    // Session Data Preparation
    // -------------------------------------------------------------------------

    private function prepareSessionData(TabbyPaymentDTO $dto): array
    {
        return [
            'payment' => $this->buildPaymentData($dto),
            'lang' => $this->getLocale(),
            'merchant_code' => $this->merchantCode,
            'merchant_urls' => $this->getMerchantUrls(),
        ];
    }

    private function buildPaymentData(TabbyPaymentDTO $dto): array
    {
        return [
            'amount' => $this->formatAmount($dto->order->amount),
            'currency' => config('tabby.currency', self::DEFAULT_CURRENCY),
            'description' => $dto->order->description,
            'buyer' => $this->buildBuyerData($dto->buyer),
            'shipping_address' => $this->buildShippingAddress($dto->shippingAddress),
            'order' => $this->buildOrderData($dto->order, $dto->items),
            'order_history' => $this->buildOrderHistory($dto->orderHistory),
            'buyer_history' => $this->buildBuyerHistory($dto->buyerHistory),
        ];
    }

    private function buildBuyerData(BuyerDTO $buyer): array
    {
        return [
            'name' => $buyer->name,
            'email' => $buyer->email,
            'phone' => $buyer->phone,
        ];
    }

    private function buildShippingAddress(AddressDTO $address): array
    {
        return [
            'city' => $address->city,
            'address' => $address->address,
            'zip' => $address->zip,
        ];
    }

    private function buildOrderData(PaymentOrderDTO $order, array $items): array
    {
        return [
            'reference_id' => $order->referenceId,
            'updated_at' => now()->toIso8601String(),
            'tax_amount' => '0.00',
            'shipping_amount' => '0.00',
            'discount_amount' => '0.00',
            'items' => $this->buildOrderItems($items),
        ];
    }

    private function buildOrderItems(array $items): array
    {
        return array_map(function (OrderItemDTO $item) {
            return [
                'reference_id' => $item->referenceId,
                'title' => $item->title,
                'description' => $item->description ?? '',
                'quantity' => $item->quantity,
                'unit_price' => $this->formatAmount($item->unitPrice),
                'discount_amount' => '0.00',
                'category' => 'General',
            ];
        }, $items);
    }

    private function buildBuyerHistory(?BuyerHistoryDTO $history): ?array
    {
        if ($history === null) {
            return null;
        }

        return [
            'registered_since' => $history->registeredSince,
            'loyalty_level' => $history->loyaltyLevel,
            'is_phone_number_verified' => $history->isPhoneVerified,
            'is_email_verified' => $history->isEmailVerified,
        ];
    }

    private function buildOrderHistory(?array $orderHistory): array
    {
        if ($orderHistory === null || empty($orderHistory)) {
            return [];
        }

        return array_map(function (OrderHistoryDTO $history) {
            return [
                'purchased_at' => $history->purchasedAt,
                'amount' => $history->amount,
                'status' => $history->status,
                'buyer' => $this->buildBuyerData($history->buyer),
                'shipping_address' => $this->buildShippingAddress($history->shippingAddress),
                'payment_method' => $history->paymentMethod,
                'items' => array_map(function (OrderItemDTO $item) {
                    return [
                        'reference_id' => $item->referenceId,
                        'title' => $item->title,
                        'quantity' => $item->quantity,
                        'unit_price' => $this->formatAmount($item->unitPrice),
                    ];
                }, $history->items),
            ];
        }, $orderHistory);
    }

    // -------------------------------------------------------------------------
    // Response Handling
    // -------------------------------------------------------------------------

    private function parseCheckoutResponse(array $data): array
    {
        if (($data['status'] ?? null) === 'created') {
            return [
                'success' => true,
                'session_id' => $data['id'] ?? null,
                'payment_id' => $data['payment']['id'] ?? null,
                'checkout_url' => $data['configuration']['available_products']['installments'][0]['web_url'] ?? null,
                'status' => $data['status'],
            ];
        }

        $rejectionReason = $data['configuration']['products']['installments']['rejection_reason'] ?? null;
        $rejectionReasonCode = $data['rejection_reason_code'] ?? null;

        return [
            'success' => false,
            'message' => $this->getRejectionMessage($rejectionReason),
            'rejection_reason' => $rejectionReason,
            'rejection_reason_code' => $rejectionReasonCode,
            'status' => $data['status'] ?? 'rejected',
        ];
    }

    private function handleFailedCheckout($response, array $sessionData): array
    {
        Log::error('Tabby checkout failed', [
            'status' => $response->status(),
            'response' => $response->json(),
            'session_data' => $sessionData,
        ]);

        $json = $response->json();

        return [
            'success' => false,
            'message' => $json['message'] ?? 'Checkout creation failed',
            'errors' => $json['errors'] ?? [],
        ];
    }

    private function handleException(string $context, \Exception $e, array $additionalData = []): array
    {
        Log::error($context, array_merge([
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], $additionalData));

        return [
            'success' => false,
            'message' => "An error occurred: {$e->getMessage()}",
        ];
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function getRejectionMessage(?string $reason): string
    {
        $locale = app()->getLocale();

        return match ($reason) {
            'not_available' => $locale === 'ar'
                ? 'نأسف، تابي غير قادرة على الموافقة على هذه العملية. الرجاء استخدام طريقة دفع أخرى.'
                : 'Sorry, Tabby is unable to approve this purchase. Please use an alternative payment method for your order.',
            'order_amount_too_high' => $locale === 'ar'
                ? 'قيمة الطلب تفوق الحد الأقصى المسموح به حاليًا مع تابي. يُرجى تخفيض قيمة السلة أو استخدام وسيلة دفع أخرى.'
                : 'This purchase is above your current spending limit with Tabby, try a smaller cart or use another payment method',
            'order_amount_too_low' => $locale === 'ar'
                ? 'قيمة الطلب أقل من الحد الأدنى المطلوب لاستخدام خدمة تابي. يُرجى زيادة قيمة الطلب أو استخدام وسيلة دفع أخرى.'
                : 'The purchase amount is below the minimum amount required to use Tabby, try adding more items or use another payment method',
            default => $locale === 'ar'
                ? 'نأسف، تابي غير قادرة على الموافقة على هذه العملية. الرجاء استخدام طريقة دفع أخرى.'
                : 'Sorry, Tabby is unable to approve this purchase. Please use an alternative payment method for your order.',
        };
    }

    private function makeApiRequest(string $method, string $endpoint, array $data = [])
    {
        return Http::withHeaders([
            'Authorization' => "Bearer {$this->secretKey}",
            'Content-Type' => 'application/json',
        ])->{strtolower($method)}($this->baseUrl . $endpoint, $data);
    }

    private function getMerchantUrls(): array
    {
        return [
            'success' => $this->successUrl,
            'cancel' => $this->cancelUrl,
            'failure' => $this->failureUrl,
        ];
    }

    private function getLocale(): string
    {
        return app()->getLocale() === 'ar' ? 'ar' : 'en';
    }

    private function formatAmount(float|int|null $amount): string
    {
        return number_format((float) ($amount ?? 0), 2, '.', '');
    }
}
