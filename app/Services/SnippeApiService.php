<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class SnippeApiService
{
    protected string $apiKey;
    protected string $apiBase;
    protected string $sessionsApiBase;
    protected string $version;

    public function __construct()
    {
        $this->apiKey = config('snippe.api_key');
        $this->apiBase = config('snippe.api_base');
        $this->sessionsApiBase = config('snippe.api_base_sessions');
        $this->version = config('snippe.version');
    }

    // ── /v1 payments endpoint (SNIPPE_API_BASE = https://api.snippe.sh/v1) ──
    // The three payment methods below power the custom checkout form flow
    // (mobile / card / QR) - currently disabled in this demo. Kept for future use.

    /**
     * Mobile Money — customer receives a USSD push to authorize payment.
     * Docs: https://docs.snippe.sh/docs/2026-01-25/payments/mobile-money
     */
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

        // api_base = https://api.snippe.sh/v1 → endpoint = payments
        return $this->request('POST', 'payments', $payload);
    }

    /**
     * Card Payment — customer is redirected to a secure checkout page.
     * Docs: https://docs.snippe.sh/docs/2026-01-25/payments/card
     *
     * 💡 LEARNER'S NOTE — HOSTED CHECKOUT / PAYMENT SESSIONS
     * This is the "create session" step: Snippe returns a payment_url
     * (the Sessions API calls it checkout_url) to redirect the customer to.
     * redirectUrl/cancelUrl bring them back to your site, webhook_url receives
     * the async status update, and metadata is your reconciliation key.
     * Docs: https://docs.snippe.sh/docs/2026-01-25/sessions
     *       https://docs.snippe.sh/docs/2026-01-25/sessions/profiles
     *       https://docs.snippe.sh/docs/2026-01-25/sessions/payment-links
     */
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

    /**
     * Dynamic QR — generates a QR code the customer scans with their mobile money app.
     * Docs: https://docs.snippe.sh/docs/2026-01-25/payments/dynamic-qr
     */
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
    /**
     * 💡 LEARNER'S NOTE — PAYMENT SESSIONS / HOSTED CHECKOUT: creates a
     * hosted checkout session. The response's checkout_url is the Snippe page
     * the customer is redirected to — they fill/confirm their details and pay
     * there (mobile money). metadata.order_reference is how the webhook
     * reconciles the payment.
     * Docs: https://docs.snippe.sh/docs/2026-01-25/sessions
     *       https://docs.snippe.sh/docs/2026-01-25/sessions/payment-links
     *       https://docs.snippe.sh/docs/2026-01-25/sessions/profiles
     * NOTE: sessions live on the /api/v1 base (SNIPPE_API_BASE_SESSIONS), NOT
     * the /v1 payments base.
     */
    public function createSession(
        int $amount,                // Min 500. Suggested amount when allow_custom_amount is true
        string $currency = 'TZS',   // ISO 4217 (only TZS is supported)
        array $customer = [],       // Pre-fills checkout form (name, email, phone)
        ?string $redirectUrl = null, // Where to send customer after payment (max 500 chars)
        ?string $webhookUrl = null,  // Receives payment events (max 500 chars)
        ?array $metadata = null,     // Max 50 keys - your reconciliation data
        ?string $description = null, // Max 500 chars - shown on the checkout page
        ?array $allowedMethods = null, // Default ["mobile_money"]
        ?int $expiresIn = null,      // Default 3600s. Range 60-86400
        ?bool $allowCustomAmount = null, // Customer enters their own amount at checkout
        ?int $minAmount = null,      // Required when custom; min 500, must be < max_amount
        ?int $maxAmount = null,      // Required when custom; must be > min_amount
        ?string $profileId = null,   // Payment profile for branding (dashboard-managed)
        ?array $lineItems = null,    // Max 50 items. Display-only (show the cart)
        ?array $customFields = null, // Max 20 fields. Collect extra info at checkout
        ?array $display = null       // Checkout UI settings (theme, button text, etc.)
    ): array {
        $payload = [
            'amount' => $amount,
            'currency' => $currency,
        ];

        if ($allowedMethods) {
            $payload['allowed_methods'] = $allowedMethods;
        }

        if ($allowCustomAmount !== null) {
            $payload['allow_custom_amount'] = $allowCustomAmount;
        }

        if ($minAmount !== null) {
            $payload['min_amount'] = $minAmount;
        }

        if ($maxAmount !== null) {
            $payload['max_amount'] = $maxAmount;
        }

        if ($customer) {
            $payload['customer'] = $customer;
        }

        if ($profileId) {
            $payload['profile_id'] = $profileId;
        }

        if ($redirectUrl) {
            $payload['redirect_url'] = $redirectUrl;
        }

        if ($webhookUrl) {
            $payload['webhook_url'] = $webhookUrl;
        }

        if ($description) {
            $payload['description'] = $description;
        }

        if ($metadata) {
            $payload['metadata'] = $metadata;
        }

        if ($expiresIn) {
            $payload['expires_in'] = $expiresIn;
        }

        if ($lineItems) {
            $payload['line_items'] = $lineItems;
        }

        if ($customFields) {
            $payload['custom_fields'] = $customFields;
        }

        if ($display) {
            $payload['display'] = $display;
        }

        return $this->request('POST', 'sessions', $payload, $this->sessionsApiBase);
    }

    protected function request(string $method, string $endpoint, ?array $body = null, ?string $base = null): array
    {
        $url = rtrim($base ?? $this->apiBase, '/') . '/' . ltrim($endpoint, '/');

        $ch = curl_init();

        // Idempotency key prevents duplicate charges on retry.
        // Must be ≤ 30 chars per Snippe docs.
        $idempotency = 'duka_' . substr(str_replace('.', '', uniqid('', true)), 0, 25);

        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
            'Accept: application/json',
            'Snippe-Version: ' . $this->version,
            'User-Agent: duka-store/1.0.0',
            'Idempotency-Key: ' . $idempotency,
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

        // Pull the API's own error message out of common response shapes so the
        // UI shows the real reason instead of a generic fallback.
        $apiMessage = null;
        if (is_array($decoded)) {
            if (isset($decoded['message'])) {
                $apiMessage = $decoded['message'];
            } elseif (isset($decoded['error']) && is_string($decoded['error'])) {
                $apiMessage = $decoded['error'];
            } elseif (isset($decoded['error']['message'])) {
                $apiMessage = $decoded['error']['message'];
            } elseif (isset($decoded['detail'])) {
                $apiMessage = $decoded['detail'];
            } elseif (isset($decoded['data']['message'])) {
                $apiMessage = $decoded['data']['message'];
            }
        }

        if ($success) {
            Log::info('Snippe API response', [
                'url' => $url,
                'http_code' => $httpCode,
                'success' => $success,
                'body' => $decoded,
            ]);
        } else {
            Log::warning('Snippe API error', [
                'url' => $url,
                'http_code' => $httpCode,
                'body' => $decoded,
            ]);
        }

        return [
            'success' => $success,
            'http_code' => $httpCode,
            'data' => $decoded,
            'error' => $success ? null : ($apiMessage ?? ('HTTP ' . $httpCode . ' - ' . mb_substr($response, 0, 300))),
        ];
    }
}
