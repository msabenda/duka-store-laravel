<?php
// Quick test to verify webhook URL is correctly built

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Http\Kernel')->handle(
    $request = \Illuminate\Http\Request::capture()
);

// Test the URL construction
$baseUrl = rtrim(config('app.url'), '/');
$baseUrl = str_replace('http://', 'https://', $baseUrl);
$webhookUrl = $baseUrl . '/webhooks/snippe';

echo "=== Webhook URL Diagnostic ===\n\n";
echo "APP_URL: " . config('app.url') . "\n";
echo "Webhook URL: " . $webhookUrl . "\n";
echo "SNIPPE_WEBHOOK_SECRET set: " . (!empty(config('snippe.webhook_secret')) ? 'YES' : 'NO') . "\n\n";

// Test if webhook endpoint is reachable
echo "Testing webhook endpoint accessibility:\n";
$ch = curl_init($webhookUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: " . $httpCode . "\n";
echo ($httpCode >= 200 && $httpCode < 400 ? "✓ Endpoint is reachable\n" : "✗ Endpoint not reachable\n");
