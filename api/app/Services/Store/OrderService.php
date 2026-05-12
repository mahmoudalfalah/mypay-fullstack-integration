<?php

namespace App\Services\Store;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\Shared\OrderNumberService;
use App\Enums\OrderStatusEnum;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use App\Exceptions\Inventory\InsufficientStockException;
use App\Exceptions\Inventory\ProductUnavailableException;

class OrderService
{
    public function createSnapshot(array $data) {  
        $productIds = array_column($data['products'], 'product_id');
        $products = Product::whereIn('id', $productIds)->lockForUpdate()->get()->keyBy('id');
        
        $itemsToInsert = [];
        $totalPrice = 0;
        $totalQuantity = 0;

        foreach ($data['products'] as $item) {
            $product = data_get($products, $item['product_id']);
            
            if (!$product) {
                throw new ProductUnavailableException("ERR_PRODUCT_UNAVAILABLE");
            }
            
            $availableStock = $product->available_stock;

            if ($availableStock < $item['quantity']) {
                throw new InsufficientStockException("ERR_INSUFFICIENT_STOCK");
            }

            $costBasePrice = (int) $product->cost_price;
            $retailBasePrice = (int) $product->retail_price;
            
            $discountValue = (int) ($product->discount ?? 0);
            $finalPrice = max(0, $retailBasePrice - $discountValue);




            $quantity = $item['quantity'];

            $itemsToInsert[] = [
                'product_id'            => $product->id,
                'product_name_snapshot' => $product->name,
                'product_sku_snapshot'  => $product->sku,
                'quantity'              => $quantity,
                'unit_retail_price'     => $retailBasePrice, 
                'unit_cost_price'       => $costBasePrice,
                'unit_discount'         => $discountValue,
                'total'                 => $finalPrice * $quantity, 
            ];

            $totalPrice += $finalPrice * $quantity;
            $totalQuantity += $quantity;

        }

        $orderData = [
            'customer_name'          => $data['customer_name'],
            'customer_phone'         => $data['customer_phone'],
            'customer_email'         => $data['customer_email'] ?? null,
            
            'city_id'                => $data['city_id'],
            'district_id'            => $data['district_id'],
            'address'                => $data['address'],
            
            'status'                 => OrderStatusEnum::PENDING->value, 
            
            'subtotal'               => $totalPrice, 
            'quantity'               => $totalQuantity,
            'total'                  => $totalPrice, 
            'notes'                  => $data['notes'] ?? null,
            'order_number'           => OrderNumberService::generate(),
        ];

        return [
            'order_data' => $orderData,
            'order_items' => $itemsToInsert,
            'total_price' => $totalPrice,
        ];
     }


    public function placeOrder(array $orderData, $orderItems): array
    {
        return DB::transaction(function () use ($orderData, $orderItems) {

            $orderProducts = Product::whereIn('id', array_column($orderItems, 'product_id'))->get()->keyBy('id');
            $itemMap = collect($orderItems)->keyBy('product_id');
            
            foreach ($orderProducts as $orderProduct) {
                $quantity = $itemMap[$orderProduct->id]['quantity'] ?? 0;
                $orderProduct->decrement('stock', $quantity);
            }

            $newOrder = Order::create($orderData);

            $now = now()->toDateTimeString();
            foreach ($orderItems as &$item) {
                $item['order_id'] = $newOrder->id;
                $item['created_at'] = $now;
                $item['updated_at'] = $now;
            }

            
            OrderItem::insert($orderItems);

            return [
                'order_id'     => $newOrder->id,
                'order_number' => $newOrder->order_number,
            ];
        });
    }
}