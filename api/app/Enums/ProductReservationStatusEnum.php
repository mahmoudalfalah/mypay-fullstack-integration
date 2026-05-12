<?php

namespace App\Enums;

enum ProductReservationStatus: string
{
    case ACTIVE = 'active';
    case CONSUMED = 'consumed';
    case EXPIRED = 'expired';
    case CANCELLED = 'cancelled';

    public function label(): string {
        return match($this) {
            self::ACTIVE => 'Active',
            self::CONSUMED => 'Consumed',
            self::EXPIRED => 'Expired',
            self::CANCELLED => 'Cancelled',
        };
    }
}