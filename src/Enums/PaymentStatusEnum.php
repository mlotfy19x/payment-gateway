<?php

namespace ML\PaymentGateway\Enums;

enum PaymentStatusEnum: string
{
    case PENDING = 'pending';
    case SUCCESS = 'success';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::SUCCESS => 'Success',
            self::FAILED => 'Failed',
            self::REFUNDED => 'Refunded',
            self::CANCELLED => 'Cancelled',
        };
    }

    public static function getColor(string $status): string
    {
        return match ($status) {
            'success' => '#00b300',
            'pending' => '#ffcc00',
            'failed' => '#ff0000',
            'refunded' => '#0066cc',
            'cancelled' => '#666666',
            default => '#000000',
        };
    }
}
