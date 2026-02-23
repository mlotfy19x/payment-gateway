<?php

namespace ML\PaymentGateway\DTOs;

class BuyerHistoryDTO
{
    public function __construct(
        public string $registeredSince,
        public int $loyaltyLevel = 0,
        public bool $isPhoneVerified = true,
        public bool $isEmailVerified = false
    ) {
    }
}
