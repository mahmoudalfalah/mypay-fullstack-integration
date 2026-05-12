<?php

namespace App\Http\Resources\Store;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $rawImages = is_array($this->images) ? $this->images : [];
        $formattedImages = array_map(function ($img) {
            return [
                'id'   => $img,
                'url'  => asset('storage/' . $img), 
                'name' => basename($img)
            ];
        }, $rawImages);

        $retailPrice = (float) ($this->retail_price / 100);
        $discount = $this->discount ? (float) ($this->discount / 100) : 0;
        $salePrice = max(0, $retailPrice - $discount);

        $currentAvailableStock = $this->available_stock;
        $maxPurchaseLimit = 10;
        $stockStatus = 'OUT_OF_STOCK';
        if ($currentAvailableStock > $maxPurchaseLimit) {
            $stockStatus = 'IN_STOCK';
        } elseif ($currentAvailableStock > 0) {
            $stockStatus = 'LOW_STOCK';
        }

        return [
            'id'                => $this->id,
            'slug'              => $this->slug,
            'sku'               => $this->sku,
            'name'              => $this->name,
            'images'            => $formattedImages,
            
            'retailPrice'       => $retailPrice, 
            'discount'          => $discount,
            'salePrice'         => $salePrice,
            'hasDiscount'       => $discount > 0,
            
            'inStock'           => $currentAvailableStock > 0,
            'stockStatus'       => $stockStatus,
            'availableQuantity' => min($currentAvailableStock, $maxPurchaseLimit),
            
            'description'       => $this->description,
            'specifications'    => $this->specifications,
            'features'          => $this->features,
            'instructions'      => $this->instructions,
            
            'brandId'           => $this->brand_id,
            'categoryId'        => $this->category_id,
            'isNewArrival'      => (bool) $this->is_new_arrival,
            
            'category'          => $this->whenLoaded('category', fn() => [
                'id'   => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug ?? null,
            ]),
            'brand'             => $this->whenLoaded('brand', fn() => [
                'id'   => $this->brand->id,
                'name' => $this->brand->name,
            ])
        ];
    }
}