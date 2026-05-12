<?php

namespace App\Http\Requests\Store;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation()
    {
        if ($this->has('products') && is_string($this->products)) {
            $this->merge([
                'products' => json_decode($this->products, true)
            ]);
        }
    }

    public function rules(): array 
    {
        return [
            'customer_name'          => ['required', 'string', 'max:255'],
            'customer_phone'         => ['required', 'string', 'max:30'],
            'customer_email'         => ['nullable', 'email', 'max:255'],
            
            'city_id'                => ['nullable', 'integer', 'exists:cities,id'], 
            'district_id'            => ['required', 'integer', 'exists:districts,id'],
            'address'                => ['required', 'string', 'max:500'],
            
            'products'               => ['required', 'array'], 
            'products.*.product_id'  => ['required', 'integer'],
            'products.*.quantity'    => ['required', 'integer', 'min:1'],
            
            'clear_cart'             => ['nullable', 'boolean'],
            
            'notes'                  => ['nullable', 'string', 'max:1000'],
        ];
    }
}