<?php

namespace MLQuarizm\PaymentGateway\DTOs;

class TabbyPaymentDTO
{
    public function __construct(
        public PaymentOrderDTO $order,
        public BuyerDTO $buyer,
        public ?AddressDTO $shippingAddress = null,
        public array $items, // OrderItemDTO[]
        public BuyerHistoryDTO $buyerHistory,
        public array $orderHistory = [] // array[] raw order history data
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

        foreach ($this->orderHistory as $history) {
            if (!is_array($history) && !$history instanceof OrderHistoryDTO) {
                throw new \InvalidArgumentException('All order history items must be arrays or instances of OrderHistoryDTO');
            }
        }
    }
}
