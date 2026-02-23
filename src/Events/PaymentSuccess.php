<?php

namespace ML\PaymentGateway\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use ML\PaymentGateway\Models\PaymentTransaction;

class PaymentSuccess
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public PaymentTransaction $transaction
    ) {
    }
}
