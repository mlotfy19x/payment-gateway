<?php

namespace MLQuarizm\PaymentGateway\Builders;

use MLQuarizm\PaymentGateway\DTOs\AddressDTO;
use MLQuarizm\PaymentGateway\DTOs\BuyerDTO;
use MLQuarizm\PaymentGateway\DTOs\BuyerHistoryDTO;
use MLQuarizm\PaymentGateway\DTOs\OrderHistoryDTO;
use MLQuarizm\PaymentGateway\DTOs\OrderItemDTO;
use MLQuarizm\PaymentGateway\DTOs\PaymentOrderDTO;
use MLQuarizm\PaymentGateway\DTOs\TabbyPaymentDTO;

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

    /**
     * Add a single item to the order
     *
     * @param string $referenceId
     * @param string $title
     * @param string|null $description
     * @param int $quantity
     * @param float $unitPrice
     * @return $this
     */
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

    /**
     * Add multiple items to the order at once
     *
     * @param array $items Array of OrderItemDTO instances or arrays that can be converted to OrderItemDTO
     * @return $this
     */
    public function items(array $items): self
    {
        foreach ($items as $item) {
            if ($item instanceof OrderItemDTO) {
                $this->items[] = $item;
            } elseif (is_array($item)) {
                $this->items[] = new OrderItemDTO(
                    referenceId: $item['referenceId'] ?? $item['reference_id'] ?? '',
                    title: $item['title'] ?? $item['name'] ?? '',
                    description: $item['description'] ?? null,
                    quantity: $item['quantity'] ?? 1,
                    unitPrice: $item['unitPrice'] ?? $item['unit_price'] ?? 0.0
                );
            } else {
                throw new \InvalidArgumentException('Items must be instances of OrderItemDTO or arrays');
            }
        }

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
