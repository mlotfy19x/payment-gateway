<?php

namespace MLQuarizm\PaymentGateway\Factory;

use InvalidArgumentException;
use MLQuarizm\PaymentGateway\Contracts\PaymentGatewayInterface;
use MLQuarizm\PaymentGateway\Gateways\TabbyPaymentService;
use MLQuarizm\PaymentGateway\Gateways\TamaraPaymentService;

class PaymentGatewayFactory
{
    /**
     * Create a payment gateway instance
     *
     * @param string|null $provider Gateway provider (tabby, tamara)
     *                              If null or empty, defaults to 'tabby'
     * @return PaymentGatewayInterface
     * @throws InvalidArgumentException If provider is not supported
     */
    public function make(?string $provider = null): PaymentGatewayInterface
    {
        $provider = strtolower(trim($provider ?? ''));

        if (empty($provider)) {
            $provider = 'tabby';
        }

        return match ($provider) {
            'tabby' => new TabbyPaymentService(),
            'tamara' => new TamaraPaymentService(),
            default => throw new InvalidArgumentException(
                "Unsupported payment gateway: {$provider}. Supported gateways: " . implode(', ', self::getSupportedGateways())
            )
        };
    }

    /**
     * Get list of supported payment gateways
     *
     * @return array
     */
    public static function getSupportedGateways(): array
    {
        return ['tabby', 'tamara'];
    }

    /**
     * Get the default payment gateway
     *
     * @return string
     */
    public static function getDefaultGateway(): string
    {
        return 'tabby';
    }
}
