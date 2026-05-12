<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Store\StoreOrderRequest;
use App\Http\Resources\Store\CheckoutSessionResource;
use App\Services\Store\CheckoutService;
use Illuminate\Support\Facades\Log;
use App\Exceptions\Checkout\SessionNotFoundException;
use App\Exceptions\Inventory\ProductUnavailableException;
use App\Exceptions\Inventory\InsufficientStockException;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CheckoutService $checkoutService,
    ) {}

    public function show($token)
    {
        try {
            $session = $this->checkoutService->getSession($token);

            return new CheckoutSessionResource($session);
            
        } catch (SessionNotFoundException $e) {
            return response()->json(['message' => 'Checkout session not found.'], 404);
            
        } catch (\Exception $e) {
            Log::error('Store failed to fetch checkout session: ' . $e->getMessage());
            return response()->json(['message' => 'An error occurred while loading the session.'], 500);
        }
    }

    public function store(StoreOrderRequest $request)
    {
        try {
            $result = $this->checkoutService->initiate($request->validated());

            return response()->json([
                'data' => $result
            ], 201);
            
        } catch (ProductUnavailableException | InsufficientStockException $e) {
            return response()->json([
                "error_code" => $e->getMessage()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('Order/Checkout placement failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                "message" => "An unexpected error occurred while placing the order."
            ], 500);
        }
    }

    public function markCartCleared(string $token)
    {
        try {
            $session = $this->checkoutService->markCartCleared($token);

            return new CheckoutSessionResource($session);
            
        } catch (SessionNotFoundException $e) {
            return response()->json(['message' => 'Checkout session not found.'], 404);
            
        } catch (\Exception $e) {
            Log::error('Store failed to mark cart cleared: ' . $e->getMessage());
            return response()->json(['message' => 'An error occurred while updating the cart status.'], 500);
        }
    }
}