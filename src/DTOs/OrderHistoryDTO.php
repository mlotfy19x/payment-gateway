<?php

namespace MLQuarizm\PaymentGateway\DTOs;

class OrderHistoryDTO
{
    public function __construct(
        public string $purchasedAt,
        public string $amount,
        public string $status,
        public BuyerDTO $buyer,
        public AddressDTO $shippingAddress,
        public string $paymentMethod,
        public array $items
    ) {
    }
}
