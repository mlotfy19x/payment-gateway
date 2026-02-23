<?php

namespace ML\PaymentGateway\Builders;

use ML\PaymentGateway\DTOs\AddressDTO;
use ML\PaymentGateway\DTOs\ConsumerDTO;
use ML\PaymentGateway\DTOs\PaymentOrderDTO;
use ML\PaymentGateway\DTOs\TamaraOrderItemDTO;
use ML\PaymentGateway\DTOs\TamaraPaymentDTO;

class TamaraPaymentDTOBuilder
{
    private ?PaymentOrderDTO $order = null;
    private ?ConsumerDTO $consumer = null;
    private ?AddressDTO $billingAddress = null;
    private ?AddressDTO $shippingAddress = null;
    private array $items = [];

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

    public function consumer(
        string $firstName,
        string $lastName,
        string $phoneNumber,
        string $email,
        string $dateOfBirth = '1990-01-01'
    ): self {
        $this->consumer = new ConsumerDTO(
            firstName: $firstName,
            lastName: $lastName,
            phoneNumber: $phoneNumber,
            email: $email,
            dateOfBirth: $dateOfBirth
        );

        return $this;
    }

    public function billingAddress(
        string $city,
        string $line1,
        ?string $line2 = null,
        ?string $zip = null,
        ?string $region = null,
        ?string $countryCode = 'SA',
        ?string $phoneNumber = null
    ): self {
        $this->billingAddress = new AddressDTO(
            city: $city,
            address: $line1,
            zip: $zip,
            countryCode: $countryCode,
            line1: $line1,
            line2: $line2,
            region: $region,
            phoneNumber: $phoneNumber
        );

        return $this;
    }

    public function shippingAddress(
        string $city,
        string $line1,
        ?string $line2 = null,
        ?string $zip = null,
        ?string $region = null,
        ?string $countryCode = 'SA',
        ?string $phoneNumber = null
    ): self {
        $this->shippingAddress = new AddressDTO(
            city: $city,
            address: $line1,
            zip: $zip,
            countryCode: $countryCode,
            line1: $line1,
            line2: $line2,
            region: $region,
            phoneNumber: $phoneNumber
        );

        return $this;
    }

    public function item(
        string $referenceId,
        string $type,
        string $name,
        string $sku,
        float $unitPrice,
        float $totalAmount,
        string $imageUrl = '',
        string $itemUrl = '',
        float $discountAmount = 0,
        int $quantity = 1
    ): self {
        $this->items[] = new TamaraOrderItemDTO(
            referenceId: $referenceId,
            type: $type,
            name: $name,
            sku: $sku,
            imageUrl: $imageUrl,
            itemUrl: $itemUrl,
            unitPrice: $unitPrice,
            discountAmount: $discountAmount,
            quantity: $quantity,
            totalAmount: $totalAmount
        );

        return $this;
    }

    public function build(): TamaraPaymentDTO
    {
        if ($this->order === null) {
            throw new \InvalidArgumentException('Order is required');
        }

        if ($this->consumer === null) {
            throw new \InvalidArgumentException('Consumer is required');
        }

        if ($this->billingAddress === null) {
            throw new \InvalidArgumentException('Billing address is required');
        }

        if ($this->shippingAddress === null) {
            throw new \InvalidArgumentException('Shipping address is required');
        }

        if (empty($this->items)) {
            throw new \InvalidArgumentException('At least one item is required');
        }

        return new TamaraPaymentDTO(
            order: $this->order,
            consumer: $this->consumer,
            billingAddress: $this->billingAddress,
            shippingAddress: $this->shippingAddress,
            items: $this->items
        );
    }
}
