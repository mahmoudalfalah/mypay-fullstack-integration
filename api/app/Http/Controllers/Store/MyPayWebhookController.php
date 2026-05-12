<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Services\Store\MyPayWebhookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Exceptions\MyPay\Webhook\InvalidWebhookSignatureException;
use App\Exceptions\MyPay\Webhook\InvalidWebhookPayloadException;

class MyPayWebhookController extends Controller
{
    public function __construct(
        private readonly MyPayWebhookService $webhookService,
    ) {}

    public function receivePaymentConfirmation(Request $request)
    {
        
        $rawPayload = $request->getContent();
        $signature = $request->header('X-MyPay-Signature');

        if (!$signature) {
            Log::warning('MyPay webhook missing signature header.');
            return response()->json(['error' => 'Missing signature header'], 400);
        }

        try {
            $this->webhookService->process($rawPayload, $signature);
            return response()->json(['received' => true]);
            
        } catch (InvalidWebhookSignatureException $e) {
            Log::warning('MyPay webhook signature verification failed: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 401);
            
        } catch (InvalidWebhookPayloadException $e) {
            Log::warning('MyPay webhook invalid payload: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 400);
            
        } catch (\Exception $e) {
            Log::error('Webhook processing failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['received' => true], 200);
        }
    }
}