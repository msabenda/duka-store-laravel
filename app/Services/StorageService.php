<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class StorageService
{
    protected string $disk = 'local';

    protected function readJson(string $filename): array
    {
        $path = "data/{$filename}";

        if (!Storage::disk($this->disk)->exists($path)) {
            return [];
        }

        $content = Storage::disk($this->disk)->get($path);
        $data = json_decode($content, true);

        return is_array($data) ? $data : [];
    }

    protected function writeJson(string $filename, array $data): void
    {
        $path = "data/{$filename}";
        Storage::disk($this->disk)->put($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    // --- Orders ---

    public function getOrders(?int $userId = null): array
    {
        $orders = $this->readJson('orders.json');

        if ($userId !== null) {
            $orders = array_values(array_filter($orders, fn($o) => ($o['user_id'] ?? null) === $userId));
        }

        // Sort by created_at descending (newest first)
        usort($orders, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));

        return $orders;
    }

    public function getOrderByReference(string $reference): ?array
    {
        $orders = $this->readJson('orders.json');

        foreach ($orders as $order) {
            if (($order['reference'] ?? '') === $reference) {
                return $order;
            }
        }

        return null;
    }

    public function getOrderBySnippeReference(string $snippeReference): ?array
    {
        $orders = $this->readJson('orders.json');

        foreach ($orders as $order) {
            if (($order['snippe_reference'] ?? '') === $snippeReference) {
                return $order;
            }
        }

        return null;
    }

    public function getOrderById(int $id): ?array
    {
        $orders = $this->readJson('orders.json');

        foreach ($orders as $order) {
            if (($order['id'] ?? 0) === $id) {
                return $order;
            }
        }

        return null;
    }

    public function saveOrder(array $data): void
    {
        $orders = $this->readJson('orders.json');

        // Auto-assign ID if not present
        if (!isset($data['id'])) {
            $maxId = 0;
            foreach ($orders as $order) {
                if (($order['id'] ?? 0) > $maxId) {
                    $maxId = $order['id'];
                }
            }
            $data['id'] = $maxId + 1;
        }

        $orders[] = $data;
        $this->writeJson('orders.json', $orders);
    }

    public function updateOrder(string $reference, array $data): void
    {
        $orders = $this->readJson('orders.json');

        foreach ($orders as $i => $order) {
            if (($order['reference'] ?? '') === $reference) {
                $orders[$i] = array_merge($order, $data);
                break;
            }
        }

        $this->writeJson('orders.json', $orders);
    }

    public function hasProcessedWebhookEvent(string $eventId): bool
    {
        $events = $this->readJson('webhook-events.json');

        foreach ($events as $event) {
            if (($event['event_id'] ?? '') === $eventId) {
                return true;
            }
        }

        return false;
    }

    public function markWebhookEventProcessed(string $eventId, array $payload = []): void
    {
        $events = $this->readJson('webhook-events.json');

        $events[] = [
            'event_id' => $eventId,
            'processed_at' => now()->toIso8601String(),
            'payload' => $payload,
        ];

        $this->writeJson('webhook-events.json', $events);
    }

    public function getAllOrders(): array
    {
        return $this->readJson('orders.json');
    }

    // --- Users ---

    public function getUsers(): array
    {
        return $this->readJson('users.json');
    }

    public function getUserByEmail(string $email): ?array
    {
        $users = $this->readJson('users.json');

        foreach ($users as $user) {
            if (($user['email'] ?? '') === $email) {
                return $user;
            }
        }

        return null;
    }

    public function getUserById(int $id): ?array
    {
        $users = $this->readJson('users.json');

        foreach ($users as $user) {
            if (($user['id'] ?? 0) === $id) {
                return $user;
            }
        }

        return null;
    }

    public function saveUser(array $data): void
    {
        $users = $this->readJson('users.json');

        // Auto-assign ID
        $maxId = 0;
        foreach ($users as $user) {
            if (($user['id'] ?? 0) > $maxId) {
                $maxId = $user['id'];
            }
        }
        $data['id'] = $maxId + 1;

        $users[] = $data;
        $this->writeJson('users.json', $users);
    }
}
