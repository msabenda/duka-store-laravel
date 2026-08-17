<?php

namespace App\Http\Controllers;

use App\Services\StorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    protected StorageService $storage;

    public function __construct(StorageService $storage)
    {
        $this->storage = $storage;
    }

    public function handle(Request $request)
    {
        // 💡 LEARNER'S NOTE — REDIRECT ≠ PROOF OF PAYMENT: the
        // return URL leaves the order 'pending'. THIS handler is what flips it
        // to completed — after the signature verifies. Webhooks = source of truth.
        $payload = $request->all();
        $rawBody = $request->getContent();

        // Always log the raw event so webhook delivery can be debugged live.
        Log::info('Snippe webhook received', ['payload' => $payload]);

        // Verify webhook signature using Snippe's documented format:
        // X-Webhook-Timestamp + raw body => HMAC-SHA256(secret)
        $secret = config('snippe.webhook_secret');
        if ($secret) {
            $timestamp = $request->header('X-Webhook-Timestamp') ?: $request->header('Snippe-Timestamp');
            $signature = $request->header('X-Webhook-Signature') ?: $request->header('Snippe-Signature');

            if (!$timestamp || !$signature || !$this->verifySignature($rawBody, $timestamp, $signature, $secret)) {
                Log::warning('Webhook signature verification failed - check SNIPPE_WEBHOOK_SECRET matches the dashboard', [
                    'payload' => $payload,
                    'timestamp' => $timestamp,
                    'signature' => $signature,
                ]);
                return response()->json(['status' => 'forged'], 401);
            }

            if ((int) $timestamp < 1000000000 || abs((int) time() - (int) $timestamp) > 300) {
                Log::warning('Webhook timestamp is stale or invalid', ['timestamp' => $timestamp, 'payload' => $payload]);
                return response()->json(['status' => 'stale'], 401);
            }
        }

        $event = $payload['type'] ?? $payload['event'] ?? ($payload['data']['status'] ?? null) ?? 'unknown';
        $eventId = $payload['id'] ?? $payload['event_id'] ?? ($payload['data']['id'] ?? null);

        $reference = $payload['reference']
            ?? $payload['order_reference']
            ?? ($payload['metadata']['order_reference'] ?? null)
            ?? ($payload['metadata']['url_metadata']['order_reference'] ?? null)
            ?? ($payload['data']['reference'] ?? null)
            ?? ($payload['data']['session_reference'] ?? null)
            ?? ($payload['data']['payment_reference'] ?? null)
            ?? ($payload['data']['metadata']['order_reference'] ?? null)
            ?? ($payload['data']['metadata']['url_metadata']['order_reference'] ?? null)
            ?? ($payload['data']['metadata']['reference'] ?? null)
            ?? ($payload['data']['external_reference'] ?? null);

        if (!$reference && isset($payload['data']['description'])) {
            $desc = $payload['data']['description'];
            if (preg_match('/DUKA-[A-Z0-9]+/', $desc, $matches)) {
                $reference = $matches[0];
            }
        }

        if (!$reference && isset($payload['data']['metadata']['description'])) {
            $desc = $payload['data']['metadata']['description'];
            if (preg_match('/DUKA-[A-Z0-9]+/', $desc, $matches)) {
                $reference = $matches[0];
            }
        }

        if (!$reference) {
            Log::warning('Webhook received without recognizable reference', $payload);
            return response()->json(['status' => 'ignored', 'message' => 'No reference found'], 200);
        }

        $order = $this->storage->getOrderByReference($reference);
        if (!$order) {
            $order = $this->storage->getOrderBySnippeReference($reference);
        }

        if (!$order) {
            $payloadText = json_encode($payload);
            $allOrders = $this->storage->getAllOrders();
            foreach ($allOrders as $candidateOrder) {
                $haystack = implode('|', [
                    (string) ($candidateOrder['reference'] ?? ''),
                    (string) ($candidateOrder['snippe_reference'] ?? ''),
                    (string) ($candidateOrder['session_reference'] ?? ''),
                    (string) ($candidateOrder['payment_reference'] ?? ''),
                ]);

                if ($payloadText !== false && str_contains($payloadText, $candidateOrder['reference'] ?? '')
                    || str_contains($haystack, (string) ($reference ?? ''))
                ) {
                    $order = $candidateOrder;
                    break;
                }
            }
        }

        if (!$order) {
            Log::warning('Webhook received for unknown order', ['reference' => $reference, 'payload' => $payload]);
            return response()->json(['status' => 'ignored', 'message' => 'Order not found'], 200);
        }

        if ($eventId && $this->storage->hasProcessedWebhookEvent((string) $eventId)) {
            Log::info('Duplicate Snippe webhook ignored', ['event_id' => $eventId, 'reference' => $reference]);
            return response()->json(['status' => 'duplicate'], 200);
        }

        // Map Snippe events to order statuses
        $statusMap = [
            'payment.completed' => 'completed',
            'payment.successful' => 'completed',
            'payment.confirmed' => 'completed',
            'payment.succeeded' => 'completed',
            'session.completed' => 'completed',
            'checkout.completed' => 'completed',
            'session.payment.completed' => 'completed',
            'payment.failed' => 'failed',
            'session.failed' => 'failed',
            'checkout.failed' => 'failed',
            'payment.cancelled' => 'cancelled',
            'payment.voided' => 'cancelled',
            'session.cancelled' => 'cancelled',
            'checkout.cancelled' => 'cancelled',
            'payment.expired' => 'expired',
            'session.expired' => 'expired',
            'checkout.expired' => 'expired',
            'payment.pending' => 'pending',
            'session.pending' => 'pending',
            'checkout.pending' => 'pending',
        ];

        $newStatus = $statusMap[$event] ?? null;

        if (!$newStatus && isset($payload['data']['status'])) {
            $statusKey = strtolower((string) $payload['data']['status']);
            $newStatus = match ($statusKey) {
                'completed', 'successful', 'confirmed', 'succeeded' => 'completed',
                'failed' => 'failed',
                'cancelled', 'voided' => 'cancelled',
                'expired' => 'expired',
                'pending' => 'pending',
                default => null,
            };
        }

        if ($newStatus) {
            $this->storage->updateOrder($order['reference'], ['status' => $newStatus]);
            if ($eventId) {
                $this->storage->markWebhookEventProcessed((string) $eventId, $payload);
            }
            Log::info('Order status updated via webhook', [
                'reference' => $order['reference'],
                'status' => $newStatus,
            ]);
        } else {
            Log::info('Webhook event not mapped, ignoring', ['event' => $event]);
            if ($eventId) {
                $this->storage->markWebhookEventProcessed((string) $eventId, $payload);
            }
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Verify HMAC-SHA256 webhook signature using the Snippe signature spec.
     */
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
}
