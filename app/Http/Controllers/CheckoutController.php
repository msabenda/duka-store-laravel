<?php

namespace App\Http\Controllers;

use App\Services\SnippeApiService;
use App\Services\StorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    protected SnippeApiService $snippe;
    protected StorageService $storage;

    public function __construct(SnippeApiService $snippe, StorageService $storage)
    {
        $this->snippe = $snippe;
        $this->storage = $storage;
    }

    public function index()
    {
        $cart = session()->get('cart', []);
        $user = session()->get('duka_user');

        if (empty($cart)) {
            return redirect('/cart')->with('error', 'Your cart is empty.');
        }

        if (!$user) {
            return redirect('/auth/login')->with('error', 'Please log in to checkout.');
        }

        $products = config('catalog');
        $items = [];
        $total = 0;

        foreach ($cart as $item) {
            $product = collect($products)->firstWhere('id', $item['product_id']);
            if ($product) {
                $subtotal = $product['price'] * $item['quantity'];
                $total += $subtotal;
                $items[] = [
                    'product' => $product,
                    'quantity' => $item['quantity'],
                    'subtotal' => $subtotal,
                ];
            }
        }

        $cartCount = array_sum(array_column($cart, 'quantity'));

        return view('checkout.index', compact('items', 'total', 'cartCount', 'user'));
    }

    public function processMobile(Request $request)
    {
        return $this->processPayment($request, 'mobile');
    }

    public function processCard(Request $request)
    {
        return $this->processPayment($request, 'card');
    }

    public function processQr(Request $request)
    {
        return $this->processPayment($request, 'dynamic-qr');
    }

    protected function processPayment(Request $request, string $paymentMethod)
    {
        $cart = session()->get('cart', []);
        $user = session()->get('duka_user');

        if (empty($cart)) {
            return redirect('/cart')->with('error', 'Your cart is empty.');
        }

        if (!$user) {
            return redirect('/auth/login')->with('error', 'Please login to checkout.');
        }

        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|string|max:20',
        ]);

        $products = config('catalog');
        $items = [];
        $totalAmount = 0;

        foreach ($cart as $cartItem) {
            $product = collect($products)->firstWhere('id', $cartItem['product_id']);
            if ($product) {
                $subtotal = $product['price'] * $cartItem['quantity'];
                $totalAmount += $subtotal;
                $items[] = [
                    'product_id' => $product['id'],
                    'product_name' => $product['name'],
                    'price' => $product['price'],
                    'quantity' => $cartItem['quantity'],
                    'subtotal' => $subtotal,
                    'image' => $product['image'] ?? null,
                ];
            }
        }

        // Generate a unique order reference
        $reference = 'DUKA-' . strtoupper(substr(md5(uniqid()), 0, 12));

        // Parse customer name into first/last
        $nameParts = explode(' ', trim($validated['customer_name']), 2);
        $firstName = $nameParts[0];
        $lastName = $nameParts[1] ?? '';

        // Phone — strip any leading + or 0, ensure 255 format
        $phone = ltrim($validated['customer_phone'], '+');
        if (strlen($phone) === 9) {
            $phone = '255' . $phone;
        } elseif (strlen($phone) === 10 && $phone[0] === '0') {
            $phone = '255' . substr($phone, 1);
        }

        // Build URLs — use APP_URL from config so Snippe gets your ngrok/domain URL
        $baseUrl = rtrim(config('app.url'), '/');
        $baseUrl = str_replace('http://', 'https://', $baseUrl);
        $successUrl = $baseUrl . '/order/success/' . $reference;
        $cancelUrl = $baseUrl . '/order/show/' . $reference;
        $webhookUrl = $baseUrl . '/webhook';

        $customer = [
            'firstname' => $firstName,
            'lastname'  => $lastName,
            'email'     => $validated['customer_email'],
        ];

        $metadata = [
            'order_reference' => $reference,
            'source' => 'duka-store',
        ];

        $snippeRef = null;
        $sessionResponse = null;

        // Call the right Snippe API based on payment type
        switch ($paymentMethod) {
            case 'mobile':
                $sessionResponse = $this->snippe->createMobilePayment(
                    $totalAmount,
                    'TZS',
                    $phone,
                    $customer,
                    $webhookUrl,
                    $metadata
                );
                break;

            case 'card':
                $sessionResponse = $this->snippe->createCardPayment(
                    $totalAmount,
                    'TZS',
                    $successUrl,
                    $cancelUrl,
                    $customer,
                    $phone,
                    $webhookUrl,
                    $metadata
                );
                break;

            case 'dynamic-qr':
                $sessionResponse = $this->snippe->createQrPayment(
                    $totalAmount,
                    'TZS',
                    $customer,
                    $successUrl,
                    $cancelUrl,
                    $phone,
                    $webhookUrl,
                    $metadata
                );
                break;
        }

        // Extract Snippe reference from response
        if ($sessionResponse && $sessionResponse['success']) {
            $snippeRef = $sessionResponse['data']['data']['reference'] ?? null;
        }

        // Save order locally
        $orderData = [
            'reference' => $reference,
            'snippe_reference' => $snippeRef,
            'user_id' => $user['id'],
            'amount' => $totalAmount,
            'currency' => 'TZS',
            'status' => 'pending',
            'payment_method' => $paymentMethod,
            'items' => $items,
            'customer_name' => $validated['customer_name'],
            'customer_email' => $validated['customer_email'],
            'customer_phone' => $validated['customer_phone'],
            'created_at' => now()->toIso8601String(),
        ];

        $this->storage->saveOrder($orderData);

        // Clear cart
        session()->forget('cart');

        // Handle response per payment type
        if ($sessionResponse && $sessionResponse['success']) {
            $responseData = $sessionResponse['data']['data'] ?? [];

            if ($paymentMethod === 'mobile') {
                // USSD push is sent automatically by Snippe
                return redirect('/order/success/' . $reference)
                    ->with('info', 'A USSD push has been sent to ' . $validated['customer_phone'] . '. Check your phone and enter your PIN to complete payment.');
            }

            // Card and QR — redirect to hosted checkout page
            $checkoutUrl = $responseData['payment_url'] ?? null;
            if ($checkoutUrl) {
                return redirect()->away($checkoutUrl);
            }

            // Fallback: no checkout URL but API succeeded
            return redirect('/order/success/' . $reference)
                ->with('info', 'Payment created. Complete it using the reference: ' . $snippeRef);
        }

        // API call failed — show the URL sent so you can debug
        $apiData = $sessionResponse['data'] ?? [];
        $errorMsg = $sessionResponse['error']
                  ?? ($apiData['message'] ?? 'Could not create payment. Check your API key and try again.');

        return redirect('/order/success/' . $reference)
            ->with('error', 'Payment failed: ' . $errorMsg . ' (webhook was: ' . $webhookUrl . ')');
    }
}
