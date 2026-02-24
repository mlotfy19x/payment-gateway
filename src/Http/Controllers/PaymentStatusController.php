<?php

namespace MLQuarizm\PaymentGateway\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\View\View;
use MLQuarizm\PaymentGateway\Models\PaymentTransaction;

class PaymentStatusController extends Controller
{
    /**
     * Show the payment status Blade (success / error / cancel) with redirect URL including order id.
     */
    public function show(string $status): View
    {
        $gateway = request('gateway', '');
        $transactionId = request('transaction_id');
        $url = $this->buildRedirectUrl($transactionId);

        return view('payment-gateway::status.' . $status, [
            'status' => $status,
            'url' => $url,
            'gateway' => $gateway,
        ]);
    }

    protected function buildRedirectUrl(?string $transactionId): string
    {
        $template = config('payment-gateway.redirect_after_status_url', '');
        $fallback = config('payment-gateway.redirect_after_status_fallback_url', '');
        if (empty($fallback)) {
            $fallback = url('/');
        }

        if (empty($template)) {
            return $fallback;
        }

        if (empty($transactionId)) {
            return str_contains($template, '{order_id}') ? $fallback : $template;
        }

        $transaction = PaymentTransaction::find($transactionId);
        if (!$transaction || !$transaction->payable_id) {
            return str_contains($template, '{order_id}') ? $fallback : $template;
        }

        return str_replace('{order_id}', (string) $transaction->payable_id, $template);
    }
}
