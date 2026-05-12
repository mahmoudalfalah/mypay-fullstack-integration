<?php

// Represents a single checkout attempt — stores order data while customer goes to pay
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CheckoutSession extends Model
{
    protected $fillable = [
        'token',
        'mypay_payment_id',
        'order_id',
        'order_data',
        'order_items',
        'status',
        'clear_cart',
        'cart_cleared_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'order_data'      => 'array',     // JSON column → PHP array
            'order_items'     => 'array',     // JSON column → PHP array
            'expires_at'      => 'datetime',  // String → Carbon datetime object
            'cart_cleared_at' => 'datetime',  // Nullable timestamp set when cart was cleared
            'clear_cart'      => 'boolean',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function stockReservations(): HasMany
    {
        return $this->hasMany(ProductStockReservation::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast() && !$this->isCompleted();
    }
}