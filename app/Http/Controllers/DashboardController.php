<?php

namespace App\Http\Controllers;

use App\Services\StorageService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected StorageService $storage;

    public function __construct(StorageService $storage)
    {
        $this->storage = $storage;
    }

    public function index()
    {
        $user = session()->get('duka_user');

        if (!$user) {
            return redirect('/auth/login')->with('error', 'Please login to access your dashboard.');
        }

        $orders = $this->storage->getOrders($user['id']);
        $cart = session()->get('cart', []);
        $cartCount = array_sum(array_column($cart, 'quantity'));

        return view('dashboard.index', compact('orders', 'user', 'cartCount'));
    }

    public function show(string $reference)
    {
        $user = session()->get('duka_user');

        if (!$user) {
            return redirect('/auth/login')->with('error', 'Please login to access your dashboard.');
        }

        $order = $this->storage->getOrderByReference($reference);

        if (!$order || ($order['user_id'] ?? null) !== $user['id']) {
            return view('error', ['message' => 'Order not found.']);
        }

        $cart = session()->get('cart', []);
        $cartCount = array_sum(array_column($cart, 'quantity'));

        return view('dashboard.order', compact('order', 'user', 'cartCount'));
    }
}
