<?php

namespace App\Exceptions\Inventory;
class InsufficientStockException extends InventoryException
{
    public function __construct(int $productId, int $requested, int $available)
    {
        parent::__construct(
            "Product #{$productId}: requested {$requested}, available {$available}"
        );
    }
}