<?php

namespace App\Services\Store;

use App\Models\CheckoutSession;
use Illuminate\Support\Facades\Log;
use App\Exceptions\MyPay\InvalidWebhookSignatureException;
use App\Exceptions\MyPay\InvalidWebhookPayloadException;

class MyPayWebhookService
{
    public function __construct(
        private readonly CheckoutService $checkoutService,
    ) {}

    public function process(string $payload, string $receivedSignature): void
    {

        Log::info('MyPay webhook received', ['payload' => $payload, 'signature' => $receivedSignature]);
        $webhookSecret = config('services.mypay.webhook_secret');

        $calculatedSignature = hash_hmac('sha256', $payload, $webhookSecret);

        if (!hash_equals($calculatedSignature, $receivedSignature)) {
            throw new InvalidWebhookSignatureException('Invalid webhook signature');
        }


        $data = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

    
        
        $event = data_get($data, 'event');

        if ($event !== 'payment.success') {
            Log::info('MyPay webhook: non-success event ignored', ['event' => $event]);
            return;
        }
        
        $paymentToken = $data['token'] ?? null;
        $sessionId = $data['custom'] ?? null;
        if (!$paymentToken || !$sessionId) {
            throw new InvalidWebhookPayloadException(
                'Webhook payload missing required fields: ' . implode(', ', array_filter([
                    !$paymentToken ? 'token' : null,
                    !$sessionId ? 'custom' : null,
                ]))
            );
        }

        $result = $this->checkoutService->fulfill($paymentToken, $sessionId);
    }
}