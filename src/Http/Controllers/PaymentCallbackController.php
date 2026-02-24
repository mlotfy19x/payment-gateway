<?php

namespace MLQuarizm\PaymentGateway\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
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
     * Always redirects the user to the package status Blade (public route) by default; never returns JSON.
     *
     * @param Request $request
     * @param string $gateway
     * @return RedirectResponse
     */
    public function handle(Request $request, string $gateway): RedirectResponse
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
            return redirect()->to($redirectUrl);
        } catch (\Exception $e) {
            Log::error("Callback Handling Error: " . $e->getMessage(), [
                'gateway' => $gateway,
                'error' => $e->getMessage(),
            ]);

            $redirectUrl = $this->getRedirectUrl($gateway, ['success' => false, 'status' => 'error']);
            return redirect()->to($redirectUrl);
        }
    }

    /**
     * Build redirect URL. Default: redirect to package status Blade (public route) with transaction_id.
     * Only use gateway redirect_*_url or fallback when package status route is not available.
     */
    protected function getRedirectUrl(string $gateway, array $result): string
    {
        $status = 'error';
        if (!empty($result['success'])) {
            $status = 'success';
        } elseif (isset($result['status']) && $result['status'] === 'cancel') {
            $status = 'cancel';
        }

        $transactionId = $result['transaction_id'] ?? null;

        // Default: use package Blade (public status page); include status in query for client listeners
        if (Route::has('payment-gateway.status')) {
            $params = ['status' => $status, 'gateway' => $gateway];
            if ($transactionId !== null) {
                $params['transaction_id'] = $transactionId;
            }
            $url = route('payment-gateway.status', $params);
            $separator = str_contains($url, '?') ? '&' : '?';
            return $url . $separator . 'status=' . rawurlencode($status);
        }

        $configKey = match ($status) {
            'success' => 'redirect_success_url',
            'cancel' => 'redirect_cancel_url',
            default => 'redirect_error_url',
        };

        $url = config("{$gateway}.{$configKey}");
        if (empty($url)) {
            $url = config('payment-gateway.redirect_fallback_url', '');
        }
        if (empty($url)) {
            $url = url('/');
        }

        $separator = str_contains($url, '?') ? '&' : '?';
        $url .= $separator . http_build_query([
            'status' => $status,
            'gateway' => $gateway,
        ]);

        return $url;
    }
}
