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

    /**
     * 💡 LEARNER'S NOTE — YOUR CUSTOM CHECKOUT (you build this page yourself)
     * This is YOUR checkout page: customer details form + payment method buttons
     * (Mobile Money / Card / QR), rendered by resources/views/checkout/index.blade.php.
     * Snippe's hosted checkout (the payment_url redirect) is the alternative —
     * this page is the part you own: what you collect, what you validate, what
     * you send to Snippe when creating the session.
     *
     * ⚠️ DISABLED FOR NOW - hosted Snippe checkout only (processHosted below).
     * Uncomment to restore the custom checkout page.
     */
    // public function index()
    // {
    //     $cart = session()->get('cart', []);
    //     $user = session()->get('duka_user');
    //
    //     if (empty($cart)) {
    //         return redirect('/cart')->with('error', 'Your cart is empty.');
    //     }
    //
    //     if (!$user) {
    //         return redirect('/auth/login')->with('error', 'Please log in to checkout.');
    //     }
    //
    //     $products = config('catalog');
    //     $items = [];
    //     $total = 0;
    //
    //     foreach ($cart as $item) {
    //         $product = collect($products)->firstWhere('id', $item['product_id']);
    //         if ($product) {
    //             $subtotal = $product['price'] * $item['quantity'];
    //             $total += $subtotal;
    //             $items[] = [
    //                 'product' => $product,
    //                 'quantity' => $item['quantity'],
    //                 'subtotal' => $subtotal,
    //             ];
    //         }
    //     }
    //
    //     $cartCount = array_sum(array_column($cart, 'quantity'));
    //
    //     return view('checkout.index', compact('items', 'total', 'cartCount', 'user'));
    // }

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

    /**
     * 💡 LEARNER'S NOTE — PAYMENT SESSIONS / HOSTED CHECKOUT: the cart's
     * "Checkout" button posts here. It creates a PAYMENT SESSION with the cart
     * total + the logged-in user's details, then redirects the customer to
     * Snippe's HOSTED CHECKOUT page (checkout_url) where they fill/confirm
     * their details and pay (mobile money). The order stays 'pending' until
     * the webhook confirms it.
     * Docs: https://docs.snippe.sh/docs/2026-01-25/sessions
     */
    public function processPay(Request $request)
    {
        $cart = session()->get('cart', []);
        $user = session()->get('duka_user');

        if (empty($cart)) {
            return redirect('/cart')->with('error', 'Your cart is empty.');
        }

        if (!$user) {
            return redirect('/auth/login')->with('error', 'Please login to checkout.');
        }

        $name = trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? ''));

        // Pre-fill the hosted checkout with the account details (editable there).
        if ($name === '' || empty($user['email']) || empty($user['phone'])) {
            return redirect('/auth/register')
                ->withErrors(['phone' => 'Your account needs a name, email and phone for checkout - please register again.']);
        }

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

        $reference = 'DUKA-' . strtoupper(substr(md5(uniqid()), 0, 12));

        $baseUrl = rtrim(config('app.url'), '/');
        $baseUrl = str_replace('http://', 'https://', $baseUrl);
        $redirectUrl = $baseUrl . '/success?ref=' . $reference;
        $webhookUrl = $baseUrl . '/webhooks/snippe';

        $customer = [
            'name' => $name,
            'email' => $user['email'],
            'phone' => $user['phone'],
        ];

        $metadata = [
            'order_reference' => $reference,
            'source' => 'duka-store-laravel',
        ];

        $sessionResponse = $this->snippe->createSession(
            $totalAmount,
            'TZS',
            $customer,
            $redirectUrl,
            $webhookUrl,
            $metadata,
            'Duka Store order ' . $reference
        );

        $snippeRef = $sessionResponse['success']
            ? ($sessionResponse['data']['data']['reference'] ?? null)
            : null;

        $orderData = [
            'reference' => $reference,
            'snippe_reference' => $snippeRef,
            'user_id' => $user['id'],
            'amount' => $totalAmount,
            'currency' => 'TZS',
            'status' => 'pending',
            'payment_method' => 'session',
            'items' => $items,
            'customer_name' => $name,
            'customer_email' => $user['email'],
            'customer_phone' => $user['phone'],
            'created_at' => now()->toIso8601String(),
        ];

        $this->storage->saveOrder($orderData);
        session()->forget('cart');

        if ($sessionResponse['success']) {
            $checkoutUrl = $sessionResponse['data']['data']['checkout_url'] ?? null;
            if ($checkoutUrl) {
                return redirect()->away($checkoutUrl);
            }
            return redirect('/order/success/' . $reference)
                ->with('info', 'Payment session created. Complete it using the reference: ' . $snippeRef);
        }

        $errorMsg = $sessionResponse['error']
            ?? ($sessionResponse['data']['message'] ?? 'Could not create payment session.');

        return redirect('/order/success/' . $reference)->with('error', 'Payment failed: ' . $errorMsg);
    }

    /**
     * Success page - the customer lands here after paying on Snippe's hosted
     * checkout. It reads the order reference from ?ref= and shows the order's
     * REAL status. The redirect is NOT proof of payment: the page shows
     * 'pending' until the webhook flips the order.
     */
    public function success(Request $request)
    {
        $reference = $request->query('ref', '');
        $order = $reference ? $this->storage->getOrderByReference($reference) : null;
        $cart = session()->get('cart', []);
        $cartCount = array_sum(array_column($cart, 'quantity'));
        return view('success', compact('order', 'cartCount'));
    }

    /**
     * JSON status endpoint used by the success page poll.
     */
    public function successStatus(Request $request)
    {
        $reference = $request->query('ref', '');
        $order = $reference ? $this->storage->getOrderByReference($reference) : null;
        if (!$order) {
            return response()->json(['status' => 'not_found'], 404);
        }
        return response()->json(['status' => $order['status']]);
    }

    protected function processPayment(Request $request, string $paymentMethod, ?array $customerInfo = null)
    {
        $cart = session()->get('cart', []);
        $user = session()->get('duka_user');

        if (empty($cart)) {
            return redirect('/cart')->with('error', 'Your cart is empty.');
        }

        if (!$user) {
            return redirect('/auth/login')->with('error', 'Please login to checkout.');
        }

        // Hosted checkout (processHosted) supplies the customer from the logged-in
        // user; the custom checkout form validates its own fields instead.
        if ($customerInfo !== null) {
            $validated = $customerInfo;
        } else {
            $validated = $request->validate([
                'customer_name' => 'required|string|max:255',
                'customer_email' => 'required|email|max:255',
                'customer_phone' => 'required|string|max:20',
            ]);
        }

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
        //  💡 LEARNER'S NOTE — SESSION → REDIRECT → RETURN URL FLOW
        // One payment session per checkout intent. The DUKA- reference is baked
        // into the return URLs AND metadata so the webhook can
        // reconcile the payment back to this order. Reference + metadata = audit trail.
        // Success lands on /order/success/{ref}, cancel lands on /order/{ref}
        // (route order.show) — the customer always comes back to your site.
        // Docs: https://docs.snippe.sh/docs/2026-01-25/sessions
        $successUrl = $baseUrl . '/order/success/' . $reference;
        $cancelUrl = $baseUrl . '/order/' . $reference;
        $webhookUrl = $baseUrl . '/webhook';

        // Billing details are required by the card API. The hosted checkout flow
        // uses defaults (demo store in Tanzania); collect them in the form later.
        $customer = [
            'firstname' => $firstName,
            'lastname'  => $lastName,
            'email'     => $validated['customer_email'],
            'address'   => $request->input('customer_address', 'Dar es Salaam'),
            'city'      => $request->input('customer_city', 'Dar es Salaam'),
            'state'     => $request->input('customer_state', 'DSM'),
            'postcode'  => $request->input('customer_postcode', '14101'),
            'country'   => $request->input('customer_country', 'TZ'),
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
        //  💡 LEARNER'S NOTE — ONE SESSION PER CHECKOUT INTENT: cart cleared right after
        // the session is created. Never loop/re-create sessions on retries.
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
            //  💡 LEARNER'S NOTE — REDIRECT ≠ PROOF OF PAYMENT: the customer landing on
            // redirect_url only proves they visited checkout. The order stays
            // 'pending' until the webhook flips it — never trust the redirect.
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
