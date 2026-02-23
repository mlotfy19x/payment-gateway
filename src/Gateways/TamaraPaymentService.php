<?php

namespace ML\PaymentGateway\Gateways;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use ML\PaymentGateway\Contracts\PaymentGatewayInterface;
use ML\PaymentGateway\DTOs\AddressDTO;
use ML\PaymentGateway\DTOs\ConsumerDTO;
use ML\PaymentGateway\DTOs\TamaraOrderItemDTO;
use ML\PaymentGateway\DTOs\TamaraPaymentDTO;

class TamaraPaymentService implements PaymentGatewayInterface
{
    private const DEFAULT_CURRENCY = 'SAR';
    private const DEFAULT_COUNTRY = 'SA';
    private const DEFAULT_INSTALMENTS = 3;

    private const SANDBOX_URL = 'https://api-sandbox.tamara.co';
    private const PRODUCTION_URL = 'https://api.tamara.co';

    protected string $baseUrl;
    protected string $apiToken;
    protected string $notificationToken;
    protected bool $sandboxMode;
    protected string $successUrl;
    protected string $cancelUrl;
    protected string $failureUrl;

    public function __construct()
    {
        $this->sandboxMode = config('tamara.sandbox_mode', true);
        $this->baseUrl = $this->sandboxMode ? self::SANDBOX_URL : self::PRODUCTION_URL;
        $this->apiToken = config('tamara.api_token');
        $this->notificationToken = config('tamara.notification_token');
        $this->successUrl = config('tamara.success_url');
        $this->cancelUrl = config('tamara.cancel_url');
        $this->failureUrl = config('tamara.failure_url');
    }

    // -------------------------------------------------------------------------
    // Public API Methods
    // -------------------------------------------------------------------------

    public function initiatePayment(mixed $paymentData): array
    {
        if (!$paymentData instanceof TamaraPaymentDTO) {
            throw new \InvalidArgumentException('Payment data must be an instance of TamaraPaymentDTO');
        }

        $orderData = $this->prepareOrderData($paymentData);
        $checkOutData = $this->createCheckout($orderData);

        return [
            'url' => $checkOutData['checkout_url'] ?? null,
            'session_id' => $checkOutData['checkout_id'] ?? null,
            'payment_id' => $checkOutData['order_id'] ?? null,
            'success' => $checkOutData['success'] ?? null,
        ];
    }

    public function createCheckout(array $orderData): array
    {
        try {
            $response = $this->makeApiRequest('POST', '/checkout', $orderData);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'checkout_id' => $data['checkout_id'] ?? null,
                    'checkout_url' => $data['checkout_url'] ?? null,
                    'order_id' => $data['order_id'] ?? null,
                ];
            }

            return $this->handleFailedResponse('Tamara checkout failed', $response, [
                'order_data' => $orderData,
            ]);
        } catch (\Exception $e) {
            return $this->handleException('Tamara checkout exception', $e, [
                'order_data' => $orderData,
            ]);
        }
    }

    public function getOrder(string $orderId): array
    {
        try {
            $response = $this->makeApiRequest('GET', "/orders/{$orderId}");

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }

            return [
                'success' => false,
                'message' => $response->json()['message'] ?? 'Failed to get order details',
            ];
        } catch (\Exception $e) {
            return $this->handleException('Tamara get order exception', $e, [
                'order_id' => $orderId,
            ]);
        }
    }

    public function authorizeOrder(string $orderId): array
    {
        try {
            $response = $this->makeApiRequest('POST', "/orders/{$orderId}/authorise");

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }

            return [
                'success' => false,
                'message' => $response->json()['message'] ?? 'Authorization failed',
            ];
        } catch (\Exception $e) {
            return $this->handleException('Tamara authorize order exception', $e, [
                'order_id' => $orderId,
            ]);
        }
    }

    public function captureOrder(string $tamaraOrderId, ?float $totalAmount = null, ?array $items = null): array
    {
        try {
            $payload = $this->buildCapturePayload($tamaraOrderId, $totalAmount, $items);
            $response = $this->makeApiRequest('POST', '/payments/capture', $payload);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('Tamara capture order successful', [
                    'tamara_order_id' => $tamaraOrderId,
                    'response' => $data,
                ]);

                return [
                    'success' => true,
                    'data' => $data,
                    'capture_status' => $data['capture_id'] ?? null,
                    'order_status' => $data['order_status'] ?? null,
                ];
            }

            return $this->handleFailedResponse('Tamara capture order failed', $response, [
                'tamara_order_id' => $tamaraOrderId,
            ]);
        } catch (\Exception $e) {
            return $this->handleException('Tamara capture order exception', $e, [
                'tamara_order_id' => $tamaraOrderId,
            ]);
        }
    }

    public function cancelOrder(string $orderId): array
    {
        try {
            $response = $this->makeApiRequest('POST', "/orders/{$orderId}/cancel");

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }

            return [
                'success' => false,
                'message' => $response->json()['message'] ?? 'Cancellation failed',
            ];
        } catch (\Exception $e) {
            return $this->handleException('Tamara cancel order exception', $e, [
                'order_id' => $orderId,
            ]);
        }
    }

    public function refundOrder(string $tamaraOrderId, ?float $totalAmount = null, ?string $comment = null): array
    {
        try {
            $payload = $this->buildRefundPayload($totalAmount, $comment);
            $response = $this->makeApiRequest('POST', "/payments/simplified-refund/{$tamaraOrderId}", $payload);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('Tamara refund order successful', [
                    'tamara_order_id' => $tamaraOrderId,
                    'refund_amount' => $totalAmount,
                    'response' => $data,
                ]);

                return [
                    'success' => true,
                    'data' => $data,
                    'refund_id' => $data['refund_id'] ?? null,
                    'refund_status' => $data['refund_status'] ?? null,
                ];
            }

            return $this->handleFailedResponse('Tamara refund order failed', $response, [
                'tamara_order_id' => $tamaraOrderId,
            ]);
        } catch (\Exception $e) {
            return $this->handleException('Tamara refund order exception', $e, [
                'tamara_order_id' => $tamaraOrderId,
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // Order Data Preparation
    // -------------------------------------------------------------------------

    private function prepareOrderData(TamaraPaymentDTO $dto): array
    {
        return [
            'order_reference_id' => $dto->order->referenceId,
            'order_number' => "ORDER-{$dto->order->id}",
            'total_amount' => $this->buildAmount($dto->order->amount),
            'description' => $dto->order->description,
            'country_code' => self::DEFAULT_COUNTRY,
            'payment_type' => 'PAY_BY_INSTALMENTS',
            'instalments' => self::DEFAULT_INSTALMENTS,
            'locale' => 'ar_SA',
            'platform' => 'web',
            'is_mobile' => false,
            'merchant_url' => $this->getMerchantUrls(),
            'consumer' => $this->buildConsumerData($dto->consumer),
            'billing_address' => $this->buildAddressData($dto->billingAddress),
            'shipping_address' => $this->buildAddressData($dto->shippingAddress),
            'items' => $this->prepareOrderItems($dto->items),
            'discount' => [
                'amount' => $this->buildAmount(0),
                'name' => 'No discount',
            ],
            'shipping_amount' => $this->buildAmount(0),
            'tax_amount' => $this->buildAmount(0),
        ];
    }

    private function buildConsumerData(ConsumerDTO $consumer): array
    {
        return [
            'first_name' => $consumer->firstName,
            'last_name' => $consumer->lastName,
            'phone_number' => $consumer->phoneNumber,
            'email' => $consumer->email,
            'date_of_birth' => $consumer->dateOfBirth,
        ];
    }

    private function buildAddressData(AddressDTO $address): array
    {
        return [
            'first_name' => $address->line1 ? explode(' ', $address->address)[0] ?? 'Customer' : 'Customer',
            'last_name' => '',
            'line1' => $address->line1 ?? $address->address,
            'line2' => $address->line2 ?? '',
            'city' => $address->city,
            'region' => $address->region ?? $address->city,
            'postal_code' => $address->zip ?? '',
            'country_code' => $address->countryCode ?? self::DEFAULT_COUNTRY,
            'phone_number' => $address->phoneNumber ?? '',
        ];
    }

    private function prepareOrderItems(array $items): array
    {
        return array_map(function (TamaraOrderItemDTO $item) {
            return [
                'reference_id' => $item->referenceId,
                'type' => $item->type,
                'name' => $item->name,
                'sku' => $item->sku,
                'image_url' => $item->imageUrl,
                'item_url' => $item->itemUrl,
                'unit_price' => $this->buildAmount($item->unitPrice),
                'discount_amount' => $this->buildAmount($item->discountAmount),
                'quantity' => $item->quantity,
                'total_amount' => $this->buildAmount($item->totalAmount),
            ];
        }, $items);
    }

    // -------------------------------------------------------------------------
    // Payload Builders
    // -------------------------------------------------------------------------

    private function buildCapturePayload(string $orderId, ?float $amount, ?array $items): array
    {
        $payload = ['order_id' => $orderId];

        if ($amount !== null) {
            $payload['total_amount'] = $this->buildAmount($amount);
        }

        if (!empty($items)) {
            $payload['items'] = $items;
        }

        return $payload;
    }

    private function buildRefundPayload(?float $amount, ?string $comment): array
    {
        $payload = [];

        if ($amount !== null) {
            $payload['total_amount'] = $this->buildAmount($amount);
        }

        if ($comment !== null) {
            $payload['comment'] = $comment;
        }

        return $payload;
    }

    // -------------------------------------------------------------------------
    // Response Handling
    // -------------------------------------------------------------------------

    private function handleFailedResponse(string $context, $response, array $additionalData = []): array
    {
        $json = $response->json();

        Log::error($context, array_merge([
            'status' => $response->status(),
            'response' => $json,
        ], $additionalData));

        return [
            'success' => false,
            'message' => $json['message'] ?? 'Operation failed',
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

    private function makeApiRequest(string $method, string $endpoint, array $data = [])
    {
        $headers = ['Authorization' => "Bearer {$this->apiToken}"];

        if (in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'])) {
            $headers['Content-Type'] = 'application/json';
        }

        return Http::withHeaders($headers)->{strtolower($method)}($this->baseUrl . $endpoint, $data);
    }

    private function getMerchantUrls(): array
    {
        return [
            'success' => $this->successUrl,
            'failure' => $this->failureUrl,
            'cancel' => $this->cancelUrl,
        ];
    }

    private function getCurrency(): string
    {
        return config('tamara.currency', self::DEFAULT_CURRENCY);
    }

    private function buildAmount(float $amount): array
    {
        return [
            'amount' => $amount,
            'currency' => $this->getCurrency(),
        ];
    }

    public function verifyTamaraToken(Request $request): bool
    {
        $notificationToken = config('tamara.notification_token');

        if (empty($notificationToken)) {
            return false;
        }

        $token = $request->query('tamaraToken');
        if (!$token) {
            $authHeader = $request->header('Authorization');
            if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
                $token = substr($authHeader, 7);
            }
        }

        if (!$token) {
            Log::warning('Tamara webhook: No token provided for verification');
            return false;
        }

        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                Log::warning('Tamara webhook: Invalid JWT token format');
                return false;
            }

            [$header, $payload, $signature] = $parts;

            $headerData = json_decode(base64_decode(strtr($header, '-_', '+/')), true);
            $payloadData = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);

            if (!$headerData || !$payloadData) {
                Log::warning('Tamara webhook: Failed to decode JWT token');
                return false;
            }

            if (($headerData['alg'] ?? null) !== 'HS256') {
                Log::warning('Tamara webhook: Unsupported JWT algorithm', ['alg' => $headerData['alg'] ?? null]);
                return false;
            }

            $expectedSignature = base64_encode(
                hash_hmac('sha256', $header . '.' . $payload, $notificationToken, true)
            );
            $expectedSignature = strtr(rtrim($expectedSignature, '='), '+/', '-_');

            if (!hash_equals($signature, $expectedSignature)) {
                Log::warning('Tamara webhook: JWT token signature verification failed');
                return false;
            }

            Log::info('Tamara webhook: JWT token verified successfully', ['payload' => $payloadData]);

            if (isset($payloadData['exp']) && $payloadData['exp'] < time()) {
                Log::warning('Tamara webhook: JWT token expired', ['exp' => $payloadData['exp']]);
                return false;
            }

            Log::debug('Tamara webhook: JWT token verified successfully');
            return true;
        } catch (\Exception $e) {
            Log::warning('Tamara webhook: Error verifying JWT token', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function mapTamaraWebhookEventsToStatus(string $tamaraStatus): string
    {
        return match ($tamaraStatus) {
            'order_approved' => 'approved',
            'order_declined' => 'declined',
            'order_canceled' => 'canceled',
            'order_captured' => 'captured',
            'order_refunded' => 'refunded',
            'order_expired' => 'expired',
            default => 'unknown',
        };
    }
}
