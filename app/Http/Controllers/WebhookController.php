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
        $payload = $request->all();
        $rawBody = $request->getContent();

        Log::info('Snippe webhook received', ['event' => $payload['event'] ?? 'unknown']);

        // Verify webhook signature
        $secret = config('snippe.webhook_secret');
        if ($secret) {
            $signature = $request->header('Snippe-Signature');
            if (!$signature || !$this->verifySignature($rawBody, $signature, $secret)) {
                Log::warning('Webhook signature verification failed');
                return response()->json(['status' => 'forged'], 401);
            }
        }

        $event = $payload['event'] ?? $payload['type'] ?? 'unknown';
        $reference = $payload['reference'] ?? $payload['order_reference'] ?? null;

        // Try to extract reference from metadata
        if (!$reference && isset($payload['data']['metadata']['reference'])) {
            $reference = $payload['data']['metadata']['reference'];
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
            Log::warning('Webhook received for unknown order', ['reference' => $reference]);
            return response()->json(['status' => 'ignored', 'message' => 'Order not found'], 200);
        }

        // Map Snippe events to order statuses
        $statusMap = [
            'payment.completed' => 'completed',
            'payment.successful' => 'completed',
            'payment.confirmed' => 'completed',
            'payment.failed' => 'failed',
            'payment.cancelled' => 'cancelled',
            'payment.expired' => 'expired',
            'checkout.expired' => 'expired',
            'payment.pending' => 'pending',
        ];

        $newStatus = $statusMap[$event] ?? null;

        if ($newStatus) {
            $this->storage->updateOrder($reference, ['status' => $newStatus]);
            Log::info('Order status updated via webhook', [
                'reference' => $reference,
                'status' => $newStatus,
            ]);
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
