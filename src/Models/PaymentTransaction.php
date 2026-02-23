<?php

namespace ML\PaymentGateway\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use ML\PaymentGateway\Enums\PaymentStatusEnum;

class PaymentTransaction extends Model
{
    protected $guarded = [];
    protected $table = 'payment_transactions';

    protected $casts = [
        'response' => 'array',
        'gateway_response' => 'array',
        'status' => PaymentStatusEnum::class,
        'amount' => 'decimal:2',
    ];

    // Payment Gateway Constants
    public const GATEWAY_TABBY = 'tabby';
    public const GATEWAY_TAMARA = 'tamara';

    /**
     * Polymorphic relationship - can belong to any model
     */
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    // Query Scopes
    public function scopeSuccessful($query)
    {
        return $query->where('status', PaymentStatusEnum::SUCCESS->value);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', PaymentStatusEnum::FAILED->value);
    }

    public function scopePending($query)
    {
        return $query->where('status', PaymentStatusEnum::PENDING->value);
    }

    public function scopeByGateway($query, string $gateway)
    {
        return $query->where('payment_gateway', $gateway);
    }

    // Accessors
    public function getGatewayNameAttribute(): string
    {
        return match ($this->payment_gateway) {
            self::GATEWAY_TABBY => 'Tabby',
            self::GATEWAY_TAMARA => 'Tamara',
            default => ucfirst($this->payment_gateway ?? 'Unknown'),
        };
    }
}
