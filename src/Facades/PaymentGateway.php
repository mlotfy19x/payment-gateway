<?php

namespace MLQuarizm\PaymentGateway\Facades;

use Illuminate\Support\Facades\Facade as BaseFacade;
use MLQuarizm\PaymentGateway\Models\PaymentTransaction;
use MLQuarizm\PaymentGateway\Services\PaymentGatewayService;

/**
 * @method static PaymentTransaction recordTransaction(\Illuminate\Database\Eloquent\Model $payable, string $trackId, ?string $paymentId, string $gateway, float $amount, array $response = [])
 *
 * @see PaymentGatewayService
 */
class PaymentGateway extends BaseFacade
{
    protected static function getFacadeAccessor(): string
    {
        return PaymentGatewayService::class;
    }
}
