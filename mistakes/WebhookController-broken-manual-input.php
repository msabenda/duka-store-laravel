<?php
// 🚨 MISTAKE 10C: Read php://input BEFORE Webhook::capture()
//
// What's wrong?  The controller reads php://input manually BEFORE calling
// Webhook::capture().  In PHP, php://input can only be read ONCE.
// Webhook::capture() tries to read it again but gets an empty stream,
// so signature verification fails because the body is empty.
//
// The error:
//   Snippe\WebhookVerificationError: unable to read request body
//
// Fix: Don't read php://input manually — let Webhook::capture() handle it.
//      If you need the raw body, read it AFTER capture() succeeds.
// --------------------------------------------------------------------------

namespace App\Http\Controllers;

use App\Services\StorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Snippe\Webhook;

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

        // 🚨 BUG: Reading raw body before Webhook::capture()!
        // php://input is a read-once stream. Once read, it's gone.
        // Webhook::capture() tries to read php://input internally
        // but gets an empty stream → signature verification fails.
        $manualRaw = file_get_contents('php://input');

        // 🚨 Now when we call Webhook::capture(), php://input is empty
        try {
            $event = Webhook::capture();
        } catch (\Snippe\WebhookVerificationError $e) {
            Log::error('Webhook verification failed', ['error' => $e->getMessage()]);
            return response()->json(['status' => 'forged'], 401);
        }

        if ($event->isPaymentCompleted()) {
            $reference = $event->reference();
            $order = $this->storage->getOrderByReference($reference);

            if ($order) {
                $this->storage->updateOrder($reference, ['status' => 'completed']);
                Log::info('Order completed via webhook', ['reference' => $reference]);
            }
        }

        return response()->json(['status' => 'ok']);
    }
}
