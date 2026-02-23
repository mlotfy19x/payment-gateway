<?php

namespace MLQuarizm\PaymentGateway\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use MLQuarizm\PaymentGateway\Actions\HandlePaymentAction;
use MLQuarizm\PaymentGateway\Services\WebhookVerificationService;

class PaymentWebhookController extends Controller
{
    protected HandlePaymentAction $handlePaymentAction;
    protected WebhookVerificationService $verificationService;

    public function __construct(
        HandlePaymentAction $handlePaymentAction,
        WebhookVerificationService $verificationService
    ) {
        $this->handlePaymentAction = $handlePaymentAction;
        $this->verificationService = $verificationService;
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

        // Verify webhook signature before processing
        if (!$this->verificationService->verify($request, $gateway)) {
            Log::error("Webhook signature verification failed for {$gateway}", [
                'headers' => $request->headers->all(),
            ]);

            // Return 200 to prevent gateway from retrying, but log the failure
            return response()->json([
                'message' => 'Webhook signature verification failed'
            ], 200);
        }

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
