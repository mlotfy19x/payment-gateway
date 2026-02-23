<?php

namespace ML\PaymentGateway\DTOs;

class OrderItemDTO
{
    public function __construct(
        public string $referenceId,
        public string $title,
        public ?string $description = null,
        public int $quantity = 1,
        public float $unitPrice
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if (empty($this->referenceId)) {
            throw new \InvalidArgumentException('Item reference ID is required');
        }

        if (empty($this->title)) {
            throw new \InvalidArgumentException('Item title is required');
        }

        if ($this->quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be greater than 0');
        }

        if ($this->unitPrice <= 0) {
            throw new \InvalidArgumentException('Unit price must be greater than 0');
        }
    }
}
