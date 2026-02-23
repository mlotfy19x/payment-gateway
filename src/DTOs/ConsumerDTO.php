<?php

namespace ML\PaymentGateway\DTOs;

class ConsumerDTO
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $phoneNumber,
        public string $email,
        public string $dateOfBirth = '1990-01-01'
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if (empty($this->firstName)) {
            throw new \InvalidArgumentException('First name is required');
        }

        if (empty($this->email) || !filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Valid email is required');
        }

        if (empty($this->phoneNumber)) {
            throw new \InvalidArgumentException('Phone number is required');
        }
    }
}
