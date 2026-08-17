<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SnippeWebhookTest extends TestCase
{
    public function test_it_verifies_snippe_signature_and_updates_order_status_on_completed_event(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('data/orders.json', json_encode([
            [
                'reference' => 'DUKA-TEST123',
                'snippe_reference' => 'pi_test123',
                'user_id' => 1,
                'amount' => 500,
                'currency' => 'TZS',
                'status' => 'pending',
                'payment_method' => 'session',
                'items' => [],
                'customer_name' => 'Test User',
                'customer_email' => 'test@example.com',
                'customer_phone' => '+255712345678',
                'created_at' => now()->toIso8601String(),
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        config(['snippe.webhook_secret' => 'whsec_test_123']);

        $payload = [
            'id' => 'evt_test123',
            'type' => 'payment.completed',
            'api_version' => '2026-01-25',
            'created_at' => '2026-01-24T10:30:00Z',
            'data' => [
                'reference' => 'pi_test123',
                'status' => 'completed',
                'amount' => [
                    'value' => 500,
                    'currency' => 'TZS',
                ],
                'metadata' => [
                    'order_reference' => 'DUKA-TEST123',
                ],
                'customer' => [
                    'name' => 'Test User',
                    'email' => 'test@example.com',
                    'phone' => '+255712345678',
                ],
            ],
        ];

        $body = json_encode($payload);
        $timestamp = (string) time();
        $signature = hash_hmac('sha256', $timestamp . '.' . $body, 'whsec_test_123');

        $response = $this->withHeaders([
            'Content-Type' => 'application/json',
            'X-Webhook-Event' => 'payment.completed',
            'X-Webhook-Timestamp' => $timestamp,
            'X-Webhook-Signature' => $signature,
        ])->postJson('/webhooks/snippe', $payload);

        $response->assertStatus(200);

        $orders = json_decode(Storage::disk('local')->get('data/orders.json'), true);
        $this->assertSame('completed', $orders[0]['status']);
    }

    public function test_it_updates_order_status_to_failed_when_snippe_reports_payment_failed(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('data/orders.json', json_encode([
            [
                'reference' => 'DUKA-FAIL456',
                'snippe_reference' => 'pi_fail456',
                'user_id' => 1,
                'amount' => 500,
                'currency' => 'TZS',
                'status' => 'pending',
                'payment_method' => 'session',
                'items' => [],
                'customer_name' => 'Fail User',
                'customer_email' => 'fail@example.com',
                'customer_phone' => '+255712345679',
                'created_at' => now()->toIso8601String(),
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        config(['snippe.webhook_secret' => 'whsec_test_123']);

        $payload = [
            'id' => 'evt_fail456',
            'type' => 'payment.failed',
            'api_version' => '2026-01-25',
            'created_at' => '2026-01-24T10:31:00Z',
            'data' => [
                'reference' => 'pi_fail456',
                'status' => 'failed',
                'failure_reason' => 'Transaction declined by user',
                'amount' => [
                    'value' => 500,
                    'currency' => 'TZS',
                ],
                'metadata' => [
                    'order_reference' => 'DUKA-FAIL456',
                ],
                'customer' => [
                    'name' => 'Fail User',
                    'email' => 'fail@example.com',
                    'phone' => '+255712345679',
                ],
            ],
        ];

        $body = json_encode($payload);
        $timestamp = (string) time();
        $signature = hash_hmac('sha256', $timestamp . '.' . $body, 'whsec_test_123');

        $response = $this->withHeaders([
            'Content-Type' => 'application/json',
            'X-Webhook-Event' => 'payment.failed',
            'X-Webhook-Timestamp' => $timestamp,
            'X-Webhook-Signature' => $signature,
        ])->postJson('/webhooks/snippe', $payload);

        $response->assertStatus(200);

        $orders = json_decode(Storage::disk('local')->get('data/orders.json'), true);
        $this->assertSame('failed', $orders[0]['status']);
    }

    public function test_it_ignores_duplicate_webhook_events_by_event_id(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('data/orders.json', json_encode([
            [
                'reference' => 'DUKA-DUPLICATE789',
                'snippe_reference' => 'pi_duplicate789',
                'user_id' => 1,
                'amount' => 500,
                'currency' => 'TZS',
                'status' => 'pending',
                'payment_method' => 'session',
                'items' => [],
                'customer_name' => 'Duplicate User',
                'customer_email' => 'duplicate@example.com',
                'customer_phone' => '+255712345680',
                'created_at' => now()->toIso8601String(),
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        config(['snippe.webhook_secret' => 'whsec_test_123']);

        $payload = [
            'id' => 'evt_duplicate_789',
            'type' => 'payment.completed',
            'api_version' => '2026-01-25',
            'created_at' => '2026-01-24T10:41:00Z',
            'data' => [
                'reference' => 'pi_duplicate789',
                'status' => 'completed',
                'amount' => [
                    'value' => 500,
                    'currency' => 'TZS',
                ],
                'metadata' => [
                    'order_reference' => 'DUKA-DUPLICATE789',
                ],
                'customer' => [
                    'name' => 'Duplicate User',
                    'email' => 'duplicate@example.com',
                    'phone' => '+255712345680',
                ],
            ],
        ];

        $body = json_encode($payload);
        $timestamp = (string) time();
        $signature = hash_hmac('sha256', $timestamp . '.' . $body, 'whsec_test_123');

        $firstResponse = $this->withHeaders([
            'Content-Type' => 'application/json',
            'X-Webhook-Event' => 'payment.completed',
            'X-Webhook-Timestamp' => $timestamp,
            'X-Webhook-Signature' => $signature,
        ])->postJson('/webhooks/snippe', $payload);

        $firstResponse->assertStatus(200);

        $secondResponse = $this->withHeaders([
            'Content-Type' => 'application/json',
            'X-Webhook-Event' => 'payment.completed',
            'X-Webhook-Timestamp' => $timestamp,
            'X-Webhook-Signature' => $signature,
        ])->postJson('/webhooks/snippe', $payload);

        $secondResponse->assertStatus(200);

        $events = json_decode(Storage::disk('local')->get('data/webhook-events.json'), true);
        $this->assertCount(1, $events ?? []);

        $orders = json_decode(Storage::disk('local')->get('data/orders.json'), true);
        $this->assertSame('completed', $orders[0]['status']);
    }

    public function test_it_updates_order_status_when_snippe_uses_a_different_completed_payment_reference(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('data/orders.json', json_encode([
            [
                'reference' => 'DUKA-PAYMENTREF123',
                'snippe_reference' => 'PAY17869683985625502',
                'user_id' => 1,
                'amount' => 500,
                'currency' => 'TZS',
                'status' => 'pending',
                'payment_method' => 'session',
                'items' => [],
                'customer_name' => 'John Doe',
                'customer_email' => 'johndoe@gmail.com',
                'customer_phone' => '+255789147096',
                'created_at' => now()->toIso8601String(),
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        config(['snippe.webhook_secret' => 'whsec_test_123']);

        $payload = [
            'id' => 'evt_completed_123',
            'type' => 'payment.completed',
            'api_version' => '2026-01-25',
            'created_at' => '2026-01-24T10:42:00Z',
            'data' => [
                'reference' => 'SN17869684027665742',
                'status' => 'completed',
                'amount' => [
                    'value' => 500,
                    'currency' => 'TZS',
                ],
                'metadata' => [
                    'description' => 'Duka Store order DUKA-PAYMENTREF123',
                ],
                'customer' => [
                    'name' => 'John Doe',
                    'email' => 'johndoe@gmail.com',
                    'phone' => '+255789147096',
                ],
            ],
        ];

        $body = json_encode($payload);
        $timestamp = (string) time();
        $signature = hash_hmac('sha256', $timestamp . '.' . $body, 'whsec_test_123');

        $response = $this->withHeaders([
            'Content-Type' => 'application/json',
            'X-Webhook-Event' => 'payment.completed',
            'X-Webhook-Timestamp' => $timestamp,
            'X-Webhook-Signature' => $signature,
        ])->postJson('/webhooks/snippe', $payload);

        $response->assertStatus(200);

        $orders = json_decode(Storage::disk('local')->get('data/orders.json'), true);
        $this->assertSame('completed', $orders[0]['status']);
    }
}
