<?php

namespace ML\PaymentGateway\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use ML\PaymentGateway\Actions\HandlePaymentAction;

class PaymentWebhookController extends Controller
{
    protected HandlePaymentAction $handlePaymentAction;

    public function __construct(HandlePaymentAction $handlePaymentAction)
    {
        $this->handlePaymentAction = $handlePaymentAction;
    }

    /**
     * Handle incoming webhook requests from payment gateways.
     *
     * @param Request $request
     * @param string $gateway
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request, string $gateway)
    {
        Log::info("Webhook Received from {$gateway}", [
            'headers' => $request->headers->all(),
            'payload' => $request->all(),
        ]);

        try {
            $data = $request->all();

            $result = $this->handlePaymentAction->handle(
                $data,
                $gateway,
                true // is_webhook = true
            );

            if ($result) {
                return response()->json(['message' => 'Webhook handled successfully'], 200);
            }

            return response()->json(['message' => 'Transaction not found or already processed'], 200);

        } catch (\Exception $e) {
            Log::error("Webhook Handling Error: " . $e->getMessage(), [
                'gateway' => $gateway,
                'error' => $e->getMessage(),
            ]);

            // Always return 200 to the gateway to prevent them from retrying indefinitely
            return response()->json(['message' => 'Error processed'], 200);
        }
    }
}
