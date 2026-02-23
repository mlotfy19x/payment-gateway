<?php

namespace ML\PaymentGateway\DTOs;

class PaymentOrderDTO
{
    public function __construct(
        public string|int $id,
        public string $referenceId,
        public float $amount,
        public string $currency,
        public string $description
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if (empty($this->referenceId)) {
            throw new \InvalidArgumentException('Reference ID is required');
        }

        if ($this->amount <= 0) {
            throw new \InvalidArgumentException('Amount must be greater than 0');
        }

        if (empty($this->currency)) {
            throw new \InvalidArgumentException('Currency is required');
        }
    }
}
