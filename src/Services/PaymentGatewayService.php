<?php

namespace MLQuarizm\PaymentGateway\Services;

use Illuminate\Database\Eloquent\Model;
use MLQuarizm\PaymentGateway\Enums\PaymentStatusEnum;
use MLQuarizm\PaymentGateway\Models\PaymentTransaction;

class PaymentGatewayService
{
    /**
     * Record a payment transaction after initiating payment with a gateway.
     * Call this after initiatePayment() and before redirecting the user to the gateway.
     *
     * @param Model $payable The related model (e.g. Order)
     * @param string $trackId Your reference (e.g. order id as string)
     * @param string|null $paymentId Gateway payment/session id from initiatePayment response
     * @param string $gateway Gateway name (e.g. 'tabby', 'tamara')
     * @param float $amount Amount
     * @param array $response Optional raw response from initiatePayment
     * @return PaymentTransaction
     */
    public function recordTransaction(
        Model $payable,
        string $trackId,
        ?string $paymentId,
        string $gateway,
        float $amount,
        array $response = []
    ): PaymentTransaction {
        return PaymentTransaction::create([
            'payable_type' => $payable->getMorphClass(),
            'payable_id' => $payable->getKey(),
            'track_id' => $trackId,
            'payment_id' => $paymentId,
            'payment_gateway' => $gateway,
            'amount' => $amount,
            'status' => PaymentStatusEnum::PENDING->value,
            'response' => $response,
        ]);
    }
}
