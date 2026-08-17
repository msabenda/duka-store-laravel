# SERIES 4 - Webhooks & Event Handling (Laravel 13)

> Real-time status updates are the heartbeat of a payment system. Snippe sends you an HTTP webhook when a payment or payout changes state, and your job is to verify the request, acknowledge it fast, and reconcile it back to your own ledger.
>
> This series shows how to receive Snippe webhooks, reject forged payloads, handle duplicates, and update your store UI from `pending` to `completed` without trusting the redirect alone.

## What You Will Learn

- How to expose a secure webhook endpoint for Snippe in Laravel
- How to configure local testing with ngrok or a tunnel
- Which Snippe event types matter for a store like Duka Store
- How to verify the webhook signature using the raw request body
- Why the response must be fast and why the processing should move async
- How retries and duplicate deliveries work in practice
- How to reconcile a webhook event against your own order reference

## Why Webhooks Matter

The redirect URL is convenient for UX, but it is not proof of payment.

Your real source of truth is the webhook payload from Snippe.

The flow should look like this:

```text
Customer pays on Snippe hosted checkout
        ↓
Snippe fires webhook: payment.completed
        ↓
Your Laravel route verifies signature
        ↓
You update your order record to completed
        ↓
Your success page/UI refreshes and shows completed
```

If you only rely on the redirect, a customer can hit the URL manually, or the order can remain stale while the real payment is already processed elsewhere.

## The Endpoint You Need

This repo exposes the webhook listener at:

- `/webhooks/snippe`
- `/webhook` (compatibility alias for local tooling)

The route is intentionally configured with CSRF disabled because Snippe is an external service, not a browser form post.

See:

- `routes/web.php`
- `app/Http/Controllers/WebhookController.php`

## Local Testing with ngrok

For local development, Snippe requires a public HTTPS URL.

```bash
# from your project root
php artisan serve

# in another terminal
ngrok http 8000
```

Set your app base URL to the HTTPS tunnel:

```env
APP_URL=https://abc123.ngrok.io
SNIPPE_WEBHOOK_SECRET=whsec_your_secret_here
```

Then create the checkout session with a `webhook_url` like:

```text
https://abc123.ngrok.io/webhooks/snippe
```

## Event Types the Store Cares About

For a Duka Store, these are the important categories:

- `payment.completed` / `payment.succeeded`
- `payment.failed`
- `payment.cancelled` / `payment.voided`
- `payment.expired`
- payout events when you are paying out merchants or refunds

In this Laravel app, the handler maps these into internal order statuses:

```php
$statusMap = [
    'payment.completed' => 'completed',
    'payment.successful' => 'completed',
    'payment.confirmed' => 'completed',
    'payment.succeeded' => 'completed',
    'payment.failed' => 'failed',
    'payment.cancelled' => 'cancelled',
    'payment.expired' => 'expired',
    'payment.pending' => 'pending',
];
```

The handler accepts current and legacy event names and matches them back to your own order reference.

## Signature Verification Is Non-Negotiable

Snippe signs every webhook. The payload must be verified before you trust it.

The documented pattern is:

```text
message = "{timestamp}.{raw_body}"
signature = HMAC-SHA256(signing_key, message)
```

This is how the app checks it:

```php
protected function verifySignature(string $rawBody, string $timestamp, string $signature, string $secret): bool
{
    $normalizedSignature = trim($signature);
    if (preg_match('/^sha256\s*=\s*/i', $normalizedSignature)) {
        $normalizedSignature = preg_replace('/^sha256\s*=\s*/i', '', $normalizedSignature);
    }

    $normalizedSignature = strtolower((string) $normalizedSignature);
    $expected = strtolower(hash_hmac('sha256', $timestamp . '.' . $rawBody, $secret));

    return hash_equals($expected, $normalizedSignature);
}
```

This matters because a forged payload should be rejected with `401`, not processed.

### What a forged payload looks like

A malicious request may send:

- a random `X-Webhook-Signature`
- a valid-looking event type
- an order reference that looks real

But because the app recomputes the signature using the raw body and secret, the request fails before the order is changed.

This is exactly the difference between: `status = pending` forever and `status = completed` after a real Snippe payment.

## Raw Body, Not Re-serialized JSON

The docs are explicit: use the raw request body as received.

Do not do this:

```php
$json = $request->all();
$body = json_encode($json);
```

Do this instead:

```php
$rawBody = $request->getContent();
```

That raw string is what gets signed. If you re-serialize JSON, whitespace and key ordering changes can break the signature even when the payload is otherwise valid.

## Fast Response, Async Processing

The docs recommend: return `2xx` quickly, then process the event asynchronously.

In a production app, your controller should do roughly this:

```php
public function handle(Request $request)
{
    $rawBody = $request->getContent();

    if (!$this->verifySignature(...)) {
        return response()->json(['status' => 'forged'], 401);
    }

    // Acknowledge immediately to avoid retry storms
    return response()->json(['status' => 'ok']);
}
```

Then queue the heavy work:

```php
// pseudo-code
ProcessWebhookJob::dispatch($payload);
```

This pattern is important because a late response can trigger Snippe retries, and repeated retries are a normal part of webhook delivery.

## Retry and Redelivery Behavior

Snippe retries a failed delivery with exponential backoff.

Typical behavior:

- attempt 1: immediate
- attempt 2: 3 minutes later
- attempt 3: 6 minutes later
- attempt 4: 12 minutes later
- attempt 5: 24 minutes later

After that, the event is marked as abandoned.

That means your webhook endpoint should be resilient to:

- timeouts
- duplicate events
- partial processing
- temporary outages

This repo handles the duplicate case by tracking processed event IDs.

## Duplicate Deliveries and Idempotency

Snippe may send the same event more than once. For a payment system, this is normal and must be handled intentionally.

This is why the handler stores processed `event_id` values:

```php
if ($eventId && $this->storage->hasProcessedWebhookEvent((string) $eventId)) {
    Log::info('Duplicate Snippe webhook ignored', ['event_id' => $eventId]);
    return response()->json(['status' => 'duplicate'], 200);
}
```

That makes the operation idempotent, which is the bridge to the next series: the app should be safe when the same event is delivered twice, or when a retry arrives after a previous response was lost.

## Reconciliation Mindset: Event + Reference = Ledger

The heart of this integration is the reference.

You need to connect:

- the order you created in your store
- the Snippe payment reference
- the webhook payload

In this app, we keep the store order reference in metadata such as:

```php
$metadata = [
    'order_reference' => $reference,
    'source' => 'duka-store-laravel',
];
```

And the webhook resolves the order by matching against:

- `data.metadata.order_reference`
- `data.metadata.url_metadata.order_reference`
- `reference`
- `snippe_reference`

This is your ledger. The event tells you what happened, and your reference tells you which internal record it belongs to.

## Real-World Pattern for This App

This repo uses a simple pattern that is a solid starting point:

1. Store the order as `pending`
2. Receive Snippe webhook
3. Verify `X-Webhook-Signature` using raw body
4. Reject stale or forged requests
5. Resolve the order from `order_reference`
6. Check whether the event ID has already been processed
7. Update the order to `completed`, `failed`, `cancelled`, etc.
8. Respond with `200 OK`
9. Optionally queue a follow-up job for notifications, email, inventory, or reconciliation

## Production Checklist

Before you ship a webhook integration:

- [ ] `SNIPPE_WEBHOOK_SECRET` is stored in a secret manager or environment file, never in client code
- [ ] The endpoint uses HTTPS
- [ ] Your app verifies the raw request body and signature
- [ ] The timestamp is checked for freshness
- [ ] You return `2xx` quickly
- [ ] Duplicate events are ignored by event ID or reference
- [ ] You log the webhook event for troubleshooting
- [ ] Your UI reads from your server ledger, not from the redirect alone

## Key Files in This Repo

- `app/Http/Controllers/WebhookController.php` — receives and validates webhooks
- `app/Services/StorageService.php` — stores order and processed event state
- `routes/web.php` — exposes the webhook endpoint
- `config/snippe.php` — configures the signing secret
- `app/Http/Controllers/CheckoutController.php` — creates the session with `webhook_url`

## Next Steps

The next upgrade is to move the processing work behind a queue worker and make the handler fully async.

That gives you:

- faster webhook responses
- less risk of timing out under retries
- cleaner reconciliation jobs
- easier monitoring and audit trails

If you want to extend this system, the natural next step is Session 5: idempotent processing and event ledger design for production-grade payment systems.
