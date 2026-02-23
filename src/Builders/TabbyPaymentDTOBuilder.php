<?php

namespace ML\PaymentGateway\Builders;

use ML\PaymentGateway\DTOs\AddressDTO;
use ML\PaymentGateway\DTOs\BuyerDTO;
use ML\PaymentGateway\DTOs\BuyerHistoryDTO;
use ML\PaymentGateway\DTOs\OrderHistoryDTO;
use ML\PaymentGateway\DTOs\OrderItemDTO;
use ML\PaymentGateway\DTOs\PaymentOrderDTO;
use ML\PaymentGateway\DTOs\TabbyPaymentDTO;

class TabbyPaymentDTOBuilder
{
    private ?PaymentOrderDTO $order = null;
    private ?BuyerDTO $buyer = null;
    private ?AddressDTO $shippingAddress = null;
    private array $items = [];
    private ?BuyerHistoryDTO $buyerHistory = null;
    private ?array $orderHistory = null;

    public static function new(): self
    {
        return new self();
    }

    public function order(
        string|int $id,
        string $referenceId,
        float $amount,
        string $currency = 'SAR',
        string $description = ''
    ): self {
        $this->order = new PaymentOrderDTO(
            id: $id,
            referenceId: $referenceId,
            amount: $amount,
            currency: $currency,
            description: $description ?: "Order #{$id}"
        );

        return $this;
    }

    public function buyer(
        string $name,
        string $email,
        string $phone
    ): self {
        $this->buyer = new BuyerDTO(
            name: $name,
            email: $email,
            phone: $phone
        );

        return $this;
    }

    public function shippingAddress(
        string $city,
        string $address,
        ?string $zip = null,
        ?string $countryCode = 'SA'
    ): self {
        $this->shippingAddress = new AddressDTO(
            city: $city,
            address: $address,
            zip: $zip,
            countryCode: $countryCode
        );

        return $this;
    }

    public function item(
        string $referenceId,
        string $title,
        ?string $description = null,
        int $quantity = 1,
        float $unitPrice
    ): self {
        $this->items[] = new OrderItemDTO(
            referenceId: $referenceId,
            title: $title,
            description: $description,
            quantity: $quantity,
            unitPrice: $unitPrice
        );

        return $this;
    }

    public function buyerHistory(
        string $registeredSince,
        int $loyaltyLevel = 0,
        bool $isPhoneVerified = true,
        bool $isEmailVerified = false
    ): self {
        $this->buyerHistory = new BuyerHistoryDTO(
            registeredSince: $registeredSince,
            loyaltyLevel: $loyaltyLevel,
            isPhoneVerified: $isPhoneVerified,
            isEmailVerified: $isEmailVerified
        );

        return $this;
    }

    public function orderHistory(array $orderHistory): self
    {
        $this->orderHistory = $orderHistory;
        return $this;
    }

    public function build(): TabbyPaymentDTO
    {
        if ($this->order === null) {
            throw new \InvalidArgumentException('Order is required');
        }

        if ($this->buyer === null) {
            throw new \InvalidArgumentException('Buyer is required');
        }

        if ($this->shippingAddress === null) {
            throw new \InvalidArgumentException('Shipping address is required');
        }

        if (empty($this->items)) {
            throw new \InvalidArgumentException('At least one item is required');
        }

        return new TabbyPaymentDTO(
            order: $this->order,
            buyer: $this->buyer,
            shippingAddress: $this->shippingAddress,
            items: $this->items,
            buyerHistory: $this->buyerHistory,
            orderHistory: $this->orderHistory
        );
    }
}
