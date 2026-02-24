<?php

namespace MLQuarizm\PaymentGateway\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\View\View;
use MLQuarizm\PaymentGateway\Models\PaymentTransaction;

class PaymentStatusController extends Controller
{
    /**
     * Show the payment status Blade (success / error / cancel).
     * When no redirect URL is configured, user stays on the page and sees a manual link.
     * Redirect URL includes status and gateway in params so clients can listen.
     */
    public function show(string $status): View
    {
        $gateway = request('gateway', '');
        $transactionId = request('transaction_id');
        [$url, $hasRedirect] = $this->buildRedirectUrl($transactionId, $status, $gateway);

        return view('payment-gateway::status.' . $status, [
            'status' => $status,
            'url' => $url,
            'hasRedirect' => $hasRedirect,
            'gateway' => $gateway,
        ]);
    }

    /**
     * @return array{0: string, 1: bool} [url, hasRedirect]
     */
    protected function buildRedirectUrl(?string $transactionId, string $status, string $gateway): array
    {
        $template = config('payment-gateway.redirect_after_status_url', '');
        $fallback = config('payment-gateway.redirect_after_status_fallback_url', '');

        $baseUrl = '';
        if (!empty($template)) {
            if (!empty($transactionId)) {
                $transaction = PaymentTransaction::find($transactionId);
                if ($transaction && $transaction->payable_id) {
                    $baseUrl = str_replace('{order_id}', (string) $transaction->payable_id, $template);
                } elseif (str_contains($template, '{order_id}')) {
                    $baseUrl = $fallback ?: url('/');
                } else {
                    $baseUrl = $template;
                }
            } else {
                $baseUrl = str_contains($template, '{order_id}') ? ($fallback ?: url('/')) : $template;
            }
        } else {
            $baseUrl = $fallback ?: url('/');
        }

        $hasRedirect = !empty($template) || !empty($fallback);
        if (!$hasRedirect) {
            $baseUrl = url('/');
        }

        $separator = str_contains($baseUrl, '?') ? '&' : '?';
        $url = $baseUrl . $separator . http_build_query(array_filter([
            'status' => $status,
            'gateway' => $gateway ?: null,
        ]));

        return [$url, $hasRedirect];
    }
}
