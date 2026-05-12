<?php

namespace App\Exceptions\Inventory;
class ProductUnavailableException extends InventoryException
{
    public function __construct(int $productId)
    {
        parent::__construct("Product #{$productId} is unavailable");
    }
}