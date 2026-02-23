<?php

namespace MLQuarizm\PaymentGateway\Handlers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use MLQuarizm\PaymentGateway\Actions\HandlePaymentAction;

class PaymentCallbackHandler
{
    protected HandlePaymentAction $handlePaymentAction;

    public function __construct(HandlePaymentAction $handlePaymentAction)
    {
        $this->handlePaymentAction = $handlePaymentAction;
    }

    /**
     * Handle payment callback from any gateway
     *
     * @param Request $request
     * @param string $gateway
     * @return array
     */
    public function handle(
        Request $request,
        string $gateway
    ): array {
        Log::info("Payment Callback Received from {$gateway}", [
            'gateway' => $gateway,
            'payload' => $request->all(),
        ]);

        try {
            $data = $request->all();

            $result = $this->handlePaymentAction->handle(
                $data,
                $gateway,
                false // is_webhook = false for callbacks
            );

            if ($result === true) {
                return [
                    'success' => true,
                    'message' => 'Payment processed successfully',
                ];
            }

            if (is_array($result) && isset($result['status']) && $result['status'] === 'cancel') {
                return [
                    'success' => false,
                    'status' => 'cancel',
                    'message' => $result['message'] ?? 'Payment was cancelled',
                ];
            }

            return [
                'success' => false,
                'message' => 'Payment processing failed',
            ];

        } catch (\Exception $e) {
            Log::error("Payment Callback Error: " . $e->getMessage(), [
                'gateway' => $gateway,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Error processing payment callback',
                'error' => $e->getMessage(),
            ];
        }
    }
}
