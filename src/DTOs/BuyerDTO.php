<?php

namespace ML\PaymentGateway\DTOs;

class BuyerDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public string $phone
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if (empty($this->name)) {
            throw new \InvalidArgumentException('Buyer name is required');
        }

        if (empty($this->email) || !filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Valid buyer email is required');
        }

        if (empty($this->phone)) {
            throw new \InvalidArgumentException('Buyer phone is required');
        }
    }
}
