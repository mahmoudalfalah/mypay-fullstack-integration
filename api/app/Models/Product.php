<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\VariationOption;
use App\Models\ProductVariation;
use App\Models\OrderItem;
use App\Models\Category;
use App\Models\FilterValueProductMapping;
use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\Builder;
use App\Core\Filters\QueryFilter;
use App\Models\Brand;
use App\Enums\ProductReservationStatus;

    
class Product extends Model
{
    use HasFactory, Searchable;


    
    public function searchableAs()
    {
        return 'products';
    }
    public function toSearchableArray() 
    {
        $this->loadMissing(['category', 'brand']);

        return [
            'id'             => $this->id,
            'name'           => $this->name, 
            'sku'            => $this->sku,
            
            'category_name'  => $this->category?->name,
            'brand_name'     => $this->brand?->name,
            
            'retail_price'   => (int) $this->retail_price,
            'is_new_arrival' => (bool) $this->is_new_arrival,
            'is_visible'     => (bool) $this->is_visible,
            'discount'       => (int) $this->discount,
            'created_at'     => $this->created_at?->timestamp,
        ];
    }

    protected $fillable = [
        'name', 
        'slug',
        'sku', 
        'retail_price', 
        'cost_price', 
        'stock', 
        'category_id', 
        'brand_id', 
        'is_new_arrival',
        'discount', 
        'images', 
        'description', 
        'specifications', 
        'features', 
        'instructions', 
        'is_visible'
    ];

    protected $casts = [
        'is_new_arrival' => 'boolean',
        'is_visible' => 'boolean',
        'images' => 'array'
    ];





    public function orderItems() {
        return $this->hasMany(OrderItem::class);
    }


    public function category() {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function productStatistics()
    {
        return $this->hasMany(ProductStatistic::class);
    }
    public function brand() {
        return $this->belongsTo(Brand::class, 'brand_id');
    }
    
    public function productStockReservation()
    {
        return $this->hasMany(ProductStockReservation::class);
    }

    public function activeReservations()  {
        return $this->hasMany(ProductStockReservation::class)
            ->where('status', ProductReservationStatus::ACTIVE->value)
            ->where('expires_at', '>', now());
    }

    public function getAvailableStockAttribute(): int
    {
        $reservedQuantity = $this->active_reservations_sum_quantity ?? $this->activeReservations()->sum('quantity');
        return max(0, $this->stock - $reservedQuantity);
    }

    public function scopeFilter(Builder $query, QueryFilter $filter): Builder {
        return $filter->apply($query);
    }

}
    