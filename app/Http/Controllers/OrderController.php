<?php

namespace App\Http\Controllers;

use App\Services\StorageService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    protected StorageService $storage;

    public function __construct(StorageService $storage)
    {
        $this->storage = $storage;
    }

    public function success(Request $request, string $reference)
    {
        $order = $this->storage->getOrderByReference($reference);

        if (!$order) {
            return view('error', ['message' => 'Order not found.']);
        }

        $cartCount = 0;

        return view('order.success', compact('order', 'cartCount'));
    }

    public function show(string $reference)
    {
        $order = $this->storage->getOrderByReference($reference);

        if (!$order) {
            return view('error', ['message' => 'Order not found.']);
        }

        $cart = session()->get('cart', []);
        $cartCount = array_sum(array_column($cart, 'quantity'));

        return view('order.show', compact('order', 'cartCount'));
    }
}
