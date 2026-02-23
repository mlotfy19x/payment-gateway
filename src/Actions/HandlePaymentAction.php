<?php

namespace ML\PaymentGateway\Actions;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use ML\PaymentGateway\Events\PaymentSuccess;
use ML\PaymentGateway\Events\PaymentFailed;
use ML\PaymentGateway\Events\PaymentCancelled;
use ML\PaymentGateway\Gateways\TabbyPaymentService;
use ML\PaymentGateway\Gateways\TamaraPaymentService;
use ML\PaymentGateway\Models\PaymentTransaction;
use ML\PaymentGateway\Enums\PaymentStatusEnum;

class HandlePaymentAction
{
    /**
     * Handle the payment response from various gateways.
     *
     * @param array $data
     * @param string $gateway
     * @param bool $is_webhook
     * @return bool|array
     */
    public function handle(
        array $data,
        string $gateway = 'tabby',
        bool $is_webhook = false
    ): bool|array {
        // 1. Normalize the gateway response data
        $parsedData = $this->parseGatewayResponse($data, $gateway, $is_webhook);

        if (!$parsedData) {
            Log::error("HandlePaymentAction: Unknown gateway or invalid data structure.", [
                'gateway' => $gateway,
                'data' => $data
            ]);
            return false;
        }

        $transactionId = $parsedData['transaction_id'];
        $orderReference = $parsedData['order_reference'];
        $isSuccess = $parsedData['is_success'];

        // 2. Find the transaction (allow retrying failed transactions)
        $transaction = PaymentTransaction::query()
            ->where(function ($query) use ($transactionId, $orderReference) {
                // Search by our internal Track ID (Reference) if available
                if ($orderReference) {
                    $query->where('track_id', $orderReference);
                }
                // Fallback to searching by Gateway Payment ID if available
                elseif ($transactionId) {
                    $query->where('payment_id', $transactionId);
                }
            })
            ->where('payment_gateway', $gateway)
            ->whereIn('status', [PaymentStatusEnum::PENDING->value, PaymentStatusEnum::FAILED->value])
            ->first();

        // 3. Update Payment ID if it was missing
        if ($transaction && empty($transaction->payment_id) && $transactionId) {
            $transaction->update(['payment_id' => $transactionId]);
        }

        if (!$transaction) {
            Log::warning("HandlePaymentAction: Transaction not found.", [
                'gateway' => $gateway,
                'parsed_data' => $parsedData
            ]);
            return false;
        }

        // 4. Handle Failure
        if (!$isSuccess) {
            Log::warning('Payment Failed', [
                'gateway' => $gateway,
                'transaction_id' => $transaction->id,
                'payment_id' => $transactionId,
                'order_reference' => $orderReference,
                'reason' => 'is_success = false',
                'raw_data' => $data
            ]);

            $transaction->update([
                'status' => PaymentStatusEnum::FAILED,
                'response' => $data,
            ]);

            // Check if payment was cancelled
            if (in_array($gateway, ['tabby', 'tamara']) && isset($parsedData['status']) && $parsedData['status'] === 'cancel') {
                // Dispatch cancellation event
                event(new PaymentCancelled($transaction));
                
                return [
                    'status' => 'cancel',
                    'message' => 'Payment was cancelled',
                ];
            }

            // Dispatch failure event
            $reason = $parsedData['status'] ?? 'Payment failed';
            event(new PaymentFailed($transaction, $reason));

            return false;
        }

        // 5. Handle Success
        $transaction->update([
            'status' => PaymentStatusEnum::SUCCESS,
            'response' => $data,
        ]);

        // Dispatch success event
        event(new PaymentSuccess($transaction));

        return true;
    }

    /**
     * Parse gateway-specific response keys into a unified structure.
     *
     * @param array $data
     * @param string $gateway
     * @param bool $is_webhook
     * @return array|null
     */
    private function parseGatewayResponse(array $data, string $gateway, bool $is_webhook): ?array
    {
        if ($gateway === 'tabby') {
            return $this->parseTabbyResponse($data, $is_webhook);
        }

        if ($gateway === 'tamara') {
            return $this->parseTamaraResponse($data, $is_webhook);
        }

        return null;
    }

    /**
     * Parse Tabby gateway response
     */
    private function parseTabbyResponse(array $data, bool $is_webhook): ?array
    {
        $paymentId = $is_webhook ? Arr::get($data, 'id') : Arr::get($data, 'payment_id');

        if (!$paymentId) {
            Log::warning('Tabby: No paymentId provided');
            return [
                'transaction_id' => null,
                'order_reference' => null,
                'is_success' => false,
            ];
        }

        $tabbyService = app(TabbyPaymentService::class);
        $paymentResult = $tabbyService->getPayment($paymentId);

        if (isset($paymentResult['success']) && $paymentResult['success'] === true) {
            $paymentData = $paymentResult['data'] ?? [];
            $isSuccess = in_array($paymentData['status'] ?? '', ['CAPTURED', 'AUTHORIZED', 'CLOSED']);

            if (($paymentData['status'] ?? '') === 'AUTHORIZED') {
                $amount = $paymentData['amount'] ?? 0;
                $referenceId = $paymentData['order']['reference_id'] ?? null;
                $result = $tabbyService->capturePayment($paymentId, $amount, $referenceId);
                $paymentData = $result['data'] ?? [];
                $isSuccess = isset($result['success']) && $result['success'] === true && in_array($paymentData['status'] ?? '', ['CLOSED', 'CAPTURED']);
                
                return [
                    'transaction_id' => $paymentData['id'] ?? $paymentId,
                    'order_reference' => $paymentData['order']['reference_id'] ?? $referenceId,
                    'status' => $data['status'] ?? 'unknown',
                    'is_success' => $isSuccess,
                ];
            }

            return [
                'transaction_id' => $paymentId,
                'order_reference' => $paymentData['order']['reference_id'] ?? null,
                'status' => $data['status'] ?? 'unknown',
                'is_success' => $isSuccess,
            ];
        }

        return [
            'transaction_id' => $paymentId,
            'order_reference' => null,
            'status' => $data['status'] ?? 'unknown',
            'is_success' => false,
        ];
    }

    /**
     * Parse Tamara gateway response
     */
    private function parseTamaraResponse(array $data, bool $is_webhook): ?array
    {
        $tamaraService = app(TamaraPaymentService::class);

        if ($is_webhook) {
            $request = request();
            if ($request instanceof Request) {
                $res = $tamaraService->verifyTamaraToken($request);
                if (!$res) {
                    Log::error('Tamara webhook token verification failed', [
                        'response' => $data
                    ]);
                    return [
                        'transaction_id' => null,
                        'order_reference' => null,
                        'is_success' => false,
                    ];
                }
            }
        }

        // Tamara sends 'order_id' in webhooks and 'orderId' in callbacks
        $orderId = $is_webhook ? Arr::get($data, 'order_id') : Arr::get($data, 'orderId');
        $status = $is_webhook 
            ? $tamaraService->mapTamaraWebhookEventsToStatus(Arr::get($data, 'event_type', ''))
            : Arr::get($data, 'paymentStatus', '');

        if (!$orderId) {
            Log::warning('Tamara: No orderId provided');
            return [
                'transaction_id' => null,
                'order_reference' => null,
                'is_success' => false,
            ];
        }

        $transaction = PaymentTransaction::where('payment_id', $orderId)
            ->where('payment_gateway', 'tamara')
            ->first();

        if (!$transaction) {
            Log::warning('Tamara: No transaction found for orderId', ['orderId' => $orderId]);
            return [
                'transaction_id' => $orderId,
                'order_reference' => null,
                'is_success' => false,
            ];
        }

        $orderInfo = $tamaraService->getOrder($orderId);

        if ($orderInfo['success'] !== true) {
            Log::error('Tamara getOrder failed', [
                'orderId' => $orderId,
                'response' => $orderInfo
            ]);
            return [
                'transaction_id' => $orderId,
                'order_reference' => $transaction->track_id,
                'is_success' => false,
            ];
        }

        $tamaraOrderStatus = $orderInfo['data']['status'] ?? null;

        if ($tamaraOrderStatus === 'approved') {
            $res = $tamaraService->authorizeOrder($orderId);

            if (isset($res['success']) && $res['success'] !== true) {
                Log::error('Tamara authorization failed', [
                    'orderId' => $orderId,
                    'response' => $res
                ]);
                return [
                    'transaction_id' => $orderId,
                    'order_reference' => $transaction->track_id,
                    'is_success' => false,
                ];
            }

            $authorizeOrderStatus = $res['data']['status'] ?? null;

            if ($authorizeOrderStatus === 'authorised') {
                $resCap = $tamaraService->captureOrder($orderId, $transaction->amount);
                if (isset($resCap['success']) && $resCap['success'] === true) {
                    return [
                        'transaction_id' => $orderId,
                        'order_reference' => $transaction->track_id,
                        'is_success' => true,
                    ];
                } else {
                    Log::error('Tamara capture failed after successful authorization', [
                        'orderId' => $orderId,
                        'response' => $resCap
                    ]);
                    return [
                        'transaction_id' => $orderId,
                        'order_reference' => $transaction->track_id,
                        'is_success' => false,
                    ];
                }
            } else {
                Log::error('Tamara authorization did not return expected status', [
                    'orderId' => $orderId,
                    'response' => $res
                ]);
                return [
                    'transaction_id' => $orderId,
                    'order_reference' => $transaction->track_id,
                    'is_success' => false,
                ];
            }
        }

        if ($tamaraOrderStatus === 'authorized') {
            $resCap = $tamaraService->captureOrder($orderId, $transaction->amount);
            if (isset($resCap['success']) && $resCap['success'] === true) {
                return [
                    'transaction_id' => $orderId,
                    'order_reference' => $transaction->track_id,
                    'is_success' => true,
                ];
            } else {
                Log::error('Tamara capture failed after successful authorization', [
                    'orderId' => $orderId,
                    'response' => $resCap
                ]);
                return [
                    'transaction_id' => $orderId,
                    'order_reference' => $transaction->track_id,
                    'is_success' => false,
                ];
            }
        }

        if ($tamaraOrderStatus === 'fully_captured' || $tamaraOrderStatus === 'partially_captured') {
            return [
                'transaction_id' => $orderId,
                'order_reference' => $transaction->track_id,
                'is_success' => true,
            ];
        }

        if ($tamaraOrderStatus === 'new' && $status === 'canceled') {
            return [
                'transaction_id' => $orderId,
                'order_reference' => $transaction->track_id,
                'status' => 'cancel',
                'is_success' => false,
            ];
        }

        return [
            'transaction_id' => $orderId,
            'order_reference' => $transaction->track_id,
            'is_success' => false,
        ];
    }
}
