<?php

namespace App\Enums;

enum CheckoutSessionStatus: string
{
    case INITIATED = 'initiated';
    case PENDING = 'payment_pending';
    case COMPLETED = 'completed';
    case FAILED = 'payment_creation_failed'; 
    case EXPIRED = 'expired';
}