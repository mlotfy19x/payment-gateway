<?php

namespace MLQuarizm\PaymentGateway\DTOs;

class BuyerDTO
{
    public function __construct(
        public string $name,
        public ?string $email = null,
        public ?string $phone = null
    ) {
        // Normalize empty strings to null
        $this->email = filled($this->email) ? $this->email : null;
        $this->phone = filled($this->phone) ? $this->phone : null;

        $this->validate();
    }

    private function validate(): void
    {
        if (empty($this->name)) {
            throw new \InvalidArgumentException('Buyer name is required');
        }

        if ($this->email !== null && !filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid buyer email format');
        }
    }
}
