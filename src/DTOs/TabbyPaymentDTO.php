<?php

namespace ML\PaymentGateway\DTOs;

class TabbyPaymentDTO
{
    public function __construct(
        public PaymentOrderDTO $order,
        public BuyerDTO $buyer,
        public AddressDTO $shippingAddress,
        public array $items, // OrderItemDTO[]
        public ?BuyerHistoryDTO $buyerHistory = null,
        public ?array $orderHistory = null // OrderHistoryDTO[]
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if (empty($this->items)) {
            throw new \InvalidArgumentException('At least one item is required');
        }

        foreach ($this->items as $item) {
            if (!$item instanceof OrderItemDTO) {
                throw new \InvalidArgumentException('All items must be instances of OrderItemDTO');
            }
        }

        if ($this->orderHistory !== null) {
            foreach ($this->orderHistory as $history) {
                if (!$history instanceof OrderHistoryDTO) {
                    throw new \InvalidArgumentException('All order history items must be instances of OrderHistoryDTO');
                }
            }
        }
    }
}
