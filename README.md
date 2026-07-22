# Duka Store - Snippe Payment Integration (Laravel 12)

A Laravel store that collects payments via **Mobile Money**, **Cards**, and **QR Codes** using the Snippe Payments API. Raw cURL, no SDK.

Use this as a reference when integrating Snippe into any Laravel or PHP project.

---

## Quick Start

```bash
composer install
npm install && npm run build
cp .env.example .env
```

Set these in `.env`:

```
SNIPPE_API_KEY=snp_your_key_here
APP_URL=https://your-domain.com      # Must be HTTPS - Snippe sends webhooks here
```

Then:

```bash
php artisan serve
# → http://localhost:8000
```

> **For local testing:** run `ngrok http 8000`, copy the HTTPS URL, and set `APP_URL` to it.
> Snippe requires a reachable HTTPS webhook URL.

---

## Integration Points

There are exactly **four** places where your Laravel app talks to Snippe. Everything else is store UI.

| # | File | What it does |
|---|---|---|
| 1 | `config/snippe.php` | API credentials and base URL |
| 2 | `app/Services/SnippeApiService.php` | Raw cURL calls to Snippe |
| 3 | `app/Http/Controllers/CheckoutController.php` | Creates payments and redirects customers |
| 4 | `app/Http/Controllers/WebhookController.php` | Handles async payment status updates |

---

## 1. Configuration - `config/snippe.php`

```php
return [
    'api_key'          => env('SNIPPE_API_KEY'),
    'api_base'         => env('SNIPPE_API_BASE', 'https://api.snippe.sh/v1'),
    'webhook_secret'   => env('SNIPPE_WEBHOOK_SECRET'),
    'version'          => '2026-01-25',
];
```

Every API call sends these headers automatically:

| Header | Value |
|---|---|
| `Authorization` | `Bearer {api_key}` |
| `Snippe-Version` | `2026-01-25` |
| `Idempotency-Key` | Unique per request (≤ 30 chars) |
| `Content-Type` | `application/json` |

---

## 2. Accepting Payments - `POST /v1/payments`

There are three payment types. Each one maps to a different API call.

### Mobile Money

Customer gets a USSD push on their phone.

**Request:**

```php
use Illuminate\Support\Facades\Http; // or your HTTP client of choice

$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . config('snippe.api_key'),
    'Snippe-Version' => '2026-01-25',
    'Idempotency-Key' => 'order_' . uniqid(),
])->post('https://api.snippe.sh/v1/payments', [
    'payment_type' => 'mobile',
    'details' => [
        'amount' => 500,
        'currency' => 'TZS',
    ],
    'phone_number' => '255781000000',
    'customer' => [
        'firstname' => 'John',
        'lastname'  => 'Doe',
        'email'     => 'john@example.com',
    ],
    'webhook_url' => 'https://your-domain.com/webhook',
    'metadata' => [
        'order_reference' => 'DUKA-ABC123',
    ],
]);
```

**Response:**

```json
{
  "status": "success",
  "code": 201,
  "data": {
    "reference": "9015c155-9e29-4e8e-8fe6-d5d81553c8e6",
    "status": "pending",
    "payment_type": "mobile",
    "amount": { "currency": "TZS", "value": 500 },
    "expires_at": "2026-01-25T05:04:54.063Z",
    "object": "payment"
  }
}
```

The USSD push is sent automatically. Customer enters their PIN on their phone to authorise.

**Required fields:**

| Field | Type | Description |
|---|---|---|
| `payment_type` | string | Must be `"mobile"` |
| `details.amount` | integer | Amount in smallest unit (e.g. 500 = 500 TZS) |
| `details.currency` | string | `"TZS"` (Tanzanian Shilling - the only supported currency) |
| `phone_number` | string | Customer phone in international format (`255XXXXXXXXX`) |
| `customer.firstname` | string | Customer's first name |
| `customer.lastname` | string | Customer's last name |
| `customer.email` | string | Customer's email address |

**Optional fields:** `webhook_url`, `metadata`, `details.redirect_url`, `details.cancel_url`

---

### Card Payment

Customer is redirected to a secure hosted checkout page where they enter card details.

**Request:**

```php
$response = Http::withHeaders([...])->post('https://api.snippe.sh/v1/payments', [
    'payment_type' => 'card',
    'details' => [
        'amount' => 500,
        'currency' => 'TZS',
        'redirect_url' => 'https://your-domain.com/order/success/DUKA-ABC123',
        'cancel_url'   => 'https://your-domain.com/order/cancelled',
    ],
    'customer' => [
        'firstname' => 'John',
        'lastname'  => 'Doe',
        'email'     => 'john@example.com',
        'address'   => '123 Main St',
        'city'      => 'Dar es Salaam',
        'state'     => 'DSM',
        'postcode'  => '14101',
        'country'   => 'TZ',
    ],
    'webhook_url' => 'https://your-domain.com/webhook',
]);
```

**Response:**

```json
{
  "status": "success",
  "code": 201,
  "data": {
    "reference": "2e0bcc5f-92ca-44f9-8c1b-4d2966d9921f",
    "status": "pending",
    "payment_type": "card",
    "payment_url": "https://tz.selcom.online/paymentgw/checkout/...",
    "amount": { "currency": "TZS", "value": 500 },
    "object": "payment"
  }
}
```

**Your next step:** Redirect the customer to `payment_url`. Snippe handles the rest - card entry, 3D Secure, confirmation.

**Additional required fields for card:**

| Field | Type | Description |
|---|---|---|
| `details.redirect_url` | string | Where to send the customer after successful payment |
| `details.cancel_url` | string | Where to send the customer on cancel/failure |
| `customer.address` | string | Billing address |
| `customer.city` | string | Billing city |
| `customer.state` | string | Billing state/region |
| `customer.postcode` | string | Billing postal code |
| `customer.country` | string | ISO 3166-1 alpha-2 country code (e.g. `"TZ"`) |

---

### Dynamic QR

Generates a QR code the customer scans with their mobile money app.

**Request:**

```php
$response = Http::withHeaders([...])->post('https://api.snippe.sh/v1/payments', [
    'payment_type' => 'dynamic-qr',
    'details' => [
        'amount' => 500,
        'currency' => 'TZS',
    ],
    'customer' => [
        'firstname' => 'John',
        'lastname'  => 'Doe',
        'email'     => 'john@example.com',
    ],
    'webhook_url' => 'https://your-domain.com/webhook',
]);
```

**Response:**

```json
{
  "status": "success",
  "code": 201,
  "data": {
    "reference": "6a490816-799b-4fc9-b9b6-2ec67c54e17e",
    "status": "pending",
    "payment_type": "dynamic-qr",
    "payment_url": "https://tz.selcom.online/paymentgw/checkout/...",
    "payment_qr_code": "000201010212...",
    "payment_token": "63890400",
    "amount": { "currency": "TZS", "value": 500 },
    "object": "payment"
  }
}
```

**Your next step:** Either redirect to `payment_url` (hosted checkout) or render `payment_qr_code` as a QR image for in-person scanning.

---

## 3. The Webhook - Your App Gets Notified

After payment, Snippe sends a `POST` to your `webhook_url`. This is how your app learns the final status without polling.

**Webhook payload:**

```json
{
  "event": "payment.completed",
  "reference": "9015c155-9e29-4e8e-8fe6-d5d81553c8e6",
  "data": { ... }
}
```

**Events you should handle:**

| Event | Meaning |
|---|---|
| `payment.completed` | Payment succeeded, funds settled |
| `payment.failed` | Payment declined or timed out |
| `payment.cancelled` | Customer cancelled before completing |
| `payment.expired` | 4-hour expiry window passed |

**Your webhook endpoint (minimal example):**

```php
Route::post('webhook', function (Request $request) {
    $payload = $request->all();
    $event = $payload['event'] ?? '';
    $reference = $payload['reference'] ?? '';

    $statusMap = [
        'payment.completed' => 'completed',
        'payment.failed'    => 'failed',
        'payment.cancelled' => 'cancelled',
        'payment.expired'   => 'expired',
    ];

    $newStatus = $statusMap[$event] ?? null;

    if ($newStatus && $reference) {
        // Update your order in the database
        Order::where('snippe_reference', $reference)
             ->update(['status' => $newStatus]);
    }

    return response()->json(['status' => 'ok']);
});
```

**Signature verification (recommended):**

If you set `SNIPPE_WEBHOOK_SECRET`, verify the `Snippe-Signature` header:

```php
$signature = $request->header('Snippe-Signature');
$expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), $secret);

if (!hash_equals($expected, $signature)) {
    return response()->json(['status' => 'forged'], 401);
}
```

---

## 4. Important Rules

### Idempotency Keys

Always send an `Idempotency-Key` header with every `POST /v1/payments`.

- **Max 30 characters** - longer keys return a 500 error
- **Same key + same body** = returns cached response (safe to retry)
- **Same key + different body** = returns error
- Keys are valid for **24 hours**

```php
// Good
'Idempotency-Key: order-abc123-retry-1'     // ≤ 30 chars ✓

// Bad
'Idempotency-Key: ' . uniqid('my-order-reference-' . $orderId, true)  // may exceed 30 chars ✗
```

### Webhook URL must be HTTPS and reachable

Snippe will reject webhook URLs that are `localhost`, `127.0.0.1`, or HTTP.

```php
// Read APP_URL directly from config, not from the current request
$baseUrl = config('app.url');
$webhookUrl = $baseUrl . '/webhook';
```

### Minimum amount

The minimum payment amount is **500 TZS** (5 smallest units). Anything lower returns a validation error.

### Payments expire after 4 hours

If the customer doesn't complete the payment within 4 hours, its status changes to `expired`. Create a fresh payment if they want to retry.

---

## Common Issues

| Symptom | Likely cause | Fix |
|---|---|---|
| `webhook_url localhost URLs are not allowed` | `url('/')` returning localhost | Use `config('app.url')` instead |
| `webhook URL must use HTTPS` | `APP_URL` has `http://` | Set `APP_URL` with `https://` |
| `invalid or missing API key` | Wrong or missing `SNIPPE_API_KEY` | Check your key in the dashboard |
| `Idempotency-Key` too long error (500) | Key exceeds 30 chars | Generate a shorter key |
| USSD push never arrives | Webhook/reachable URL not set | Use ngrok in dev, set `APP_URL` |
| `amount is required` | Wrong field name | Use `details.amount`, not top-level `amount` |

---

## Files That Matter

```
duka-store-laravel/
│
├── config/
│   └── snippe.php              ← API credentials
│
├── app/Services/
│   └── SnippeApiService.php    ← All cURL calls to Snippe
│
├── app/Http/Controllers/
│   ├── CheckoutController.php  ← Creates payments, redirects customers
│   └── WebhookController.php   ← Handles payment status updates
│
├── routes/
│   └── web.php                 ← Route for /webhook
│
└── .env                        ← SNIPPE_API_KEY, APP_URL, SNIPPE_WEBHOOK_SECRET
```