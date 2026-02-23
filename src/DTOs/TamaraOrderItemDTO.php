<?php

namespace ML\PaymentGateway\DTOs;

class TamaraOrderItemDTO
{
    public function __construct(
        public string $referenceId,
        public string $type,
        public string $name,
        public string $sku,
        public string $imageUrl = '',
        public string $itemUrl = '',
        public float $unitPrice,
        public float $discountAmount = 0,
        public int $quantity = 1,
        public float $totalAmount
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if (empty($this->referenceId)) {
            throw new \InvalidArgumentException('Reference ID is required');
        }

        if (empty($this->name)) {
            throw new \InvalidArgumentException('Item name is required');
        }

        if ($this->quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be greater than 0');
        }

        if ($this->unitPrice <= 0) {
            throw new \InvalidArgumentException('Unit price must be greater than 0');
        }
    }
}
