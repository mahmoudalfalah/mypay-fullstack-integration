<?php

namespace App\Services\Store;

use App\Models\CheckoutSession;
use App\Models\Product;
use App\Models\ProductStockReservation;
use App\Services\Store\Payment\MyPayGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Order;
use App\Enums\ProductReservationStatus;
use App\Enums\CheckoutSessionStatus;
use InvalidArgumentException;
use App\Exceptions\Checkout\SessionExpiredException;
use App\Exceptions\Checkout\SessionNotFoundException;
use App\Exceptions\Inventory\InsufficientStockException;
use App\Exceptions\Inventory\ProductUnavailableException;

class CheckoutService
{

    public function __construct(
        private readonly MyPayGateway $paymentGateway,
        private readonly OrderService $orderService,
    ) {}



    public function getSession(string $token): CheckoutSession
    {
        $session = CheckoutSession::where('token', $token)->first();
        
        if (!$session) {
            throw new SessionNotFoundException("ERR_SESSION_NOT_FOUND");
        }
        
        return $session;
    }

    public function markCartCleared(string $token): CheckoutSession
    {
        $session = $this->getSession($token);

        if ($session->cart_cleared_at === null) {
            $session->update(['cart_cleared_at' => now()]);
        }

        return $session->fresh();
    }

    public function initiate(array $data): array
    {
        $ctx = DB::transaction(function () use ($data) {
            

            $orderSnapshot = $this->orderService->createSnapshot($data);


            $token = Str::random(64);

            $reservedUntil = now()->addMinutes(10);

            $session = CheckoutSession::create([
                'token'      => $token,                       
                'order_data' => data_get($orderSnapshot, 'order_data'),
                'order_items' => data_get($orderSnapshot, 'order_items'),                        
                'clear_cart' => $data['clear_cart'] ?? false, 
                'status'     => CheckoutSessionStatus::INITIATED->value,
                'expires_at' => $reservedUntil,        
            ]);
                
           

            foreach ($data['products'] as $item) {
                ProductStockReservation::create([
                    'checkout_session_id' => $session->id,
                    'product_id'          => $item['product_id'],
                    'quantity'            => $item['quantity'],
                    'expires_at'          => $reservedUntil,
                    'status'              => ProductReservationStatus::ACTIVE->value,
                ]);
            }            

            $baseUrl = rtrim(config('app.frontend_url'), '/');

            $returnUrl = $baseUrl . '/checkout/callback?token=' . $session->token;
            $cancelUrl = $baseUrl . '/checkout/callback?token=' . $session->token . '&status=cancelled';
            

            $totalAmountCents = data_get($orderSnapshot, 'total_price');
        
            if ($totalAmountCents === null) {
                throw new \Exception('Error calculating the total amount');
            }
            
            $totalAmount = $totalAmountCents / 100;


            return [
                'session'     => $session,
                'totalAmount' => $totalAmount,
                'returnUrl'   => $returnUrl,
                'cancelUrl'   => $cancelUrl,
            ];
        });

        try {
            $paymentResult = $this->paymentGateway->createPayment(
                amount: $ctx['totalAmount'],
                returnUrl: $ctx['returnUrl'],
                cancelUrl: $ctx['cancelUrl'],
                metadata: [
                    'checkout_session_id' => $ctx['session']->id,
                ],
            );
        }
        catch (\Exception $e) {
            $ctx['session']->update(['status' => CheckoutSessionStatus::FAILED->value]);
            ProductStockReservation::where('checkout_session_id', $ctx['session']->id)
                ->update(['status' => ProductReservationStatus::CANCELLED->value]);
            throw $e;
        }

         
        $ctx['session']->update([
            'mypay_payment_id' => $paymentResult['payment_id'],
            'status' => CheckoutSessionStatus::PENDING->value,
        ]);

        
        return [
            'conf' => 'success',
            'result' => $paymentResult,
            'payment_url' => $paymentResult['payment_url'],
            'token'       => $ctx['session']->token,
        ];
    }

    public function fulfill(string $paymentToken, string $sessionId): array
    {
        return DB::transaction(function () use ($paymentToken, $sessionId) {
            $session = CheckoutSession::where('id', $sessionId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($session->status === CheckoutSessionStatus::COMPLETED->value) {
                $order = Order::find($session->order_id);
                
                if (!$order) {
                    throw new \Exception('Order not found');
                }
                
                return [
                    'order_id'     => $order->id,
                    'order_number' => $order->order_number,
                ];
            }

            $orderResult = $this->orderService->placeOrder($session->order_data, $session->order_items);

            ProductStockReservation::where('checkout_session_id', $session->id)
                ->where('status', ProductReservationStatus::ACTIVE->value)
                ->update(['status' => ProductReservationStatus::CONSUMED->value]);


            $session->update([
                'status'   => CheckoutSessionStatus::COMPLETED->value,
                'order_number' => data_get($orderResult, 'order_number'),
            ]);

            return $orderResult;
        });
    }
}