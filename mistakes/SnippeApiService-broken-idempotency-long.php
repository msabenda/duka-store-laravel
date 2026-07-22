<?php
// 🚨 MISTAKE 2A: Idempotency key can exceed 30 characters
//
// What's wrong?  The idempotency key generator uses full order reference
// + timestamp, which can easily exceed Snippe's 30-character limit.
// Keys longer than 30 chars return a 500 PAY_001 error — misleading
// because it looks like the payment processor is down.
//
// The error:
//   { "status": "error", "code": 500,
//     "error_code": "PAY_001",
//     "message": "Failed to initiate payment" }
//
// Fix: Keep idempotency key under 30 characters (use substr + random data)
//      See the original SnippeApiService.php for the correct implementation
// --------------------------------------------------------------------------

namespace App\Services;

use Illuminate\Support\Facades\Log;

class SnippeApiService
{
    protected string $apiKey;
    protected string $apiBase;
    protected string $version;

    public function __construct()
    {
        $this->apiKey = config('snippe.api_key');
        $this->apiBase = config('snippe.api_base');
        $this->version = config('snippe.version');
    }

    public function createMobilePayment(
        int $amount,
        string $currency,
        string $phoneNumber,
        array $customer,
        ?string $webhookUrl = null,
        ?array $metadata = null
    ): array {
        $payload = [
            'payment_type' => 'mobile',
            'details' => [
                'amount' => $amount,
                'currency' => $currency,
            ],
            'phone_number' => $phoneNumber,
            'customer' => [
                'firstname' => $customer['firstname'] ?? '',
                'lastname'  => $customer['lastname'] ?? '',
                'email'     => $customer['email'] ?? '',
            ],
        ];

        if ($webhookUrl) {
            $payload['webhook_url'] = $webhookUrl;
        }

        if ($metadata) {
            $payload['metadata'] = $metadata;
        }

        return $this->request('POST', 'payments', $payload);
    }

    public function createCardPayment(
        int $amount,
        string $currency,
        string $redirectUrl,
        string $cancelUrl,
        array $customer,
        ?string $phoneNumber = null,
        ?string $webhookUrl = null,
        ?array $metadata = null
    ): array {
        $payload = [
            'payment_type' => 'card',
            'details' => [
                'amount' => $amount,
                'currency' => $currency,
                'redirect_url' => $redirectUrl,
                'cancel_url' => $cancelUrl,
            ],
            'customer' => [
                'firstname' => $customer['firstname'] ?? '',
                'lastname'  => $customer['lastname'] ?? '',
                'email'     => $customer['email'] ?? '',
                'address'   => $customer['address'] ?? '',
                'city'      => $customer['city'] ?? '',
                'state'     => $customer['state'] ?? '',
                'postcode'  => $customer['postcode'] ?? '',
                'country'   => $customer['country'] ?? 'TZ',
            ],
        ];

        if ($phoneNumber) {
            $payload['phone_number'] = $phoneNumber;
        }

        if ($webhookUrl) {
            $payload['webhook_url'] = $webhookUrl;
        }

        if ($metadata) {
            $payload['metadata'] = $metadata;
        }

        return $this->request('POST', 'payments', $payload);
    }

    public function createQrPayment(
        int $amount,
        string $currency,
        array $customer,
        ?string $redirectUrl = null,
        ?string $cancelUrl = null,
        ?string $phoneNumber = null,
        ?string $webhookUrl = null,
        ?array $metadata = null
    ): array {
        $payload = [
            'payment_type' => 'dynamic-qr',
            'details' => [
                'amount' => $amount,
                'currency' => $currency,
            ],
            'customer' => [
                'firstname' => $customer['firstname'] ?? '',
                'lastname'  => $customer['lastname'] ?? '',
                'email'     => $customer['email'] ?? '',
            ],
        ];

        if ($redirectUrl) {
            $payload['details']['redirect_url'] = $redirectUrl;
        }

        if ($cancelUrl) {
            $payload['details']['cancel_url'] = $cancelUrl;
        }

        if ($phoneNumber) {
            $payload['phone_number'] = $phoneNumber;
        }

        if ($webhookUrl) {
            $payload['webhook_url'] = $webhookUrl;
        }

        if ($metadata) {
            $payload['metadata'] = $metadata;
        }

        return $this->request('POST', 'payments', $payload);
    }

    /**
     * Raw cURL request to the Snippe API.
     */
    protected function request(string $method, string $endpoint, ?array $body = null): array
    {
        $url = rtrim($this->apiBase, '/') . '/' . ltrim($endpoint, '/');

        $ch = curl_init();

        // 🚨 BUG: Idempotency key can exceed 30 characters!
        // This builds a key like "duka-order-DUKA-ABC123-1768912345" (40+ chars)
        // Snippe returns 500 PAY_001 for keys > 30 characters.
        $orderRef = $body['metadata']['order_reference'] ?? 'unknown';
        $idempotency = 'duka-order-' . $orderRef . '-' . time();

        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
            'Accept: application/json',
            'Snippe-Version: ' . $this->version,
            'User-Agent: duka-store/1.0.0',
            'Idempotency-Key: ' . $idempotency,   // 🚨 likely > 30 chars!
        ];

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => $headers,
        ]);

        if ($body !== null && in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'])) {
            $encoded = json_encode($body);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $encoded);
        }

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            Log::error('Snippe API curl error', [
                'url' => $url,
                'method' => $method,
                'error' => $error,
            ]);

            return [
                'success' => false,
                'error' => $error,
                'http_code' => $httpCode,
            ];
        }

        $decoded = json_decode($response, true);

        $success = $httpCode >= 200 && $httpCode < 300;

        Log::info('Snippe API response', [
            'url' => $url,
            'http_code' => $httpCode,
            'success' => $success,
            'body' => $decoded,
        ]);

        return [
            'success' => $success,
            'http_code' => $httpCode,
            'data' => $decoded,
        ];
    }
}
