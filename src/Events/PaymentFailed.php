<?php

namespace ML\PaymentGateway\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use ML\PaymentGateway\Models\PaymentTransaction;

class PaymentFailed
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public PaymentTransaction $transaction,
        public string $reason = 'Payment failed'
    ) {
    }
}
