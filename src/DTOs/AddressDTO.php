<?php

namespace MLQuarizm\PaymentGateway\DTOs;

class AddressDTO
{
    public function __construct(
        public string $city,
        public string $address,
        public ?string $zip = null,
        public ?string $countryCode = null,
        // Additional fields for Tamara
        public ?string $line1 = null,
        public ?string $line2 = null,
        public ?string $region = null,
        public ?string $phoneNumber = null
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if (empty($this->city)) {
            throw new \InvalidArgumentException('City is required');
        }

        if (empty($this->address) && empty($this->line1)) {
            throw new \InvalidArgumentException('Address or line1 is required');
        }
    }
}
