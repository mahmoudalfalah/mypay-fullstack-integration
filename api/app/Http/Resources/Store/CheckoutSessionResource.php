<?php

namespace App\Http\Resources\Store;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckoutSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'token'            => $this->token,
            'paymentId'        => $this->mypay_payment_id,
            'orderNumber'      => data_get($this->order_data, 'order_number'),
            'orderData'        => $this->order_data,
            'status'           => $this->status,
            'expiresAt'        => $this->expires_at,
            'clearCart'        => (bool) $this->clear_cart,
            'cartClearedAt'    => $this->cart_cleared_at,
            'createdAt'        => $this->created_at,
            'updatedAt'        => $this->updated_at,
        ];
    }
}
