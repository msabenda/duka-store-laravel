<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\WebhookController;
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
    Route::get('/', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('mobile', [CheckoutController::class, 'processMobile'])->name('checkout.mobile');
    Route::post('card', [CheckoutController::class, 'processCard'])->name('checkout.card');
    Route::post('qr', [CheckoutController::class, 'processQr'])->name('checkout.qr');
});

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

// Webhook route
Route::post('webhook', [WebhookController::class, 'handle'])->name('webhook');
