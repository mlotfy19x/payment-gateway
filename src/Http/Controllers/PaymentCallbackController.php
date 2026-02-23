<?php

namespace MLQuarizm\PaymentGateway\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use MLQuarizm\PaymentGateway\Handlers\PaymentCallbackHandler;

class PaymentCallbackController extends Controller
{
    protected PaymentCallbackHandler $callbackHandler;

    public function __construct(PaymentCallbackHandler $callbackHandler)
    {
        $this->callbackHandler = $callbackHandler;
    }

    /**
     * Handle incoming callback requests from payment gateways.
     *
     * @param Request $request
     * @param string $gateway
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request, string $gateway)
    {
        Log::info("Callback Received from {$gateway}", [
            'headers' => $request->headers->all(),
            'payload' => $request->all(),
        ]);

        try {
            $result = $this->callbackHandler->handle(
                $request,
                $gateway
            );

            if ($result['success']) {
                return response()->json([
                    'message' => 'Callback handled successfully',
                    'data' => $result
                ], 200);
            }

            return response()->json([
                'message' => $result['message'] ?? 'Callback processing failed',
                'data' => $result
            ], 200); // Always return 200 to prevent gateway retries

        } catch (\Exception $e) {
            Log::error("Callback Handling Error: " . $e->getMessage(), [
                'gateway' => $gateway,
                'error' => $e->getMessage(),
            ]);

            // Always return 200 to the gateway to prevent them from retrying indefinitely
            return response()->json([
                'message' => 'Error processed',
                'error' => $e->getMessage()
            ], 200);
        }
    }
}
