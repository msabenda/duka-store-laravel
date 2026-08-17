<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\WebhookController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

// Auth routes
Route::prefix('auth')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('auth.login');
    Route::post('login', [AuthController::class, 'login']);
    Route::get('register', [AuthController::class, 'showRegister'])->name('auth.register');
    Route::post('register', [AuthController::class, 'register']);
    Route::get('logout', [AuthController::class, 'logout'])->name('auth.logout');
});

// Cart routes
Route::prefix('cart')->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('cart.index');
    Route::get('add/{id}', [CartController::class, 'add'])->name('cart.add');
    Route::get('remove/{idx}', [CartController::class, 'remove'])->name('cart.remove');
});

// Checkout routes
Route::prefix('checkout')->group(function () {
    // Custom checkout page (GET /checkout) DISABLED for now - mobile money
    // checkout only (POST /checkout/pay). Card/QR unavailable in this Snippe
    // environment. Uncomment to restore:
    // Route::get('/', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('pay', [CheckoutController::class, 'processPay'])->name('checkout.pay');
    Route::post('mobile', [CheckoutController::class, 'processMobile'])->name('checkout.mobile');
    Route::post('card', [CheckoutController::class, 'processCard'])->name('checkout.card');
    Route::post('qr', [CheckoutController::class, 'processQr'])->name('checkout.qr');
});

// Success page - the customer lands here after paying on Snippe's hosted
// checkout (?ref= order reference). It shows the order's REAL status and
// polls /success/status. Registered before the order routes on purpose.
Route::get('success/status', [CheckoutController::class, 'successStatus'])->name('checkout.success.status');
Route::get('success', [CheckoutController::class, 'success'])->name('checkout.success');

// Order routes
Route::prefix('order')->group(function () {
    Route::get('success/{ref}', [OrderController::class, 'success'])->name('order.success');
    Route::get('{ref}', [OrderController::class, 'show'])->name('order.show');
});

// Dashboard routes
Route::prefix('dashboard')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('order/{ref}', [DashboardController::class, 'show'])->name('dashboard.order');
});

// Snippe webhook routes. Snippe sends to /webhooks/snippe according to the
// docs, but we keep /webhook as a compatibility alias for local tooling.
// External webhook posts must skip CSRF, otherwise Laravel rejects them with
// 419 Page Expired before the signature is even verified.
Route::withoutMiddleware([VerifyCsrfToken::class])->group(function () {
    Route::post('webhooks/snippe', [WebhookController::class, 'handle'])->name('webhooks.snippe');
    Route::post('webhook', [WebhookController::class, 'handle'])->name('webhook');
});
