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
     * Always redirects the user to a status page (package Blade when redirect_after_status_url is set); never returns JSON.
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
     * Build redirect URL. When redirect_after_status_url is set, redirect to package status Blade (with transaction_id
     * and order id in the Blade's redirect URL). Otherwise use gateway redirect_*_url, fallback, or app root.
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
        $usePackageBlade = !empty(config('payment-gateway.redirect_after_status_url'));

        if ($usePackageBlade && $transactionId !== null && Route::has('payment-gateway.status')) {
            return route('payment-gateway.status', [
                'status' => $status,
                'transaction_id' => $transactionId,
                'gateway' => $gateway,
            ]);
        }

        if ($usePackageBlade && Route::has('payment-gateway.status')) {
            return route('payment-gateway.status', [
                'status' => $status,
                'gateway' => $gateway,
            ]);
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
