<?php

namespace MLQuarizm\PaymentGateway\Http\Controllers;

use Illuminate\Http\RedirectResponse;
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
     * Handle incoming callback requests from payment gateways (GET for user redirect, POST for server).
     * When redirect_*_url are configured, redirects the user to the appropriate status page; otherwise returns JSON.
     *
     * @param Request $request
     * @param string $gateway
     * @return \Illuminate\Http\JsonResponse|RedirectResponse
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

            $redirectUrl = $this->getRedirectUrl($gateway, $result);
            if ($redirectUrl !== null) {
                return redirect()->to($redirectUrl);
            }

            if ($result['success']) {
                return response()->json([
                    'message' => 'Callback handled successfully',
                    'data' => $result
                ], 200);
            }

            return response()->json([
                'message' => $result['message'] ?? 'Callback processing failed',
                'data' => $result
            ], 200);

        } catch (\Exception $e) {
            Log::error("Callback Handling Error: " . $e->getMessage(), [
                'gateway' => $gateway,
                'error' => $e->getMessage(),
            ]);

            $redirectUrl = $this->getRedirectUrl($gateway, ['success' => false, 'status' => 'error']);
            if ($redirectUrl !== null) {
                return redirect()->to($redirectUrl);
            }

            return response()->json([
                'message' => 'Error processed',
                'error' => $e->getMessage()
            ], 200);
        }
    }

    /**
     * Get redirect URL from config based on result (success / cancel / failure).
     * Returns null if no redirect URL is configured.
     */
    protected function getRedirectUrl(string $gateway, array $result): ?string
    {
        $status = 'error';
        if (!empty($result['success'])) {
            $status = 'success';
        } elseif (isset($result['status']) && $result['status'] === 'cancel') {
            $status = 'cancel';
        }

        $configKey = match ($status) {
            'success' => 'redirect_success_url',
            'cancel' => 'redirect_cancel_url',
            default => 'redirect_error_url',
        };

        $url = config("{$gateway}.{$configKey}");
        if (empty($url)) {
            return null;
        }

        $separator = str_contains($url, '?') ? '&' : '?';
        $url .= $separator . http_build_query([
            'status' => $status,
            'gateway' => $gateway,
        ]);

        return $url;
    }
}
