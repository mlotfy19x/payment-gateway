<?php

namespace ML\PaymentGateway\DTOs;

class TamaraPaymentDTO
{
    public function __construct(
        public PaymentOrderDTO $order,
        public ConsumerDTO $consumer,
        public AddressDTO $billingAddress,
        public AddressDTO $shippingAddress,
        public array $items // TamaraOrderItemDTO[]
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if (empty($this->items)) {
            throw new \InvalidArgumentException('At least one item is required');
        }

        foreach ($this->items as $item) {
            if (!$item instanceof TamaraOrderItemDTO) {
                throw new \InvalidArgumentException('All items must be instances of TamaraOrderItemDTO');
            }
        }
    }
}
