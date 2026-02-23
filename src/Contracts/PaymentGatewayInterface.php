<?php

namespace MLQuarizm\PaymentGateway\Contracts;

interface PaymentGatewayInterface
{
    /**
     * Initiate the payment process and return the necessary data (e.g., payment URL).
     *
     * @param mixed $paymentData Payment DTO (TabbyPaymentDTO or TamaraPaymentDTO)
     * @return array
     */
    public function initiatePayment(mixed $paymentData): array;
}
