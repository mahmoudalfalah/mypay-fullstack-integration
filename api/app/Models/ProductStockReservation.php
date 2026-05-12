<?php

// Represents a temporary hold on product stock during the payment process
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductStockReservation extends Model
{
    // Fields that can be mass-assigned when reserving stock
    protected $fillable = [
        'checkout_session_id',
        'product_id',
        'quantity',
        'expires_at',
        'status',
    ];

    // Automatically cast to proper PHP types when reading from database
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',  // String → Carbon datetime object
        ];
    }

    // Each reservation belongs to a specific checkout session
    public function checkoutSession(): BelongsTo
    {
        return $this->belongsTo(CheckoutSession::class);
    }

    // Each reservation is for a specific product
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // Checks if this reservation is still valid (not expired, not consumed)
    public function isActive(): bool
    {
        // Active means: status is 'active' AND the expiry time hasn't passed
        return $this->status === ProductReservationStatus::ACTIVE->value
            && $this->expires_at->isFuture();
    }

    // Scope to quickly query only active reservations (used in stock calculations)
    public function scopeActive($query)
    {
        // Filters to rows where status='active' AND expires_at is in the future
        return $query->where('status', ProductReservationStatus::ACTIVE->value)
                     ->where('expires_at', '>', now());
    }
}