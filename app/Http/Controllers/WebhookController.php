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

        // Verify webhook signature
        $secret = config('snippe.webhook_secret');
        if ($secret) {
            $signature = $request->header('Snippe-Signature');
            if (!$signature || !$this->verifySignature($rawBody, $signature, $secret)) {
                Log::warning('Webhook signature verification failed - check SNIPPE_WEBHOOK_SECRET matches the dashboard', ['payload' => $payload]);
                return response()->json(['status' => 'forged'], 401);
            }
        }

        $event = $payload['event'] ?? $payload['type'] ?? 'unknown';
        $reference = $payload['reference'] ?? $payload['order_reference'] ?? ($payload['metadata']['order_reference'] ?? null);

        // Try to extract reference from metadata
        if (!$reference && isset($payload['data']['metadata']['reference'])) {
            $reference = $payload['data']['metadata']['reference'];
        }

        // Sessions webhooks carry our order reference in metadata.order_reference
        if (!$reference && isset($payload['data']['metadata']['order_reference'])) {
            $reference = $payload['data']['metadata']['order_reference'];
        }

        // Try to find the reference in the description
        if (!$reference && isset($payload['data']['description'])) {
            $desc = $payload['data']['description'];
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
            Log::warning('Webhook received for unknown order', ['reference' => $reference, 'payload' => $payload]);
            return response()->json(['status' => 'ignored', 'message' => 'Order not found'], 200);
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

        if ($newStatus) {
            $this->storage->updateOrder($order['reference'], ['status' => $newStatus]);
            Log::info('Order status updated via webhook', [
                'reference' => $order['reference'],
                'status' => $newStatus,
            ]);
        } else {
            Log::info('Webhook event not mapped, ignoring', ['event' => $event]);
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Verify HMAC-SHA256 webhook signature.
     */
    protected function verifySignature(string $rawBody, string $signature, string $secret): bool
    {
        $expected = 'sha256=' . hash_hmac('sha256', $rawBody, $secret);
        return hash_equals($expected, $signature);
    }
}
