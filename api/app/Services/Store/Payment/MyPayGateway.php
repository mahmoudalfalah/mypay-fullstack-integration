<?php

namespace App\Services\Store\Payment;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Exceptions\MyPay\Gateway\MyPayApiException;
use App\Exceptions\MyPay\Gateway\AuthenticateException;

class MyPayGateway
{
    private string $clientId;
    private string $clientSecret;
    private string $baseUrl;
        
    public function __construct()
    {
        $this->clientId = config('services.mypay.client_id');
        $this->clientSecret = config('services.mypay.client_secret');
        $this->baseUrl = config('services.mypay.base_url', 'https://api.mypay.ly/v1');
    }

 
    public function createPayment(float $amount, string $returnUrl, string $cancelUrl, array $metadata = []): array
    {

        if (!$amount || $amount <= 0) {
            throw new MyPayApiException('Amount must be greater than 0');
        }
        
        if (empty($returnUrl)) {
            throw new MyPayApiException('Return URL is required');
        }
        
        if (empty($cancelUrl)) {
            throw new MyPayApiException('Cancel URL is required');
        }
        
        if (empty($metadata) || !isset($metadata['checkout_session_id'])) {
            throw new MyPayApiException('Metadata with checkout_session_id is required');
        }
        
        $payload = [
            'amount' => number_format($amount, 2, '.', ''),
            'currency'    => 'LYD',
            'return_url'  => $returnUrl,
            'cancel_url'  => $cancelUrl,
            'custom'      => (string) $metadata['checkout_session_id'],
        ];

        $response = $this->sendRequest('post', '/payment/create', $payload);

        

        return [
            'payment_id'  => data_get($response, 'data.token'),
            'payment_url' => data_get($response, 'data.payment_url'),
        ];
    }


    private function sendRequest(string $method, string $path, array $data = []): array
    {
        $accessToken = $this->authenticate();

        $url = $this->baseUrl . $path;

        $http = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ]);

        $response = match ($method) {
            'get'  => $http->get($url),
            'post' => $http->post($url, $data),
            default => throw new InvalidArgumentException("Unsupported HTTP method: $method"),
        };

        if (!$response->successful()) {
            Log::error('MyPay API request failed', [
                'method'     => strtoupper($method),
                'url'        => $url,
                'status'     => $response->status(),
                'response'   => $response->body(),
            ]);
            throw new MyPayApiException('MyPay API request failed: ' . $response->status());
        }

        return $response->json();
    }


    private function authenticate(): string
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ])->post($this->baseUrl . '/authentication/token', [
            'client_id'  => $this->clientId,
            'secret_id'  => $this->clientSecret,
        ]);

        if (!$response->successful()) {
            Log::error('MyPay authentication failed', [
                'status'   => $response->status(),
                'response' => $response->body(),
            ]);
            throw new AuthenticateException('MyPay authentication failed. Check your Client ID and Client Secret.');
        }

        $data = $response->json();
        $accessToken = data_get($data, 'data.access_token');

        if (!$accessToken) {
            throw new AuthenticateException('MyPay authentication succeeded, but no token was returned.');
        }

        return $accessToken;
    }

}
